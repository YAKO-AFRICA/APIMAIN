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

        $respZoneExite = ZoneByUser::where('responsable_uuid', $request->responsable_uuid)->first();
        if($respZoneExite){
            return response()->json([
                'success' => false,
                'message' => 'Le superviseur a deja une zone',
                'data' => $respZoneExite
            ], 400);
        }

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

    public function updateZone(Request $request, $uuid)
    {
        $zone = ZoneByUser::where('uuid', $uuid)->first();

        if (!$zone) {
            return response()->json([
                'success' => false,
                'message' => 'Zone non trouvée.',
            ], 404);
        }

        $type = $request->input('type');

        $agencesSelectionnees = $request->input('agence_codes', []);

        // S'assurer que agence_codes est toujours un tableau
        if (is_string($agencesSelectionnees)) {
            $decoded = json_decode($agencesSelectionnees, true);

            $agencesSelectionnees = is_array($decoded)
                ? $decoded
                : [$agencesSelectionnees];
        }

        // Récupérer les anciennes agences
        $agences = $zone->agence_codes ?? [];

        // Si agence_codes est une chaîne
        if (is_string($agences)) {

            $decoded = json_decode($agences, true);

            if (is_array($decoded)) {
                $agences = $decoded;
            } else {
                $agences = [$agences];
            }
        }

        if ($type === 'add') {

            // Ajouter les nouvelles agences
            $agences = array_merge(
                $agences,
                $agencesSelectionnees
            );

            // Supprimer les doublons
            $agences = array_values(
                array_unique($agences)
            );

        } elseif ($type === 'delete') {

            // Retirer les agences sélectionnées
            $agences = array_values(
                array_diff(
                    $agences,
                    $agencesSelectionnees
                )
            );

        } else {

            return response()->json([
                'success' => false,
                'message' => 'Le type doit être "add" ou "delete".',
            ], 400);
        }

        // Mise à jour
        $zone->update([
            'libelle' => $request->libelle ?? $zone->libelle,
            'responsable_uuid' => $request->responsable_uuid ?? $zone->responsable_uuid,
            'agence_codes' => $agences,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Zone mise à jour avec succès.',
            'data' => [
                'zone' => $zone,
                'user' => $zone->user,
                'agences' => $agences,
            ],
        ], 200);
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
