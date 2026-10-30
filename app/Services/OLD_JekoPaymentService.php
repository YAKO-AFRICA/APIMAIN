<?php

namespace App\Services;

use App\Models\Contrat;
use App\Models\TblDocument;
use App\Models\TblFacture;
use App\Models\TblPaiement;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JekoPaymentService
{
    protected string $baseUrl;
    protected string $storeId;
    protected string $apiKey;
    protected string $apiKeyId;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.jeko.base_url', 'https://api.jeko.africa');
        $this->storeId = config('services.jeko.store_id');
        $this->apiKey = config('services.jeko.api_key');
        $this->apiKeyId = config('services.jeko.api_key_id');
        $this->webhookSecret = config('services.jeko.webhook_secret'); // Ajouté
    }

    /**
     * Initialise un paiement chez Jeko
     */
    public function initialiserPaiement(array $donnees, string $referenceInterne): array
    {
        $corpsRequete = [
            'storeId' => $this->storeId,
            'amountCents' => $donnees['amountCents'],
            'currency' => $donnees['currency'] ?? 'XOF',
            'reference' => $referenceInterne,
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $donnees['paymentMethod'],
                    'successUrl' => $donnees['successUrl'] ?? null,
                    'errorUrl' => $donnees['errorUrl'] ?? null,
                    'payerPhone' => $donnees['payerPhone'] ?? null,
                ],
            ],
        ];

        // Ajout des informations optionnelles
        if (!empty($donnees['customerEmail'])) {
            $corpsRequete['customerEmail'] = $donnees['customerEmail'];
        }
        if (!empty($donnees['customerName'])) {
            $corpsRequete['customerName'] = $donnees['customerName'];
        }
        if (!empty($donnees['description'])) {
            $corpsRequete['description'] = $donnees['description'];
        }
        if (!empty($donnees['metadata'])) {
            $corpsRequete['metadata'] = $donnees['metadata'];
        }

        Log::info('Initialisation du paiement Jeko', [
            'reference' => $referenceInterne,
            'currency' => $donnees['currency'] ?? 'XOF',
            'payment_method' => $donnees['paymentMethod'],
        ]);

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-KEY' => $this->apiKey,
            'X-API-KEY-ID' => $this->apiKeyId,
        ])
            ->timeout(15)
            ->retry(3, 100)
            ->post($this->baseUrl . '/partner_api/payment_requests', $corpsRequete);

        $data = $response->json();

        if (!$response->successful() || empty($data['redirectUrl'])) {
            Log::error('Échec initialisation paiement Jeko', [
                'reference' => $referenceInterne,
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                'code' => $data['code'] ?? 'JEKO_ERROR',
                'status' => $response->status(),
            ];
        }

        Log::info('Paiement Jeko initialisé avec succès', [
            'reference' => $referenceInterne,
            'status' => $data['status'] ?? 'pending',
        ]);

        return [
            'success' => true,
            'redirectUrl' => $data['redirectUrl'],
            'paymentId' => $data['id'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'code' => 'PAYMENT_INITIATED',
        ];
    }

    /**
     * Vérifie le statut d'un paiement
     */
    public function verifierStatut($paiement): array
    {
        if (empty($paiement->payment_token)) {
            Log::warning('Tentative de vérification de statut avec un token de paiement vide');

            return [
                'success' => false,
                'code' => 'INVALID_PAYMENT_CODE',
                'status' => $paiement->payment_status ?? 'unknown',
                'message' => 'Token de paiement manquant ou invalide',
                'details' => [],
            ];
        }

        // Utiliser l'endpoint payment_requests pour obtenir le statut
        $response = Http::withHeaders([
            'X-API-KEY' => $this->apiKey,
            'X-API-KEY-ID' => $this->apiKeyId,
        ])
            ->timeout(10)
            ->retry(2, 100)
            ->get($this->baseUrl . '/partner_api/payment_requests/' . $paiement->payment_token);

        if (!$response->successful()) {
            Log::warning('Échec de la vérification du statut du paiement', [
                'status' => $response->status(),
            ]);

            return [
                'success' => false,
                'code' => 'TRANSACTION_VERIFICATION_FAILED',
                'status' => $paiement->payment_status ?? 'unknown',
                'message' => 'Erreur lors de la vérification du statut du paiement',
                'details' => [
                    'http_status' => $response->status(),
                    'payment_token' => $paiement->payment_token,
                ],
            ];
        }

        $data = $response->json();

        Log::info('Statut du paiement vérifié avec succès', [
            'status' => $data['status'] ?? 'unknown',
        ]);

        return [
            'success' => true,
            'code' => 'TRANSACTION_VERIFIED',
            'message' => 'Statut du paiement vérifié',
            'status' => $data['status'] ?? 'unknown',
            'details' => $data,
        ];
    }

    /**
     * Valide le webhook Jeko
     */

    public function validerWebhook(Request $request): bool
    {

        // Essayer plusieurs noms de headers possibles
        $signature = $request->header('jeko-signature');

        $payload = $request->getContent();


        if (empty($signature) || empty($payload)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }


    /**
     * Traite le webhook Jeko
     */
    public function traiterWebhook(array $payload): ?array
    {
        $reference = $payload['transactionDetails']['reference'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$reference || !$status) {
            Log::warning('Webhook Jeko avec données incomplètes');
            return null;
        }

        // $paiement = TblPaiement::where('command_number', $reference)->first();
        $paiement = TblPaiement::where('codePaiement', $reference)
            ->orWhere('command_number', $reference)->first();

        if (!$paiement) {
            Log::warning('Paiement non trouvé pour le webhook');
            return null;
        }

        // Idempotency: si le paiement est déjà dans l'état final, ignorer le webhook
        $targetStatus = $this->convertirStatut($status);
        if ($paiement->payment_status === $targetStatus && in_array($targetStatus, ['success', 'cancelled', 'error'])) {
            Log::info('Webhook ignoré - paiement déjà dans l\'état final', [
                'reference' => $reference,
                'current_status' => $paiement->payment_status,
                'webhook_status' => $targetStatus,
            ]);
            return null;
        }

        // Extraire les informations du payload
        $phone = null;
        if (!empty($payload['counterpartIdentifier'])) {
            $phone = preg_replace('/\D/', '', $payload['counterpartIdentifier']);
            $phone = substr($phone, -10);
        }

        return [
            'reference' => $reference,
            'status' => $targetStatus,
            'phone' => $phone,
            'amount' => ($payload['amount']['amount'] ?? null) / 100,
        ];
    }


    public function generateReceipt(string $codePaiement, string $typeReglement)
    {
        try {
            $paiement = TblPaiement::where('codePaiement', $codePaiement)->firstOrFail();
            // $contrat = Contrat::where('id', $paiement->idContrat)->firstOrFail();

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

            // Créer un fichier temporaire
            $tempPath = tempnam(sys_get_temp_dir(), 'pdf_');
            file_put_contents($tempPath, $dompdf->output());

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
            }

            return [
                'status' => 'success',
                'message' => 'PDF généré avec succès',
                'file_name' => $fileName,
            ];

        } catch (\Exception $e) {
            Log::error('Erreur génération PDF', [
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Erreur lors de la génération du PDF : ' . $e->getMessage(),
                'file_name' => null,
            ];
        }
    }
    // public function generateReceipt(string $codePaiement, string $typeReglement)
    // {
    //     try {
    //         $externalUploadDir = base_path(env('UPLOADS_PATH'));
    //         if (!is_dir($externalUploadDir)) {
    //             mkdir($externalUploadDir, 0777, true);
    //         }

    //         $paiement = TblPaiement::where('codePaiement', $codePaiement)->firstOrFail();
    //         $contrat = Contrat::where('id', $paiement->idContrat)->firstOrFail();

    //         $libellesTypeFacture = [
    //             'N' => 'Prime principale',
    //             'F' => 'Frais d\'adhésion',
    //         ];

    //         $libellesType = [
    //             'firstPayment' => 'Premier paiement',
    //             'earlyPayment' => 'Paiement anticipé',
    //             'recoveryPrime' => 'Régularisation de primes',
    //         ];

    //         $libelleType = $libellesType[$paiement->typeReglement] ?? $paiement->typeReglement;

    //         $factures = TblFacture::where('codePaiement', $codePaiement)
    //             ->orderBy('dateAjout')
    //             ->get()
    //             ->map(function ($facture) use ($libellesTypeFacture) {
    //                 $facture->libelleTypeFacture =
    //                     $libellesTypeFacture[$facture->typeFacture]
    //                     ?? $facture->typeFacture;
    //                 return $facture;
    //             });

    //         // Charger la vue et générer le HTML
    //         $html = view('paiement.recu_pdf', compact('paiement', 'factures', 'libelleType'))->render();

    //         // Configurer DomPDF
    //         $options = new Options();
    //         $options->set('defaultFont', 'DejaVu Sans');
    //         $options->set('isRemoteEnabled', false);
    //         $options->set('isHtml5ParserEnabled', true);
    //         $options->set('isPhpEnabled', false);

    //         $dompdf = new Dompdf($options);
    //         $dompdf->loadHtml($html);
    //         $dompdf->setPaper('A4', 'portrait');
    //         $dompdf->render();

    //         $identifiantContrat = ($paiement->typeReglement === 'firstPayment') ? $paiement->idContrat : $paiement->idproposition;

    //         $fileName = 'recu-paiement-' . $paiement->codePaiement . '-' . $identifiantContrat . '.pdf';
    //         $filePath = $externalUploadDir . DIRECTORY_SEPARATOR . $fileName;

    //         // Sauvegarder le PDF
    //         file_put_contents($filePath, $dompdf->output());

    //         Log::info('PDF généré avec succès pour : ' . $codePaiement);

    //         if ($typeReglement === 'firstPayment') {

    //             // Ajoute le reçu au contrat
    //             TblDocument::create([
    //                 'codecontrat' => $paiement->idContrat,
    //                 'filename' => $fileName,
    //                 'libelle' => 'Recu de paiement',
    //                 'saisiele' => now(),
    //                 'saisiepar' => $contrat->saisiepar ?? null,
    //                 'source' => "ES",
    //             ]);

    //             // Mettre le contrat en statut "payé"
    //             $contrat->update(['estpaye' => 1]);
    //         }

    //         return [
    //             'status' => 'success',
    //             'file_name' => $fileName,
    //             'file_path' => $filePath,
    //         ];

    //     } catch (\Exception $e) {
    //         Log::error('Erreur génération PDF : ' . $e->getMessage());
    //         Log::error('Stack trace: ' . $e->getTraceAsString());

    //         return [
    //             'status' => 'error',
    //             'message' => 'Erreur lors de la génération du PDF : ' . $e->getMessage()
    //         ];
    //     }
    // }

    /**
     * Convertit le statut Jeko en statut interne
     */
    private function convertirStatut(string $status): string
    {
        $map = [
            'pending' => 'pending',
            'processing' => 'pending',
            'completed' => 'success',
            'success' => 'success',
            'succeeded' => 'success',
            'failed' => 'error',
            'cancelled' => 'cancelled',
            'refunded' => 'cancelled',
            'error' => 'error',
        ];

        return $map[strtolower($status)] ?? $status;
    }
}
