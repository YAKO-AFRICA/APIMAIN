<?php
// ============================================================
//  app/Services/YvonService.php
//  Service YVON — Serveur EX2 (apimain.yakoafricassur.com)
//  Proxy vers https://yvon.yakoafricassur.com
// ============================================================

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\RequestException;

// class YvonService
// {
//     private string $baseUrl;
//     private string $username;
//     private string $password;

//     public function __construct()
//     {
//         $this->baseUrl  = rtrim(config('services.yvon.url'), '/');
//         $this->username = config('services.yvon.username');
//         $this->password = config('services.yvon.password');
//     }

//     // ── Authentification avec cache 2h ──────────────────────
//     public function getToken(): string
//     {
//         return Cache::remember('yvon_jwt_token', 7200, function () {
//             $response = Http::timeout(10)
//                 ->post($this->baseUrl . '/auth/login', [
//                     'username' => $this->username,
//                     'password' => $this->password,
//                 ]);

//             if (! $response->successful()) {
//                 Log::error('YVON auth failed', ['status' => $response->status()]);
//                 throw new \RuntimeException('Authentification YVON échouée : ' . $response->status());
//             }

//             return $response->json('access_token');
//         });
//     }

//     // ── Chat texte ──────────────────────────────────────────
//     public function chat(
//         string  $query,
//         string  $language   = 'fr',
//         string  $department = 'default',
//         ?string $sessionId  = null
//     ): array {
//         return $this->withAutoRefresh(function () use ($query, $language, $department, $sessionId) {
//             return Http::timeout(30)
//                 ->withToken($this->getToken())
//                 ->post($this->baseUrl . '/chat', [
//                     'query'      => $query,
//                     'language'   => $language,
//                     'department' => $department,
//                     'session_id' => $sessionId,
//                 ])
//                 ->throw()
//                 ->json();
//         });
//     }

//     // ── Upload audio (WAV) ──────────────────────────────────
//     public function voiceUpload(
//         string  $audioPath,
//         string  $language  = 'fr',
//         ?string $sessionId = null
//     ): array {
//         return $this->withAutoRefresh(function () use ($audioPath, $language, $sessionId) {
//             return Http::timeout(60)
//                 ->withToken($this->getToken())
//                 ->attach('audio', file_get_contents($audioPath), 'voice.wav')
//                 ->post($this->baseUrl . '/voice/upload', [
//                     'language'   => $language,
//                     'session_id' => $sessionId ?? '',
//                 ])
//                 ->throw()
//                 ->json();
//         });
//     }

//     public function YvonWidget(): array { 
//         return Http::timeout(60)
//         ->withToken($this->getToken())
//         ->get($this->baseUrl . '/static/widget.js')
//         ->throw()
//         ->json(); 
//     } // widget

//     // ── Santé de l'API ──────────────────────────────────────
//     public function health(): array
//     {
//         return Http::timeout(5)
//             ->get($this->baseUrl . '/health')
//             ->json();
//     }

//     // ── Effacer une session ─────────────────────────────────
//     public function deleteSession(string $sessionId): bool
//     {
//         return $this->withAutoRefresh(function () use ($sessionId) {
//             return Http::timeout(10)
//                 ->withToken($this->getToken())
//                 ->delete($this->baseUrl . '/session/' . $sessionId)
//                 ->successful();
//         });
//     }

//     // ── Helper : renouvelle le token si 401 ─────────────────
//     private function withAutoRefresh(callable $fn, int $retry = 1): mixed
//     {
//         try {
//             return $fn();
//         } catch (RequestException $e) {
//             if ($e->response->status() === 401 && $retry > 0) {
//                 Cache::forget('yvon_jwt_token');
//                 return $this->withAutoRefresh($fn, 0);
//             }
//             Log::error('YVON API error', [
//                 'status'  => $e->response->status(),
//                 'message' => $e->getMessage(),
//             ]);
//             throw $e;
//         }
//     }
// }

// class YvonService
// {
//     private string $baseUrl;
//     private string $username;
//     private string $password;
 
//     public function __construct()
//     {
//         $this->baseUrl  = rtrim(config('services.yvon.url'), '/');
//         $this->username = config('services.yvon.username');
//         $this->password = config('services.yvon.password');
//     }
 
//     // ── Authentification avec cache 2h ──────────────────────
//     public function getToken(): string
//     {
//         return Cache::remember('yvon_jwt_token', 7000, function () {
//             $response = Http::timeout(10)
//                 ->post($this->baseUrl . '/auth/login', [
//                     'username' => $this->username,
//                     'password' => $this->password,
//                 ]);
 
//             if (! $response->successful()) {
//                 Log::error('YVON auth failed', ['status' => $response->status()]);
//                 throw new \RuntimeException('Authentification YVON échouée : ' . $response->status());
//             }
 
//             return $response->json('access_token');
//         });
//     }
 
//     // ── Chat texte ──────────────────────────────────────────
//     public function chat(
//         string  $query,
//         string  $language   = 'fr',
//         string  $department = 'default',
//         ?string $sessionId  = null
//     ): array {
//         return $this->withAutoRefresh(function () use ($query, $language, $department, $sessionId) {
//             return Http::timeout(30)
//                 ->withToken($this->getToken())
//                 ->post($this->baseUrl . '/chat', [
//                     'query'      => $query,
//                     'language'   => $language,
//                     'department' => $department,
//                     'session_id' => $sessionId,
//                 ])
//                 ->throw()
//                 ->json();
//         });
//     }
 
