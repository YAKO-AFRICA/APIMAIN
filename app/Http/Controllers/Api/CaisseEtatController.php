<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caisse;
use App\Models\CaisseEtat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CaisseEtatController extends Controller
{
    /**
     * Vérifier si une caisse est ouverte aujourd'hui
     */
    public function checkOuverture(Request $request)
    {
        $caisseEtat = CaisseEtat::where('caisse_uuid', $request->caisse_uuid)
            ->whereDate('date_journee', today())
            ->first();
            
        return response()->json([
            'success' => true,
            'data' => [
                'est_ouverte' => $caisseEtat && in_array($caisseEtat->statut, ['OUVERTE', 'EN_COURS']),
                'etat' => $caisseEtat,
                'statut' => $caisseEtat ? $caisseEtat->statut : null
            ]
        ]);
    }
    
    /**
     * Ouvrir une caisse
     */
    public function ouvrir(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caisse_uuid' => 'required|exists:caisses,uuid',
            'solde_initial' => 'required|numeric|min:0',
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
            
            // Vérifier si déjà ouverte aujourd'hui
            $existant = CaisseEtat::where('caisse_uuid', $request->caisse_uuid)
                ->whereDate('date_journee', today())
                ->first();
                
            if ($existant && in_array($existant->statut, ['OUVERTE', 'EN_COURS'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette caisse est déjà ouverte aujourd\'hui'
                ], 400);
            }
            
            // Créer ou mettre à jour l'état
            $caisseEtat = CaisseEtat::updateOrCreate(
                [
                    'caisse_uuid' => $request->caisse_uuid,
                    'date_journee' => today()
                ],
                [
                    'statut' => 'OUVERTE',
                    'solde_initial' => $request->solde_initial,
                    'solde_theorique' => $request->solde_initial,
                    'date_ouverture' => now(),
                    'ouverte_par' => $request->user_uuid,
                    'notes' => $request->notes,
                    'est_verrouille' => false
                ]
            );
            
            // Mettre à jour le solde physique de la caisse
            $caisse = Caisse::where('uuid', $request->caisse_uuid)->first();
            $caisse->solde_physique = $request->solde_initial;
            $caisse->save();
            
            DB::connection('mysql2')->commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Caisse ouverte avec succès',
                'data' => $caisseEtat->load('caisse')
            ]);
            
        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ouverture',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Rapprochement de caisse
     */
    public function rapprochement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caisse_uuid' => 'required|exists:caisses,uuid',
            'solde_physique' => 'required|numeric|min:0',
            'justification_ecart' => 'required_if:ecart,!=,0|nullable|string'
        ]);
        
        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'errors' => $validator->errors()
        //     ], 422);
        // }
        
        try {
            DB::connection('mysql2')->beginTransaction();
            
            $caisseEtat = CaisseEtat::where('caisse_uuid', $request->caisse_uuid)
                ->whereDate('date_journee', today())
                ->first();
                
            if (!$caisseEtat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caisse non ouverte aujourd\'hui'
                ], 400);
            }
            
            if ($caisseEtat->statut === 'FERMEE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette caisse est déjà fermée'
                ], 400);
            }
            
            // Calculer le solde théorique
            $soldeTheorique = $caisseEtat->getSoldeTheoriqueActuel();
            $ecart = $request->solde_physique - $soldeTheorique;
            
            $caisseEtat->solde_theorique = $soldeTheorique;
            $caisseEtat->solde_physique = $request->solde_physique;
            $caisseEtat->ecart = $ecart;
            $caisseEtat->justification_ecart = $request->justification_ecart;
            $caisseEtat->statut = 'EN_COURS';
            $caisseEtat->save();
            
            // Mettre à jour le solde physique de la caisse
            $caisse = Caisse::where('uuid', $request->caisse_uuid)->first();
            $caisse->solde_physique = $request->solde_physique;
            $caisse->save();
            
            DB::connection('mysql2')->commit();
            
            return response()->json([
                'success' => true,
                'message' => $ecart == 0 ? 'Rapprochement effectué avec succès' : 'Rapprochement effectué avec écart',
                'data' => [
                    'solde_theorique' => $soldeTheorique,
                    'solde_physique' => $request->solde_physique,
                    'ecart' => $ecart,
                    'justification_ecart' => $request->justification_ecart
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du rapprochement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Fermer une caisse
     */
    public function fermer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caisse_uuid' => 'required|exists:caisses,uuid',
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
            
            $caisseEtat = CaisseEtat::where('caisse_uuid', $request->caisse_uuid)
                ->whereDate('date_journee', today())
                ->first();
                
            if (!$caisseEtat) {
                return response()->json([
                    'success' => false,
                    'message' => 'Caisse non ouverte aujourd\'hui'
                ], 400);
            }
            
            if ($caisseEtat->statut === 'FERMEE') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette caisse est déjà fermée'
                ], 400);
            }
            
            // Si pas encore de rapprochement, faire un rapprochement automatique
            if ($caisseEtat->solde_physique === null) {
                $soldeTheorique = $caisseEtat->getSoldeTheoriqueActuel();
                $caisseEtat->solde_theorique = $soldeTheorique;
                $caisseEtat->solde_physique = $soldeTheorique;
                $caisseEtat->ecart = 0;
            }
            
            $caisseEtat->statut = 'FERMEE';
            $caisseEtat->date_fermeture = now();
            $caisseEtat->fermee_par = $request->user_uuid;
            if ($request->notes) {
                $caisseEtat->notes = ($caisseEtat->notes ? $caisseEtat->notes . "\n" : '') . $request->notes;
            }
            $caisseEtat->save();
            
            DB::connection('mysql2')->commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Caisse fermée avec succès',
                'data' => $caisseEtat
            ]);
            
        } catch (\Exception $e) {
            DB::connection('mysql2')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la fermeture',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verrouiller une caisse (admin seulement)
     */
    public function verrouiller($uuid, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'motif' => 'required|string|min:10'
        ]);
        
        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'errors' => $validator->errors()
        //     ], 422);
        // }
        
        try {
            $caisseEtat = CaisseEtat::where('uuid', $uuid)->first();
            
            if (!$caisseEtat) {
                return response()->json([
                    'success' => false,
                    'message' => 'État de caisse non trouvé'
                ], 404);
            }
            
            $caisseEtat->est_verrouille = true;
            $caisseEtat->date_verrouillage = now();
            $caisseEtat->verrouille_par = $request->user_uuid;
            $caisseEtat->motif_verrouillage = $request->motif;
            $caisseEtat->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Caisse verrouillée avec succès'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du verrouillage',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Déverrouiller une caisse (admin seulement)
     */
    public function deverrouiller($uuid, Request $request)
    {
        try {
            $caisseEtat = CaisseEtat::where('uuid', $uuid)->first();
            
            if (!$caisseEtat) {
                return response()->json([
                    'success' => false,
                    'message' => 'État de caisse non trouvé'
                ], 404);
            }
            
            $caisseEtat->est_verrouille = false;
            $caisseEtat->date_verrouillage = null;
            $caisseEtat->verrouille_par = null;
            $caisseEtat->motif_verrouillage = null;
            $caisseEtat->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Caisse déverrouillée avec succès'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du déverrouillage',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Historique des états de caisse
     */
    public function historique(Request $request)
    {
        $query = CaisseEtat::with(['caisse', 'ouvertePar', 'fermeePar']);
        
        if ($request->caisse_uuid) {
            $query->where('caisse_uuid', $request->caisse_uuid);
        }
        
        if ($request->date_debut) {
            $query->whereDate('date_journee', '>=', $request->date_debut);
        }
        
        if ($request->date_fin) {
            $query->whereDate('date_journee', '<=', $request->date_fin);
        }
        
        $historique = $query->orderBy('date_journee', 'desc')
            ->paginate($request->per_page ?? 15);
            
        return response()->json([
            'success' => true,
            'data' => $historique
        ]);
    }
}
