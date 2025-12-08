<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Rapport;

use Illuminate\Http\Request;
use App\Models\TypeOperation;
use App\Models\RapportOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
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

    // public function store(Request $request)
    // {
    //     DB::beginTransaction();

    //     Log::info($request->all());
        
    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'date_rapport' => 'required|date',
    //             'observations' => 'nullable|string|max:500',
    //             'operations' => 'required|array|min:1',
    //             'operations.*.type_operation_uuid' => 'required',
    //             'operations.*.quantite' => 'required|integer|min:1',
    //             'operations.*.montant_unitaire' => 'required|numeric|min:0',
    //             'operations.*.nature' => 'required|in:entree,sortie',
    //             'operations.*.produit_assurance' => 'nullable|string|max:100',
    //             'operations.*.prime_souhaitee' => 'nullable|numeric|min:0',
    //             'operations.*.code_contrat' => 'nullable|string|max:50',
    //             'operations.*.client_a_paye' => 'boolean',
    //             'operations.*.description' => 'nullable|string|max:255',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Erreur de validation',
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         // Vérifier que l'utilisateur existe
    //         if (!$request->user) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Utilisateur non spécifié'
    //             ], 422);
    //         }

    //         $user_data = $request->user;
    //         $user_id = $user_data['id'];
            
    //         // Vérifier que l'utilisateur existe dans la base
    //         $user = User::where('id', $user_id)->first();
            
    //         if (!$user) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Utilisateur non trouvé'
    //             ], 422);
    //         }

    //         Log::info($user);

    //         // Vérifier que les types d'opérations existent dans l'autre base
    //         foreach ($request->operations as $operation) {
    //             $typeOperationExists = TypeOperation::on('mysql2')
    //                 ->where('uuid', $operation['type_operation_uuid'])
    //                 ->where('isActive', true)
    //                 ->exists();
                    
    //             if (!$typeOperationExists) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => "Type d'opération non trouvé ou inactif: " . $operation['type_operation_uuid']
    //                 ], 422);
    //             }
    //         }

    //         // Génération du code rapport
    //         $code = Refgenerate(Rapport::class, 'RAPPORT', 'code');

    //         // Création du rapport
    //         $rapport = Rapport::create([
    //             'uuid' => \Illuminate\Support\Str::uuid(),
    //             'code' => $code,
    //             'date_rapport' => $request->date_rapport,
    //             'observations' => $request->observations,
    //             'user_id' => $user_id,
    //         ]);

    //         // Création des opérations
    //         foreach ($request->operations as $operationData) {
    //             $montantTotal = $operationData['quantite'] * $operationData['montant_unitaire'];

    //             RapportOperation::create([
    //                 'uuid' => \Illuminate\Support\Str::uuid(),
    //                 'code' => Refgenerate(RapportOperation::class, 'RO', 'code'),
    //                 'rapport_uuid' => $rapport->uuid,
    //                 'type_operation_uuid' => $operationData['type_operation_uuid'],
    //                 'quantite' => $operationData['quantite'],
    //                 'montant_unitaire' => $operationData['montant_unitaire'],
    //                 'montant_total' => $montantTotal,
    //                 'nature' => $operationData['nature'],
    //                 'produit_assurance' => $operationData['produit_assurance'] ?? null,
    //                 'prime_souhaitee' => $operationData['prime_souhaitee'] ?? null,
    //                 'code_contrat' => $operationData['code_contrat'] ?? null,
    //                 'client_a_paye' => $operationData['client_a_paye'] ?? false,

    //                 'type_category' => $operationData['type_category'] ?? null,
    //                 'type_mouvement' => $operationData['type_mouvement'] ?? null,

    //                 'nb_agents_terrain' => $operationData['nb_agents_terrain'] ?? null,
    //                 'nb_agents_commerciaux' => $operationData['nb_agents_commerciaux'] ?? null,
    //                 'nb_souscriptions_hors_agence' => $operationData['nb_souscriptions_hors_agence'] ?? null,
    //                 'nb_souscriptions_en_agence' => $operationData['nb_souscriptions_en_agence'] ?? null,
    //                 'nb_souscriptions' => $operationData['nb_souscriptions_hors_agence'] + $operationData['nb_souscriptions_en_agence'] ?? 0,

    //                 'nb_personnes' => $operationData['nb_personnes'] ?? null,
    //                 'taux_satisfaction' => $operationData['taux_satisfaction'] ?? null,

    //                 'description' => $operationData['description'] ?? null,
    //             ]);
    //         }

            

    //         // Calcul des totaux
    //         $rapport->calculateTotals();

    //         DB::commit();

    //         // Charger les relations pour la réponse
    //         $rapport->load(['user', 'operations']);

    //         // Ajouter manuellement les types d'opération depuis l'autre base
    //         $rapportData = $rapport->toArray();
    //         $rapportData['operations'] = $rapport->operations->map(function ($operation) {
    //             $typeOperation = TypeOperation::on('mysql2')
    //                 ->where('uuid', $operation->type_operation_uuid)
    //                 ->first();
                    
    //             $operationData = $operation->toArray();
    //             $operationData['type_operation'] = $typeOperation ? $typeOperation->toArray() : null;
                
    //             return $operationData;
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Rapport créé avec succès',
    //             'data' => $rapportData
    //         ], 201);

    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         Log::error('Erreur lors de la création du rapport:', [
    //             'error' => $e->getMessage(),
    //             'data' => $request->all(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur lors de la création du rapport',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function store(Request $request)
    {
        DB::beginTransaction();

        Log::info('Données reçues pour création de rapport:', $request->all());
        
        try {
            // Validation étendue avec les nouveaux champs
            $validator = Validator::make($request->all(), [
                'date_rapport' => 'required|date',
                'observations' => 'nullable|string|max:500',
                'operations' => 'required|array|min:1',
                'operations.*.type_operation_uuid' => 'required',
                'operations.*.quantite' => 'required|integer|min:1',
                'operations.*.montant_unitaire' => 'required|numeric|min:0',
                'operations.*.nature' => 'required|in:entree,sortie',
                // Champs catégorie et mouvement (optionnels car seront remplis automatiquement)
                'operations.*.type_category' => 'nullable|string|max:50',
                'operations.*.type_mouvement' => 'nullable|in:entree,sortie,neutre',
                // Assurance
                'operations.*.produit_assurance' => 'nullable|string|max:100',
                'operations.*.prime_souhaitee' => 'nullable|numeric|min:0',
                'operations.*.code_contrat' => 'nullable|string|max:50',
                'operations.*.client_a_paye' => 'boolean',
                // Commercial
                'operations.*.nb_agents_terrain' => 'nullable|integer|min:0',
                'operations.*.nb_agents_commerciaux' => 'nullable|integer|min:0',
                'operations.*.nb_souscriptions_hors_agence' => 'nullable|integer|min:0',
                'operations.*.nb_souscriptions_en_agence' => 'nullable|integer|min:0',
                // Trafic
                'operations.*.nb_personnes' => 'nullable|integer|min:0',
                'operations.*.taux_satisfaction' => 'nullable|integer|min:0|max:5',
                // Description
                'operations.*.description' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Vérifier que l'utilisateur existe
            if (!$request->user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non spécifié'
                ], 422);
            }

            $user_data = $request->user;
            $user_id = $user_data['id'];
            
            // Vérifier que l'utilisateur existe dans la base
            $user = User::where('id', $user_id)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 422);
            }

            Log::info('Utilisateur trouvé:', ['user_id' => $user_id, 'user_name' => $user->name]);

            // Vérifier que les types d'opérations existent et récupérer leurs données
            $validatedOperations = [];
            
            foreach ($request->operations as $index => $operation) {
                $typeOperation = TypeOperation::on('mysql2')
                    ->where('uuid', $operation['type_operation_uuid'])
                    ->where('isActive', true)
                    ->first();
                    
                if (!$typeOperation) {
                    return response()->json([
                        'success' => false,
                        'message' => "Type d'opération non trouvé ou inactif: " . $operation['type_operation_uuid']
                    ], 422);
                }
                
                Log::info("Type d'opération trouvé:", [
                    'uuid' => $typeOperation->uuid,
                    'libelle' => $typeOperation->libelle,
                    'category' => $typeOperation->category,
                    'mouvement' => $typeOperation->mouvement
                ]);
                
                // Préparer les données de l'opération avec catégorie et mouvement depuis la base
                $validatedOperation = [
                    'type_operation_uuid' => $operation['type_operation_uuid'],
                    'quantite' => $operation['quantite'],
                    'montant_unitaire' => $operation['montant_unitaire'],
                    'montant_total' => $operation['quantite'] * $operation['montant_unitaire'],
                    'nature' => $operation['nature'],
                    // Récupérer catégorie et mouvement depuis le type d'opération
                    'type_category' => $typeOperation->category,
                    'type_mouvement' => $typeOperation->mouvement,
                    // Assurance
                    'produit_assurance' => $operation['produit_assurance'] ?? null,
                    'prime_souhaitee' => $operation['prime_souhaitee'] ?? null,
                    'code_contrat' => $operation['code_contrat'] ?? null,
                    'client_a_paye' => $operation['client_a_paye'] ?? false,
                    // Commercial
                    'nb_agents_terrain' => $operation['nb_agents_terrain'] ?? 0,
                    'nb_agents_commerciaux' => $operation['nb_agents_commerciaux'] ?? 0,
                    'nb_souscriptions_hors_agence' => $operation['nb_souscriptions_hors_agence'] ?? 0,
                    'nb_souscriptions_en_agence' => $operation['nb_souscriptions_en_agence'] ?? 0,
                    'nb_souscriptions' => ($operation['nb_souscriptions_hors_agence'] ?? 0) + ($operation['nb_souscriptions_en_agence'] ?? 0),
                    // Trafic
                    'nb_personnes' => $operation['nb_personnes'] ?? 0,
                    'taux_satisfaction' => $operation['taux_satisfaction'] ?? 0,
                    // Description
                    'description' => $operation['description'] ?? null,
                ];
                
                $validatedOperations[] = $validatedOperation;
                
                Log::info("Opération validée $index:", $validatedOperation);
            }

            // Génération du code rapport
            $code = Refgenerate(Rapport::class, 'RAPPORT', 'code');

            Log::info('Création du rapport avec code:', ['code' => $code]);

            // Création du rapport
            $rapport = Rapport::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'code' => $code,
                'date_rapport' => $request->date_rapport,
                'observations' => $request->observations ?? '',
                'user_id' => $user_id,
            ]);

            Log::info('Rapport créé:', ['rapport_uuid' => $rapport->uuid, 'code' => $rapport->code]);

            // Création des opérations
            foreach ($validatedOperations as $operationData) {
                $rapportOperation = RapportOperation::create([
                    'uuid' => \Illuminate\Support\Str::uuid(),
                    'code' => Refgenerate(RapportOperation::class, 'RO', 'code'),
                    'rapport_uuid' => $rapport->uuid,
                    'type_operation_uuid' => $operationData['type_operation_uuid'],
                    'quantite' => $operationData['quantite'],
                    'montant_unitaire' => $operationData['montant_unitaire'],
                    'montant_total' => $operationData['montant_total'],
                    'nature' => $operationData['nature'],
                    'type_category' => $operationData['type_category'],
                    'type_mouvement' => $operationData['type_mouvement'],
                    // Assurance
                    'produit_assurance' => $operationData['produit_assurance'],
                    'prime_souhaitee' => $operationData['prime_souhaitee'],
                    'code_contrat' => $operationData['code_contrat'],
                    'client_a_paye' => $operationData['client_a_paye'],
                    // Commercial
                    'nb_agents_terrain' => $operationData['nb_agents_terrain'],
                    'nb_agents_commerciaux' => $operationData['nb_agents_commerciaux'],
                    'nb_souscriptions_hors_agence' => $operationData['nb_souscriptions_hors_agence'],
                    'nb_souscriptions_en_agence' => $operationData['nb_souscriptions_en_agence'],
                    'nb_souscriptions' => $operationData['nb_souscriptions'],
                    // Trafic
                    'nb_personnes' => $operationData['nb_personnes'],
                    'taux_satisfaction' => $operationData['taux_satisfaction'],
                    // Description
                    'description' => $operationData['description'],
                ]);
                
                Log::info('Opération créée:', [
                    'operation_uuid' => $rapportOperation->uuid,
                    'type_category' => $rapportOperation->type_category,
                    'type_mouvement' => $rapportOperation->type_mouvement
                ]);
            }

            // Calcul des totaux
            $rapport->calculateTotals();
            
            Log::info('Totaux calculés:', [
                'total_entrees' => $rapport->total_entrees,
                'total_sorties' => $rapport->total_sorties,
                'solde' => $rapport->solde
            ]);

            DB::commit();

            // Charger les relations pour la réponse
            $rapport->load(['user', 'operations']);

            // Ajouter manuellement les types d'opération depuis l'autre base
            $rapportData = $rapport->toArray();
            $rapportData['operations'] = $rapport->operations->map(function ($operation) {
                $typeOperation = TypeOperation::on('mysql2')
                    ->where('uuid', $operation->type_operation_uuid)
                    ->first();
                    
                $operationData = $operation->toArray();
                $operationData['type_operation'] = $typeOperation ? $typeOperation->toArray() : null;
                
                return $operationData;
            });

            Log::info('Rapport créé avec succès:', ['rapport_uuid' => $rapport->uuid]);

            return response()->json([
                'success' => true,
                'message' => 'Rapport créé avec succès',
                'data' => $rapportData
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Erreur lors de la création du rapport:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du rapport',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function show($uuid)
    {
        
        try {
            $rapport = Rapport::with(['user.membre', 'operations.typeOperation'])
                ->where('uuid', $uuid)
                ->first();

                Log::info('details du rapport:', [
                    'rapport' => $rapport
                ]);

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

    // public function update(Request $request, $uuid)
    // {
    //     DB::beginTransaction();
    //     try {
    //         $rapport = Rapport::where('uuid', $uuid)->first();

    //         if (!$rapport) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Rapport non trouvé'
    //             ], 404);
    //         }

    //         $validator = Validator::make($request->all(), [
    //             'date_rapport' => 'sometimes|required|date',
    //             'observations' => 'nullable|string|max:500',
    //             'operations' => 'sometimes|array|min:1',
    //             'operations.*.type_operation_uuid' => 'required|exists:type_operations,uuid',
    //             'operations.*.quantite' => 'required|integer|min:1',
    //             'operations.*.montant_unitaire' => 'required|numeric|min:0',
    //             'operations.*.nature' => 'required|in:entree,sortie',
    //             'operations.*.produit_assurance' => 'nullable|string|max:100',
    //             'operations.*.prime_souhaitee' => 'nullable|numeric|min:0',
    //             'operations.*.code_contrat' => 'nullable|string|max:50',
    //             'operations.*.client_a_paye' => 'boolean',
    //             'operations.*.description' => 'nullable|string|max:255',
    //         ]);

    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Erreur de validation',
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         }

    //         // Mise à jour du rapport
    //         $rapport->update([
    //             'date_rapport' => $request->date_rapport ?? $rapport->date_rapport,
    //             'observations' => $request->observations ?? $rapport->observations,
    //         ]);

    //         // Mise à jour des opérations si fournies
    //         if ($request->has('operations')) {
    //             // Supprimer les anciennes opérations
    //             $rapport->operations()->delete();

    //             // Créer les nouvelles opérations
    //             foreach ($request->operations as $operationData) {
    //                 $montantTotal = $operationData['quantite'] * $operationData['montant_unitaire'];

    //                 RapportOperation::create([
    //                     'uuid' => \Illuminate\Support\Str::uuid(),
    //                     'rapport_uuid' => $rapport->iuuid,
    //                     'type_operation_uuid' => $operationData['type_operation_uuid'],
    //                     'quantite' => $operationData['quantite'],
    //                     'montant_unitaire' => $operationData['montant_unitaire'],
    //                     'montant_total' => $montantTotal,
    //                     'nature' => $operationData['nature'],
    //                     'produit_assurance' => $operationData['produit_assurance'] ?? null,
    //                     'prime_souhaitee' => $operationData['prime_souhaitee'] ?? null,
    //                     'code_contrat' => $operationData['code_contrat'] ?? null,
    //                     'client_a_paye' => $operationData['client_a_paye'] ?? false,
    //                     'description' => $operationData['description'] ?? null,
    //                 ]);
    //             }
    //         }

    //         // Recalculer les totaux
    //         $rapport->calculateTotals();

    //         DB::commit();

    //         // Recharger les relations
    //         $rapport->load(['user', 'operations.typeOperation']);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Rapport mis à jour avec succès',
    //             'data' => $rapport
    //         ], 200);

    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         Log::error('Erreur lors de la mise à jour du rapport:', [
    //             'error' => $e->getMessage(),
    //             'uuid' => $uuid,
    //             'data' => $request->all(),
    //             'trace' => $e->getTraceAsString()
    //         ]);

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur lors de la mise à jour du rapport',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function update(Request $request, $uuid)
{
    DB::beginTransaction();

    Log::info('Données reçues pour modification de rapport:', ['uuid' => $uuid, 'data' => $request->all()]);
    
    try {
        // Validation étendue avec les nouveaux champs
        $validator = Validator::make($request->all(), [
            'date_rapport' => 'required|date',
            'observations' => 'nullable|string|max:500',
            'operations' => 'required|array|min:1',
            'operations.*.type_operation_uuid' => 'required',
            'operations.*.quantite' => 'required|integer|min:1',
            'operations.*.montant_unitaire' => 'required|numeric|min:0',
            'operations.*.nature' => 'required|in:entree,sortie',
            // Champs catégorie et mouvement (optionnels car seront remplis automatiquement)
            'operations.*.type_category' => 'nullable|string|max:50',
            'operations.*.type_mouvement' => 'nullable|in:entree,sortie,neutre',
            // Assurance
            'operations.*.produit_assurance' => 'nullable|string|max:100',
            'operations.*.prime_souhaitee' => 'nullable|numeric|min:0',
            'operations.*.code_contrat' => 'nullable|string|max:50',
            'operations.*.client_a_paye' => 'boolean',
            // Commercial
            'operations.*.nb_agents_terrain' => 'nullable|integer|min:0',
            'operations.*.nb_agents_commerciaux' => 'nullable|integer|min:0',
            'operations.*.nb_souscriptions_hors_agence' => 'nullable|integer|min:0',
            'operations.*.nb_souscriptions_en_agence' => 'nullable|integer|min:0',
            // Trafic
            'operations.*.nb_personnes' => 'nullable|integer|min:0',
            'operations.*.taux_satisfaction' => 'nullable|integer|min:0|max:5',
            // Description
            'operations.*.description' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier que l'utilisateur existe
        if (!$request->user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non spécifié'
            ], 422);
        }

        $user_data = $request->user;
        $user_id = $user_data['id'];
        
        // Vérifier que l'utilisateur existe dans la base
        $user = User::where('id', $user_id)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé'
            ], 422);
        }

        // Vérifier que le rapport existe
        $rapport = Rapport::where('uuid', $uuid)->first();
        
        if (!$rapport) {
            return response()->json([
                'success' => false,
                'message' => 'Rapport non trouvé'
            ], 404);
        }

        // Vérifier que les types d'opérations existent et récupérer leurs données
        $validatedOperations = [];
        
        foreach ($request->operations as $index => $operation) {
            $typeOperation = TypeOperation::on('mysql2')
                ->where('uuid', $operation['type_operation_uuid'])
                ->where('isActive', true)
                ->first();
                
            if (!$typeOperation) {
                return response()->json([
                    'success' => false,
                    'message' => "Type d'opération non trouvé ou inactif: " . $operation['type_operation_uuid']
                ], 422);
            }
            
            // Préparer les données de l'opération avec catégorie et mouvement depuis la base
            $validatedOperation = [
                'type_operation_uuid' => $operation['type_operation_uuid'],
                'quantite' => $operation['quantite'],
                'montant_unitaire' => $operation['montant_unitaire'],
                'montant_total' => $operation['quantite'] * $operation['montant_unitaire'],
                'nature' => $operation['nature'],
                // Récupérer catégorie et mouvement depuis le type d'opération
                'type_category' => $typeOperation->category,
                'type_mouvement' => $typeOperation->mouvement,
                // Assurance
                'produit_assurance' => $operation['produit_assurance'] ?? null,
                'prime_souhaitee' => $operation['prime_souhaitee'] ?? null,
                'code_contrat' => $operation['code_contrat'] ?? null,
                'client_a_paye' => $operation['client_a_paye'] ?? false,
                // Commercial
                'nb_agents_terrain' => $operation['nb_agents_terrain'] ?? 0,
                'nb_agents_commerciaux' => $operation['nb_agents_commerciaux'] ?? 0,
                'nb_souscriptions_hors_agence' => $operation['nb_souscriptions_hors_agence'] ?? 0,
                'nb_souscriptions_en_agence' => $operation['nb_souscriptions_en_agence'] ?? 0,
                'nb_souscriptions' => ($operation['nb_souscriptions_hors_agence'] ?? 0) + ($operation['nb_souscriptions_en_agence'] ?? 0),
                // Trafic
                'nb_personnes' => $operation['nb_personnes'] ?? 0,
                'taux_satisfaction' => $operation['taux_satisfaction'] ?? 0,
                // Description
                'description' => $operation['description'] ?? null,
            ];
            
            $validatedOperations[] = $validatedOperation;
        }

        // Mise à jour du rapport
        $rapport->update([
            'date_rapport' => $request->date_rapport,
            'observations' => $request->observations ?? '',
        ]);

        // Supprimer les anciennes opérations
        $rapport->operations()->delete();

        // Créer les nouvelles opérations
        foreach ($validatedOperations as $operationData) {
            RapportOperation::create([
                'uuid' => \Illuminate\Support\Str::uuid(),
                'code' => Refgenerate(RapportOperation::class, 'RO', 'code'),
                'rapport_uuid' => $rapport->uuid,
                'type_operation_uuid' => $operationData['type_operation_uuid'],
                'quantite' => $operationData['quantite'],
                'montant_unitaire' => $operationData['montant_unitaire'],
                'montant_total' => $operationData['montant_total'],
                'nature' => $operationData['nature'],
                'type_category' => $operationData['type_category'],
                'type_mouvement' => $operationData['type_mouvement'],
                // Assurance
                'produit_assurance' => $operationData['produit_assurance'],
                'prime_souhaitee' => $operationData['prime_souhaitee'],
                'code_contrat' => $operationData['code_contrat'],
                'client_a_paye' => $operationData['client_a_paye'],
                // Commercial
                'nb_agents_terrain' => $operationData['nb_agents_terrain'],
                'nb_agents_commerciaux' => $operationData['nb_agents_commerciaux'],
                'nb_souscriptions_hors_agence' => $operationData['nb_souscriptions_hors_agence'],
                'nb_souscriptions_en_agence' => $operationData['nb_souscriptions_en_agence'],
                'nb_souscriptions' => $operationData['nb_souscriptions'],
                // Trafic
                'nb_personnes' => $operationData['nb_personnes'],
                'taux_satisfaction' => $operationData['taux_satisfaction'],
                // Description
                'description' => $operationData['description'],
            ]);
        }

        // Recalculer les totaux
        $rapport->calculateTotals();

        DB::commit();

        // Charger les relations pour la réponse
        $rapport->load(['user', 'operations']);

        // Ajouter manuellement les types d'opération depuis l'autre base
        $rapportData = $rapport->toArray();
        $rapportData['operations'] = $rapport->operations->map(function ($operation) {
            $typeOperation = TypeOperation::on('mysql2')
                ->where('uuid', $operation->type_operation_uuid)
                ->first();
                
            $operationData = $operation->toArray();
            $operationData['type_operation'] = $typeOperation ? $typeOperation->toArray() : null;
            
            return $operationData;
        });

        return response()->json([
            'success' => true,
            'message' => 'Rapport mis à jour avec succès',
            'data' => $rapportData
        ], 200);

    } catch (\Throwable $e) {
        DB::rollBack();

        Log::error('Erreur lors de la mise à jour du rapport:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'uuid' => $uuid,
            'data' => $request->all()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la mise à jour du rapport',
            'error' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : null
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

    // Méthode pour la vue superviseur avec filtres
    public function indexSuperviseur(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 50);
            
            $query = Rapport::with(['user', 'operations'])
                ->orderBy('created_at', 'desc');

            // Filtre par utilisateur
            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }

            // Filtre par date
            if ($request->start_date) {
                $query->where('date_rapport', '>=', $request->start_date);
            }
            
            if ($request->end_date) {
                $query->where('date_rapport', '<=', $request->end_date);
            }

            // Filtre par code
            if ($request->search) {
                $query->where('code', 'like', "%{$request->search}%");
            }

            $rapports = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Liste des rapports récupérée avec succès',
                'data' => $rapports
            ], 200);

        } catch (\Throwable $e) {
            Log::error('Erreur supervision rapports:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des rapports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

// Méthode pour la synthèse périodique
    public function getSynthesePeriodique(Request $request)
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

            $rapports = Rapport::with(['user', 'operations'])
                ->whereBetween('date_rapport', [$request->start_date, $request->end_date])
                ->where('isActive', true)
                ->orderBy('date_rapport', 'desc')
                ->get();

            // Calcul des statistiques globales
            $totalEntrees = $rapports->sum('total_entrees');
            $totalSorties = $rapports->sum('total_sorties');
            $soldeGeneral = $totalEntrees - $totalSorties;

            return response()->json([
                'success' => true,
                'message' => 'Synthèse récupérée avec succès',
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
            Log::error('Erreur synthèse rapports:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul de la synthèse',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // synthese du rapport superviseur
   public function rapportSynthese(Request $request)
{
    try {

        $dateRapport = $request->date_rapport ?? now()->format('Y-m-d');
        
        // Valider que la date est au bon format
        if (!strtotime($dateRapport)) {
            return response()->json([
                'success' => false,
                'message' => 'Format de date invalide'
            ], 400);
        }

        // Charger les rapports + opérations pour la date spécifiée
        $rapports = Rapport::with(['user', 'operations'])
            ->where('date_rapport', $dateRapport)
            ->where('isActive', true)
            ->orderBy('created_at', 'desc')
            ->get();

        // Vérifier s'il y a des rapports
        if ($rapports->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Aucun rapport trouvé pour cette date',
                'data' => [
                    'rapports' => [],
                    'nb_rapports' => 0,
                    'global' => [
                        'nb_operations' => 0,
                        'total_montant' => 0,
                        'total_quantite' => 0,
                        'par_categorie' => []
                    ],
                    'agences' => []
                ]
            ]);
        }

        // Toutes les opérations confondues
        $allOperations = $rapports->pluck('operations')->flatten();


        /**
         * ▪▪▪▪ GLOBAL ▪▪▪▪
         */
        $global = [
            'nb_operations'     => $allOperations->count(),
            'total_montant'     => $allOperations->sum('montant_total'),
            'total_quantite'    => $allOperations->sum('quantite'),
            'par_categorie'     => $allOperations->groupBy('type_category')->map(function ($group, $key) {
                return [
                    'categorie'      => $key ?? 'non_defini',
                    'nb_operations'  => $group->count(),
                    'total_montant'  => $group->sum('montant_total'),
                    'total_quantite' => $group->sum('quantite'),
                    'operations'     => $group->values(),
                ];
            })->values()
        ];


        /**
         * ▪▪▪▪ PAR AGENCE ▪▪▪▪
         */
        $agences = $rapports
            ->groupBy(fn($r) => $r->user->membre->agence ?? 'AGENCE_NON_DEFINIE')
            ->map(function ($rapportsAgence, $codeAgence) {

                $ops = $rapportsAgence->pluck('operations')->flatten();

                return [
                    'agence_code'     => $codeAgence,
                    'agence_nom'      => $rapportsAgence->first()->user->membre->nomagence ?? null,
                    'nb_operations'   => $ops->count(),
                    'total_montant'   => $ops->sum('montant_total'),
                    'total_quantite'  => $ops->sum('quantite'),

                    // regroupement par type_category
                    'par_categorie' => $ops->groupBy('type_category')->map(function ($group, $key) {
                        return [
                            'categorie'      => $key ?? 'non_defini',
                            'nb_operations'  => $group->count(),
                            'total_montant'  => $group->sum('montant_total'),
                            'total_quantite' => $group->sum('quantite'),
                            'operations'     => $group->values(),
                        ];
                    })->values()
                ];
            })
            ->values();


        return response()->json([
            'success' => true,
            'message' => 'Synthèse complète récupérée avec succès',
            'data' => [
                'rapports'        => $rapports,
                'nb_rapports'     => $rapports->count(),
                'global'          => $global,
                'agences'         => $agences,
            ]
        ], 200);


    } catch (\Throwable $e) {

        Log::error("Erreur synthèse rapport : " . $e->getMessage());

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors de la récupération'
        ], 500);
    }
}



}
