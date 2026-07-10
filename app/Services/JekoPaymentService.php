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
            'reference' => $donnees['reference'],
            'paymentDetails' => [
                'type' => 'redirect',
                'data' => [
                    'paymentMethod' => $donnees['paymentMethod'],
                    'successUrl' => $donnees['successUrl'] ,
                    'errorUrl' => $donnees['errorUrl'],
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

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-API-KEY' => $this->apiKey,
            'X-API-KEY-ID' => $this->apiKeyId,
        ])
            ->timeout(15)
            ->retry(3, 100, function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException;
            })
            ->post($this->baseUrl . '/partner_api/payment_requests', $corpsRequete);

        $data = $response->json();

        if (!$response->successful() || empty($data['redirectUrl'])) {
            Log::warning('Échec initialisation paiement Jeko', [
                'status' => $response->status(),
                'response' => $data,
                'request' => $corpsRequete,
            ]);

            return [
                'success' => false,
                'message' => $data['message'] ?? 'Erreur lors de l\'initialisation du paiement',
                'code' => $data['code'] ?? 'JEKO_ERROR',
                'status' => $response->status(),
            ];
        }

        return [
            'success' => true,
            'redirectUrl' => $data['redirectUrl'],
            'paymentId' => $data['paymentId'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'code' => 'PAYMENT_INITIATED',
        ];
    }

    /**
     * Vérifie le statut d'un paiement
     */
    public function verifierStatut($paiement): array
    {
        $response = Http::withHeaders([
            'X-API-KEY' => $this->apiKey,
            'X-API-KEY-ID' => $this->apiKeyId,
        ])
            ->timeout(10)
            ->get($this->baseUrl . '/partner_api/payment_requests/' . $paiement->referenceSource);

        if (!$response->successful()) {
            return [
                'status' => $paiement->statut,
                'details' => ['error' => 'Unable to fetch status'],
            ];
        }

        $data = $response->json();

        return [
            'status' => $data['status'] ?? 'unknown',
            'details' => $data,
        ];
    }

    /**
     * Valide le webhook Jeko
     */
    public function validerWebhook(Request $request): bool
    {
        // Récupérer la signature depuis les headers
        $signature = $request->header('X-Jeko-Signature');
        $payload = $request->getContent();

        Log::info('Webhook signature', ['signature' => $signature, 'payload' => $payload]);

        if (empty($signature) || empty($payload)) {
            return false;
        }

        // Vérifier la signature (à adapter selon la doc Jeko)
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);

        Log::info('Webhook signature', ['signature' => $signature, 'expectedSignature' => $expectedSignature]);

        return hash_equals($expectedSignature, $signature);
    }


    /**
     * Traite le webhook Jeko
     */
    public function traiterWebhook(array $payload): ?array
    {
        $reference = $payload['reference'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$reference || !$status) {
            return null;
        }

        // Mettre à jour la transaction
        $paiement = TblPaiement::where('referenceSource', $reference)->first();

        if (!$paiement) {
            Log::warning('Transaction non trouvée pour le webhook', ['reference' => $reference]);
            return null;
        }

        $ancienStatut = $paiement->statut;
        $paiement->statut = $this->convertirStatut($status);
        $paiement->reponse_webhook = array_merge(
            $paiement->reponse_webhook ?? [],
            ['webhook' => $payload, 'date_maj' => now()]
        );
        $paiement->save();

        Log::info('Transaction mise à jour via webhook', [
            'reference' => $reference,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $paiement->statut,
        ]);

        return [
            'reference' => $reference,
            'ancien_statut' => $ancienStatut,
            'nouveau_statut' => $paiement->statut,
        ];
    }

    /**
     * Convertit le statut Jeko en statut interne
     */
    private function convertirStatut(string $status): string
    {
        $map = [
            'pending' => 'en_attente',
            'processing' => 'en_cours',
            'completed' => 'termine',
            'success' => 'reussi',
            'failed' => 'echec',
            'cancelled' => 'annule',
            'refunded' => 'rembourse',
            'error' => 'erreur',
        ];

        return $map[strtolower($status)] ?? $status;
    }
}