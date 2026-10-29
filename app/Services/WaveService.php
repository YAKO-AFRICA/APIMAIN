<?php

namespace App\Services;

use App\Models\TblFacture;
use App\Models\TblPaiement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WaveService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey  = (string) config('services.wave.api_key');
        $this->baseUrl = rtrim((string) config('services.wave.base_url', 'https://api.wave.com'), '/');
    }

    /**
     * Initialise une session de paiement Wave.
     */
    public function initialiserPaiement(array $donnees, string $referenceInterne): array
    {

        $payload = [
            'amount'           => 1,
            // 'amount'           => (string) $donnees['amountCents'],
            'currency'         => $donnees['currency'],
            'client_reference' => $referenceInterne,
            'success_url'      => $donnees['successUrl'] ?? route('wave.retour.success', ['ref' => $referenceInterne]),
            'error_url'        => $donnees['errorUrl']   ?? route('wave.retour.error',   ['ref' => $referenceInterne]),
        ];

        $response = Http::withToken($this->apiKey)
            ->withHeaders(['Idempotency-Key' => 'order-' . $referenceInterne])
            ->acceptJson()
            ->post("{$this->baseUrl}/v1/checkout/sessions", $payload);

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
     * Récupère une session Wave par son ID.
     */
    public function getSession(string $sessionId): array
    {
        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->get("{$this->baseUrl}/v1/checkout/sessions/{$sessionId}");

        if ($response->failed()) {
            throw new \Exception('Wave check failed: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Rafraîchit le statut d'un paiement depuis Wave.
     */
    public function refreshStatus(TblPaiement $paiement): array
    {
        if (empty($paiement->payment_token)) {
            return [
                'success' => false,
                'message' => 'Aucune session Wave associée à ce paiement.',
                'data' => [],
            ];
        }

        try {
            $data = $this->getSession($paiement->payment_token);
            $status = strtolower((string) ($data['payment_status'] ?? ''));

            if (in_array($status, ['succeeded', 'failed', 'cancelled'], true)) {
                $status = $this->mapperStatutVersEtat($status);

                DB::connection('mysql3')->transaction(function () use ($paiement, $data, $status) {
                    $paiement->payment_status = $status;

                    if ($status === 'success') {
                        $paiement->etat = 2;
                        $paiement->payment_validation_date = $paiement->payment_validation_date
                            ?? now()->format('Y-m-d H:i:s');

                        if (isset($data['amount']) && is_numeric($data['amount'])) {
                            $paiement->paid_sum = (int) $data['amount'];
                            $paiement->paid_amount = (int) $data['amount'];
                        }
                    }

                    $paiement->save();

                    if ($status === 'success') {
                        TblFacture::where('codePaiement', $paiement->codePaiement)
                            ->update(['etat' => 2]);
                    }
                });
            }

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (\Throwable $e) {
            Log::warning('refreshStatus Wave impossible', [
                'reference' => $paiement->codePaiement,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    private function mapperStatutVersEtat(string $statutWave): string
    {
        return match (strtolower($statutWave)) {
            'completed', 'success', 'succeeded' => 'success',
            'failed', 'cancelled' => 'failed',
            default => strtolower($statutWave),
        };
    }


    /**
     * Vérifie la signature du webhook Wave.
     */
    public function verifyWebhookSignature(string $rawBody, string $signatureHeader): bool
    {
        $secret = (string) config('services.wave.webhook_secret');
        if (empty($signatureHeader) || $secret === '') {
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
}
