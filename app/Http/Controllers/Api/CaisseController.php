<?php

namespace App\Http\Controllers\Api;

use App\Models\Caisse;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CaisseController extends Controller
{
    public function index()
    {
        $caisses = Caisse::where('isActive', true)->get();
        return response()->json(
            [
                'success' => true,
                'message' => 'Liste des caisses',
                'total trouvé' => $caisses->count(),
                'data' => $caisses
            ]
        );
    }
    public function store(Request $request)
    {
        try {

            DB::connection('mysql2')->beginTransaction();

            $validated = $request->validate([
                'libelle'       => 'required|string|max:255',
                'type'          => 'nullable|in:physique,virtuelle',
                'solde_alert'   => 'nullable|numeric',
                'description'   => 'nullable|string',
            ]);

            $code = Refgenerate(Caisse::class, 'CAISSE', 'code');

            // ➕ Création de la caisse
            $caisse = Caisse::create([
                'uuid'        => Str::uuid(),
                'code'        => $code,
                'libelle'     => $request->libelle,
                'type'        => $request->type ?? null,
                'solde_alert' => $request->solde_alert ?? 0,
                'description' => $request->description ?? null,
                'isActive'    => true,
                'created_by'  => $request->created_by ?? null
            ]);

            DB::connection('mysql2')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Caisse créée avec succès',
                'data' => $caisse
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la caisse',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function update(Request $request, $uuid)
    {
        try {

            DB::connection('mysql2')->beginTransaction();

            // Vérifie si la caisse existe
            $caisse = Caisse::where('uuid', $uuid)->first();

            if (!$caisse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caisse introuvable',
                ], 404);
            }

            // 🔍 Validation des données
            $validated = $request->validate([
                'libelle'       => 'sometimes|string|max:255',
                'type'          => 'sometimes|in:physique,virtuelle',
                'solde_alert'   => 'sometimes|numeric',
                'description'   => 'sometimes|string|nullable',
            ]);

            // Mise à jour des informations
            $caisse->update([
                'libelle'       => $validated['libelle']      ?? $caisse->libelle,
                'type'          => $validated['type']         ?? $caisse->type,
                'solde_alert'   => $validated['solde_alert']  ?? $caisse->solde_alert,
                'description'   => $validated['description']  ?? $caisse->description,
                'updated_by'    => $request->updated_by ?? null,
            ]);

            DB::connection('mysql2')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Caisse mise à jour avec succès',
                'data' => $caisse
            ], 200);

        } catch (\Exception $e) {

            DB::connection('mysql2')->rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la caisse',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy($uuid)
    {
        try {

            DB::connection('mysql2')->beginTransaction();

            // Vérifie si la caisse existe
            $caisse = Caisse::where('uuid', $uuid)->first();

            if (!$caisse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caisse introuvable',
                ], 404);
            }

            // Mise à jour des informations
            $caisse->update([
                'isActive'      => false,
                'deleted_by'    => $request->deleted_by ?? null,
                'deleted_at'    => now()
            ]);

            DB::connection('mysql2')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Caisse supprimée avec succès',
            ], 200);

        } catch (\Exception $e) {

            DB::connection('mysql2')->rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la caisse',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function restore($uuid)
    {
        try {

            DB::connection('mysql2')->beginTransaction();

            // Vérifie si la caisse existe
            $caisse = Caisse::where('uuid', $uuid)->first();

            if (!$caisse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caisse introuvable',
                ], 404);
            }

            // Mise à jour des informations
            $caisse->update([
                'isActive'      => true,
            ]);

            DB::connection('mysql2')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Caisse restaurée avec succès',
            ], 200);

        } catch (\Exception $e) {

            DB::connection('mysql2')->rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la restauration de la caisse',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
