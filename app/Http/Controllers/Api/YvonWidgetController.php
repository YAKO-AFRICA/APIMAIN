<?php

// ============================================================
//  app/Http/Controllers/Api/YvonWidgetController.php
//
//  Gère :
//   - Authentification publique du widget (sans compte utilisateur)
//   - Proxy des requêtes chat/voice vers YVON API
//   - Service du fichier widget.js avec la bonne URL d'API injectée
//   - Service de l'icône yvon.png
//
//  Serveur EX2 : apimain.yakoafricassur.com
// ============================================================
 
namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use App\Services\YvonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
 
// class YvonWidgetController extends Controller
// {
//     public function __construct(private YvonService $yvon) {}
 
//     // ── POST /api/widget/yvon/auth ────────────────────────────
//     // Retourne un token YVON public au widget JS
//     // Le widget l'utilise pour s'authentifier auprès de l'API
//     public function getPublicToken(Request $request): JsonResponse
//     {
//         try {
//             // Token public mis en cache 2h — partagé entre tous les visiteurs
//             $token = Cache::remember('yvon_public_widget_token', 7000, function () {
//                 return $this->yvon->getToken();
//             });
 
//             return response()->json([
//                 'access_token' => $token,
//                 'token_type'   => 'bearer',
//                 'expires_in'   => 7200,
//                 'api_url'      => config('services.yvon.public_url'),
//             ]);
//         } catch (\Exception $e) {
//             return response()->json(['error' => 'Service temporairement indisponible'], 503);
//         }
//     }
//     // ── POST /api/widget/yvon/chat ────────────────────────────
//     // Proxy chat pour le widget et les apps mobiles sans compte
//     public function chat(Request $request): JsonResponse
//     {
//         $validated = $request->validate([
//             'query'      => 'required|string|max:1000',
//             'language'   => 'sometimes|string|max:5',
//             'department' => 'sometimes|string|in:default,commercial,sinistres',
//             'session_id' => 'sometimes|nullable|string|uuid',
//         ]);
 
//         try {
//             $result = $this->yvon->chat(
//                 query:      $validated['query'],
//                 language:   $validated['language']   ?? 'fr',
//                 department: $validated['department']  ?? 'default',
//                 sessionId:  $validated['session_id']  ?? null,
//             );
//             return response()->json($result);
//         } catch (\Exception $e) {
//             return response()->json([
//                 'error'  => 'Service YVON indisponible',
//                 'detail' => $e->getMessage(),
//             ], 503);
//         }
//     }
//     // ── POST /api/widget/yvon/voice ───────────────────────────
//     public function voiceChat(Request $request): JsonResponse
//     {
//         $request->validate([
//             'audio'      => 'required|file|mimes:wav,webm,mp3|max:10240',
//             'language'   => 'sometimes|string|max:5',
//             'session_id' => 'sometimes|nullable|string|uuid',
//         ]);
 
//         try {
//             $result = $this->yvon->voiceUpload(
//                 audioPath:  $request->file('audio')->getPathname(),
//                 language:   $request->input('language', 'fr'),
//                 sessionId:  $request->input('session_id'),
//             );
//             return response()->json($result);
//         } catch (\Exception $e) {
//             return response()->json(['error' => 'Service vocal indisponible'], 503);
//         }
//     }
//     // ── DELETE /api/widget/yvon/session/{id} ──────────────────
//     public function deleteSession(string $sessionId): JsonResponse
//     {
//         try {
//             $this->yvon->deleteSession($sessionId);
//             return response()->json(['message' => 'Session effacée']);
//         } catch (\Exception $e) {
//             return response()->json(['error' => $e->getMessage()], 500);
//         }
//     }
//     // ── GET /static/widget.js ─────────────────────────────────
//     // Sert le widget.js depuis le storage Laravel
//     // Injecte dynamiquement l'URL de l'API publique
//     public function serveWidget(): Response
//     {
//         $widgetPath = storage_path('app/yvon/widget.js');
//         if (!file_exists($widgetPath)) {
//             // Télécharger depuis YVON API si pas encore en cache local
//             $this->cacheWidgetFiles();
//         }
//         if (!file_exists($widgetPath)) {
//             abort(404, 'Widget not found');
//         }
//         $content = file_get_contents($widgetPath);
//         // Injecter l'URL de l'API publique (apimain) à la place de localhost
//         // Le widget utilisera /api/widget/yvon/* comme proxy
//         $publicApiUrl = rtrim(config('app.url'), '/');
//         $content = str_replace(
//             ["'http://localhost:8000'", '"http://localhost:8000"'],
//             ["'$publicApiUrl'",         "\"$publicApiUrl\""],
//             $content
//         );
//         // Remplacer les endpoints directs par les endpoints proxy Laravel
//         $content = str_replace(
//             [
//                 '`${this.apiUrl}/chat`',
//                 '`${this.apiUrl}/voice/upload`',
//                 '`${this.apiUrl}/auth/login`',
//                 '`${this.apiUrl}/languages`',
//             ],
//             [
//                 '`${this.apiUrl}/api/widget/yvon/chat`',
//                 '`${this.apiUrl}/api/widget/yvon/voice`',
//                 '`${this.apiUrl}/api/widget/yvon/auth`',
//                 '`${this.apiUrl}/api/yvon/languages`',
//             ],
//             $content
//         );
 
