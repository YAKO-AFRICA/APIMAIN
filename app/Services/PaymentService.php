<?php

namespace App\Services;

use App\Models\TblDocument;
use App\Models\TblFacture;
use App\Models\TblPaiement;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    // Configuration Jeko
    protected string $jekoBaseUrl;
    protected string $jekoStoreId;
    protected string $jekoApiKey;
    protected string $jekoApiKeyId;
    protected string $jekoWebhookSecret;

    // Configuration Wave
    protected string $waveApiKey;
    protected string $waveBaseUrl;
    protected string $waveWebhookSecret;

    public function __construct()
    {
        // Jeko
        $this->jekoBaseUrl = config('services.jeko.base_url', 'https://api.jeko.africa');
        $this->jekoStoreId = config('services.jeko.store_id');
        $this->jekoApiKey = config('services.jeko.api_key');
        $this->jekoApiKeyId = config('services.jeko.api_key_id');
        $this->jekoWebhookSecret = config('services.jeko.webhook_secret');

        // Wave
        $this->waveApiKey = (string) config('services.wave.api_key');
        $this->waveBaseUrl = rtrim((string) config('services.wave.base_url', 'https://api.wave.com'), '/');
        $this->waveWebhookSecret = config('services.wave.webhook_secret');
    }

    /**
     * Route l'initialisation vers Wave ou Jeko selon paymentMethod
     */
    public function initialiserPaiement(array $donnees, string $referenceInterne): array
    {
        $paymentMethod = $donnees['paymentMethod'] ?? '';

        if ($paymentMethod === 'wave') {
            return $this->initialiserPaiementWave($donnees, $referenceInterne);
        } else {
            return $this->initialiserPaiementJeko($donnees, $referenceInterne);
        }
    }

    /**
     * Initialise un paiement chez Wave
     */
    protected function initialiserPaiementWave(array $donnees, string $referenceInterne): array
    {
        $payload = [
            'amount' => 2,
            // 'amount' => $donnees['amountCents'],
            'currency' => $donnees['currency'],
            'client_reference' => $referenceInterne,
            'success_url' => (isset($donnees['successUrl']) && !empty($donnees['successUrl'])) ? $donnees['successUrl'] : env('APP_URL') . '/paiement/recu/' . $referenceInterne,
            'error_url' => (isset($donnees['errorUrl']) && !empty($donnees['errorUrl'])) ? $donnees['errorUrl'] : env('APP_URL') . '/paiement/error/' . $referenceInterne,
        ];

        $response = Http::withToken($this->waveApiKey)
            ->withHeaders(['Idempotency-Key' => 'order-' . $referenceInterne])
            ->acceptJson()
            ->post("{$this->waveBaseUrl}/v1/checkout/sessions", $payload);

        $data = $response->json();

        if (!$response->successful()) {
            Log::warning('Échec initialisation paiement Wave', [
                'status' => $response->status(),
                'response' => $data,
                'request' => $payload,
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                'code' => $data['code'] ?? 'WAVE_API_ERROR',
                'status' => $response->status(),
            ];
        }

        return [
            'success' => true,
            'redirectUrl' => $data['wave_launch_url'],
            'status' => $data['status'] ?? 'pending',
            'code' => 'PAYMENT_INITIATED',
            'paymentId' => $data['id'] ?? null,
        ];
    }

    /**
     * Initialise un paiement chez Jeko
     */
    protected function initialiserPaiementJeko(array $donnees, string $referenceInterne): array
    {
        $corpsRequete = [
            'storeId' => $this->jekoStoreId,
            'amountCents' => 200,
            // 'amountCents' => $donnees['amountCents'],
            'currency' => $donnees['currency'] ?? 'XOF',
            'reference' => $referenceInterne,
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $donnees['paymentMethod'],
                    'successUrl' => $donnees['successUrl'] ?? env('APP_URL') . '/paiement/recu/' . $referenceInterne,
                    'errorUrl' => $donnees['errorUrl'] ?? env('APP_URL') . '/paiement/error/' . $referenceInterne,
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
            'X-API-KEY' => $this->jekoApiKey,
            'X-API-KEY-ID' => $this->jekoApiKeyId,
        ])
            ->timeout(15)
            ->retry(3, 100)
            ->post($this->jekoBaseUrl . '/partner_api/payment_requests', $corpsRequete);

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
     * Vérifie le statut d'un paiement selon le provider
     */
    public function verifierStatut(TblPaiement $paiement, string $provider = null): array
    {
        $provider = $provider ?? $paiement->reglementSource ?? 'JEKO';

        if ($provider === 'WAVE_API' || $provider === 'WAVE') {
            return $this->verifierStatutWave($paiement);
        } else {
            return $this->verifierStatutJeko($paiement);
        }
    }

    /**
     * Vérifie le statut d'un paiement Wave
     */
    protected function verifierStatutWave(TblPaiement $paiement): array
    {
        if (empty($paiement->payment_token)) {
            return [
                'success' => false,
                'message' => 'Aucune session Wave associée à ce paiement.',
                'details' => [],
            ];
        }

        try {
            $data = $this->getWaveSession($paiement->payment_token);
            $status = strtolower((string) ($data['payment_status'] ?? ''));
            $status = $this->convertirStatut($status ?? 'pending');

            // if (in_array($status, ['succeeded', 'failed', 'cancelled'], true)) {
            //     $status = $this->convertirStatut($status);

            //     $paiement->payment_status = $status;

            //     if ($status === 'success') {
            //         $paiement->etat = 2;
            //         $paiement->payment_validation_date = $paiement->payment_validation_date
            //             ?? now()->format('Y-m-d H:i:s');

            //         if (isset($data['amount']) && is_numeric($data['amount'])) {
            //             $paiement->paid_sum = (int) $data['amount'];
            //             $paiement->paid_amount = (int) $data['amount'];
            //         }
            //     }

            //     $paiement->save();

            //     if ($status === 'success') {
            //         TblFacture::where('codePaiement', $paiement->codePaiement)
            //             ->update(['etat' => 2]);
            //     }
            // }

            return [
                'success' => true,
                'status' => $status,
                'details' => $data,
            ];
        } catch (\Throwable $e) {
            Log::warning('verifierStatutWave impossible', [
                'reference' => $paiement->codePaiement,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /**
     * Vérifie le statut d'un paiement Jeko
     */
    protected function verifierStatutJeko(TblPaiement $paiement): array
    {
        if (empty($paiement->payment_token)) {
            return [
                'success' => false,
                'message' => 'Aucune session Wave associée à ce paiement.',
                'details' => [],
            ];
        }
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-API-KEY' => $this->jekoApiKey,
                'X-API-KEY-ID' => $this->jekoApiKeyId,
            ])
                ->timeout(10)
                ->get($this->jekoBaseUrl . '/partner_api/payment_requests/' . $paiement->payment_token);

            $data = $response->json();

            if (!$response->successful()) {
                Log::error('Échec vérification statut Jeko', [
                    'reference' => $paiement->codePaiement,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Impossible de vérifier le statut du paiement.',
                    'status' => 'error',
                    'details' => [],
                ];
            }

            $status = $this->convertirStatut($data['status'] ?? 'pending');

            return [
                'success' => true,
                'status' => $status,
                'details' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('Erreur vérification statut Jeko', [
                'reference' => $paiement->codePaiement,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la vérification du statut.',
                'status' => 'error',
                'details' => [],
            ];
        }
    }

    /**
     * Récupère une session Wave par son ID
     */
    protected function getWaveSession(string $sessionId): array
    {
        $response = Http::withToken($this->waveApiKey)
            ->acceptJson()
            ->get("{$this->waveBaseUrl}/v1/checkout/sessions/{$sessionId}");

        if ($response->failed()) {
            throw new \Exception('Wave check failed: ' . $response->body());
        }

        return $response->json();
    }

    

    /**
     * Valide le webhook Jeko
     */
    public function validerWebhookJeko(Request $request): bool
    {
        $signature = $request->header('X-Signature', '');
        $timestamp = $request->header('X-Timestamp', '');
        $payload = $request->getContent();

        if (empty($signature) || empty($timestamp) || empty($this->jekoWebhookSecret)) {
            return false;
        }

        // Vérifier anti-replay (5 minutes)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . $payload, $this->jekoWebhookSecret);
        return hash_equals($expected, $signature);
    }

    /**
     * Traite le webhook Jeko
     */
    public function traiterWebhookJeko(array $payload): ?array
    {
        // Structure réelle du webhook Jeko :
        // {
        //   "id": "...",
        //   "status": "success",
        //   "amount": {"amount": 200, "currency": "XOF"},
        //   "paymentMethod": "orange",
        //   "transactionDetails": {"reference": "PAI-..."}
        // }

        $status = $payload['status'] ?? '';
        $reference = $payload['transactionDetails']['reference'] ?? '';
        // $phone = $payload['counterpartIdentifier'] ?? null;
        $amount = $payload['amount']['amount'] ?? 0;
        $paymentMethod = $payload['paymentMethod'] ?? null;
        $mappedStatus = $this->convertirStatut($status);
        $phone = null;
        if (!empty($payload['counterpartIdentifier'])) {
            $phone = preg_replace('/\D/', '', $payload['counterpartIdentifier']);
            $phone = substr($phone, -10);
        }

        if (!in_array($mappedStatus, ['success', 'failed', 'cancelled', 'error'], true)) {
            return null;
        }

        // $mappedStatus = match (strtolower($status)) {
        //     'success' => 'success',
        //     'failed' => 'error',
        //     'cancelled' => 'cancelled',
        //     default => strtolower($status),
        // };
        

        return [
            'reference' => $reference,
            'status' => $mappedStatus,
            'phone' => $phone,
            'amount' => ((float) $amount) / 100,
            'paymentMethod' => $paymentMethod,
        ];
    }

    /**
     * Valide la signature du webhook Wave
     */
    public function validerWebhookWave(string $rawBody, string $signatureHeader): bool
    {
        $secret = $this->waveWebhookSecret;
        
        Log::info('Validation signature Wave', [
            'secret_set' => !empty($secret),
            'signature_header' => $signatureHeader,
        ]);
        
        // TEMPORAIRE : Désactiver la validation pour les tests
        // À retirer une fois le secret configuré
        if (empty($secret)) {
            Log::warning('Webhook Wave : validation désactivée (secret non configuré)');
            return true;
        }
        
        if (empty($signatureHeader) || $secret === '') {
            Log::warning('Webhook Wave : signature ou secret manquant', [
                'signature_header' => $signatureHeader,
                'secret_set' => !empty($secret),
            ]);
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }

        $timestamp = (int) ($parts['t'] ?? 0);
        $signature = $parts['v1'] ?? '';

        if ($timestamp <= 0 || $signature === '') {
            return false;
        }

        // Fenêtre anti-replay de 5 minutes
        if (abs(time() - $timestamp) > 300) {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            $timestamp . '.' . $rawBody,
            $secret
        );

        return hash_equals($expected, $signature);
    }

    // /**
    //  * Convertir les statuts des webhooks vers un format unifié
    //  */
    // protected function convertirStatut(string $status): string
    // {
    //     return match (strtolower($status)) {
    //         'success', 'succeeded', 'completed' => 'success',
    //         'failed', 'error' => 'error',
    //         'cancelled' => 'cancelled',
    //         'pending' => 'pending',
    //         default => strtolower($status),
    //     };
    // }

    /**
     * Traite le webhook Wave
     */
    public function traiterWebhookWave(array $event): ?array
    {
        // Structure réelle du webhook Wave :
        // {
        //   "id": "AE_ci_AHTDwsaOJ2Wz",
        //   "type": "checkout.session.completed",
        //   "data": {
        //     "amount": "1",
        //     "client_reference": "YAKO-1793193317",
        //     "id": "cos-285dadct02726",
        //     "payment_status": "succeeded",
        //     "transaction_id": "TIKK2W4FY346D4LWE",
        //     ...
        //   }
        // }

        $type = $event['type'] ?? null;
        $data = $event['data'] ?? [];
        $clientReference = $data['client_reference'] ?? null;
        $sessionId = $data['id'] ?? null;
        $status = strtolower((string) ($data['payment_status'] ?? ''));
        $amount = $data['amount'] ?? 0;
        $transactionId = $data['transaction_id'] ?? null;

        if (!in_array($type, [
            'checkout.session.completed',
            'checkout.session.failed',
            'checkout.session.cancelled',
        ], true)) {
            return null;
        }

        // $mappedStatus = match ($status) {
        //     'succeeded' => 'success',
        //     'failed' => 'error',
        //     'cancelled' => 'cancelled',
        //     default => $status,
        // };
        $mappedStatus = $this->convertirStatut($status);

        return [
            'reference' => $clientReference,
            'status' => $mappedStatus,
            'sessionId' => $sessionId,
            'amount' => (float) $amount,
            'transactionId' => $transactionId,
        ];
    }

    /**
     * Génère le reçu PDF (commun pour Wave et Jeko)
     */
    public function generateReceipt(string $codePaiement, string $typeReglement): array
    {
        try {
            $paiement = TblPaiement::where('codePaiement', $codePaiement)->firstOrFail();

            $libellesTypeFacture = [
                'N' => 'Prime principale',
                'F' => "Frais d'adhésion",
                'P' => 'Partielle (Reste à payer)',
                'U' => 'Unique',
                'B' => 'Participation aux Bénéfices',
                'E' => 'Exceptionnelle',
                'A' => 'Avance (Remboursement de prêts)',
            ];

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

            $html = view('paiement.recu_pdf', compact('paiement', 'factures', 'libelleType'))->render();

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

            $tempPath = tempnam(sys_get_temp_dir(), 'pdf_');
            file_put_contents($tempPath, $dompdf->output());

            $externalUploadDir = base_path(env('UPLOADS_PATH'));
            if (!is_dir($externalUploadDir)) {
                mkdir($externalUploadDir, 0777, true);
            }

            $finalPath = $externalUploadDir . DIRECTORY_SEPARATOR . $fileName;
            file_put_contents($finalPath, file_get_contents($tempPath));
            unlink($tempPath);

            if ($paiement->typeReglement === 'firstPayment') {
                $contrat = \App\Models\Contrat::where('id', $identifiantContrat)->first();

                if ($contrat) {
                    TblDocument::create([
                        'codecontrat' => $contrat->id,
                        'filename' => $fileName,
                        'libelle' => 'Recu de paiement',
                        'dateAjout' => now()->format('Y-m-d H:i:s'),
                    ]);
                }
            }

            Log::info('PDF reçu généré avec succès', [
                'reference' => $codePaiement,
                'file_name' => $fileName,
            ]);

            return [
                'status' => 'success',
                'file_name' => $fileName,
                'path' => $finalPath,
            ];
        } catch (\Throwable $e) {
            Log::error('Erreur génération PDF reçu', [
                'reference' => $codePaiement,
                'message' => $e->getMessage(),
            ]);

            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Convertit le statut en statut interne
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
