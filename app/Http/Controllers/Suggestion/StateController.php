<?php

namespace App\Http\Controllers\Suggestion;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ESuggestion;
use App\Models\QrCode;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StateController extends Controller
{
    public function State(Request $request)
    {
        $query = ESuggestion::query();

        // filtre sur l'etat de la suggestion
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        // filtre par etat
        if ($request->has('etat')) {
            $query->where('etat', $request->etat);
        }

        // filtre par category
        if ($request->has('uuid_category')) {
            $query->where('uuid_category', $request->uuid_category);
        }

        // filtre par note
        if ($request->has('note')) {
            $query->where('note', $request->note);
        }

        // filtre par plage de date de creation
        // if ($request->filled('date_debut') && $request->filled('date_fin')) {
        //     $query->whereBetween('created_at', [
        //         $request->date_debut,
        //         $request->date_fin,
        //     ]);
        // }
        // filtre par plage de date de creation
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->date_debut)->startOfDay(),
                Carbon::parse($request->date_fin)->endOfDay(),
            ]);
        }

        // filtre par agence lier au qrcode
        if ($request->has('agency_code')) {
            $qrCodePluckUuid = QrCode::where('agency_code', $request->agency_code)->pluck('uuid');
            $query->whereIn('uuid_qrcode', $qrCodePluckUuid);
            
        }

        // calcule de la note moyenne des suggestions
        $query->selectRaw('AVG(note) as note_moyenne');

        // calcule des satisfaction moyenne des suggestions
        $query->selectRaw('AVG(CASE WHEN note >= 4 THEN 1 ELSE 0 END) as satisfaction_moyenne');

        // nombre de suggestion enregistrées
        $query->selectRaw('count(*) as nombre_total_suggestions');

        // compter toute les fois ou un qr code a été scanné
        if ($request->has('agency_code')) {
            $qrCodeScan = QrCode::select('scan_count', 'agency_code')->where('agency_code', $request->agency_code)->sum('scan_count');
            $query->selectRaw($qrCodeScan . ' as nombre_total_qr_code_scan');
        } else {
            $qrCodeScan = QrCode::select('scan_count')->sum('scan_count');
            $query->selectRaw($qrCodeScan . ' as nombre_total_qr_code_scan');
        }

        // calcule du taux de particapation des suggestions par rapport au nombre de scan de qr code
        $query->selectRaw('CASE WHEN ' . $qrCodeScan . ' > 0 THEN (count(*) / ' . $qrCodeScan . ') * 100 ELSE 0 END as taux_participation');

        // count des suggestion avec note inferieure ou egale a 2
        $query->selectRaw('count(CASE WHEN note <= 2 THEN 1 END) as nombre_suggestions_negatives');

        // calcule du taux de traitement
        $query->selectRaw('CASE WHEN count(*) > 0 THEN (count(CASE WHEN statut IN ("traited", "closed") THEN 1 END) / count(*)) * 100 ELSE 0 END as taux_traitement');




        return $query->get();
    }

    public function getCategoryStatistics(Request $request)
    {
        $query = ESuggestion::query();


        // filtre suggestion par agence lier au qrcode
        if ($request->has('agency_code')) {
            $qrCodePluckUuid = QrCode::where('agency_code', $request->agency_code)->pluck('uuid');
            $query->whereIn('uuid_qrcode', $qrCodePluckUuid);
        }

        

        // Get category statistics
        $categoryStats = $query
            ->select(
                'uuid_category',
                DB::raw('COUNT(*) as frequency'),
                DB::raw('AVG(note) as average_note'),
                DB::raw('COUNT(CASE WHEN note <= 2 THEN 1 END) as negative_count'),
                DB::raw('COUNT(CASE WHEN note >= 4 THEN 1 END) as positive_count')
            )
            ->groupBy('uuid_category')
            ->orderBy('frequency', 'DESC')
            ->get();

            

        // If you want to join with category table to get category names
        // Assuming you have a Category model
        $categoryNames = Category::whereIn('uuid', $categoryStats->pluck('uuid_category'))
            ->pluck('libelle', 'uuid');

        

        // Format the response
        $formattedStats = $categoryStats->map(function ($item) use ($categoryNames) {
            $total = $item->frequency;
            return [
                'uuid_category' => $item->uuid_category,
                'category_name' => $categoryNames[$item->uuid_category] ?? 'Unknown',
                'frequency' => $total,
                'percentage' => 0, // Will be calculated after total is known
                'average_note' => round($item->average_note, 2),
                'negative_count' => $item->negative_count,
                'positive_count' => $item->positive_count,
                'negative_rate' => $total > 0 ? round(($item->negative_count / $total) * 100, 2) : 0,
                'positive_rate' => $total > 0 ? round(($item->positive_count / $total) * 100, 2) : 0,
            ];
        });

        // Calculate total and percentages
        $totalSuggestions = $formattedStats->sum('frequency');
        $formattedStats = $formattedStats->map(function ($item) use ($totalSuggestions) {
            $item['percentage'] = $totalSuggestions > 0 ? round(($item['frequency'] / $totalSuggestions) * 100, 2) : 0;
            return $item;
        });

        return response()->json([
            'categories' => $formattedStats,
            'total_suggestions' => $totalSuggestions,
            'total_categories' => $formattedStats->count(),
        ]);
    }
}
