<?php

namespace App\Http\Controllers\Bni;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class BniController extends Controller
{
    public function getAdherent(Request $request)
    {
      
        $request->validate([
            'numerocompte' => 'required|string',
            'rib' => 'required|string',
        ]);

        $numerocompte = $request->numerocompte;
        $rib = $request->rib;

        try {

            $url = "https://192.168.240.105/bff-refonte/v1/api/LoyaleVie";

            $response = Http::withOptions([
                'verify' => false, // équivalent verify_peer = false
            ])->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->post($url, [
                'numerocompte' => $numerocompte,
                'rib' => $rib,
            ]);

            if ($response->failed()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Erreur lors de l’appel API BNI',
                ], 500);
            }

            return response()->json($response->json(), 200);

        } catch (\Exception $e) {

            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
