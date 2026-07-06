<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TblPaiement;
use App\Services\JekoPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

// class JekoPaymentController extends Controller
// {

//     /**
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
//     private const METHODES_AUTORISEES = [
//         'wave',
//         'orange',
//         'moov',
//         'mtn',
//         'djamo',
//         'visa',
//         'mastercard',
//     ];

//     private const METHODES_NECESSITANT_TELEPHONE = [
//         'wave',
//         'orange',
//         'moov',
//         'mtn',
//         'djamo',
//     ];

//     /**
//      * Initialise un paiement auprès de Jeko à partir des données
//      * envoyées par le widget, puis renvoie l'URL de redirection.
//      */
//     public function initierPaiement(Request $request): JsonResponse
//     {
//         $validator = Validator::make($request->all(), [
//             'amountCents' => ['required', 'integer', 'min:100'],
//             'currency' => ['nullable', 'string', 'size:3'],
//             'reference' => ['required', 'string', 'max:100'],
//             'paymentMethod' => ['required', 'string', 'in:' . implode(',', self::METHODES_AUTORISEES)],
//             'payerPhone' => ['nullable', 'string', 'max:20'],
//             'successUrl' => ['nullable', 'url'],
//             'errorUrl' => ['nullable', 'url'],
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Données de paiement invalides.',
//                 'code' => 'VALIDATION_ERROR',
//                 'data' => $validator->errors(),
//             ], 422);
//         }

//         $donnees = $validator->validated();

//         if (
//             in_array($donnees['paymentMethod'], self::METHODES_NECESSITANT_TELEPHONE, true)
//             && empty($donnees['payerPhone'])
//         ) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Le numéro de téléphone est requis pour ce moyen de paiement.',
//                 'code' => 'PHONE_REQUIRED',
//                 'data' => null,
//             ], 422);
//         }

//         // Référence interne unique pour retrouver la transaction plus tard,
//         // indépendamment de la référence métier envoyée par le widget.
//         $referenceInterne = (string) Str::uuid();

//         $corpsRequete = [
//             'storeId' => config('services.jeko.store_id'),
//             'amountCents' => $donnees['amountCents'],
//             'currency' => $donnees['currency'] ?? 'XOF',
//             'reference' => $donnees['reference'],
//             'paymentDetails' => [
//                 'type' => 'redirect',
//                 'data' => [
//                     'paymentMethod' => $donnees['paymentMethod'],
//                     'successUrl' => $donnees['successUrl'] ?? config('app.url'),
//                     'errorUrl' => $donnees['errorUrl'] ?? config('app.url'),
//                     'payerPhone' => $donnees['payerPhone'] ?? null,
//                 ],
//             ],
//         ];

//         try {
//             $reponse = Http::withHeaders([
//                 'Content-Type' => 'application/json',
//                 'X-API-KEY' => config('services.jeko.api_key'),
//                 'X-API-KEY-ID' => config('services.jeko.api_key_id'),
//             ])
//                 ->timeout(15)
//                 ->post(config('services.jeko.base_url') . '/partner_api/payment_requests', $corpsRequete);

//             $donneesReponse = $reponse->json();

//             // Traçabilité : on enregistre chaque tentative d'initialisation,
//             // quel que soit le résultat, pour pouvoir réconcilier plus tard.
//             TblPaiement::create([
//                 'reference_interne' => $referenceInterne,
//                 'reference_metier' => $donnees['reference'],
//                 'montant_cents' => $donnees['amountCents'],
//                 'devise' => $donnees['currency'] ?? 'XOF',
//                 'methode' => $donnees['paymentMethod'],
//                 'statut' => $reponse->successful() ? 'initie' : 'echec',
//                 'reponse_jeko' => $donneesReponse,
//             ]);

//             if (! $reponse->successful() || empty($donneesReponse['redirectUrl'])) {
//                 Log::warning('Echec initialisation paiement Jeko', [
//                     'reference' => $donnees['reference'],
//                     'statut_http' => $reponse->status(),
//                     'reponse' => $donneesReponse,
//                 ]);

//                 return response()->json([
//                     'success' => false,
//                     'message' => "Impossible d'initialiser le paiement pour le moment.",
//                     'code' => 'JEKO_INIT_FAILED',
//                     'data' => null,
//                 ], 502);
//             }