//     // ── Upload audio (WAV/WebM) ─────────────────────────────
//     public function voiceUpload(
//         string  $audioPath,
//         string  $language  = 'fr',
//         ?string $sessionId = null
//     ): array {
//         return $this->withAutoRefresh(function () use ($audioPath, $language, $sessionId) {
//             return Http::timeout(60)
//                 ->withToken($this->getToken())
//                 ->attach('audio', file_get_contents($audioPath), 'voice.wav')
//                 ->post($this->baseUrl . '/voice/upload', [
//                     'language'   => $language,
//                     'session_id' => $sessionId ?? '',
//                 ])
//                 ->throw()
//                 ->json();
//         });
//     }
 
//     // ── Santé ───────────────────────────────────────────────
//     public function health(): array
//     {
//         // $url = $this->baseUrl . '/health';
//         // // dd($url);
//         return Http::timeout(5)->get($this->baseUrl . '/health')->json();
//     }
 
//     // ── Langues disponibles ─────────────────────────────────
//     public function languages(): array
//     {
//         return Cache::remember('yvon_languages', 86400, function () {
//             return Http::timeout(5)->get($this->baseUrl . '/languages')->json();
//         });
//     }
 
//     // ── Effacer session ─────────────────────────────────────
//     public function deleteSession(string $sessionId): bool
//     {
//         return $this->withAutoRefresh(function () use ($sessionId) {
//             return Http::timeout(10)
//                 ->withToken($this->getToken())
//                 ->delete($this->baseUrl . '/session/' . $sessionId)
//                 ->successful();
//         });
//     }
 
//     // ── Auto-refresh token si 401 ───────────────────────────
//     private function withAutoRefresh(callable $fn, int $retry = 1): mixed
//     {
//         try {
//             return $fn();
//         } catch (RequestException $e) {
//             if ($e->response->status() === 401 && $retry > 0) {
//                 Cache::forget('yvon_jwt_token');
//                 Cache::forget('yvon_public_widget_token');
//                 return $this->withAutoRefresh($fn, 0);
//             }
//             Log::error('YVON API error', [
//                 'status'  => $e->response->status(),
//                 'message' => $e->getMessage(),
//             ]);
//             throw $e;
//         }
//     }
// }

class YvonService
{
    private string $baseUrl;
    private string $username;
    private string $password;
 
    public function __construct()
    {
        $this->baseUrl  = rtrim(config('services.yvon.url'), '/');
        $this->username = config('services.yvon.username');
        $this->password = config('services.yvon.password');
    }
 
    
    // ── Authentification avec cache 2h ──────────────────────
    public function getToken(): string
    {
        return Cache::remember('yvon_jwt_token', 7000, function () {
            $response = Http::timeout(10)
                ->post($this->baseUrl . '/auth/login', [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);
 
            if (! $response->successful()) {
                Log::error('YVON auth failed', ['status' => $response->status()]);
                throw new \RuntimeException('Authentification YVON échouée : ' . $response->status());
            }
 
            return $response->json('access_token');
        });
    }
 
    // ── Chat texte ──────────────────────────────────────────
    public function chat(
        string  $query,
        string  $language   = 'fr',
        string  $department = 'default',
        ?string $sessionId  = null
    ): array {
        return $this->withAutoRefresh(function () use ($query, $language, $department, $sessionId) {
            return Http::timeout(30)
                ->withToken($this->getToken())
                ->post($this->baseUrl . '/chat', [
                    'query'      => $query,
                    'language'   => $language,
                    'department' => $department,
                    'session_id' => $sessionId,
                ])
                ->throw()
                ->json();
        });
    }
 

    // ── Upload audio (WAV/WebM) ─────────────────────────────
    public function voiceUpload(
        string  $audioPath,
        string  $language  = 'fr',
        ?string $sessionId = null
    ): array {
        return $this->withAutoRefresh(function () use ($audioPath, $language, $sessionId) {
            return Http::timeout(60)
                ->withToken($this->getToken())
                ->attach('audio', file_get_contents($audioPath), 'voice.wav')
                ->post($this->baseUrl . '/voice/upload', [
                    'language'   => $language,
                    'session_id' => $sessionId ?? '',
                ])
                ->throw()
                ->json();
        });
    }
 
    // ── Santé ───────────────────────────────────────────────
    public function health(): array
    {
        return Http::timeout(5)->get($this->baseUrl . '/health')->json();
    }
 
    // ── Langues disponibles ─────────────────────────────────
    public function languages(): array
    {
        return Cache::remember('yvon_languages', 86400, function () {
            return Http::timeout(5)->get($this->baseUrl . '/languages')->json();
        });
    }
 
    // ── Effacer session ─────────────────────────────────────
    public function deleteSession(string $sessionId): bool
    {
        return $this->withAutoRefresh(function () use ($sessionId) {
            return Http::timeout(10)
                ->withToken($this->getToken())
                ->delete($this->baseUrl . '/session/' . $sessionId)
                ->successful();
        });
    }
 
    // ── Auto-refresh token si 401 ───────────────────────────
    private function withAutoRefresh(callable $fn, int $retry = 1): mixed
    {
        try {
            return $fn();
        } catch (RequestException $e) {
            if ($e->response->status() === 401 && $retry > 0) {
                Cache::forget('yvon_jwt_token');
                Cache::forget('yvon_public_widget_token');
                return $this->withAutoRefresh($fn, 0);
            }
            Log::error('YVON API error', [
                'status'  => $e->response->status(),
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
