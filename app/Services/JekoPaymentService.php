<?php

namespace App\Services;

use App\Models\TblPaiement;
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
        $paiement = Paiement::where('codePaiement', $reference)
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
            'failed' => 'error',
            'cancelled' => 'cancelled',
            'refunded' => 'cancelled',
            'error' => 'error',
        ];

        return $map[strtolower($status)] ?? $status;
    }
}