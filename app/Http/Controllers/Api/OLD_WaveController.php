<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TblFacture;
use App\Models\TblPaiement;
use App\Services\EncaissementBisService;
use App\Services\JekoPaymentService;
use App\Services\PrimePaymentOrchestrator;
use App\Services\OLD_WaveService;
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
        protected JekoPaymentService $jekoService,
    ) {}

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

    // ==================================================================
    // INITIER PAIEMENT (appelé par le widget)
    // ==================================================================

    public function initierPaiement(Request $request): JsonResponse
    {
        // log::info($request->all());


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
        // $donnees['paymentMethod'] = 'wave';

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
                'successUrl' => (isset($donnees['successUrl']) && !empty($donnees['successUrl'])) ? $donnees['successUrl'] : env('APP_URL') . '/paiement/recu/' . $referenceInterne,
                'errorUrl' => (isset($donnees['errorUrl']) && !empty($donnees['errorUrl'])) ? $donnees['errorUrl'] : env('APP_URL') . '/paiement/error',
                // 'successUrl' => $donnees['errorUrl'] ?? route('wave.retour.success', ['ref' => $referenceInterne]),
                // 'errorUrl' => $donnees['errorUrl'] ?? route('wave.retour.error',   ['ref' => $referenceInterne]),
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
                    $facturePdf = $this->jekoService->generateReceipt($paiement->codePaiement, $paiement->typeReglement);
                    if ($facturePdf['status'] === 'error') {
                        Log::error('Erreur génération PDF reçu', [
                            'reference' => $paiement->codePaiement,
                            'message' => $facturePdf['message'],
                        ]);
                    } else {
                        Log::info('PDF reçu généré avec succès', [
                            'reference' => $paiement->codePaiement,
                            'file_name' => $facturePdf['file_name'],
                        ]);
                    }
                }
                // $this->jekoService->generateReceipt($paiement->codePaiement, $paiement->typeReglement);
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

}
