<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caisse;
use App\Models\CaisseMouvement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CaisseMouvementController extends Controller
{
    // Liste des mouvements
    public function index(Request $request)
    {
        $query = CaisseMouvement::with(['caisseSource', 'caisseDestination', 'envoyePar', 'recuPar']);


        
        if ($request->type) {
            $query->where('type', $request->type);
        }
        
        if ($request->statut) {
            $query->where('statut', $request->statut);
        }
        
        if ($request->caisse_uuid) {
            $query->where(function($q) use ($request) {
                $q->where('caisse_source_uuid', $request->caisse_uuid)
                  ->orWhere('caisse_destination_uuid', $request->caisse_uuid);
            });
        }
        
        if ($request->date_debut) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        
        if ($request->date_fin) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }
        
        $mouvements = $query->orderBy('created_at', 'desc')
                            ->paginate($request->per_page ?? 15);

                            Log::info($mouvements);
        
        return response()->json([
            'success' => true,
            'data' => $mouvements
        ]);
    }
    
    // Créer un approvisionnement
    public function approvisionnement(Request $request)
    {

        Log::info('Création d\'un approvisionnement', ['request' => $request->all()]);
        $validator = Validator::make($request->all(), [
            'caisse_source_uuid' => 'required|exists:caisses,uuid',
            'caisse_destination_uuid' => 'required|exists:caisses,uuid|different:caisse_source_uuid',
            'montant' => 'required|numeric|min:1000',
            'notes' => 'nullable|string'
        ], [
            'caisse_destination_uuid.different' => 'La caisse source et destination doivent être différentes'
        ]);
        
        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'errors' => $validator->errors()
        //     ], 422);
        // }
        
        try {
            DB::connection('mysql2')->beginTransaction();
            
            $caisseSource = Caisse::on('mysql2')->where('uuid', $request->caisse_source_uuid)->first();
            $caisseDestination = Caisse::on('mysql2')->where('uuid', $request->caisse_destination_uuid)->first();
            
            // Vérifier le solde
            if ($caisseSource->solde_theorique < $request->montant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solde insuffisant dans la caisse source'
                ], 400);
            }
            
            // Créer le mouvement
            $mouvement = CaisseMouvement::create([
                'type' => 'APPROVISIONNEMENT',
                'statut' => 'EN_ATTENTE',
                'caisse_source_uuid' => $request->caisse_source_uuid,
                'caisse_destination_uuid' => $request->caisse_destination_uuid,
                'montant_envoye' => $request->montant,
                'frais' => 0,
                'envoye_par' => $request->user_uuid,
                'notes' => $request->notes,
                'date_envoi' => now()
            ]);
            
            DB::connection('mysql2')->commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Approvisionnement créé avec succès',
                'data' => $mouvement->load(['caisseSource', 'caisseDestination'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Créer un rapatriement UVE
    public function rapatriement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caisse_source_uuid' => 'required|exists:caisses,uuid',
            'caisse_destination_uuid' => 'required|exists:caisses,uuid|different:caisse_source_uuid',
            'montant' => 'required|numeric|min:1000',
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
            
            $caisseSource = Caisse::where('uuid', $request->caisse_source_uuid)->first();
            
            // Vérifier le solde
            if ($caisseSource->solde_theorique < $request->montant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solde insuffisant dans la caisse source'
                ], 400);
            }
            
            // Créer le mouvement
            $mouvement = CaisseMouvement::create([
                'type' => 'RAPATRIEMENT',
                'statut' => 'EN_ATTENTE',
                'caisse_source_uuid' => $request->caisse_source_uuid,
                'caisse_destination_uuid' => $request->caisse_destination_uuid,
                'montant_envoye' => $request->montant,
                'frais' => 0,
                'envoye_par' => $request->user_uuid,
                'notes' => $request->notes,
                'date_envoi' => now()
            ]);
            
            DB::connection('mysql2')->commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Rapatriement créé avec succès',
                'data' => $mouvement->load(['caisseSource', 'caisseDestination'])
            ], 201);
            
        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Valider l'envoi (étape 1)
    public function validerEnvoi($uuid, Request $request)
    {
        try {
            DB::connection('mysql2')->beginTransaction();
            
            $mouvement = CaisseMouvement::where('uuid', $uuid)->first();
            
            if (!$mouvement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mouvement non trouvé'
                ], 404);
            }
            
            if ($mouvement->statut !== 'EN_ATTENTE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce mouvement ne peut pas être validé'
                ], 400);
            }
            
            // Mettre à jour le statut
            $mouvement->statut = 'EN_TRANSIT';
            $mouvement->envoye_par = $request->user_uuid;
            $mouvement->date_envoi = now();
            $mouvement->save();
            
            // Mettre à jour les soldes
            $mouvement->updateCaissesSolde();
            
            DB::connection('mysql2')->commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Envoi validé avec succès',
                'data' => $mouvement
            ]);
            
        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Confirmer la réception (étape 2)
    public function confirmerReception($uuid, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'montant_recu' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string'
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            DB::connection('mysql2')->beginTransaction();
            
            $mouvement = CaisseMouvement::where('uuid', $uuid)->first();
            
            if (!$mouvement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mouvement non trouvé'
                ], 404);
            }
            
            if ($mouvement->statut !== 'EN_TRANSIT') {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce mouvement ne peut pas être confirmé'
                ], 400);
            }
            
            // Mettre à jour le statut
            $montantRecu = $request->montant_recu ?? $mouvement->montant_envoye;
            $ecart = $montantRecu - $mouvement->montant_envoye;
            
            $mouvement->statut = 'RECU';
            $mouvement->montant_recu = $montantRecu;
            $mouvement->confirmation_recu = true;
            $mouvement->recu_par = $request->user_uuid;
            $mouvement->date_reception = now();
            if ($request->notes) {
                $mouvement->notes = ($mouvement->notes ? $mouvement->notes . "\n" : '') . $request->notes;
            }
            $mouvement->save();
            
            // Mettre à jour les soldes de la destination
            $mouvement->updateCaissesSolde();
            
            DB::connection('mysql2')->commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Réception confirmée avec succès',
                'data' => [
                    'mouvement' => $mouvement,
                    'ecart' => $ecart
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Annuler un mouvement
    public function annuler($uuid, Request $request)
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
            
            $mouvement = CaisseMouvement::where('uuid', $uuid)->first();
            
            if (!$mouvement) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mouvement non trouvé'
                ], 404);
            }
            
            if ($mouvement->statut === 'RECU') {
                return response()->json([
                    'success' => false,
                    'message' => 'Un mouvement reçu ne peut pas être annulé'
                ], 400);
            }
            
            // Si le mouvement était en transit, rembourser la source
            if ($mouvement->statut === 'EN_TRANSIT') {
                $caisseSource = Caisse::where('uuid', $mouvement->caisse_source_uuid)->first();
                if ($caisseSource) {
                    $caisseSource->solde_theorique += $mouvement->montant_envoye;
                    $caisseSource->save();
                }
            }
            
            $mouvement->statut = 'ANNULE';
            $mouvement->justification_annulation = $request->justification;
            $mouvement->save();
            
            DB::connection('mysql2')->commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Mouvement annulé avec succès'
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


    public function getStats(Request $request)
    {
        try {
            $query = CaisseMouvement::query();
            
            // Appliquer les filtres
            $this->applyFilters($query, $request);
            
            // Statistiques de base
            $totalMouvements = (clone $query)->count();
            $totalApprovisionnements = (clone $query)->where('type', 'APPROVISIONNEMENT')->count();
            $totalRapatriements = (clone $query)->where('type', 'RAPATRIEMENT')->count();
            
            // Montants
            $totalMontantEnvoye = (clone $query)->sum('montant_envoye');
            $totalMontantRecu = (clone $query)->sum('montant_recu');
            
            // Par statut
            $enAttente = (clone $query)->where('statut', 'EN_ATTENTE')->count();
            $enTransit = (clone $query)->where('statut', 'EN_TRANSIT')->count();
            $recus = (clone $query)->where('statut', 'RECU')->count();
            $annules = (clone $query)->where('statut', 'ANNULE')->count();
            
            // Délai moyen de livraison (pour les mouvements reçus)
            $delaiMoyen = (clone $query)->where('statut', 'RECU')
                ->whereNotNull('date_envoi')
                ->whereNotNull('date_reception')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, date_envoi, date_reception)) as delai_moyen'))
                ->value('delai_moyen') ?? 0;
            
            // Taux de succès
            $tauxSucces = $totalMouvements > 0 ? ($recus / $totalMouvements) * 100 : 0;
            
            // Écart total
            $totalEcart = $totalMontantRecu - $totalMontantEnvoye;
            
            return response()->json([
                'success' => true,
                'data' => [
                    'total_mouvements' => $totalMouvements,
                    'total_approvisionnements' => $totalApprovisionnements,
                    'total_rapatriements' => $totalRapatriements,
                    'total_montant_envoye' => floatval($totalMontantEnvoye),
                    'total_montant_recu' => floatval($totalMontantRecu),
                    'total_ecart' => floatval($totalEcart),
                    'en_attente' => $enAttente,
                    'en_transit' => $enTransit,
                    'recus' => $recus,
                    'annules' => $annules,
                    'delai_moyen_livraison' => round($delaiMoyen, 1),
                    'taux_succes' => round($tauxSucces, 1)
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
    
    /**
     * Mouvements par période (jour/mois/année)
     */
    public function getMouvementsByPeriod(Request $request)
    {
        try {
            $type = $request->type ?? 'month';
            $query = CaisseMouvement::query();
            
            $this->applyFilters($query, $request);
            
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
            
            $mouvements = (clone $query)
                ->select(
                    $selectPeriod,
                    DB::raw('COUNT(*) as total'),
                    DB::raw('SUM(CASE WHEN type = "APPROVISIONNEMENT" THEN 1 ELSE 0 END) as approvisionnements'),
                    DB::raw('SUM(CASE WHEN type = "RAPATRIEMENT" THEN 1 ELSE 0 END) as rapatriements'),
                    DB::raw('SUM(montant_envoye) as montant')
                )
                ->groupBy('periode')
                ->orderBy('periode', 'desc')
                ->limit($limit)
                ->get()
                ->reverse()
                ->values();
            
            return response()->json([
                'success' => true,
                'data' => $mouvements
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Top caisses émettrices
     */
    public function getTopCaisses(Request $request)
    {
        try {
            $query = CaisseMouvement::query();
            
            $this->applyFilters($query, $request);
            
            $topCaisses = (clone $query)
                ->select(
                    'caisse_source_uuid',
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(montant_envoye) as montant')
                )
                ->with('caisseSource')
                ->groupBy('caisse_source_uuid')
                ->orderBy('montant', 'desc')
                ->limit(5)
                ->get()
                ->map(function($item) {
                    return [
                        'name' => $item->caisseSource->libelle ?? 'N/A',
                        'count' => $item->count,
                        'montant' => floatval($item->montant)
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $topCaisses
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Répartition par statut
     */
    public function getMouvementsByStatut(Request $request)
    {
        try {
            $query = CaisseMouvement::query();
            
            $this->applyFilters($query, $request);
            
            $statuts = (clone $query)
                ->select('statut', DB::raw('COUNT(*) as count'))
                ->groupBy('statut')
                ->get()
                ->map(function($item) {
                    $colors = [
                        'EN_ATTENTE' => '#faad14',
                        'EN_TRANSIT' => '#1890ff',
                        'RECU' => '#52c41a',
                        'ANNULE' => '#ff4d4f'
                    ];
                    
                    $labels = [
                        'EN_ATTENTE' => 'En attente',
                        'EN_TRANSIT' => 'En transit',
                        'RECU' => 'Reçus',
                        'ANNULE' => 'Annulés'
                    ];
                    
                    return [
                        'name' => $labels[$item->statut] ?? $item->statut,
                        'value' => $item->count,
                        'color' => $colors[$item->statut] ?? '#8884d8'
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $statuts
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Appliquer les filtres communs
     */
    private function applyFilters($query, Request $request)
    {
        if ($request->caisse_uuid) {
            $query->where(function($q) use ($request) {
                $q->where('caisse_source_uuid', $request->caisse_uuid)
                  ->orWhere('caisse_destination_uuid', $request->caisse_uuid);
            });
        }
        
        if ($request->type && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        
        if ($request->statut && $request->statut !== 'all') {
            $query->where('statut', $request->statut);
        }
        
        if ($request->date_debut) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        
        if ($request->date_fin) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }
        
        if ($request->periode) {
            switch ($request->periode) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                    break;
                case 'month':
                    $query->whereMonth('created_at', now()->month)
                          ->whereYear('created_at', now()->year);
                    break;
                case 'year':
                    $query->whereYear('created_at', now()->year);
                    break;
            }
        }
    }
}
