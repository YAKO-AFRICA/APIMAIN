<?php
// app/Http/Controllers/Api/DashboardController.php

namespace App\Http\Controllers\Api;

use App\Models\Transaction;
use App\Models\Caisse;
use App\Models\Operator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    // Statistiques générales
    public function getStats(Request $request)
    {
        try {
            $query = Transaction::where('statut', 'VALIDEE');

            // Filtres
            if ($request->periode) {
                switch ($request->periode) {
                    case 'today':
                        $query->whereDate('created_at', today());
                        break;
                    case 'week':
                        $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'month':
                        $query->whereMonth('created_at', now()->month);
                        $query->whereYear('created_at', now()->year);
                        break;
                    case 'year':
                        $query->whereYear('created_at', now()->year);
                        break;
                }
            }

            if ($request->date_debut && $request->date_fin) {
                $query->whereBetween('created_at', [$request->date_debut, $request->date_fin]);
            }

            if ($request->caisse_uuid) {
                $query->where('caisse_uuid', $request->caisse_uuid);
            }

            $caisses_actives = Caisse::where('isActive', true)->count();
            $total_transactions = $query->count();
            $total_entrees = $query->where('sens', 'ENTREE')->sum('montant_total');
            $total_sorties = $query->where('sens', 'SORTIE')->sum('montant_total');
            $total_frais = $query->sum('frais');
            $volume_journalier = Transaction::where('statut', 'VALIDEE')
                ->whereDate('created_at', today())
                ->sum('montant_total');
            $transactions_journalieres = Transaction::where('statut', 'VALIDEE')
                ->whereDate('created_at', today())
                ->count();

            // Calcul des écarts
            $caisses = Caisse::all();
            $ecarts = [];
            foreach ($caisses as $caisse) {
                $solde_theorique = floatval($caisse->solde_theorique ?? 0);
                $solde_physique = floatval($caisse->solde_physique ?? 0);
                $ecart = $solde_theorique - $solde_physique;
                if (abs($ecart) > 0) {
                    $ecarts[] = [
                        'caisse' => $caisse->libelle,
                        'solde_theorique' => $solde_theorique,
                        'solde_physique' => $solde_physique,
                        'ecart' => $ecart
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'caisses_actives' => $caisses_actives,
                    'total_transactions' => $total_transactions,
                    'total_entrees' => floatval($total_entrees),
                    'total_sorties' => floatval($total_sorties),
                    'total_frais' => floatval($total_frais),
                    'volume_journalier' => floatval($volume_journalier),
                    'transactions_journalieres' => $transactions_journalieres,
                    'ecarts' => [
                        'total_ecarts' => count($ecarts),
                        'details' => $ecarts
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Transactions par période (jour/mois/année)
    public function getTransactionsByPeriod(Request $request)
    {
        try {
            $type = $request->type ?? 'month';

            switch ($type) {
                case 'day':
                    $groupBy = DB::raw('DATE(created_at)');
                    $selectPeriod = DB::raw("DATE_FORMAT(created_at, '%Y-%m-%d') as periode");
                    $limit = 30;
                    break;
                case 'month':
                    $groupBy = DB::raw("DATE_FORMAT(created_at, '%Y-%m')");
                    $selectPeriod = DB::raw("DATE_FORMAT(created_at, '%Y-%m') as periode");
                    $limit = 12;
                    break;
                case 'year':
                    $groupBy = DB::raw('YEAR(created_at)');
                    $selectPeriod = DB::raw("DATE_FORMAT(created_at, '%Y') as periode");
                    $limit = 5;
                    break;
                default:
                    $groupBy = DB::raw("DATE_FORMAT(created_at, '%Y-%m')");
                    $selectPeriod = DB::raw("DATE_FORMAT(created_at, '%Y-%m') as periode");
                    $limit = 12;
            }

            $query = Transaction::where('statut', 'VALIDEE')
                ->select(
                    $selectPeriod,
                    DB::raw('COUNT(*) as total_transactions'),
                    DB::raw('SUM(montant_total) as total_montant'),
                    DB::raw('SUM(CASE WHEN sens = "ENTREE" THEN montant_total ELSE 0 END) as entrees'),
                    DB::raw('SUM(CASE WHEN sens = "SORTIE" THEN montant_total ELSE 0 END) as sorties'),
                    DB::raw('SUM(frais) as total_frais')
                )
                ->groupBy('periode')
                ->orderBy('periode', 'desc');

            // Ajouter les filtres
            if ($request->caisse_uuid) {
                $query->where('caisse_uuid', $request->caisse_uuid);
            }

            if ($request->date_debut && $request->date_fin) {
                $query->whereBetween('created_at', [$request->date_debut, $request->date_fin]);
            }

            $transactions = $query->limit($limit)->get();

            // Trier par ordre croissant pour l'affichage
            $transactions = $transactions->reverse()->values();

            return response()->json([
                'success' => true,
                'data' => $transactions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des transactions par période',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Top opérateurs les plus utilisés
    public function getTopOperators(Request $request)
    {
        try {
            $query = Transaction::where('statut', 'VALIDEE')
                ->select(
                    'operator_uuid',
                    DB::raw('COUNT(*) as nombre_transactions'),
                    DB::raw('SUM(montant_total) as total_montant')
                )
                ->groupBy('operator_uuid');

            if ($request->periode) {
                switch ($request->periode) {
                    case 'today':
                        $query->whereDate('created_at', today());
                        break;
                    case 'week':
                        $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'month':
                        $query->whereMonth('created_at', now()->month);
                        $query->whereYear('created_at', now()->year);
                        break;
                    case 'year':
                        $query->whereYear('created_at', now()->year);
                        break;
                }
            }

            if ($request->date_debut && $request->date_fin) {
                $query->whereBetween('created_at', [$request->date_debut, $request->date_fin]);
            }

            if ($request->caisse_uuid) {
                $query->where('caisse_uuid', $request->caisse_uuid);
            }

            $topOperators = $query->orderBy('nombre_transactions', 'desc')
                ->limit(5)
                ->get();

            // Charger les informations des opérateurs
            foreach ($topOperators as $operator) {
                if ($operator->operator_uuid) {
                    $op = Operator::where('uuid', $operator->operator_uuid)->first();
                    $operator->operator = $op;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $topOperators
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des top opérateurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Transactions par type
    public function getTransactionsByType(Request $request)
    {
        try {
            $query = Transaction::where('statut', 'VALIDEE')
                ->select(
                    'type',
                    DB::raw('COUNT(*) as nombre'),
                    DB::raw('SUM(montant_total) as total_montant')
                )
                ->groupBy('type');

            if ($request->periode) {
                switch ($request->periode) {
                    case 'today':
                        $query->whereDate('created_at', today());
                        break;
                    case 'week':
                        $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                        break;
                    case 'month':
                        $query->whereMonth('created_at', now()->month);
                        $query->whereYear('created_at', now()->year);
                        break;
                    case 'year':
                        $query->whereYear('created_at', now()->year);
                        break;
                }
            }

            if ($request->date_debut && $request->date_fin) {
                $query->whereBetween('created_at', [$request->date_debut, $request->date_fin]);
            }

            if ($request->caisse_uuid) {
                $query->where('caisse_uuid', $request->caisse_uuid);
            }

            $types = $query->get();

            $typeLabels = [
                'DEPOT' => 'Dépôts',
                'RETRAIT' => 'Retraits',
                'ENVOI_MTO' => 'Envois MTO',
                'RETRAIT_MTO' => 'Réceptions MTO'
            ];

            $data = [];
            foreach ($types as $item) {
                $data[] = [
                    'type' => $typeLabels[$item->type] ?? $item->type,
                    'nombre' => $item->nombre,
                    'total_montant' => floatval($item->total_montant)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des transactions par type',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
