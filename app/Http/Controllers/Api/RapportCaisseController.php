<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caisse;
use App\Models\CaisseEtat;
use App\Models\CaisseMouvement;
use App\Models\Operator;
use App\Models\Transaction;
use Illuminate\Http\Request;

class RapportCaisseController extends Controller
{
    /**
     * Rapport journalier complet
     */
    public function rapportJournalier(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'date' => 'required|date',
            'caisse_uuid' => 'nullable|exists:caisses,uuid'
        ]);

        $date = $request->date;
        $caisseUuid = $request->caisse_uuid;

        try {
            // 1. État de la caisse
            $etatCaisse = $this->getEtatCaisse($date, $caisseUuid);
            
            // 2. Transactions du jour
            $transactions = $this->getTransactions($date, $caisseUuid);
            
            // 3. Mouvements inter-caisses
            $mouvements = $this->getMouvements($date, $caisseUuid);
            
            // 4. Résumé par opérateur
            $resumeOperateurs = $this->getResumeByOperateur($date, $caisseUuid);
            
            // 5. Résumé par type de transaction
            $resumeParType = $this->getResumeByType($date, $caisseUuid);
            
            // 6. Synthèse financière
            $syntheseFinanciere = $this->getSyntheseFinanciere($date, $caisseUuid);
            
            // 7. Écarts et anomalies
            $ecarts = $this->getEcarts($date, $caisseUuid);
            
            // 8. Activité des caisses
            $activiteCaisses = $this->getActiviteCaisses($date, $caisseUuid);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $date,
                    'etat_caisse' => $etatCaisse,
                    'transactions' => $transactions,
                    'mouvements' => $mouvements,
                    'resume_operateurs' => $resumeOperateurs,
                    'resume_par_type' => $resumeParType,
                    'synthese_financiere' => $syntheseFinanciere,
                    'ecarts' => $ecarts,
                    'activite_caisses' => $activiteCaisses,
                    'generated_at' => now()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Export du rapport en PDF
     */
    public function exportPdf(Request $request)
    {
        // À implémenter avec une librairie PDF
        return response()->json([
            'success' => false,
            'message' => 'Fonctionnalité à venir'
        ]);
    }
    
    /**
     * Export du rapport en Excel
     */
    public function exportExcel(Request $request)
    {
        // À implémenter avec une librairie Excel
        return response()->json([
            'success' => false,
            'message' => 'Fonctionnalité à venir'
        ]);
    }
    
    // Méthodes privées pour récupérer les données
    
    private function getEtatCaisse($date, $caisseUuid = null)
    {
        $query = CaisseEtat::with(['caisse', 'ouvertePar', 'fermeePar'])
            ->whereDate('date_journee', $date);
            
        if ($caisseUuid) {
            $query->where('caisse_uuid', $caisseUuid);
        }
        
        $etats = $query->get();
        
        return [
            'total_caisses' => $etats->count(),
            'caisses_ouvertes' => $etats->where('statut', 'OUVERTE')->count(),
            'caisses_en_cours' => $etats->where('statut', 'EN_COURS')->count(),
            'caisses_fermees' => $etats->where('statut', 'FERMEE')->count(),
            'details' => $etats->map(function($etat) {
                return [
                    'caisse' => $etat->caisse->libelle ?? 'N/A',
                    'statut' => $etat->statut,
                    'solde_initial' => $etat->solde_initial,
                    'solde_theorique' => $etat->solde_theorique,
                    'solde_physique' => $etat->solde_physique,
                    'ecart' => $etat->ecart,
                    'ouvert_par' => $etat->ouvertePar->name ?? 'N/A',
                    'date_ouverture' => $etat->date_ouverture,
                    'date_fermeture' => $etat->date_fermeture
                ];
            })
        ];
    }
    
    private function getTransactions($date, $caisseUuid = null)
    {
        $query = Transaction::with(['caisse', 'operator'])
            ->whereDate('created_at', $date)
            ->where('statut', 'VALIDEE');
            
        if ($caisseUuid) {
            $query->where('caisse_uuid', $caisseUuid);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->get();
        
        return [
            'total' => $transactions->count(),
            'total_entrees' => $transactions->where('sens', 'ENTREE')->sum('montant_total'),
            'total_sorties' => $transactions->where('sens', 'SORTIE')->sum('montant_total'),
            'total_frais' => $transactions->sum('frais'),
            'liste' => $transactions->map(function($transaction) {
                return [
                    'reference' => $transaction->reference,
                    'type' => $transaction->type,
                    'sens' => $transaction->sens,
                    'montant' => $transaction->montant,
                    'frais' => $transaction->frais,
                    'montant_total' => $transaction->montant_total,
                    'caisse' => $transaction->caisse->libelle ?? 'N/A',
                    'operateur' => $transaction->operator->name ?? 'N/A',
                    'numero_telephone' => $transaction->numero_telephone,
                    'beneficiaire_nom' => $transaction->beneficiaire_nom,
                    'date' => $transaction->created_at
                ];
            })
        ];
    }
    
    private function getMouvements($date, $caisseUuid = null)
    {
        $query = CaisseMouvement::with(['caisseSource', 'caisseDestination'])
            ->whereDate('created_at', $date);
            
        if ($caisseUuid) {
            $query->where(function($q) use ($caisseUuid) {
                $q->where('caisse_source_uuid', $caisseUuid)
                  ->orWhere('caisse_destination_uuid', $caisseUuid);
            });
        }
        
        $mouvements = $query->orderBy('created_at', 'desc')->get();
        
        return [
            'total' => $mouvements->count(),
            'total_approvisionnements' => $mouvements->where('type', 'APPROVISIONNEMENT')->sum('montant_envoye'),
            'total_rapatriements' => $mouvements->where('type', 'RAPATRIEMENT')->sum('montant_envoye'),
            'total_montant' => $mouvements->sum('montant_envoye'),
            'liste' => $mouvements->map(function($mouvement) {
                return [
                    'reference' => $mouvement->reference,
                    'type' => $mouvement->type,
                    'statut' => $mouvement->statut,
                    'source' => $mouvement->caisseSource->libelle ?? 'N/A',
                    'destination' => $mouvement->caisseDestination->libelle ?? 'N/A',
                    'montant_envoye' => $mouvement->montant_envoye,
                    'montant_recu' => $mouvement->montant_recu,
                    'date_envoi' => $mouvement->date_envoi,
                    'date_reception' => $mouvement->date_reception
                ];
            })
        ];
    }
    
    private function getResumeByOperateur($date, $caisseUuid = null)
    {
        $query = Transaction::with('operator')
            ->whereDate('created_at', $date)
            ->where('statut', 'VALIDEE');
            
        if ($caisseUuid) {
            $query->where('caisse_uuid', $caisseUuid);
        }
        
        $operateurs = $query->select('operator_uuid')
            ->groupBy('operator_uuid')
            ->get()
            ->map(function($item) use ($date, $caisseUuid) {
                $opQuery = Transaction::whereDate('created_at', $date)
                    ->where('operator_uuid', $item->operator_uuid)
                    ->where('statut', 'VALIDEE');
                    
                if ($caisseUuid) {
                    $opQuery->where('caisse_uuid', $caisseUuid);
                }
                
                $operator = Operator::where('uuid', $item->operator_uuid)->first();
                
                return [
                    'operateur' => $operator->name ?? 'N/A',
                    'nombre_transactions' => $opQuery->count(),
                    'total_entrees' => (clone $opQuery)->where('sens', 'ENTREE')->sum('montant_total'),
                    'total_sorties' => (clone $opQuery)->where('sens', 'SORTIE')->sum('montant_total'),
                    'total_frais' => (clone $opQuery)->sum('frais')
                ];
            });
            
        return $operateurs;
    }
    
    private function getResumeByType($date, $caisseUuid = null)
    {
        $query = Transaction::whereDate('created_at', $date)
            ->where('statut', 'VALIDEE');
            
        if ($caisseUuid) {
            $query->where('caisse_uuid', $caisseUuid);
        }
        
        $types = $query->select('type')
            ->groupBy('type')
            ->get()
            ->map(function($item) use ($date, $caisseUuid) {
                $typeQuery = Transaction::whereDate('created_at', $date)
                    ->where('type', $item->type)
                    ->where('statut', 'VALIDEE');
                    
                if ($caisseUuid) {
                    $typeQuery->where('caisse_uuid', $caisseUuid);
                }
                
                $labels = [
                    'DEPOT' => 'Dépôts',
                    'RETRAIT' => 'Retraits',
                    'ENVOI_MTO' => 'Envois MTO',
                    'RETRAIT_MTO' => 'Réceptions MTO'
                ];
                
                return [
                    'type' => $labels[$item->type] ?? $item->type,
                    'nombre' => $typeQuery->count(),
                    'montant_total' => $typeQuery->sum('montant_total')
                ];
            });
            
        return $types;
    }
    
    private function getSyntheseFinanciere($date, $caisseUuid = null)
    {
        // Transactions
        $transactionsQuery = Transaction::whereDate('created_at', $date)
            ->where('statut', 'VALIDEE');
        if ($caisseUuid) $transactionsQuery->where('caisse_uuid', $caisseUuid);
        
        // Mouvements
        $mouvementsQuery = CaisseMouvement::whereDate('created_at', $date);
        if ($caisseUuid) {
            $mouvementsQuery->where(function($q) use ($caisseUuid) {
                $q->where('caisse_source_uuid', $caisseUuid)
                  ->orWhere('caisse_destination_uuid', $caisseUuid);
            });
        }
        
        return [
            'transactions' => [
                'total_entrees' => (clone $transactionsQuery)->where('sens', 'ENTREE')->sum('montant_total'),
                'total_sorties' => (clone $transactionsQuery)->where('sens', 'SORTIE')->sum('montant_total'),
                'total_frais' => (clone $transactionsQuery)->sum('frais'),
                'solde_net' => (clone $transactionsQuery)->where('sens', 'ENTREE')->sum('montant_total') - 
                               (clone $transactionsQuery)->where('sens', 'SORTIE')->sum('montant_total')
            ],
            'mouvements' => [
                'total_approvisionnements' => (clone $mouvementsQuery)->where('type', 'APPROVISIONNEMENT')->sum('montant_envoye'),
                'total_rapatriements' => (clone $mouvementsQuery)->where('type', 'RAPATRIEMENT')->sum('montant_envoye'),
                'solde_net' => (clone $mouvementsQuery)->where('type', 'RAPATRIEMENT')->sum('montant_envoye') - 
                               (clone $mouvementsQuery)->where('type', 'APPROVISIONNEMENT')->sum('montant_envoye')
            ],
            'global' => [
                'total_mouvements' => $this->calculerTotalGlobal($date, $caisseUuid)
            ]
        ];
    }
    
    private function calculerTotalGlobal($date, $caisseUuid = null)
    {
        $total = 0;
        
        // Transactions entrées
        $transactionsEntrees = Transaction::whereDate('created_at', $date)
            ->where('statut', 'VALIDEE')
            ->where('sens', 'ENTREE');
        if ($caisseUuid) $transactionsEntrees->where('caisse_uuid', $caisseUuid);
        $total += $transactionsEntrees->sum('montant_total');
        
        // Transactions sorties
        $transactionsSorties = Transaction::whereDate('created_at', $date)
            ->where('statut', 'VALIDEE')
            ->where('sens', 'SORTIE');
        if ($caisseUuid) $transactionsSorties->where('caisse_uuid', $caisseUuid);
        $total -= $transactionsSorties->sum('montant_total');
        
        // Mouvements entrants (rapatriements)
        $mouvementsEntrants = CaisseMouvement::whereDate('created_at', $date)
            ->where('type', 'RAPATRIEMENT')
            ->where('statut', 'RECU');
        if ($caisseUuid) $mouvementsEntrants->where('caisse_destination_uuid', $caisseUuid);
        $total += $mouvementsEntrants->sum('montant_recu');
        
        // Mouvements sortants (approvisionnements)
        $mouvementsSortants = CaisseMouvement::whereDate('created_at', $date)
            ->where('type', 'APPROVISIONNEMENT')
            ->where('statut', 'EN_TRANSIT');
        if ($caisseUuid) $mouvementsSortants->where('caisse_source_uuid', $caisseUuid);
        $total -= $mouvementsSortants->sum('montant_envoye');
        
        return $total;
    }
    
    private function getEcarts($date, $caisseUuid = null)
    {
        $query = CaisseEtat::whereDate('date_journee', $date)
            ->whereNotNull('ecart')
            ->where('ecart', '!=', 0);
            
        if ($caisseUuid) {
            $query->where('caisse_uuid', $caisseUuid);
        }
        
        $ecarts = $query->with('caisse')->get();
        
        return [
            'total_ecarts' => $ecarts->count(),
            'montant_total_ecarts' => $ecarts->sum('ecart'),
            'details' => $ecarts->map(function($ecart) {
                return [
                    'caisse' => $ecart->caisse->libelle ?? 'N/A',
                    'solde_theorique' => $ecart->solde_theorique,
                    'solde_physique' => $ecart->solde_physique,
                    'ecart' => $ecart->ecart,
                    'justification' => $ecart->justification_ecart
                ];
            })
        ];
    }
    
    private function getActiviteCaisses($date, $caisseUuid = null)
    {
        $query = Caisse::where('isActive', true);
        
        if ($caisseUuid) {
            $query->where('uuid', $caisseUuid);
        }
        
        $caisses = $query->get();
        
        return $caisses->map(function($caisse) use ($date) {
            $transactions = Transaction::whereDate('created_at', $date)
                ->where('caisse_uuid', $caisse->uuid)
                ->where('statut', 'VALIDEE');
                
            $etat = CaisseEtat::whereDate('date_journee', $date)
                ->where('caisse_uuid', $caisse->uuid)
                ->first();
                
            return [
                'nom' => $caisse->libelle,
                'code' => $caisse->code,
                'statut' => $etat->statut ?? 'NON_OUVERTE',
                'nombre_transactions' => $transactions->count(),
                'montant_total' => $transactions->sum('montant_total'),
                'solde_actuel' => $caisse->solde_theorique
            ];
        });
    }
}


