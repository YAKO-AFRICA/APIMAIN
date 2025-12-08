<?php

namespace App\Http\Controllers\Api;

use App\Models\Rapport;
use App\Models\RapportOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RapportController extends Controller
{
    public function index(Request $request)
    {

        Log::info('Récupération des rapports avec les paramètres:', [
            'params' => $request->all()
        ]);
        try {
            $perPage = $request->get('per_page', 20);
            $search = $request->get('search');
            $date = $request->get('date');

            $query = Rapport::with(['user', 'operations.typeOperation'])
                ->orderBy('created_at', 'desc');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                      ->orWhere('observations', 'like', "%{$search}%");
                });
            }

            if ($date) {
                $query->where('date_rapport', $date);
            }

            $rapports = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Liste des rapports récupérée avec succès',
                'data' => $rapports
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erreur lors de la récupération des rapports:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rapports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'date_rapport' => 'required|date',
                'observations' => 'nullable|string|max:500',
                'operations' => 'required|array|min:1',
                'operations.*.type_operation_uuid' => 'required|exists:type_operations,uuid',
                'operations.*.quantite' => 'required|integer|min:1',
                'operations.*.montant_unitaire' => 'required|numeric|min:0',
                'operations.*.nature' => 'required|in:entree,sortie',
                'operations.*.produit_assurance' => 'nullable|string|max:100',
                'operations.*.prime_souhaitee' => 'nullable|numeric|min:0',
                'operations.*.code_contrat' => 'nullable|string|max:50',
                'operations.*.client_a_paye' => 'boolean',
                'operations.*.description' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Génération du code rapport
            $code = Refgenerate(Rapport::class, 'RAPPORT', 'code');

            // Création du rapport
            $rapport = Rapport::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'code' => $code,
                // 'date_rapport' => $request->date_rapport,
                'date_rapport' => now(),
                'observations' => $request->observations,
                'user_id' => $request->user_id,
            ]);

            // Création des opérations
            foreach ($request->operations as $operationData) {
                $montantTotal = $operationData['quantite'] * $operationData['montant_unitaire'];

                RapportOperation::create([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'code' => Refgenerate(RapportOperation::class, 'RO', 'code'),
                    'rapport_uuid' => $rapport->uuid,
                    'type_operation_uuid' => $operationData['type_operation_uuid'],
                    'quantite' => $operationData['quantite'],
                    'montant_unitaire' => $operationData['montant_unitaire'],
                    'montant_total' => $montantTotal,
                    'nature' => $operationData['nature'],
                    'produit_assurance' => $operationData['produit_assurance'] ?? null,
                    'prime_souhaitee' => $operationData['prime_souhaitee'] ?? null,
                    'code_contrat' => $operationData['code_contrat'] ?? null,
                    'client_a_paye' => $operationData['client_a_paye'] ?? false,
                    'description' => $operationData['description'] ?? null,
                ]);
            }

            // Calcul des totaux
            $rapport->calculateTotals();

            DB::commit();

            // Charger les relations pour la réponse
            $rapport->load(['user', 'operations.typeOperation']);

            return response()->json([
                'success' => true,
                'message' => 'Rapport créé avec succès',
                'data' => $rapport
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Erreur lors de la création du rapport:', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($uuid)
    {
        try {
            $rapport = Rapport::with(['user', 'operations.typeOperation'])
                ->where('uuid', $uuid)
                ->first();

            if (!$rapport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rapport non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Rapport récupéré avec succès',
                'data' => $rapport
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erreur lors de la récupération du rapport:', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $uuid)
    {
        DB::beginTransaction();
        try {
            $rapport = Rapport::where('uuid', $uuid)->first();

            if (!$rapport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rapport non trouvé'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'date_rapport' => 'sometimes|required|date',
                'observations' => 'nullable|string|max:500',
                'operations' => 'sometimes|array|min:1',
                'operations.*.type_operation_id' => 'required|exists:type_operations,id',
                'operations.*.quantite' => 'required|integer|min:1',
                'operations.*.montant_unitaire' => 'required|numeric|min:0',
                'operations.*.nature' => 'required|in:entree,sortie',
                'operations.*.produit_assurance' => 'nullable|string|max:100',
                'operations.*.prime_souhaitee' => 'nullable|numeric|min:0',
                'operations.*.code_contrat' => 'nullable|string|max:50',
                'operations.*.client_a_paye' => 'boolean',
                'operations.*.description' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Mise à jour du rapport
            $rapport->update([
                'date_rapport' => $request->date_rapport ?? $rapport->date_rapport,
                'observations' => $request->observations ?? $rapport->observations,
            ]);

            // Mise à jour des opérations si fournies
            if ($request->has('operations')) {
                // Supprimer les anciennes opérations
                $rapport->operations()->delete();

                // Créer les nouvelles opérations
                foreach ($request->operations as $operationData) {
                    $montantTotal = $operationData['quantite'] * $operationData['montant_unitaire'];

                    RapportOperation::create([
                        'uuid' => \Illuminate\Support\Str::uuid(),
                        'rapport_id' => $rapport->id,
                        'type_operation_id' => $operationData['type_operation_id'],
                        'quantite' => $operationData['quantite'],
                        'montant_unitaire' => $operationData['montant_unitaire'],
                        'montant_total' => $montantTotal,
                        'nature' => $operationData['nature'],
                        'produit_assurance' => $operationData['produit_assurance'] ?? null,
                        'prime_souhaitee' => $operationData['prime_souhaitee'] ?? null,
                        'code_contrat' => $operationData['code_contrat'] ?? null,
                        'client_a_paye' => $operationData['client_a_paye'] ?? false,
                        'description' => $operationData['description'] ?? null,
                    ]);
                }
            }

            // Recalculer les totaux
            $rapport->calculateTotals();

            DB::commit();

            // Recharger les relations
            $rapport->load(['user', 'operations.typeOperation']);

            return response()->json([
                'success' => true,
                'message' => 'Rapport mis à jour avec succès',
                'data' => $rapport
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Erreur lors de la mise à jour du rapport:', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($uuid)
    {
        DB::beginTransaction();
        try {
            $rapport = Rapport::where('uuid', $uuid)->first();

            if (!$rapport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rapport non trouvé'
                ], 404);
            }

            // Désactiver le rapport et ses opérations
            $rapport->update(['isActive' => false]);
            $rapport->operations()->update(['isActive' => false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rapport désactivé avec succès'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Erreur lors de la désactivation du rapport:', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la désactivation du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function restore($uuid)
    {
        DB::beginTransaction();
        try {
            $rapport = Rapport::where('uuid', $uuid)->first();

            if (!$rapport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rapport non trouvé'
                ], 404);
            }

            // Réactiver le rapport et ses opérations
            $rapport->update(['isActive' => true]);
            $rapport->operations()->update(['isActive' => true]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Rapport réactivé avec succès'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Erreur lors de la réactivation du rapport:', [
                'error' => $e->getMessage(),
                'uuid' => $uuid,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réactivation du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getByDateRange(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $rapports = Rapport::with(['user', 'operations.typeOperation'])
                ->whereBetween('date_rapport', [$request->start_date, $request->end_date])
                ->where('isActive', true)
                ->orderBy('date_rapport', 'desc')
                ->get();

            $totalEntrees = $rapports->sum('total_entrees');
            $totalSorties = $rapports->sum('total_sorties');
            $soldeGeneral = $totalEntrees - $totalSorties;

            return response()->json([
                'success' => true,
                'message' => 'Rapports récupérés avec succès',
                'data' => [
                    'rapports' => $rapports,
                    'statistiques' => [
                        'total_entrees' => $totalEntrees,
                        'total_sorties' => $totalSorties,
                        'solde_general' => $soldeGeneral,
                        'nombre_rapports' => $rapports->count()
                    ]
                ]
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erreur lors de la récupération des rapports par période:', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rapports',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}