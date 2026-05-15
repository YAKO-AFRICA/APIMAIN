<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use App\Models\TblReseauProduct;
use Illuminate\Http\Request;

class SouscriptionController extends Controller
{
    public function calculHomeData($idmembre)
    {
        $userId = $idmembre;
        $year = now()->year;

        $contratsYear = Contrat::where('saisiepar', $userId)
            ->whereYear('saisiele', $year);

        $contratsTransmisYear = Contrat::where('saisiepar', $userId)
            ->whereYear('saisiele', $year)->whereNotNull('transmisle')->count();

        /* ========================
        COUNTS RAPIDES
        ========================*/
        $counts = Contrat::selectRaw("
                COUNT(*) as total,
                SUM(etape = 2) as transmis_actif,
                SUM(etape = 3) as acceptes,
                SUM(etape = 4) as rejetes
            ")
            ->where('saisiepar', $userId)
            ->whereYear('saisiele', $year)
            ->first();

        /* ========================
        PRIME
        ========================*/
        $primeYearCumule = (clone $contratsYear)
            ->where('etape', 3)
            ->sum('primepricipale');

        $primeMonthCumule = (clone $contratsYear)
            ->where('etape', 3)
            ->whereMonth('accepterle', now()->month)
            ->sum('primepricipale');

        /* ========================
        CHART SEMAINE (GROUP BY)
        ========================*/
        $weekData = Contrat::selectRaw("
                DATE(transmisle) as date,
                COUNT(*) as total
            ")
            ->where('saisiepar', $userId)
            ->whereBetween('transmisle', [now()->startOfWeek(), now()->endOfWeek()])
            ->groupBy('date')
            ->pluck('total','date');

        $weekAccept = Contrat::selectRaw("
                DATE(accepterle) as date,
                COUNT(*) as total
            ")
            ->where('saisiepar', $userId)
            ->where('etape',3)
            ->whereBetween('accepterle', [now()->startOfWeek(), now()->endOfWeek()])
            ->groupBy('date')
            ->pluck('total','date');

        $chartWeekTransmis = [];
        $chartWeekAcceptes = [];

        for($i=0;$i<7;$i++){
            $day = now()->startOfWeek()->addDays($i)->format('Y-m-d');
            $chartWeekTransmis[] = $weekData[$day] ?? 0;
            $chartWeekAcceptes[] = $weekAccept[$day] ?? 0;
        }

        /* ========================
        CHART MOIS (GROUP BY)
        ========================*/
        $monthData = Contrat::selectRaw("
            MONTH(transmisle) as month,
            COUNT(*) as total")
            ->where('saisiepar', $userId)
            ->whereYear('transmisle', now()->year)
            ->groupBy('month')
            ->pluck('total','month');

        $monthAccept = Contrat::selectRaw("
                MONTH(accepterle) as month,
                COUNT(*) as total
            ")
            ->where('saisiepar', $userId)
            ->where('etape',3)
            ->whereYear('accepterle', now()->year)
            ->groupBy('month')
            ->pluck('total','month');

        $chartMonthTransmis = [];
        $chartMonthAcceptes = [];

        for($i=1;$i<=12;$i++){
            $chartMonthTransmis[] = $monthData[$i] ?? 0;
            $chartMonthAcceptes[] = $monthAccept[$i] ?? 0;
        }

        /* ========================
        TAUX
        ========================*/
        $transmis = $contratsTransmisYear;
        $acceptes = $counts->acceptes;
        $rejetes = $counts->rejetes;

        $tauxAcceptPercent = $transmis > 0 ? round(($acceptes/$transmis)*100,2) : 0;
        $tauxRejetPercent = $transmis > 0 ? round(($rejetes/$transmis)*100,2) : 0;


        // calcule des produit les plus vendu dans l'annee avec cumule prime
        $produitsVendusYear = (clone $contratsYear)
            ->whereNotNull('transmisle')
            ->whereYear('transmisle', now()->year)
            ->groupBy('codeproduit', 'libelleproduit')
            ->selectRaw("
                codeproduit,
                libelleproduit,
                COUNT(*) as total,
                SUM(primepricipale) as primeCumule
            ")
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();


        $produitVendusMonth = (clone $contratsYear)
            ->whereNotNull('transmisle')
            ->whereMonth('accepterle', now()->month)
            ->whereYear('accepterle', now()->year)
            ->groupBy('codeproduit', 'libelleproduit')
            ->selectRaw("
                codeproduit,
                libelleproduit,
                COUNT(*) as total,
                SUM(primepricipale) as primeCumule
            ")
            ->orderBy('total','desc')
            ->limit(5)
            ->get();

        return response()->json([
            'idmembre' => $userId,
            'contratsYear' => $counts->total,
            'transmisActifYear' => $counts->transmis_actif,
            'transmisYear' => $contratsTransmisYear,
            'accepteYear' => $acceptes,
            'rejetesYear' => $rejetes,
            'tauxAcceptPercent' => $tauxAcceptPercent,
            'tauxRejetPercent' => $tauxRejetPercent,
            'primeYearCumule' => $primeYearCumule,
            'primeMonthCumule' => $primeMonthCumule,
            'chart' => [
                'week'=>[
                    'labels'=>['Lun','Mar','Mer','Jeu','Ven','Sam','Dim'],
                    'transmis'=>$chartWeekTransmis,
                    'acceptes'=>$chartWeekAcceptes
                ],
                'month'=>[
                    'labels'=> ['Janv','Fev','Mars','Avr','Mai','Juin','Juil','Aout','Sept','Oct','Nov','Dec'],
                    'transmis'=>$chartMonthTransmis,
                    'acceptes'=>$chartMonthAcceptes
                ]
            ],
            'produits' => [
                'year' => $produitsVendusYear,
                'month' => $produitVendusMonth
            ],
        ]);
    }

    


}