//             return response()->json([
//                 'success' => true,
//                 'message' => 'Paiement initialisé avec succès.',
//                 'code' => 'PAYMENT_INITIATED',
//                 'data' => [
//                     'redirectUrl' => $donneesReponse['redirectUrl'],
//                     'referenceInterne' => $referenceInterne,
//                 ],
//             ]);
//         } catch (\Throwable $e) {
//             Log::error('Erreur appel API Jeko', [
//                 'reference' => $donnees['reference'],
//                 'message' => $e->getMessage(),
//             ]);

//             return response()->json([
//                 'success' => false,
//                 'message' => 'Erreur technique lors de la communication avec le service de paiement.',
//                 'code' => 'JEKO_UNREACHABLE',
//                 'data' => null,
//             ], 503);
//         }
//     }
// }

class JekoPaymentController extends Controller
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

    private const METHODES_NECESSITANT_TELEPHONE = [
        'wave',
        'orange',
        'moov',
        'mtn',
        'djamo',
    ];

    private const DEVISES_SUPPORTEES = ['XOF', 'XAF', 'USD', 'EUR'];

    protected JekoPaymentService $jekoService;

    public function __construct(JekoPaymentService $jekoService)
    {
        $this->jekoService = $jekoService;
    }

    public function demoJekoWidget()
    {
        return view('paiement.demo-jeko-widget');
    }

    /**
     * Initialise un paiement auprès de Jeko
     */
    public function initierPaiement(Request $request): JsonResponse
    {
        // Validation renforcée
        $validator = Validator::make($request->all(), [
            'amountCents' => ['required', 'integer', 'min:100', 'max:999999999'],
            'currency' => ['nullable', 'string', 'size:3', 'in:' . implode(',', self::DEVISES_SUPPORTEES)],
            'reference' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9\-_]+$/'],
            'paymentMethod' => ['required', 'string', 'in:' . implode(',', self::METHODES_AUTORISEES)],
            'payerPhone' => ['nullable', 'string', 'max:20', 'regex:/^[\+]?[0-9]{8,15}$/'],
            'successUrl' => ['nullable', 'url', 'max:500'],
            'errorUrl' => ['nullable', 'url', 'max:500'],
            'description' => ['nullable', 'string', 'max:255'],
            'customerEmail' => ['nullable', 'email', 'max:100'],
            'customerName' => ['nullable', 'string', 'max:100'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            Log::warning('Validation du paiement échouée', [
                'errors' => $validator->errors(),
                'input' => $request->except(['amountCents'])
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Données de paiement invalides.',
                'code' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        $donnees = $validator->validated();

        // Vérification du téléphone pour les méthodes qui en nécessitent
        if (
            in_array($donnees['paymentMethod'], self::METHODES_NECESSITANT_TELEPHONE, true)
            && empty($donnees['payerPhone'])
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Le numéro de téléphone est requis pour ce moyen de paiement.',
                'code' => 'PHONE_REQUIRED',
                'data' => null,
            ], 422);
        }

        // Nettoyage du numéro de téléphone
        if (!empty($donnees['payerPhone'])) {
            $donnees['payerPhone'] = $this->cleanPhoneNumber($donnees['payerPhone']);
        }

        // Génération d'une référence interne unique
        $referenceInterne = (string) Str::uuid();

        try {
            // Appel au service Jeko
            $resultat = $this->jekoService->initialiserPaiement($donnees, $referenceInterne);

            // Log des données de resultat
            Log::info('Initialisation paiement Jeko', [
                'donnees' => $donnees,
                'reference_interne' => $referenceInterne,
                'resultat' => $resultat
            ]);

            // Enregistrement de la transaction
            $this->enregistrerTransaction($donnees, $referenceInterne, $resultat);


            if (!$resultat['success']) {
                Log::warning('Échec initialisation paiement Jeko', [
                    'reference' => $donnees['reference'],
                    'reference_interne' => $referenceInterne,
                    'erreur' => $resultat['message'] ?? 'Erreur inconnue',
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $resultat['message'] ?? "Impossible d'initialiser le paiement.",
                    'code' => $resultat['code'] ?? 'JEKO_INIT_FAILED',
                    'data' => null,
                ], $resultat['status'] ?? 502);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiement initialisé avec succès.',
                'code' => 'PAYMENT_INITIATED',
                'data' => [
                    'redirectUrl' => $resultat['redirectUrl'],
                    'referenceInterne' => $referenceInterne,
                    'referenceMetier' => $donnees['reference'],
                    'montant' => $donnees['amountCents'] / 100,
                    'devise' => $donnees['currency'] ?? 'XOF',
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Erreur critique appel API Jeko', [
                'reference' => $donnees['reference'] ?? 'inconnue',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Enregistrement de l'échec
            // TblPaiement::create([
            //     'reference_interne' => $referenceInterne ?? Str::uuid(),
            //     'reference_metier' => $donnees['reference'] ?? 'inconnue',
            //     'montant_cents' => $donnees['amountCents'] ?? 0,
            //     'devise' => $donnees['currency'] ?? 'XOF',
            //     'methode' => $donnees['paymentMethod'] ?? 'inconnue',
            //     'statut' => 'echec_critique',
            //     'reponse_jeko' => ['error' => $e->getMessage()],
            // ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur technique lors de la communication avec le service de paiement.',
                'code' => 'JEKO_UNREACHABLE',
                'data' => null,
            ], 503);
        }
    }

    /**
     * Vérifie le statut d'un paiement
     */
    public function verifierStatut(Request $request, string $referenceInterne): JsonResponse
    {
        try {
            $paiement = TblPaiement::where('reference_interne', $referenceInterne)->first();

            if (!$paiement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction non trouvée.',
                    'code' => 'TRANSACTION_NOT_FOUND',
                ], 404);
            }

            // Vérification du statut auprès de Jeko
            $statut = $this->jekoService->verifierStatut($paiement);

            return response()->json([
                'success' => true,
                'data' => [
                    'statut' => $statut['status'],
                    'montant' => $paiement->montant_cents / 100,
                    'devise' => $paiement->devise,
                    'reference' => $paiement->reference_metier,
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
     * Webhook pour recevoir les notifications de Jeko
     */
    public function webhook(Request $request)
    // public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();

        Log::info('Webhook Jeko reçu', ['payload' => $payload]);

        // try {
        //     // Validation du webhook
        //     if (!$this->jekoService->validerWebhook($request)) {
        //         Log::warning('Webhook Jeko non valide', ['payload' => $payload]);
        //         return response()->json(['error' => 'Invalid webhook'], 401);
        //     }

        //     // Traitement du webhook
        //     $resultat = $this->jekoService->traiterWebhook($payload);

        //     if ($resultat) {
        //         Log::info('Webhook Jeko traité avec succès', ['reference' => $resultat['reference'] ?? 'inconnue']);
        //         return response()->json(['status' => 'success'], 200);
        //     }

        //     return response()->json(['status' => 'ignored'], 200);
        // } catch (\Throwable $e) {
        //     Log::error('Erreur traitement webhook Jeko', [
        //         'message' => $e->getMessage(),
        //         'payload' => $payload,
        //     ]);

        //     return response()->json(['error' => 'Webhook processing failed'], 500);
        // }
    }

    /**
     * Nettoie le numéro de téléphone
     */
    private function cleanPhoneNumber(string $phone): string
    {
        // Supprimer les espaces, tirets, points
        $phone = preg_replace('/[\s\-\.\(\)]/', '', $phone);
        
        // S'assurer que le numéro commence par +
        if (!str_starts_with($phone, '+')) {
            if (str_starts_with($phone, '00')) {
                $phone = '+' . substr($phone, 2);
            } elseif (strlen($phone) === 10 && str_starts_with($phone, '0')) {
                // Format local (ex: 078817235) -> +22578817235
                $phone = '+225' . substr($phone, 1);
            } else {
                $phone = '+' . $phone;
            }
        }

        return $phone;
    }

    /**
     * Enregistre la transaction en base de données
     */
    private function enregistrerTransaction(array $donnees, string $referenceInterne, array $resultat): void
    {
        // log des données de paiement
        Log::info('Transaction enregistrée', [
            'reference_interne' => $referenceInterne,
            'reference_metier' => $donnees['reference'],
            'montant_cents' => $donnees['amountCents'],
            'devise' => $donnees['currency'] ?? 'XOF',
            'methode' => $donnees['paymentMethod'],
            'statut' => $resultat['success'] ? 'initie' : 'echec',
            'reponse_jeko' => $resultat,
        ]);

        // TblPaiement::create([
        //     'reference_interne' => $referenceInterne,
        //     'reference_metier' => $donnees['reference'],
        //     'montant_cents' => $donnees['amountCents'],
        //     'devise' => $donnees['currency'] ?? 'XOF',
        //     'methode' => $donnees['paymentMethod'],
        //     'statut' => $resultat['success'] ? 'initie' : 'echec',
        //     'reponse_jeko' => $resultat,
        //     'telephone' => $donnees['payerPhone'] ?? null,
        //     'email' => $donnees['customerEmail'] ?? null,
        //     'description' => $donnees['description'] ?? null,
        //     'metadata' => $donnees['metadata'] ?? null,
        // ]);
    }
}
