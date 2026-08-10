<?php

namespace App\Http\Controllers\Suggestion;

use App\Http\Controllers\Controller;
use App\Models\ZoneByUser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ParamController extends Controller
{
    public function paramStoreZoneByUser(Request $request)
    {

        // 3. Créer un code de zone
        $code = Refgenerate(ZoneByUser::class, 'ZONE', 'code');

        // 4. Créer la nouvelle zone
        $zoneByUser = ZoneByUser::create([
            'uuid' => Str::uuid(),
            'code' => $code,
            'libelle' => $request->libelle,
            'responsable_uuid' => $request->responsable_uuid,
            'agence_codes' => $request->agence_codes,
        ]);


        return response()->json([
            'success' => true,
            'message' => 'Zone creee avec success.',
            'data' => [
                'zone' => $zoneByUser,
                'user' => $zoneByUser->user,
                'agences' => $zoneByUser->agence_codes,
            ],
        ], 201);
    }

    public function getZoneByUser(Request $request)
    {
        $query = ZoneByUser::query();

        // Filtrer par code de zone
        if ($request->has('code')) {
            $query->where('code', $request->code);
        }

        // Filtrer par responsable_uuid
        if ($request->has('responsable_uuid')) {
            $query->where('responsable_uuid', $request->responsable_uuid);
        }

        // Filtrer par agence_codes
        if ($request->has('agence_code')) {
            $query->whereJsonContains('agence_codes', $request->agence_code);
        }

        $zones = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Zones récupérées avec succès.',
            'data' => $zones,
        ], 200);
    }

    public function showZoneByUser($uuid)
    {
        $zone = ZoneByUser::where('uuid', $uuid)->first();

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Zone non trouvée.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Zone récupérée avec succès.',
            'data' => $zone,
        ], 200);
    }
}
