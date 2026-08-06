<?php

namespace App\Http\Controllers\Suggestion;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CategoryController extends Controller
{

    public function index(Request $request)
    {
        $query = Category::query();

        // Filtrage par etat
        if ($request->has('etat')) {
            $query->where('etat', $request->input('etat'));
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $categories = $query->paginate($perPage);

        return response()->json(
            [
                'success' => true,
                'message' => 'Liste des catégories.',
                'data' => $categories,
                'total' => $categories->total(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total_pages' => $categories->lastPage()
            ]
        );
    }
    public function store(Request $request)
    {

        try {

            DB::connection('mysql')->beginTransaction();
            // Validation des données de la requête
            $validatedData = $request->validate([
                'libelle' => 'required|string|max:255',
                'description' => 'nullable|string',
                'etat' => 'nullable|string|in:actif,inactif',
            ]);

            $code = Refgenerate(Category::class, 'QRCODE', 'code');

            // Création de la catégorie
            $category = Category::create(
                [   
                    'uuid' => Str::uuid(),
                    'code' => $code,
                    'libelle' => $validatedData['libelle'],
                    'description' => $validatedData['description'] ?? null,
                    'etat' => $validatedData['etat'] ?? 'actif',
                ]
            );

            DB::connection('mysql')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie créée avec succès.',
                'data' => $category,
            ], 201);
        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la catégorie.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function changeEtat(string $uuid)
    {
        try {

            Log::info("Changement d'état de la catégorie avec UUID: $uuid");
            $category = Category::where('uuid', $uuid)->first();

            Log::info("Catégorie trouvée: " . json_encode($category));

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée.',
                ], 404);
            }

            // changement d'etat de la catégorie
            $category->etat = $category->etat === 'actif' ? 'inactif' : 'actif';
            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'État de la catégorie mis à jour avec succès.',
                'data' => $category,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'état de la catégorie: ' . $e->getMessage(),
            ], 500);
        }
    }

     public function destroy(string $uuid)
    {
        try {
            $category = Category::where('uuid', $uuid)->first();

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Catégorie non trouvée.',
                ], 404);
            }

            $category->delete();

            return response()->json([
                'success' => true,
                'message' => 'Catégorie supprimée avec succès.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la catégorie: ' . $e->getMessage(),
            ], 500);
        }
    }
       
}
