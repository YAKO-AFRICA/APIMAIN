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
}
