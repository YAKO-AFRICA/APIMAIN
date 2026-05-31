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
        $validator = Validator::make($request->all(), [
            'caisse_uuid' => 'required|exists:caisses,uuid',
            'operator_uuid' => 'required|exists:operators,uuid',
            'type' => 'required|in:ENVOI_MTO,RETRAIT_MTO',
            'montant' => 'required|numeric|min:100',
            'beneficiaire_nom' => 'required_if:type,ENVOI_MTO|string',
            'beneficiaire_telephone' => 'required_if:type,ENVOI_MTO|string',
            'beneficiaire_pays' => 'required_if:type,ENVOI_MTO|string',
            'reference_transaction' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::connection('mysql2')->beginTransaction();

            $caisse = Caisse::where('uuid', $request->caisse_uuid)->first();
            $sens = $request->type === 'ENVOI_MTO' ? 'SORTIE' : 'ENTREE';

            // Pour un envoi, vérifier le solde
            if ($sens === 'SORTIE' && $caisse->solde_theorique < $request->montant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solde insuffisant'
                ], 400);
            }

            // Frais MTO (exemple: 2%)
            $frais = $request->montant * 0.02;
            $montant_total = $request->montant + $frais;

            $transaction = Transaction::create([
                'type' => $request->type,
                'sens' => $sens,
                'montant' => $request->montant,
                'frais' => $frais,
                'montant_total' => $montant_total,
                'caisse_uuid' => $request->caisse_uuid,
                'operator_uuid' => $request->operator_uuid,
                'user_uuid' => $request->user_uuid,
                'beneficiaire_nom' => $request->beneficiaire_nom,
                'beneficiaire_telephone' => $request->beneficiaire_telephone,
                'beneficiaire_pays' => $request->beneficiaire_pays,
                'reference_transaction' => $request->reference_transaction,
                'statut' => 'VALIDEE',
                'validated_by' => $request->user_uuid,
                'validated_at' => now()
            ]);

            $transaction->updateCaisseSolde();

            DB::connection('mysql2')->commit();

            return response()->json([
                'success' => true,
                'message' => 'Opération MTO effectuée avec succès',
                'data' => $transaction->load('operator')
            ], 201);

        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
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
        $query = Transaction::where('statut', 'VALIDEE');

        if ($request->caisse_uuid) {
            $query->where('caisse_uuid', $request->caisse_uuid);
        }

        if ($request->date_debut) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }

        if ($request->date_fin) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }

        $stats = [
            'total_transactions' => $query->count(),
            'total_entrees' => $query->where('sens', 'ENTREE')->sum('montant_total'),
            'total_sorties' => $query->where('sens', 'SORTIE')->sum('montant_total'),
            'total_frais' => $query->sum('frais'),
            'par_type' => $query->select('type', DB::raw('count(*) as count, sum(montant_total) as total'))
                                ->groupBy('type')
                                ->get(),
            'par_operateur' => $query->select('operator_uuid', DB::raw('count(*) as count, sum(montant_total) as total'))
                                    ->with('operator')
                                    ->groupBy('operator_uuid')
                                    ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
