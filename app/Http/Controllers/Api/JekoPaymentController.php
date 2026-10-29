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
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;


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
<<<<<<< HEAD

        Log::info('initierPaiement', $request->all());
=======
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff
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
<<<<<<< HEAD
            // Log::warning('Validation du paiement échouée', [
            //     'errors' => $validator->errors(),
            //     'input' => $request->except(['metadata']),
            // ]);

=======
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff
            return response()->json([
                'success' => false,
                'message' => 'Données de paiement invalides.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $donnees = $validator->validated();

<<<<<<< HEAD
        // Log::info('Données de paiement validées', $donnees);

        // 1) Calcul du montant et des lignes de factures — TOUJOURS côté serveur
=======
        Log::info('Initialisation de paiement', $donnees);

        // 1) Calcul du montant et des lignes de factures
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff
        try {
            $preparation = $this->orchestrator->preparer($donnees);
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
<<<<<<< HEAD
        // $referenceInterne = 'PAI-' . date('Ymd') . date('His'). '-'. rand(1, 9999);
=======
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff

        try {
            // 2) Appel Jeko
            $resultat = $this->jekoService->initialiserPaiement([
                'amountCents' => $preparation['montantTotal'] * 100,
                'currency' => $donnees['currency'] ?? 'XOF',
                'reference' => $referenceInterne,
                'paymentMethod' => $donnees['paymentMethod'],
                'successUrl' => (isset($donnees['successUrl']) && !empty($donnees['successUrl'])) ? $donnees['successUrl'] : env('APP_URL') . '/paiement/recu/' . $referenceInterne,
                'errorUrl' => (isset($donnees['errorUrl']) && !empty($donnees['errorUrl'])) ? $donnees['errorUrl'] : env('APP_URL') . '/paiement/error',
                'customerEmail' => $donnees['customerEmail'] ?? null,
                'customerName' => $donnees['customerName'] ?? null,
                'description' => $donnees['description'] ?? null,
                'metadata' => $donnees['metadata'] ?? null,
            ], $referenceInterne);

<<<<<<< HEAD
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

=======
            Log::info('Paiement initialisé via Jeko', [
                'reference' => $referenceInterne,
            ]);

            if (!$resultat['success']) {
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff
                return response()->json([
                    'success' => false,
                    'message' => $resultat['message'] ?? "Impossible d'initialiser le paiement.",
                    'code' => $resultat['code'] ?? 'JEKO_INIT_FAILED',
                    'data' => null,
                ], $resultat['status'] ?? 502);
            }

<<<<<<< HEAD
            // 3) Enregistrement tblpaiement + tblfacture (colonnes réelles du schéma)
            $paiement = $this->orchestrator->enregistrer($donnees, $preparation, $referenceInterne, $resultat);
=======
            Log::info('Enregistrement du paiement', [
                'referenceInterne' => $referenceInterne,
            ]);

            // 3) Enregistrement du paiement et des factures
            try {
                $paiement = $this->orchestrator->enregistrer($donnees, $preparation, $referenceInterne, $resultat);

                Log::info('Paiement enregistré', [
                    'referenceInterne' => $referenceInterne,
                ]);
            } catch (\Throwable $e) {
                Log::error('Erreur lors de l\'enregistrement du paiement dans le contrôleur', [
                    'referenceInterne' => $referenceInterne,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'enregistrement du paiement: ' . $e->getMessage(),
                    'code' => 'PAYMENT_REGISTRATION_FAILED',
                    'data' => null,
                ], 500);
            }
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff

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
            Log::error('Erreur lors de l\'initialisation du paiement', [
                'referenceInterne' => $referenceInterne,
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
<<<<<<< HEAD
            $paiement = TblPaiement::where('command_number', $referenceInterne)->first();
=======
            $paiement = TblPaiement::where('codePaiement', $referenceInterne)
            ->orWhere('command_number', $referenceInterne)->first();
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff

            if (!$paiement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction non trouvée.',
                    'code' => 'TRANSACTION_NOT_FOUND',
                ], 404);
            }

            $statut = $this->jekoService->verifierStatut($paiement);

<<<<<<< HEAD
=======
            // Si le statut est "error", mettre à jour le statut en base
            if ($statut['success'] && $statut['status'] === 'error') {
                $this->orchestrator->mettreAJourStatut($paiement, 'error', []);
                Log::info('Statut du paiement mis à jour en base (error)', [
                    'reference' => $referenceInterne,
                ]);
            }

>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff
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
<<<<<<< HEAD
        Log::info('Webhook Jeko reçu', ['payload' => $payload]);
=======
        Log::info('Webhook Jeko reçu', [
            'payload' => $payload,
        ]);
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff

        try {
            // Valider le webhook
            if (!$this->jekoService->validerWebhook($request)) {
                Log::warning('Webhook Jeko non valide - signature incorrecte');
                return response()->json(['error' => 'Invalid webhook'], 401);
            }

<<<<<<< HEAD
            $reference = $payload['transactionDetails']['reference'] ?? null;
            // $referenceInterne = $payload['reference'] ?? null;
            $statutJeko = $payload['status'] ?? null;

            if (!$reference || !$statutJeko) {
                return response()->json(['status' => 'ignored'], 200);
            }

            $paiement = TblPaiement::where('command_number', $reference)->first();
=======
            // Traiter le webhook
            $resultat = $this->jekoService->traiterWebhook($payload);

            if (!$resultat) {
                Log::info('Webhook ignoré - traitement non nécessaire');
                return response()->json(['status' => 'ignored'], 200);
            }

            // Mettre à jour le paiement
            $paiement = TblPaiement::where('codePaiement', $resultat['reference'])
            ->orWhere('command_number', $resultat['reference'])->first();
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff

            if (!$paiement) {
                Log::warning('Paiement non trouvé pour le webhook', ['reference' => $resultat['reference']]);
                return response()->json(['status' => 'ignored'], 200);
            }
<<<<<<< HEAD

            // $nouvelEtat = $this->mapperStatutVersEtat($statutJeko);
            // $ancienEtat = $paiement->etat;

            $phone = preg_replace('/\D/', '', $payload['counterpartIdentifier']);
            $phone = substr($phone, -10);

            $paiement->etat = 2;
            $paiement->codePaiement = $payload['transactionDetails']['paymentLinkId'] ?? null;
            $paiement->payment_status = $statutJeko;

            $paiement->telpaiement = $phone ?? null;
            $paiement->paid_sum = (int) $payload['amount']['amount'] / 100 ?? null;
            $paiement->paid_amount = (int) $payload['amount']['amount'] / 100 ?? null;
            $paiement->payment_token = $payload['transactionDetails']['paymentLinkId'] ?? null;
            // $paiement->command_number = $payload['id'] ?? null;
            $paiement->payment_validation_date = Carbon::now()->format('Y-m-d H:i:s');
            $paiement->reponse_webhook = array_merge($paiement->reponse_webhook ?? [], ['webhook' => $payload, 'date_maj' => now()]);
            $paiement->save();
=======

            Log::info('Mise à jour du paiement via webhook', [
                'reference' => $resultat['reference'],
                'nouveau_statut' => $resultat['status'],
            ]);
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff

            // Mettre à jour le statut via l'orchestrateur
            $this->orchestrator->mettreAJourStatut(
                $paiement,
                $resultat['status'],
                [
                    'phone' => $resultat['phone'],
                    'amount' => $resultat['amount'],
                ]
            );

            // Si le paiement est réussi, générer le reçu
            if ($resultat['status'] === 'success') {
                $this->generateReceipt($paiement->codePaiement, $paiement->typeReglement);
            }

            Log::info('Webhook traité avec succès', [
                'reference' => $resultat['reference'],
                'statut' => $resultat['status'],
            ]);

<<<<<<< HEAD
            // Propage le statut aux factures liées (même codePaiement)
            TblFacture::where('codePaiement', $paiement->command_number)->update([
                'etat' => 2,
                'codePaiement' => $payload['transactionDetails']['paymentLinkId']
            ]);
            Log::info('Factures mise à jour via webhook');

            $this->generateReceipt($paiement->codePaiement, $paiement->typeReglement);

            Log::info('Transaction mise à jour via webhook', [
                'reference' => $reference,
                // 'ancien_etat' => $ancienEtat,
                // 'nouvel_etat' => $nouvelEtat,
            ]);

=======
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff
            return response()->json(['status' => 'success'], 200);
        } catch (\Throwable $e) {
            Log::error('Erreur traitement webhook Jeko', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    private function generateReceipt(string $codePaiement, string $typeReglement)
    {
        try {
            $paiement = TblPaiement::where('codePaiement', $codePaiement)->firstOrFail();
<<<<<<< HEAD
            $contrat = Contrat::where('id', $paiement->idContrat)->firstOrFail();
=======

            Log::info('Génération du reçu PDF', [
                'reference' => $paiement->command_number,
            ]);
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff

            $libellesTypeFacture = [
                'N' => 'Prime principale',
                'F' => "Frais d'adhésion",
                'P' => 'Partielle (Reste à payer)',
                'U' => 'Unique',
                'B' => 'Participation aux Bénéfices',
                'E' => 'Exceptionnelle',
                'A' => 'Avance (Remboursement de prêts)',
            ];

            // Libellés des types de paiement
            $libellesType = [
                'firstPayment' => 'Premier paiement',
                'earlyPayment' => 'Paiement anticipé',
                'recoveryPrime' => 'Régularisation de primes',
            ];

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

            // Charger la vue et générer le HTML
            $html = view('paiement.recu_pdf', compact('paiement', 'factures', 'libelleType'))->render();

            // Configurer DomPDF
            $options = new Options();
            $options->set('defaultFont', 'DejaVu Sans');
            $options->set('isRemoteEnabled', false);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', false);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $identifiantContrat = $paiement->idContrat ?? $paiement->idproposition;
            $fileName = 'recu-paiement-' . $paiement->codePaiement . '-' . $identifiantContrat . '.pdf';
<<<<<<< HEAD
            $filePath = $externalUploadDir . DIRECTORY_SEPARATOR . $fileName;

            // Sauvegarder le PDF
            file_put_contents($filePath, $dompdf->output());

            Log::info('PDF généré avec succès pour : ' . $codePaiement);
=======
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff

            // Créer un fichier temporaire
            $tempPath = tempnam(sys_get_temp_dir(), 'pdf_');
            file_put_contents($tempPath, $dompdf->output());

<<<<<<< HEAD
                // Ajoute le reçu au contrat
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
=======
            Log::info('PDF généré avec succès', [
                'file_name' => $fileName,
            ]);

            if ($paiement->typeReglement === 'firstPayment') {
                $contrat = Contrat::where('id', $identifiantContrat)->first();

                if ($contrat) {
                    // Créer un UploadedFile à partir du fichier temporaire
                    $uploadedFile = new \Illuminate\Http\UploadedFile(
                        $tempPath,
                        $fileName,
                        'application/pdf',
                        null,
                        true
                    );

                    // Sauvegarder le fichier dans le dossier d'upload
                    $externalUploadDir = base_path(env('UPLOADS_PATH'));
                    if (!is_dir($externalUploadDir)) {
                        mkdir($externalUploadDir, 0777, true);
                    }

                    $finalPath = $externalUploadDir . DIRECTORY_SEPARATOR . $fileName;
                    file_put_contents($finalPath, file_get_contents($tempPath));

                    // Créer l'entrée dans tbldocuments
                    TblDocument::create([
                        'codecontrat' => $contrat->id,
                        'filename' => $fileName,
                        'libelle' => 'Recu de paiement',
                        'saisiele' => now(),
                        'saisiepar' => $contrat->saisiepar ?? null,
                        'source' => 'ES',
                    ]);

                    // Mettre le contrat en statut "payé"
                    $oldIsPaid = (bool) $contrat->estpaye;
                    $contrat->update(['estpaye' => 1]);

                    Log::info('Contrat marqué comme payé', [
                        'contrat_id' => $contrat->id,
                        'old_is_paid' => $oldIsPaid,
                    ]);
                }
            }

            // Supprimer le fichier temporaire
            if (file_exists($tempPath)) {
                unlink($tempPath);
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff
            }

            return [
                'status' => 'success',
                'file_name' => $fileName,
            ];

        } catch (\Exception $e) {
<<<<<<< HEAD
            Log::error('Erreur génération PDF : ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
=======
            Log::error('Erreur génération PDF', [
                'message' => $e->getMessage(),
            ]);
>>>>>>> 492a4f44264db1d80930cd8597eb72b1c9e402ff

            return [
                'status' => 'error',
                'message' => 'Erreur lors de la génération du PDF : ' . $e->getMessage()
            ];
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
