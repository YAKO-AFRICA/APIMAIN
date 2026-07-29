<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use App\Models\TblDocument;
use App\Models\TblFacture;
use App\Models\TblPaiement;
use App\Services\EncaissementBisService;
use App\Services\JekoPaymentService;
use App\Services\PrimePaymentOrchestrator;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;


//      * Contrôleur proxy pour l'API de paiement Jeko.
//      *
//      * Le widget frontend (jeko-widget.js) n'appelle JAMAIS l'API Jeko
//      * directement : il envoie les données de paiement ici, et c'est ce
//      * contrôleur qui ajoute les clés secrètes (stockées dans .env) avant
//      * d'appeler https://api.jeko.africa côté serveur.
//      *
//      * Config attendue dans .env :
//      *   JEKO_BASE_URL=https://api.jeko.africa
//      *   JEKO_STORE_ID=xxxxx
//      *   JEKO_PARTNER_API_KEY=xxxxx
//      *   JEKO_PARTNER_API_KEY_ID=xxxxx
//      *
//      * Et dans config/services.php :
//      *   'jeko' => [
//      *       'base_url' => env('JEKO_BASE_URL'),
//      *       'store_id' => env('JEKO_STORE_ID'),
//      *       'api_key' => env('JEKO_PARTNER_API_KEY'),
//      *       'api_key_id' => env('JEKO_PARTNER_API_KEY_ID'),
//      *   ],
//      */


class  JekoPaymentController extends Controller
{
    private const METHODES_AUTORISEES = [
        'wave',
        'orange',
        'moov',
        'mtn',
        'djamo',
        'visa',
        'mastercard',
    ];
 
    private const TYPES_PAIEMENT_AUTORISES = [
        'firstPayment',
        'earlyPayment',
        'recoveryPrime',
    ];
 
    private const DEVISES_SUPPORTEES = ['XOF', 'XAF', 'USD', 'EUR'];
 
    public function __construct(
        protected JekoPaymentService $jekoService,
        protected PrimePaymentOrchestrator $orchestrator,
        protected EncaissementBisService $encaissementBis,
    ) {
    }
 
    public function demoJekoWidget()
    {
        return view('paiement.demo-jeko-widget');
    }


    public function jekoPaymentWidget()
    {
        $path = public_path('payment-widget/jeko-payment-widget.js');

        if (!File::exists($path)) {
            abort(404);
        }

        return response(File::get($path), 200)
            ->header('Content-Type', 'application/javascript');
    }

    
 
