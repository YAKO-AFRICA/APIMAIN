<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operateur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperateurController extends Controller
{
    // 🔹 Liste
    public function index(Request $request)
    {
        $query = Operateur::where('etat', 'actif');

        $operateurs = $query->orderBy('id', 'desc')->paginate(10);

        return response()->json($operateurs);
    }

    // 🔹 Création
    public function store(Request $request)
    {
        try {
            DB::connection('mysql2')->beginTransaction();

            $code = Refgenerate(Operateur::class, 'OP', 'code');

            $operateur = Operateur::create([
                'code' => $code,
                'libelle' => $request->libelle,
                'etat' => 'actif',
            ]);

            DB::connection('mysql2')->commit();

            return response()->json([
                'message' => 'Opérateur créé',
                'data' => $operateur
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de l\'opérateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 🔹 Détail
    public function show($uuid)
    {
        $operateur = Operateur::where('uuid', $uuid)->firstOrFail();

        return response()->json($operateur);
    }

    // 🔹 Mise à jour
    public function update(Request $request, $uuid)
    {
        try {
            DB::connection('mysql2')->beginTransaction();
            $operateur = Operateur::where('uuid', $uuid)->firstOrFail();

            $operateur->update(
                [
                    'libelle' => $request->libelle,
                ]
            );

            DB::connection('mysql2')->commit();

            return response()->json([
                'message' => 'Opérateur mis à jour',
                'data' => $operateur
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour de l\'opérateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 🔹 Suppression (soft logique recommandée)
    public function destroy($uuid)
    {
        try {
            DB::connection('mysql2')->beginTransaction();
            $operateur = Operateur::where('uuid', $uuid)->firstOrFail();

            $operateur->update(
                [
                    'etat' => 'inactif',
                ]
            );

            DB::connection('mysql2')->commit();

            return response()->json([
                'message' => 'Opérateur supprimé'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression de l\'opérateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function restore($uuid)
    {
        try {
            DB::connection('mysql2')->beginTransaction();
            $operateur = Operateur::where('uuid', $uuid)->firstOrFail();

            $operateur->update(
                [
                    'etat' => 'actif',
                ]
            );

            DB::connection('mysql2')->commit();

            return response()->json([
                'message' => 'Opérateur restauré'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la restauration de l\'opérateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}