//         return response($content, 200)
//             ->header('Content-Type', 'application/javascript; charset=utf-8')
//             ->header('Cache-Control', 'public, max-age=3600')
//             ->header('Access-Control-Allow-Origin', '*');
//     }
//     // ── GET /static/yvon.png ──────────────────────────────────
//     public function serveIcon(): Response
//     {
//         $iconPath = storage_path('app/yvon/yvon.png');
//         if (!file_exists($iconPath)) {
//             $this->cacheWidgetFiles();
//         }
//         if (!file_exists($iconPath)) {
//             abort(404, 'Icon not found');
//         }
//         return response(file_get_contents($iconPath), 200)
//             ->header('Content-Type', 'image/png')
//             ->header('Cache-Control', 'public, max-age=86400')
//             ->header('Access-Control-Allow-Origin', '*');
//     }
 
//     // ── Helper : télécharger les fichiers depuis YVON API ─────
//     private function cacheWidgetFiles(): void
//     {
//         $yvonBaseUrl = config('services.yvon.url');
//         $storagePath = storage_path('app/yvon');
//         if (!is_dir($storagePath)) {
//             mkdir($storagePath, 0755, true);
//         }
//         try {
//             // Télécharger widget.js
//             $widget = Http::timeout(10)->get("$yvonBaseUrl/static/widget.js");
//             if ($widget->successful()) {
//                 file_put_contents("$storagePath/widget.js", $widget->body());
//             }
 
//             // Télécharger yvon.png
//             $icon = Http::timeout(10)->get("$yvonBaseUrl/static/yvon.png");
//             if ($icon->successful()) {
//                 file_put_contents("$storagePath/yvon.png", $icon->body());
//             }
//         } catch (\Exception $e) {
//             Log::error('YVON widget cache error: ' . $e->getMessage());
//         }
//     }
// }

class YvonWidgetController extends Controller
{
    public function __construct(private YvonService $yvon) {}
 
