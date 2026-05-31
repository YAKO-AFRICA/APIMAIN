<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\YvonService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

// class YvonController extends Controller
// {
//     public function __construct(private YvonService $yvon) {}

//     // POST /api/yvon/chat
//     public function chat(Request $request): JsonResponse
//     {
//         $validated = $request->validate([
//             'query'      => 'required|string|max:1000',
//             'language'   => 'sometimes|string|size:2',
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
//             return response()->json(['error' => 'Service YVON indisponible', 'detail' => $e->getMessage()], 503);
//         }
//     }

//     // POST /api/yvon/voice
//     public function voiceChat(Request $request): JsonResponse
//     {
//         $request->validate([
//             'audio'      => 'required|file|mimes:wav,webm,mp3|max:10240',
//             'language'   => 'sometimes|string|size:2',
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
//             return response()->json(['error' => 'Service vocal YVON indisponible'], 503);
//         }
//     }
    
//     // GET /api/yvon/static/widget.js
//     public function YvonWidget(): JsonResponse
//     {
//         try {
//             return response()->json($this->yvon->YvonWidget());
//         } catch (\Exception $e) {
//             return response()->json(['error' => 'Service YVON indisponible'], 503);
//         }
//     }

//     // GET /api/yvon/health
//     public function health(): JsonResponse
//     {
//         try {
//             return response()->json($this->yvon->health());
//         } catch (\Exception $e) {
//             return response()->json(['status' => 'unreachable', 'error' => $e->getMessage()], 503);
//         }
//     }

//     // DELETE /api/yvon/session/{id}
//     public function deleteSession(string $sessionId): JsonResponse
//     {
//         try {
//             $this->yvon->deleteSession($sessionId);
//             return response()->json(['message' => 'Session effacée']);
//         } catch (\Exception $e) {
//             return response()->json(['error' => $e->getMessage()], 500);
//         }
//     }
// }

class YvonController extends Controller
{
    public function __construct(private YvonService $yvon) {}
 
    // POST /api/yvon/chat — utilisateurs Sanctum connectés
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
 
    // POST /api/yvon/voice
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
            return response()->json(['error' => 'Service vocal YVON indisponible'], 503);
        }
    }
 
    // GET /api/yvon/health — public
    public function health(): JsonResponse
    {
        try {
            return response()->json($this->yvon->health());
        } catch (\Exception $e) {
            return response()->json(['status' => 'unreachable', 'error' => $e->getMessage()], 503);
        }
    }
 
    // GET /api/yvon/languages — public
    public function languages(): JsonResponse
    {
        try {
            return response()->json($this->yvon->languages());
        } catch (\Exception $e) {
            // Retourner les langues en fallback si YVON indisponible
            return response()->json([
                'fr'  => ['name' => 'Français'],
                'en'  => ['name' => 'English'],
                'bci' => ['name' => 'Baoulé'],
                'dyu' => ['name' => 'Dioula'],
                'ar'  => ['name' => 'العربية'],
                'zh'  => ['name' => '中文'],
            ]);
        }
    }
 
    // DELETE /api/yvon/session/{id}
    public function deleteSession(string $sessionId): JsonResponse
    {
        try {
            $this->yvon->deleteSession($sessionId);
            return response()->json(['message' => 'Session effacée']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