    /**
     * Vérifie un contrat auprès de l'API legacy encaissement-bis.
     * Utilisé par le widget avant d'afficher les options earlyPayment / recoveryPrime
     * (et optionnellement firstPayment si un contractId est déjà connu).
     */
    public function verifierContrat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'idContrat' => ['required', 'string', 'max:100'],
            'paymentType' => ['nullable', 'string', 'in:' . implode(',', self::TYPES_PAIEMENT_AUTORISES)],
        ]);
 
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiant de contrat invalide.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }
 
        $idContrat = $request->input('idContrat');
        $paymentType = $request->input('paymentType');
        $resultat = ($paymentType === 'firstPayment') ? $this->encaissementBis->recupDetailsContratWeb($idContrat, $paymentType) : $this->encaissementBis->verifierContrat($idContrat, $paymentType);
 
        if (!$resultat['success']) {
            return response()->json([
                'success' => false,
                'message' => $resultat['message'] ?? 'Contrat introuvable.',
                'code' => 'CONTRACT_NOT_FOUND',
                'data' => null,
            ], 404);
        }
 
        return response()->json([
            'success' => true,
            'message' => 'Contrat vérifié.',
            'code' => 'CONTRACT_VERIFIED',
            'data' => $resultat,
        ]);
    }
 
    /**
     * Initialise un paiement auprès de Jeko pour l'un des 3 cas d'usage.
     */
    public function initierPaiement(Request $request): JsonResponse
    {

        // Log::info('initierPaiement', $request->all());
        $validator = Validator::make($request->all(), [
            'reference' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\-_]+$/'],
            'currency' => ['nullable', 'string', 'size:3', 'in:' . implode(',', self::DEVISES_SUPPORTEES)],
            'paymentMethod' => ['required', 'string', 'in:' . implode(',', self::METHODES_AUTORISEES)],
            'paymentType' => ['required', 'string', 'in:' . implode(',', self::TYPES_PAIEMENT_AUTORISES)],
 
            'contractId' => ['nullable', 'max:100'],
            'numberOfPrimes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'selectedInvoiceIds' => ['nullable', 'array'],
            'selectedInvoiceIds.*' => ['string', 'max:50'],
 
            'successUrl' => ['nullable', 'url', 'max:500'],
            'errorUrl' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:255'],
            'customerEmail' => ['nullable', 'email', 'max:100'],
            'customerName' => ['nullable', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ]);
 
        $validator->after(function ($v) use ($request) {
            $type = $request->input('paymentType');
 
            if (in_array($type, ['earlyPayment', 'recoveryPrime', 'firstPayment'], true) && !$request->filled('contractId')) {
                $v->errors()->add('contractId', "L'identifiant du contrat est requis pour ce type de paiement.");
            }
 
            if ($type === 'recoveryPrime' && !$request->filled('selectedInvoiceIds')) {
                $v->errors()->add('selectedInvoiceIds', 'Veuillez sélectionner au moins une facture à régulariser.');
            }

        });
 
        if ($validator->fails()) {
            // Log::warning('Validation du paiement échouée', [
            //     'errors' => $validator->errors(),
            //     'input' => $request->except(['metadata']),
            // ]);
 
            return response()->json([
                'success' => false,
                'message' => 'Données de paiement invalides.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }
 
        $donnees = $validator->validated();

        // Log::info('Données de paiement validées', $donnees);
 
        // 1) Calcul du montant et des lignes de factures — TOUJOURS côté serveur
        try {
            $preparation = $this->orchestrator->preparer($donnees);
            // Log::info('Données de la preparation', $preparation);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'code' => 'PREPARATION_FAILED',
                'data' => null,
            ], 422);
        }
 
        if ($preparation['montantTotal'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant calculé est invalide.',
                'code' => 'INVALID_AMOUNT',
                'data' => null,
            ], 422);
        }
 
        $referenceInterne = $donnees['reference'];
        // $referenceInterne = 'PAI-' . date('Ymd') . date('His'). '-'. rand(1, 9999);
 
        try {
            // 2) Appel Jeko avec le montant recalculé (convention "amountCents" = XOF * 100)
            $resultat = $this->jekoService->initialiserPaiement([
                'amountCents' => $preparation['montantTotal'] * 100,
                'currency' => $donnees['currency'] ?? 'XOF',
                'reference' => $donnees['reference'],
                'paymentMethod' => $donnees['paymentMethod'],
                'successUrl' => route('paiement.recu', ['referenceInterne' => $referenceInterne]),
                // 'successUrl' => $donnees['successUrl'] ?? null,
                'errorUrl' => $donnees['errorUrl'] ?? null,
                'customerEmail' => $donnees['customerEmail'] ?? null,
                'customerName' => $donnees['customerName'] ?? null,
                'description' => $donnees['description'] ?? null,
                'metadata' => $donnees['metadata'] ?? null,
            ], $referenceInterne);
 
            // Log::info('Initialisation paiement Jeko', [
            //     'reference_interne' => $referenceInterne,
            //     'paymentType' => $donnees['paymentType'],
            //     'montant' => $preparation['montantTotal'],
            //     'resultat' => $resultat,
            // ]);
 
            if (!$resultat['success']) {
                // Log::warning('Échec initialisation paiement Jeko', [
                //     'reference' => $donnees['reference'],
                //     'reference_interne' => $referenceInterne,
                //     'erreur' => $resultat['message'] ?? 'Erreur inconnue',
                // ]);
 
                return response()->json([
                    'success' => false,
                    'message' => $resultat['message'] ?? "Impossible d'initialiser le paiement.",
                    'code' => $resultat['code'] ?? 'JEKO_INIT_FAILED',
                    'data' => null,
                ], $resultat['status'] ?? 502);
            }
 
            // 3) Enregistrement tblpaiement + tblfacture (colonnes réelles du schéma)
            $paiement = $this->orchestrator->enregistrer($donnees, $preparation, $referenceInterne, $resultat);
 
            return response()->json([
                'success' => true,
                'message' => 'Paiement initialisé avec succès.',
                'code' => 'PAYMENT_INITIATED',
                'data' => [
                    'redirectUrl' => $resultat['redirectUrl'],
                    'referenceInterne' => $referenceInterne,
                    'referenceMetier' => $donnees['reference'],
                    'montant' => $preparation['montantTotal'],
                    'devise' => $donnees['currency'] ?? 'XOF',
                    'nombreDePrimes' => $preparation['nombreDePrimes'],
                    'recuUrl' => route('paiement.recu', ['referenceInterne' => $referenceInterne]),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur critique appel API Jeko', [
                'reference' => $donnees['reference'] ?? 'inconnue',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
 
            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la communication avec le service de paiement.',
                'code' => 'JEKO_UNREACHABLE',
                'data' => null,
            ], 503);
        }
    }
 
    /**
     * Vérifie le statut d'un paiement (via referenceInterne = codePaiement).
     */
    public function verifierStatut(Request $request, string $referenceInterne): JsonResponse
    {
        try {
            $paiement = TblPaiement::where('codePaiement', $referenceInterne)->first();
 
            if (!$paiement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction non trouvée.',
                    'code' => 'TRANSACTION_NOT_FOUND',
                ], 404);
            }
 
            $statut = $this->jekoService->verifierStatut($paiement);
 
            return response()->json([
                'success' => true,
                'data' => [
                    'statut' => $statut['status'],
                    'montant' => $paiement->montant,
                    'reference' => $paiement->codePaiement,
                    'typePaiement' => $paiement->typeReglement,
                    'details' => $statut['details'] ?? null,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur vérification statut', [
                'reference' => $referenceInterne,
                'message' => $e->getMessage(),
            ]);
 
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut.',
                'code' => 'STATUS_CHECK_FAILED',
            ], 500);
        }
    }
 
    /**
     * Webhook pour recevoir les notifications de Jeko.
     * Met à jour tblpaiement.etat/payment_status ET les tblfacture liées.
     */
    public function webhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Webhook Jeko reçu', ['payload' => $payload]);
 
        try {
            if (!$this->jekoService->validerWebhook($request)) {
                Log::warning('Webhook Jeko non valide', ['payload' => $payload]);
                return response()->json(['error' => 'Invalid webhook'], 401);
            }
 
            $reference = $payload['transactionDetails']['reference'] ?? null;
            // $referenceInterne = $payload['reference'] ?? null;
            $statutJeko = $payload['status'] ?? null;
 
            if (!$reference || !$statutJeko) {
                return response()->json(['status' => 'ignored'], 200);
            }
 
            $paiement = TblPaiement::where('codePaiement', $reference)->first();
 
            if (!$paiement) {
                Log::warning('Transaction non trouvée pour le webhook', ['reference' => $reference]);
                return response()->json(['status' => 'ignored'], 200);
            }
 
            // $nouvelEtat = $this->mapperStatutVersEtat($statutJeko);
            // $ancienEtat = $paiement->etat;
 
            $paiement->etat = 2;
            $paiement->payment_status = $statutJeko;
            $paiement->telpaiement = $payload['counterpartIdentifier'] ?? null;
            $paiement->paid_sum = (int) $payload['amount']['amount'] / 100 ?? null;
            $paiement->paid_amount = (int) $payload['amount']['amount'] / 100 ?? null;
            $paiement->payment_token = $payload['transactionDetails']['paymentLinkId'] ?? null;
            $paiement->command_number = $payload['id'] ?? null;
            $paiement->payment_validation_date = Carbon::now()->format('Y-m-d H:i:s');
            $paiement->reponse_webhook = array_merge($paiement->reponse_webhook ?? [], ['webhook' => $payload, 'date_maj' => now()]);
            $paiement->save();
 
            // Propage le statut aux factures liées (même codePaiement)
            TblFacture::where('codePaiement', $paiement->codePaiement)->update([
                'etat' => 2,
            ]);
            $this->generateReceipt($paiement->codePaiement);
 
            Log::info('Transaction mise à jour via webhook', [
                'reference' => $reference,
                // 'ancien_etat' => $ancienEtat,
                // 'nouvel_etat' => $nouvelEtat,
            ]);
 
            return response()->json(['status' => 'success'], 200);
        } catch (\Throwable $e) {
            Log::error('Erreur traitement webhook Jeko', [
                'message' => $e->getMessage(),
                'payload' => $payload,
            ]);
 
            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    private function generateReceipt(string $codePaiement)
    {
        try {

            $externalUploadDir = base_path(env('UPLOADS_PATH'));
            if (!is_dir($externalUploadDir)) {
                mkdir($externalUploadDir, 0777, true);
            }
            $paiement = TblPaiement::where('codePaiement', $codePaiement)->firstOrFail();
            $contrat = Contrat::where('id', $paiement->idContrat)->firstOrFail();
            $libellesTypeFacture = [
                'N' => 'Prime principale',
                'F' => 'Frais d\'adhésion',
            ];

            $libellesType = [
                'firstPayment' => 'Premier paiement',
                'earlyPayment' => 'Paiement anticipé',
                'recoveryPrime' => 'Régularisation de primes',
            ];
            $paiement = TblPaiement::where('codePaiement', $codePaiement)->firstOrFail();
            $libelleType = $libellesType[$paiement->typeReglement] ?? $paiement->typeReglement;

            $factures = TblFacture::where('codePaiement', $codePaiement)
                ->orderBy('dateAjout')
                ->get()
                ->map(function ($facture) use ($libellesTypeFacture) {
                    $facture->libelleTypeFacture =
                        $libellesTypeFacture[$facture->typeFacture]
                        ?? $facture->typeFacture;

                    return $facture;
                });

            $pdf = Pdf::loadView('paiement.recu_pdf', compact('paiement', 'factures', 'libelleType'));
            $pdf->setPaper('A4', 'portrait');

            $fileName = 'recu-paiement-' . $paiement->codePaiement . '.pdf';
            $filePath = $externalUploadDir . $fileName;
            $pdf->save($filePath);

             // Log pour vérifier que tout fonctionne
            Log::info('PDF généré avec succès pour : ' . $codePaiement);
            Log::info('Chemin du fichier PDF : ' . $filePath);

            // Ajoute le recu au contrat
            TblDocument::create([
                'codecontrat' => $paiement->idContrat,
                'filename' => $fileName,
                'libelle' => 'Recu de paiement',
                'saisiele' => now(),
                'saisiepar' => $contrat->saisiepar ?? null,
                'source' => "ES",
            ]);

            // Mettre le contrat en statut "payé"
            $contrat->update(['estpaye' => 1]);

            // Retourne le fichier PDF
            return [
                'status' => 'success',
                'file_name' => $fileName,
                'file_path' => $filePath,
            ];
        } catch (\Exception $e) {
            // Log l'erreur
            Log::error('Erreur génération PDF : ' . $e->getMessage());

            // Retourne une réponse d'erreur
            return back()->with('error', 'Erreur lors de la génération du PDF : ' . $e->getMessage());
        }
        
    }
 
    /**
     * Convention d'état : 0 = en attente, 1 = payé, 2 = échec/annulé.
     */
    // private function mapperStatutVersEtat(string $statutJeko): int
    // {
    //     return match (strtolower($statutJeko)) {
    //         'completed', 'success' => 1,
    //         'failed', 'cancelled' => 2,
    //         default => 0, // pending / processing
    //     };
    // }
}
