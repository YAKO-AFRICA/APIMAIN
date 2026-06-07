<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caisse;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    // Liste des transactions
    public function index(Request $request)
    {
        $query = Transaction::with(['operator', 'caisse']);

        // Filtres
        if ($request->caisse_uuid) {
            $query->where('caisse_uuid', $request->caisse_uuid);
        }

        if ($request->operator_uuid) {
            $query->where('operator_uuid', $request->operator_uuid);
        }

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->statut) {
            $query->where('statut', $request->statut);
        }

        if ($request->date_debut) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }

        if ($request->date_fin) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('reference', 'like', "%{$request->search}%")
                  ->orWhere('numero_telephone', 'like', "%{$request->search}%")
                  ->orWhere('beneficiaire_nom', 'like', "%{$request->search}%");
            });
        }

        $transactions = $query->orderBy('created_at', 'desc')
                              ->paginate($request->per_page ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Liste des transactions',
            'data' => $transactions
        ]);
    }

    // Dépôt Mobile Money

    public function depotMobileMoney(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caisse_uuid' => 'required|exists:caisses,uuid',
            'operator_uuid' => 'required|exists:operators,uuid',
            'montant' => 'required|numeric|min:100',
            'numero_telephone' => 'required|string',
            'client_nom' => 'nullable|string', // Nom du client qui dépose
            'reference_transaction' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'errors' => $validator->errors()
        //     ], 422);
        // }

        try {
            DB::connection('mysql2')->beginTransaction();

            // Vérifier si la caisse est active
            $caisse = Caisse::where('uuid', $request->caisse_uuid)->first();
            if (!$caisse || !$caisse->isActive) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caisse inactive ou inexistante'
                ], 400);
            }

            // Calcul des frais (1% du montant)
            $frais = $request->montant * 0.01;
            $montant_total = $request->montant + $frais;

            // Création de la transaction
            $transaction = Transaction::create([
                'type' => 'DEPOT',
                'sens' => 'ENTREE',
                'montant' => $request->montant,
                'frais' => $frais,
                'montant_total' => $montant_total,
                'caisse_uuid' => $request->caisse_uuid,
                'operator_uuid' => $request->operator_uuid,
                'user_uuid' => $request->user_uuid,
                'numero_telephone' => $request->numero_telephone,
                'beneficiaire_nom' => $request->client_nom, // Stocker le nom du client
                'reference_transaction' => $request->reference_transaction,
                'notes' => $request->notes,
                'statut' => 'VALIDEE',
                'validated_by' => $request->user_uuid,
                'validated_at' => now()
            ]);

            // Mise à jour du solde de la caisse
            $transaction->updateCaisseSolde();

            DB::connection('mysql2')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Dépôt effectué avec succès',
                'data' => [
                    'transaction' => $transaction->load('operator'),
                    'solde_actuel' => $caisse->fresh()->solde_theorique
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du dépôt',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Retrait Mobile Money

    public function retraitMobileMoney(Request $request)
    {
        Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'caisse_uuid' => 'required|exists:caisses,uuid',
            'operator_uuid' => 'required|exists:operators,uuid',
            'montant' => 'required|numeric|min:100',
            'numero_telephone' => 'required|string',
            'client_nom' => 'required|string|min:3', // Nouveau champ
            'client_prenom' => 'nullable|string', // Nouveau champ
            'client_cni' => 'nullable|string', // Pièce d'identité
            'reference_transaction' => 'nullable|string'
        ]);

        log::info("validation");




        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'errors' => $validator->errors(),
        //         Log::error("$validator->errors()")

        //     ], 422);
        // }

        Log::info('after validation');

        try {
            DB::connection('mysql2')->beginTransaction();

            Log::info("debut try");

            $caisse = Caisse::where('uuid', $request->caisse_uuid)->first();

            // $caisse = DB::connection('mysql2')->table('caisses')->where('uuid', $request->caisse_uuid)->first();



            // Log::info(json_encode($caisse));

            // Vérifier si la caisse est active
            if (!$caisse || !$caisse->isActive) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caisse inactive ou inexistante'
                ], 400);
            }

            // Calcul des frais (0.5% du montant)
            $frais = $request->montant * 0.005;
            $montant_total = $request->montant + $frais;

            // Vérifier le solde
            if ($caisse->solde_theorique < $montant_total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solde insuffisant',
                    'solde_disponible' => $caisse->solde_theorique,
                    'montant_demande' => $montant_total
                ], 400);
            }

            // Nom complet du client
            $clientNomComplet = trim($request->client_nom);
            if ($request->client_prenom) {
                $clientNomComplet .= ' ' . trim($request->client_prenom);
            }

            Log::info("debut inser transaction");

            $transaction = Transaction::create([
                'type' => 'RETRAIT',
                'sens' => 'SORTIE',
                'montant' => $request->montant,
                'frais' => $frais,
                'montant_total' => $montant_total,
                'caisse_uuid' => $request->caisse_uuid,
                'operator_uuid' => $request->operator_uuid,
                'user_uuid' => $request->user_uuid,
                'numero_telephone' => $request->numero_telephone,
                'reference_transaction' => $request->reference_transaction,
                'beneficiaire_nom' => $clientNomComplet, // Stocker le nom complet
                'notes' => $request->client_cni ? "CNI: {$request->client_cni}" : null,
                'statut' => 'VALIDEE',
                'validated_by' => $request->user_uuid,
                'validated_at' => now()
            ]);

            Log::info("debut update caisse solde");
            Log::info($transaction);

            // Mise à jour du solde de la caisse
            $transaction->updateCaisseSolde();

            Log::info("after update caisse solde");
            Log::info($caisse);

            DB::connection('mysql2')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Retrait effectué avec succès',
                'data' => [
                    'transaction' => $transaction->load('operator'),
                    'solde_restant' => $caisse->solde_theorique
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du retrait',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Opération MTO (Money Transfer Operator)

    public function operationMTO(Request $request)
    {

    Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'caisse_uuid' => 'required|exists:caisses,uuid',
            'operator_uuid' => 'required|exists:operators,uuid',
            'type' => 'required|in:ENVOI_MTO,RETRAIT_MTO',
            'montant' => 'required|numeric|min:1000',
            // Champs pour l'expéditeur/client
            'client_nom' => 'required|string|min:2',
            'client_prenom' => 'required|string|min:2',
            'numero_telephone' => 'required|string|regex:/^[0-9]{9,13}$/',
            // Champs pour le bénéficiaire (obligatoires pour ENVOI_MTO)
            'beneficiaire_nom' => 'required_if:type,ENVOI_MTO|string|min:2',
            'beneficiaire_telephone' => 'required_if:type,ENVOI_MTO|string|regex:/^[0-9]{9,13}$/',
            'beneficiaire_pays' => 'required_if:type,ENVOI_MTO|string',
            // Champs optionnels
            'reference_transaction' => 'nullable|string',
            'notes' => 'nullable|string'
        ], [
            'client_nom.required' => 'Le nom du client est requis',
            'client_prenom.required' => 'Le prénom du client est requis',
            'numero_telephone.required' => 'Le numéro de téléphone est requis',
            'numero_telephone.regex' => 'Le numéro de téléphone doit contenir 9 à 13 chiffres',
            'beneficiaire_nom.required_if' => 'Le nom du bénéficiaire est requis pour un envoi',
            'beneficiaire_telephone.required_if' => 'Le téléphone du bénéficiaire est requis pour un envoi',
            'beneficiaire_pays.required_if' => 'Le pays du bénéficiaire est requis pour un envoi',
        ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'errors' => $validator->errors()
        //     ], 422);
        // }

        try {
            DB::connection('mysql2')->beginTransaction();

            // Vérifier si la caisse existe et est active
            $caisse = Caisse::where('uuid', $request->caisse_uuid)->first();

            if (!$caisse || !$caisse->isActive) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caisse inactive ou inexistante'
                ], 400);
            }

            $sens = $request->type === 'ENVOI_MTO' ? 'SORTIE' : 'ENTREE';
            
            // Calcul des frais MTO (2% du montant)
            $frais = $request->montant * 0.02;
            $montant_total = $request->montant + $frais;

            // Pour un envoi, vérifier le solde
            if ($sens === 'SORTIE' && $caisse->solde_theorique < $montant_total) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solde insuffisant',
                    'solde_disponible' => $caisse->solde_theorique,
                    'montant_demande' => $montant_total
                ], 400);
            }

            // Nom complet du client (expéditeur)
            $clientNomComplet = trim($request->client_nom) . ' ' . trim($request->client_prenom);

            // Préparer les données de la transaction
            $transactionData = [
                'type' => $request->type,
                'sens' => $sens,
                'montant' => $request->montant,
                'frais' => $frais,
                'montant_total' => $montant_total,
                'caisse_uuid' => $request->caisse_uuid,
                'operator_uuid' => $request->operator_uuid,
                'user_uuid' => $request->user_uuid,
                'numero_telephone' => $request->numero_telephone,
                'reference_transaction' => $request->reference_transaction,
                'notes' => $request->notes,
                'statut' => 'VALIDEE',
                'validated_by' => $request->user_uuid,
                'validated_at' => now()
            ];

            // Ajouter le nom du client (expéditeur)
            if ($request->type === 'ENVOI_MTO') {
                $transactionData['beneficiaire_nom'] = $request->beneficiaire_nom;
                $transactionData['beneficiaire_telephone'] = $request->beneficiaire_telephone;
                $transactionData['beneficiaire_pays'] = $request->beneficiaire_pays;
                // Stocker le nom de l'expéditeur dans les notes si nécessaire
                $transactionData['notes'] = ($request->notes ? $request->notes . "\n" : '') . "Expéditeur: {$clientNomComplet}";
            } else {
                // Pour une réception, le bénéficiaire est le client
                $transactionData['beneficiaire_nom'] = $clientNomComplet;
            }

            $transaction = Transaction::create($transactionData);

            // Mettre à jour le solde de la caisse
            $transaction->updateCaisseSolde();

            DB::connection('mysql2')->commit();

            // Recharger la caisse avec le nouveau solde
            $caisseActualisee = Caisse::where('uuid', $request->caisse_uuid)->first();

            return response()->json([
                'success' => true,
                'message' => $request->type === 'ENVOI_MTO' ? 'Envoi MTO effectué avec succès' : 'Réception MTO validée avec succès',
                'data' => [
                    'transaction' => $transaction->load('operator'),
                    'solde_restant' => $caisseActualisee->solde_theorique
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            \Log::error('Erreur operationMTO:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'opération MTO',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Annuler une transaction
    public function annuler(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'justification' => 'required|string|min:10'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::connection('mysql2')->beginTransaction();

            $transaction = Transaction::where('uuid', $uuid)->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction non trouvée'
                ], 404);
            }

            if ($transaction->statut !== 'VALIDEE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les transactions validées peuvent être annulées'
                ], 400);
            }

            // Inverser l'effet sur le solde
            $caisse = Caisse::where('uuid', $transaction->caisse_uuid)->first();
            if ($transaction->sens === 'ENTREE') {
                $caisse->solde_theorique -= $transaction->montant_total;
            } else {
                $caisse->solde_theorique += $transaction->montant_total;
            }
            $caisse->save();

            $transaction->statut = 'ANNULEE';
            $transaction->justification_annulation = $request->justification;
            $transaction->save();

            DB::connection('mysql2')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction annulée avec succès'
            ]);

        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Statistiques des transactions
    public function statistiques(Request $request)
{
    try {
        // Construire la requête de base
        $query = Transaction::where('statut', 'VALIDEE');

        // Appliquer les filtres
        if ($request->caisse_uuid) {
            $query->where('caisse_uuid', $request->caisse_uuid);
        }

        if ($request->date_debut) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }

        if ($request->date_fin) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        // Exécuter les agrégations en une seule requête
        $aggregates = (clone $query)->select(
            DB::raw('COUNT(*) as total_transactions'),
            DB::raw('SUM(CASE WHEN sens = "ENTREE" THEN montant_total ELSE 0 END) as total_entrees'),
            DB::raw('SUM(CASE WHEN sens = "SORTIE" THEN montant_total ELSE 0 END) as total_sorties'),
            DB::raw('SUM(frais) as total_frais')
        )->first();

        // Statistiques par type
        $parType = (clone $query)
            ->select('type', 
                DB::raw('COUNT(*) as count'), 
                DB::raw('SUM(montant_total) as total'))
            ->groupBy('type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'count' => $item->count,
                    'total' => floatval($item->total)
                ];
            });

        // Statistiques par opérateur
        $parOperateur = (clone $query)
            ->select('operator_uuid', 
                DB::raw('COUNT(*) as count'), 
                DB::raw('SUM(montant_total) as total'))
            ->with('operator')
            ->groupBy('operator_uuid')
            ->get()
            ->map(function ($item) {
                return [
                    'operator_uuid' => $item->operator_uuid,
                    'operator' => $item->operator,
                    'count' => $item->count,
                    'total' => floatval($item->total)
                ];
            });

        // Calculer les métriques supplémentaires
        $totalEntrees = floatval($aggregates->total_entrees ?? 0);
        $totalSorties = floatval($aggregates->total_sorties ?? 0);
        $totalVolume = $totalEntrees + $totalSorties;
        $totalTransactions = intval($aggregates->total_transactions ?? 0);
        
        $stats = [
            'total_transactions' => $totalTransactions,
            'total_entrees' => $totalEntrees,
            'total_sorties' => $totalSorties,
            'total_frais' => floatval($aggregates->total_frais ?? 0),
            'total_volume' => $totalVolume,
            'moyenne_transaction' => $totalTransactions > 0 ? round($totalVolume / $totalTransactions, 2) : 0,
            'par_type' => $parType,
            'par_operateur' => $parOperateur
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);

    } catch (\Exception $e) {
        \Log::error('Erreur statistiques:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Erreur lors du chargement des statistiques',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