    // ── POST /api/widget/yvon/auth ────────────────────────────
    // Retourne un token YVON public au widget JS
    // Le widget l'utilise pour s'authentifier auprès de l'API
    public function getPublicToken(Request $request): JsonResponse
    {
        try {
            // Token public mis en cache 2h — partagé entre tous les visiteurs
            $token = Cache::remember('yvon_public_widget_token', 7000, function () {
                return $this->yvon->getToken();
            });
 
            return response()->json([
                'access_token' => $token,
                'token_type'   => 'bearer',
                'expires_in'   => 7200,
                'api_url'      => config('services.yvon.public_url'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Service temporairement indisponible'], 503);
        }
    }
 
    // ── POST /api/widget/yvon/chat ────────────────────────────
    // Proxy chat pour le widget et les apps mobiles sans compte
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query'      => 'required|string|max:1000',
            'language'   => 'sometimes|string|max:5',
            'department' => 'sometimes|string|in:default,commercial,sinistres',
            'session_id' => 'sometimes|nullable|string|uuid',
        ]);
 
        try {
            $result = $this->yvon->chat(
                query:      $validated['query'],
                language:   $validated['language']   ?? 'fr',
                department: $validated['department']  ?? 'default',
                sessionId:  $validated['session_id']  ?? null,
            );
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error'  => 'Service YVON indisponible',
                'detail' => $e->getMessage(),
            ], 503);
        }
    }
 
    // ── POST /api/widget/yvon/voice ───────────────────────────
    public function voiceChat(Request $request): JsonResponse
    {
        $request->validate([
            'audio'      => 'required|file|mimes:wav,webm,mp3|max:10240',
            'language'   => 'sometimes|string|max:5',
            'session_id' => 'sometimes|nullable|string|uuid',
        ]);
 
        try {
            $result = $this->yvon->voiceUpload(
                audioPath:  $request->file('audio')->getPathname(),
                language:   $request->input('language', 'fr'),
                sessionId:  $request->input('session_id'),
            );
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Service vocal indisponible'], 503);
        }
    }
 
    // ── DELETE /api/widget/yvon/session/{id} ──────────────────
    public function deleteSession(string $sessionId): JsonResponse
    {
        try {
            $this->yvon->deleteSession($sessionId);
            return response()->json(['message' => 'Session effacée']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
 
    // ── GET /static/widget.js ─────────────────────────────────
    // Sert le widget.js depuis le storage Laravel
    // Injecte dynamiquement l'URL de l'API publique
    public function serveWidget(): Response
    {
        $widgetPath = storage_path('app/yvon/widget.js');
 
        if (!file_exists($widgetPath)) {
            // Télécharger depuis YVON API si pas encore en cache local
            $this->cacheWidgetFiles();
        }
 
        if (!file_exists($widgetPath)) {
            abort(404, 'Widget not found');
        }
 
        $content = file_get_contents($widgetPath);
 
        // Injecter l'URL de l'API publique (apimain) à la place de localhost
        // Le widget utilisera /api/widget/yvon/* comme proxy
        $publicApiUrl = rtrim(config('app.url'), '/');
        $content = str_replace(
            ["'http://localhost:8000'", '"http://localhost:8000"'],
            ["'$publicApiUrl'",         "\"$publicApiUrl\""],
            $content
        );
 
        // Remplacer les endpoints directs par les endpoints proxy Laravel
        $content = str_replace(
            [
                '`${this.apiUrl}/chat`',
                '`${this.apiUrl}/voice/upload`',
                '`${this.apiUrl}/auth/login`',
                '`${this.apiUrl}/languages`',
            ],
            [
                '`${this.apiUrl}/api/widget/yvon/chat`',
                '`${this.apiUrl}/api/widget/yvon/voice`',
                '`${this.apiUrl}/api/widget/yvon/auth`',
                '`${this.apiUrl}/api/yvon/languages`',
            ],
            $content
        );
 
        return response($content, 200)
            ->header('Content-Type', 'application/javascript; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600')
            ->header('Access-Control-Allow-Origin', '*');
    }
 
    // ── GET /static/yvon.png ──────────────────────────────────
    public function serveIcon(): Response
    {
        $iconPath = storage_path('app/yvon/yvon.png');
 
        if (!file_exists($iconPath)) {
            $this->cacheWidgetFiles();
        }
 
        if (!file_exists($iconPath)) {
            abort(404, 'Icon not found');
        }
 
        return response(file_get_contents($iconPath), 200)
            ->header('Content-Type', 'image/png')
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('Access-Control-Allow-Origin', '*');
    }
 
    // ── Helper : télécharger les fichiers depuis YVON API ─────
    private function cacheWidgetFiles(): void
    {
        $yvonBaseUrl = config('services.yvon.url');
        $storagePath = storage_path('app/yvon');
 
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
 
        try {
            // Télécharger widget.js
            $widget = Http::timeout(10)->get("$yvonBaseUrl/static/widget.js");
            if ($widget->successful()) {
                file_put_contents("$storagePath/widget.js", $widget->body());
            }
 
            // Télécharger yvon.png
            $icon = Http::timeout(10)->get("$yvonBaseUrl/static/yvon.png");
            if ($icon->successful()) {
                file_put_contents("$storagePath/yvon.png", $icon->body());
            }
        } catch (\Exception $e) {
            Log::error('YVON widget cache error: ' . $e->getMessage());
        }
    }
}
