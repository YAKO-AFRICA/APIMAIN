<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TblFacture;
use App\Models\TblPaiement;
use App\Services\EncaissementBisService;
use App\Services\JekoPaymentService;
use App\Services\PrimePaymentOrchestrator;
use App\Services\WaveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WaveController extends Controller
{
    private const TYPES_PAIEMENT_AUTORISES = [
        'firstPayment',
        'earlyPayment',
        'recoveryPrime',
    ];

    private const DEVISES_SUPPORTEES = ['XOF'];

    public function __construct(
        protected WaveService $waveService,
        protected PrimePaymentOrchestrator $orchestrator,
        protected EncaissementBisService $encaissementBis,
        protected JekoPaymentService $jekoPaymentService,
    ) {}

    // ==================================================================
    // WIDGET — démo + fichier JS
    // ==================================================================

    public function demoWaveWidget()
    {
        return view('wave.wave-demo');
    }

    public function wavePaymentWidget()
    {
        $path = public_path('payment-widget/wave-payment-widget.js');

        if (!File::exists($path)) {
            abort(404, 'Widget Wave introuvable.');
        }

        return response(File::get($path), 200)
            ->header('Content-Type', 'application/javascript');
    }

    // ==================================================================
    // VÉRIFICATION DE CONTRAT
    // ==================================================================

    public function verifierContrat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'idContrat'   => ['required', 'string', 'max:100'],
            'paymentType' => ['nullable', 'string', 'in:' . implode(',', self::TYPES_PAIEMENT_AUTORISES)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiant de contrat invalide.',
                'code'    => 'VALIDATION_ERROR',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $idContrat   = $request->input('idContrat');
        $paymentType = $request->input('paymentType');

        try {
            $resultat = ($paymentType === 'firstPayment')
                ? $this->encaissementBis->recupDetailsContratWeb($idContrat, 'firstPayment')
                : $this->encaissementBis->verifierContrat($idContrat, $paymentType ?? 'earlyPayment');
        } catch (\Throwable $e) {
            Log::error('Wave verifierContrat exception', [
                'idContrat'   => $idContrat,
                'paymentType' => $paymentType,
                'message'     => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la vérification du contrat.',
                'code'    => 'CONTRACT_CHECK_FAILED',
                'data'    => null,
            ], 500);
        }

        if (!($resultat['success'] ?? false)) {
            return response()->json([
                'success' => true,
                'status'  => 'contract_not_found',
                'message' => $resultat['message'] ?? 'Contrat introuvable.',
                'code'    => 'CONTRACT_NOT_FOUND',
                'data'    => null,
            ], 200);
        }

        $facturesImpayees = $resultat['facturesImpayees'] ?? [];
        $hasUnpaid = ($resultat['aDesImpayes'] ?? false) === true || count($facturesImpayees) > 0;

        return response()->json([
            'success'    => true,
            'status'     => 'contract_found',
            'message'    => 'Contrat vérifié.',
            'code'       => 'CONTRACT_VERIFIED',
            'has_unpaid' => $hasUnpaid,
            'data'       => $resultat,
        ]);
    }

    public function afficherFactureImpayer(Request $request): JsonResponse
    {
        $request->validate([
            'contractId' => ['required', 'string', 'max:100'],
        ]);

        $resultat = $this->encaissementBis->verifierContrat($request->contractId, 'recoveryPrime');

        if (!($resultat['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $resultat['message'] ?? 'Contrat introuvable.',
            ], 404);
        }

        return response()->json([
            'success'          => true,
            'facturesImpayees' => $resultat['facturesImpayees'] ?? [],
            'total'            => array_sum(array_column($resultat['facturesImpayees'] ?? [], 'MontantNet')),
        ]);
    }

    // ==================================================================
    // INITIER PAIEMENT (appelé par le widget)
    // ==================================================================

    public function initierPaiement(Request $request): JsonResponse
    {
        log::info($request->all());


        $validator = Validator::make($request->all(), [
            'reference'            => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\-_]+$/'],
            'currency'             => ['nullable', 'string', 'size:3', 'in:' . implode(',', self::DEVISES_SUPPORTEES)],
            'paymentType'          => ['required', 'string', 'in:' . implode(',', self::TYPES_PAIEMENT_AUTORISES)],
            'amount'               => ['nullable', 'integer', 'min:1'],
            'contractId'           => ['nullable', 'string', 'max:100'],
            'numberOfPrimes'       => ['nullable', 'integer', 'min:1', 'max:60'],
            'selectedInvoiceIds'   => ['nullable', 'array'],
            'selectedInvoiceIds.*' => ['string', 'max:50'],
            'manualAmount'         => ['nullable', 'integer', 'min:1', 'max:100000000'],

            'description'          => ['nullable', 'string', 'max:255'],
            'customerEmail'        => ['nullable', 'email', 'max:100'],
            'customerName'         => ['nullable', 'string', 'max:100'],
            'metadata'             => ['nullable', 'array'],
        ]);

        $validator->after(function ($v) use ($request) {
            $type = $request->input('paymentType');

            if ($type === 'recoveryPrime') {
                if (!$request->filled('contractId')) {
                    $v->errors()->add('contractId', "L'identifiant du contrat est requis.");
                }
                if (!$request->filled('selectedInvoiceIds')) {
                    $v->errors()->add('selectedInvoiceIds', 'Veuillez sélectionner au moins une facture.');
                }
            }

            if ($type === 'earlyPayment') {
                if (!$request->filled('contractId')) {
                    $v->errors()->add('contractId', "L'identifiant du contrat est requis.");
                }
                if (!$request->filled('numberOfPrimes')) {
                    $v->errors()->add('numberOfPrimes', 'Le nombre de primes est requis.');
                }
            }

            if ($type === 'firstPayment') {
                if (!$request->filled('contractId') && !$request->filled('manualAmount')) {
                    $v->errors()->add('contractId', "L'identifiant du contrat ou un montant manuel est requis.");
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données de paiement invalides.',
                'code'    => 'VALIDATION_ERROR',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $donnees = $validator->validated();
        $donnees['paymentMethod'] = 'wave';

        try {
            $preparation = $this->orchestrator->preparer($donnees);
            Log::info('Données de la preparation', $preparation);
            // $preparation = $this->preparerPaiement($donnees);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code'    => 'PREPARATION_FAILED',
                'data'    => null,
            ], 422);
        }

        if (($preparation['montantTotal'] ?? 0) <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant calculé est invalide.',
                'code'    => 'INVALID_AMOUNT',
                'data'    => null,
            ], 422);
        }

        $referenceInterne = $donnees['reference'];

        // Contrôle de cohérence montant client / serveur
        if (isset($donnees['amount']) && (int) $donnees['amount'] !== (int) $preparation['montantTotal']) {
            Log::warning('Wave : écart entre montant client et montant serveur', [
                'reference'      => $referenceInterne,
                'payment_type'   => $donnees['paymentType'],
                'amount_client'  => $donnees['amount'],
                'amount_serveur' => $preparation['montantTotal'],
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Le montant transmis ne correspond pas au montant attendu.',
                'code'    => 'AMOUNT_MISMATCH',
                'data'    => ['montant_attendu' => (int) $preparation['montantTotal']],
            ], 422);
        }

        try {

            // 2) Appel Wave avec le montant recalculé.
            $resultat = $this->waveService->initialiserPaiement([
                'amountCents' => $preparation['montantTotal'],
                'currency' => $donnees['currency'] ?? 'XOF',
                'reference' => $donnees['reference'],
                'paymentMethod' => $donnees['paymentMethod'],
                'successUrl' => $donnees['errorUrl'] ?? route('wave.retour.success', ['ref' => $referenceInterne]),
                'errorUrl' => $donnees['errorUrl'] ?? route('wave.retour.error',   ['ref' => $referenceInterne]),
                'customerEmail' => $donnees['customerEmail'] ?? null,
                'customerName' => $donnees['customerName'] ?? null,
                'description' => $donnees['description'] ?? null,
                'metadata' => $donnees['metadata'] ?? null,
            ], $referenceInterne);

            if (!$resultat['success']) {
                Log::warning('Échec initialisation paiement Jeko', [
                    'reference' => $donnees['reference'],
                    'reference_interne' => $referenceInterne,
                    'erreur' => $resultat['message'] ?? 'Erreur inconnue',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $resultat['message'] ?? "Impossible d'initialiser le paiement.",
                    'code' => $resultat['code'] ?? 'WAVE_INIT_FAILED',
                    'data' => null,
                ], $resultat['status'] ?? 502);
            }

            // 3) Enregistrement tblpaiement + tblfacture (colonnes réelles du schéma)
            $paiement = $this->orchestrator->enregistrer($donnees, $preparation, $referenceInterne, $resultat, 'WAVE_API');


            // $launchUrl = $waveOrder->wave_response['wave_launch_url'] ?? null;
            $launchUrl = $resultat['redirectUrl'] ?? null;

            Log::warning('Donnée initialisation paiement Wave', [
                'reference' => $donnees['reference'],
                'reference_interne' => $referenceInterne,
                'Resut' => $resultat,
            ]);

            if (!$launchUrl || $launchUrl == null) {
                return response()->json([
                    'success' => false,
                    'message' => "Wave n'a pas retourné d'URL de lancement.",
                    'code'    => 'WAVE_NO_LAUNCH_URL',
                    'data'    => null,
                ], 502);
            }

            // $waveOrder->update([
            //     'payment_type'        => $donnees['paymentType'],
            //     'contract_id'         => $donnees['contractId'] ?? null,
            //     'number_of_primes'    => $donnees['numberOfPrimes'] ?? null,
            //     'selected_invoices'   => $donnees['selectedInvoiceIds'] ?? null,
            //     'preparation_context' => array_merge($preparation, [
            //         'amount_client' => $donnees['amount'] ?? null,
            //     ]),
            // ]);

            Log::info('Paiement Wave initialisé', [
                'reference_interne' => $referenceInterne,
                'payment_type'      => $donnees['paymentType'],
                'montant'           => $preparation['montantTotal'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement initialisé avec succès.',
                'code'    => 'PAYMENT_INITIATED',
                'data'    => [
                    'redirectUrl'      => $launchUrl,
                    'referenceInterne' => $referenceInterne,
                    'referenceMetier'  => $donnees['reference'],
                    'montant'          => $preparation['montantTotal'],
                    'devise'           => $donnees['currency'] ?? 'XOF',
                    'waveSessionId'    => $resultat['paymentId'],
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur critique appel API Wave', [
                'reference' => $donnees['reference'] ?? 'inconnue',
                'message'   => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la communication avec Wave.',
                'code'    => 'WAVE_UNREACHABLE',
                'data'    => $e->getMessage(),
            ], 503);
        }
    }

    private function preparerPaiement(array $donnees): array
    {
        $type       = $donnees['paymentType'];
        $contractId = $donnees['contractId'] ?? null;

        if ($type === 'recoveryPrime') {
            $recalcul = $this->encaissementBis->recalculerTotalImpayes(
                $contractId,
                $donnees['selectedInvoiceIds'],
                'recoveryPrime'
            );

            if (!($recalcul['success'] ?? false)) {
                throw new \RuntimeException($recalcul['message'] ?? 'Impossible de recalculer les impayés.');
            }

            return [
                'montantTotal' => (int) $recalcul['totalCents'],
                'idProposition' => $contractId,
                'nombreDePrimes' => count($donnees['selectedInvoiceIds']),
                'contexte'     => [
                    'contract'              => $recalcul['contrat'] ?? null,
                    'facturesSelectionnees' => $recalcul['facturesSelectionnees'] ?? [],
                ],
            ];
        }

        if ($type === 'earlyPayment') {
            $contrat = $this->encaissementBis->verifierContrat($contractId, 'earlyPayment');

            if (!($contrat['success'] ?? false)) {
                throw new \RuntimeException($contrat['message'] ?? 'Contrat introuvable.');
            }
            if (($contrat['aDesImpayes'] ?? false) === true) {
                throw new \RuntimeException(
                    "Ce contrat a des primes impayées. Le paiement anticipé n'est pas disponible."
                );
            }

            $prime = (int) ($contrat['primePrincipale'] ?? 0);
            $nb    = (int) $donnees['numberOfPrimes'];

            if ($prime <= 0) {
                throw new \RuntimeException('Prime principale invalide pour ce contrat.');
            }

            return [
                'montantTotal' => $prime * $nb,
                'nombreDePrimes' => $nb,
                'idProposition' => $contractId,
                'contexte'     => ['contract' => $contrat, 'prime' => $prime, 'nb' => $nb],
            ];
        }

        if ($type === 'firstPayment') {
            if ($contractId) {
                $contrat = $this->encaissementBis->recupDetailsContratWeb($contractId, 'firstPayment');

                if (($contrat['success'] ?? false)) {
                    $prime = (int) ($contrat['primePrincipale'] ?? 0);
                    $frais = (int) ($contrat['fraisAdhesion'] ?? 0);

                    if ($prime + $frais > 0) {
                        return [
                            'montantTotal' => $prime + $frais,
                            'nombreDePrimes' => 2,
                            'idProposition' => $contractId,
                            'contexte'     => ['contract' => $contrat, 'source' => 'db'],
                        ];
                    }
                }

                if (!isset($donnees['manualAmount'])) {
                    throw new \RuntimeException(
                        $contrat['message'] ?? 'Contrat introuvable et aucun montant manuel fourni.'
                    );
                }
            }

            if (!isset($donnees['manualAmount'])) {
                throw new \RuntimeException('Montant manuel requis pour une nouvelle souscription.');
            }

            $montant = (int) $donnees['manualAmount'];
            if ($montant <= 0) {
                throw new \RuntimeException('Montant manuel invalide.');
            }

            return [
                'montantTotal' => $montant,
                'nombreDePrimes' => 2,
                'idProposition' => $contractId,
                'contexte'     => ['source' => 'manual', 'contract_id' => $contractId],
            ];
        }

        throw new \RuntimeException('Type de paiement non supporté.');
    }

    // ==================================================================
    // PAIEMENT DIRECT (via URL)
    // ==================================================================

    public function payerDirect(Request $request, string $identifiant = null, int $montant = 0)
    {


        $paymentType = $request->input('paymentType', 'recoveryPrime');

        if ($montant <= 0) {
            abort(400, 'Le montant doit être supérieur à 0.');
        }

        if ($montant > 100_000_000) {
            abort(400, 'Le montant dépasse la limite autorisée.');
        }

        if (empty(trim($identifiant)) || strlen($identifiant) > 100) {
            abort(400, 'Identifiant invalide.');
        }

        $identifiant = trim($identifiant);
        $reference   = 'WAVE-' . now()->format('YmdHis') . '-' . Str::random(8);



        if ($paymentType === 'firstPayment') {
            $description = 'Paiement de la première prime';

            $donnees = [
                'reference'          => $reference,
                'currency'           => 'XOF',
                'paymentType'        => $paymentType,
                'amount'             => $montant,
                'contractId'         => $identifiant,
                'numberOfPrimes'     => 1,
                'selectedInvoiceIds' => null,
                'manualAmount'       => $montant,
                'description'        => $description,
                'customerName'       => null,
                'customerEmail'      => null,
                'metadata'           => null,
            ];
        } else {
            $contrat = $this->encaissementBis->verifierContrat($identifiant, 'earlyPayment');



            if (!($contrat['success'] ?? false)) {
                abort(404, $contrat['message'] ?? 'Contrat introuvable.');
            }

            $paymentType = (($contrat['aDesImpayes'] ?? false) === true)
                ? 'recoveryPrime'
                : 'earlyPayment';

            $prime = (int) ($contrat['primePrincipale'] ?? 0);
            if ($prime <= 0) {
                abort(422, 'Prime principale invalide pour ce contrat.');
            }

            $nbrePrime = max(1, (int) floor($montant / $prime));
            $montant   = $nbrePrime * $prime;

            $description = sprintf(
                'Paiement de %d prime(s) de %s %s - Contrat %s (%s)',
                $nbrePrime,
                number_format($prime, 0, ',', ' '),
                $contrat['devise'] ?? 'XOF',
                $contrat['idProposition'] ?? $identifiant,
                $contrat['produit'] ?? ''
            );

            $donnees = [
                'reference'          => $reference,
                'currency'           => $contrat['devise'] ?? 'XOF',
                'paymentType'        => $paymentType,
                'amount'             => $montant,
                'contractId'         => $contrat['idProposition'] ?? $identifiant,
                'numberOfPrimes'     => $nbrePrime,
                'selectedInvoiceIds' => null,
                'manualAmount'       => $montant,
                'description'        => $description,
                'customerName'       => $contrat['souscripteur'] ?? null,
                'customerEmail'      => null,
                'metadata'           => null,
            ];
        }

        try {

            $resultat = $this->waveService->initialiserPaiement([
                'amountCents' => (int) $donnees['amount'],
                'currency' => $donnees['currency'] ?? 'XOF',
                'successUrl' => route('wave.retour.success', ['ref' => $reference]),
                'errorUrl' => route('wave.retour.error', ['ref' => $reference]),
            ], $reference);
            $launchUrl = $resultat['redirectUrl'] ?? null;

            if (!$launchUrl) {
                Log::error('Wave : pas d\'URL de lancement (direct)', [
                    'reference' => $reference,
                    'response'  => $resultat,
                ]);
                abort(502, "Wave n'a pas retourné d'URL de lancement.");
            }

            DB::connection('mysql3')->transaction(function () use ($donnees, $reference, $resultat) {
                TblPaiement::create([
                    'codePaiement' => $reference,
                    'command_number' => $reference,
                    'montant' => $donnees['amount'],
                    'etat' => 1,
                    'datepaiement' => now()->format('Y-m-d H:i:s'),
                    'payment_mode' => 'wave',
                    'payment_status' => $resultat['status'] ?? 'pending',
                    'typePaiement' => 1,
                    'idproposition' => $donnees['paymentType'] === 'firstPayment' ? null : $donnees['contractId'],
                    'idContrat' => $donnees['paymentType'] === 'firstPayment' ? $donnees['contractId'] : null,
                    'payment_token' => $resultat['paymentId'] ?? null,
                    'typeReglement' => $donnees['paymentType'],
                    'reglementSource' => 'WAVE_API',
                    'referenceSource' => $donnees['paymentType'] === 'firstPayment' ? null : $donnees['contractId'],
                    'nombreDePrime' => $donnees['numberOfPrimes'],
                    'emailpayeur' => $donnees['customerEmail'],
                    'saisiele' => now()->format('Y-m-d H:i:s'),
                ]);

                TblFacture::create([
                    'codePaiement' => $reference,
                    'prime' => $donnees['amount'],
                    'typeFacture' => 'N',
                    'etat' => 1,
                    'dateAjout' => now()->format('Y-m-d H:i:s'),
                    'typePaiement' => 1,
                    'referenceSource' => $reference,
                    'idcontrat' => $donnees['contractId'],
                    'saisiele' => now()->format('Y-m-d H:i:s'),
                ]);
            });

            Log::info('Paiement Wave direct initié', [
                'reference_interne' => $reference,
                'payment_type'      => $donnees['paymentType'],
                'montant'           => $donnees['amount'],
                'identifiant'       => $identifiant,
                'session_id'        => $resultat['paymentId'] ?? null,
            ]);

            return redirect()->away($launchUrl);
        } catch (\Throwable $e) {
            Log::error('Erreur paiement Wave direct', [
                'identifiant' => $identifiant,
                'montant'     => $montant,
                'message'     => $e->getMessage(),
            ]);

            abort(503, 'Erreur technique : ' . $e->getMessage());
        }
    }

    // ==================================================================
    // RETOURS
    // ==================================================================

    public function retourSuccess(Request $request)
    {
        $orderId = $request->query('ref');

        if (!$orderId) {
            abort(400, 'Paramètre ref manquant.');
        }

        $paiement = TblPaiement::where('codePaiement', $orderId)->firstOrFail();
        // dd($request->all());
        $this->waveService->refreshStatus($paiement);
        $paiement->fresh();

        $identifiantContrat = ($paiement->typeReglement === 'firstPayment') ? $paiement->idContrat : $paiement->idproposition;

        $fileName = 'recu-paiement-' . $paiement->codePaiement . '-' . $identifiantContrat . '.pdf';
        $urlFacture = url('storage/documents/' . $fileName);

        return view('wave.success', compact('paiement', 'urlFacture'));
    }

    public function retourError(Request $request)
    {
        $orderId = $request->query('ref');

        $paiement = TblPaiement::where('codePaiement', $orderId)->firstOrFail();
        $this->waveService->refreshStatus($paiement);

        // $paiement = $orderId
        //     ? TblPaiement::where('codePaiement', $orderId)->first()
        //     : null;

        return view('wave.error', compact('paiement'));
    }

    // ==================================================================
    // STATUT
    // ==================================================================

    public function verifierStatut(Request $request, string $referenceInterne): JsonResponse
    {
        $paiement = TblPaiement::where('codePaiement', $referenceInterne)->first();
        if (!$paiement) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction non trouvée.',
                'code'    => 'TRANSACTION_NOT_FOUND',
            ], 404);
        }

        $data = $this->waveService->refreshStatus($paiement);

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    /**
     * Affiche directement la liste des factures impayées d'un contrat.
     *
     * Usage : GET /api/wave/factures/{identifiant}
     *
     * - Vérifie le contrat auprès de l'API encaissement-bis
     * - Si le contrat a des impayés → renvoie la liste + total
     * - Si le contrat n'a pas d'impayés → renvoie un message clair
     * - Si le contrat est introuvable → renvoie 404
     */
    public function facturesDirect(Request $request, string $identifiant): JsonResponse
    {
        $identifiant = trim($identifiant);

        if (empty($identifiant) || strlen($identifiant) > 100) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiant de contrat invalide.',
                'code'    => 'INVALID_CONTRACT_ID',
            ], 400);
        }

        try {
            $contrat = $this->encaissementBis->verifierContrat($identifiant, 'recoveryPrime');
        } catch (\Throwable $e) {
            Log::error('Wave facturesDirect exception', [
                'identifiant' => $identifiant,
                'message'     => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la vérification du contrat.',
                'code'    => 'CONTRACT_CHECK_FAILED',
            ], 500);
        }

        // Contrat introuvable
        if (!($contrat['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $contrat['message'] ?? 'Contrat introuvable.',
                'code'    => 'CONTRACT_NOT_FOUND',
            ], 404);
        }

        $facturesImpayees = $contrat['facturesImpayees'] ?? [];

        // Contrat trouvé mais aucune facture impayée
        if (count($facturesImpayees) === 0) {
            return response()->json([
                'success'    => true,
                'status'     => 'no_unpaid',
                'message'    => 'Aucune facture impayée sur ce contrat.',
                'contrat'    => [
                    'idProposition' => $contrat['idProposition'] ?? null,
                    'souscripteur'  => $contrat['souscripteur'] ?? null,
                    'produit'       => $contrat['produit'] ?? null,
                    'devise'        => $contrat['devise'] ?? 'XOF',
                ],
                'factures'   => [],
                'total'      => 0,
            ]);
        }

        // Contrat trouvé avec impayés
        $total = array_sum(array_column($facturesImpayees, 'MontantNet'));

        return response()->json([
            'success'  => true,
            'status'   => 'has_unpaid',
            'message'  => 'Factures impayées récupérées.',
            'contrat'  => [
                'idProposition' => $contrat['idProposition'] ?? null,
                'souscripteur'  => $contrat['souscripteur'] ?? null,
                'produit'       => $contrat['produit'] ?? null,
                'devise'        => $contrat['devise'] ?? 'XOF',
            ],
            'factures' => array_map(static function (array $f) {
                return [
                    'IdPresentation'   => $f['IdPresentation'] ?? '',
                    'CodePresentation' => $f['CodePresentation'] ?? '',
                    'MaDate'           => $f['MaDate'] ?? null,
                    'MontantNet'       => (int) ($f['MontantNet'] ?? 0),
                ];
            }, $facturesImpayees),
            'total'    => (int) $total,
        ]);
    }

    /**
     * Page HTML : liste des factures impayées avec sélection.
     * Usage : GET /wave/factures/{identifiant}
     */
    public function facturesPage(Request $request, string $identifiant)
    {
        $identifiant = trim($identifiant);

        try {
            $contrat = $this->encaissementBis->verifierContrat($identifiant, 'recoveryPrime');
        } catch (\Throwable $e) {
            Log::error('Wave facturesPage exception', [
                'identifiant' => $identifiant,
                'message'     => $e->getMessage(),
            ]);
            abort(500, 'Erreur technique lors de la vérification du contrat.');
        }

        if (!($contrat['success'] ?? false)) {
            abort(404, $contrat['message'] ?? 'Contrat introuvable.');
        }

        $facturesImpayees = $contrat['facturesImpayees'] ?? [];
        $total            = array_sum(array_column($facturesImpayees, 'MontantNet'));

        return view('wave.factures', [
            'identifiant'      => $identifiant,
            'contrat'          => $contrat,
            'facturesImpayees' => $facturesImpayees,
            'total'            => $total,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $rawBody   = $request->getContent();
        $signature = $request->header('Wave-Signature', '');

        // 1. Journalisation systématique (même si invalide)
        Log::channel('wave_webhooks')->info('Webhook Wave reçu', [
            'signature' => $signature,
            'body'      => $rawBody,
            'ip'        => $request->ip(),
            'timestamp' => now()->toIso8601String(),
        ]);

        // 2. Vérification de signature
        if (!$this->waveService->verifyWebhookSignature($rawBody, $signature)) {
            Log::channel('wave_webhooks')->warning('Webhook Wave : signature invalide', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $event = json_decode($rawBody, true);

        if (!is_array($event)) {
            return response()->json(['error' => 'Invalid JSON payload'], 400);
        }

        try {
            $type = $event['type'] ?? null;

            if (!in_array($type, [
                'checkout.session.completed',
                'checkout.session.failed',
                'checkout.session.cancelled',
            ], true)) {
                return response()->json(['status' => 'ignored'], 200);
            }

            $data = $event['data'] ?? [];
            $clientReference = $data['client_reference'] ?? null;
            $sessionId = $data['id'] ?? null;
            $status = strtolower((string) ($data['payment_status'] ?? ''));

            if (!in_array($status, ['succeeded', 'failed', 'cancelled'], true)) {
                Log::channel('wave_webhooks')->warning('Webhook Wave : statut de paiement inconnu', [
                    'client_reference' => $clientReference,
                    'status' => $status,
                ]);
                return response()->json(['status' => 'ignored'], 200);
            }
            DB::connection('mysql3')->transaction(function () use ($clientReference, $sessionId, $status, $event, $data) {
                $paiementQuery = TblPaiement::query();
                if ($clientReference) {
                    $paiementQuery->where(function ($query) use ($clientReference) {
                        $query->where('codePaiement', $clientReference)
                            ->orWhere('command_number', $clientReference);
                    });
                }
                if ($sessionId) {
                    if ($clientReference) {
                        $paiementQuery->orWhere('payment_token', $sessionId);
                    } else {
                        $paiementQuery->where('payment_token', $sessionId);
                    }
                }
                $paiement = ($clientReference || $sessionId) ? $paiementQuery->first() : null;

                // if (!$paiement || $paiement == null) {
                //     Log::channel('wave_webhooks')->warning('Paiement introuvable pour le webhook Wave', [
                //         'client_reference' => $clientReference,
                //         'session_id' => $sessionId,
                //     ]);
                //     return;
                // }

                $paymentStatus = $this->mapperStatutVersEtat($status);

                $paiement->payment_status = $paymentStatus;
                $paiement->reponse_webhook = array_merge($paiement->reponse_webhook ?? [], [
                    'webhook' => $event,
                    'date_maj' => now(),
                ]);

                if ($paymentStatus === 'success') {
                    $paiement->etat = 2;
                    $paiement->payment_validation_date = now()->format('Y-m-d H:i:s');

                    if (isset($data['amount']) && is_numeric($data['amount'])) {
                        $paiement->paid_sum = (int) $data['amount'];
                        $paiement->paid_amount = (int) $data['amount'];
                    }
                }

                $paiement->save();

                if ($paymentStatus === 'success') {
                    TblFacture::where('codePaiement', $paiement->codePaiement)->update([
                        'etat' => 2,
                    ]);
                }
                $this->jekoPaymentService->generateReceipt($paiement->codePaiement, $paiement->typeReglement);
                Log::channel('wave_webhooks')->info('Paiement mis à jour via webhook Wave', [
                    'reference' => $clientReference,
                    'status' => $paymentStatus,
                ]);
            });
        } catch (\Throwable $e) {
            Log::channel('wave_webhooks')->error('Erreur traitement webhook Wave', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }

        return response()->json(['status' => 'success'], 200);
    }



    private function mapperStatutVersEtat(string $statutWave): string
    {
        return match (strtolower($statutWave)) {
            'completed', 'success', 'succeeded' => 'success',
            'failed', 'cancelled' => 'failed',
            default => strtolower($statutWave),
        };
    }


   public function payerPage(Request $request, string $identifiant)
    {
        $identifiant = trim($identifiant);

        try {
            $contrat = $this->encaissementBis->verifierContrat($identifiant, 'recoveryPrime');
        } catch (\Throwable $e) {
            Log::error('Wave payerPage exception', [
                'identifiant' => $identifiant,
                'message'     => $e->getMessage(),
            ]);
            abort(500, 'Erreur technique lors de la vérification du contrat.');
        }

        if (!($contrat['success'] ?? false)) {
            abort(404, $contrat['message'] ?? 'Contrat introuvable.');
        }

        $facturesImpayees = $contrat['facturesImpayees'] ?? [];

        if (count($facturesImpayees) === 0) {
            abort(400, 'Aucune facture impayée sur ce contrat.');
        }

        // Uniquement les champs utiles à l'affichage : rien de plus n'est exposé au navigateur
        $factures = array_values(array_map(static fn (array $f) => [
            'IdPresentation'   => (string) ($f['IdPresentation'] ?? ''),
            'CodePresentation' => (string) ($f['CodePresentation'] ?? ''),
            'MaDate'           => $f['MaDate'] ?? null,
            'MontantNet'       => (int) ($f['MontantNet'] ?? 0),
        ], $facturesImpayees));

        // Présélection : liste d'identifiants (?factures=…) ou mode (?preselect=…)
        $preselect = in_array($request->query('preselect'), ['all', 'first', 'none'], true)
            ? $request->query('preselect')
            : 'all';

        if ($request->filled('factures')) {
            $idsConnus = array_column($factures, 'IdPresentation');
            $demandes  = array_filter(array_map('trim', explode(',', (string) $request->query('factures'))));
            $preselect = array_values(array_intersect($demandes, $idsConnus));
        }

        return view('wave.payer', [
            'identifiant'  => $identifiant,
            'contrat'      => $contrat,
            'widgetConfig' => [
                'contract' => [
                    'idProposition'    => $contrat['idProposition'] ?? $identifiant,
                    'souscripteur'     => $contrat['souscripteur'] ?? null,
                    'produit'          => $contrat['produit'] ?? null,
                    'devise'           => $contrat['devise'] ?? 'XOF',
                    'facturesImpayees' => $factures,
                ],
                'preselect'   => $preselect,
                'sequential'  => $request->boolean('chronologique'),
                'skipSummary' => $request->boolean('direct'),
            ],
        ]);
    }


}
