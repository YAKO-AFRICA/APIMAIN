<?php

namespace App\Http\Controllers\Suggestion;

use App\Http\Controllers\Controller;
use App\Models\ESuggestion;
use App\Models\SuggestionTreatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ESuggestionController extends Controller
{

    public function index(Request $request)
    {
        $query = ESuggestion::query();

        // Filtrage par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        // Filtrage par etat
        if ($request->has('etat')) {
            $query->where('etat', $request->input('etat'));
        }

        // Filtrage par date de creation
        if ($request->has('date_creation')) {
            $query->whereDate('created_at', $request->input('date_creation'));
        }

        // Filtrage par qrcode / agence
        if ($request->has('uuid_qrcode')) {
            $query->where('uuid_qrcode', $request->input('uuid_qrcode'));
        }
        // get sugestion note inferieure ou egale a request note
        if ($request->has('note')) {
            $query->where('note', '<=', $request->input('note'));
        }

        // Pagination
        $perPage = $request->input('per_page', 10);
        $suggestions = $query->with('treatments','qrCode')->paginate($perPage);

        return response()->json(
            [
                'success' => true,
                'message' => 'Liste des suggestions.',
                'data' => $suggestions,
                'total' => $suggestions->total(),
                'last_page' => $suggestions->lastPage(),
                'per_page' => $suggestions->perPage(),
                'total_pages' => $suggestions->lastPage()
            ]
        );
    }
    public function store(Request $request)
    {

        try {

            DB::connection('mysql')->beginTransaction();

            $code = Refgenerate(ESuggestion::class, 'SUG', 'code');

            $saveSuggestion = ESuggestion::create([
                'uuid' => Str::uuid(),
                'code' => $code,
                'uuid_qrcode' => $request->uuid_qrcode,
                'note' => $request->note,
                'uuid_category' => $request->uuid_category,
                'comment' => $request->comment,
                'nom_client' => $request->nom_client,
                'prenom_client' => $request->prenom_client,
                'tel_client' => $request->tel_client,
                'email_client' => $request->email_client,
                'statut' => 'new',
                'etat' => 'actif',
            ]);

            DB::connection('mysql')->commit();

            if ($saveSuggestion) {
                return response()->json([
                    'success' => true,
                    'message' => 'Suggestion creee avec success.',
                    'data' => $saveSuggestion
                ], 201);
            } else {
                DB::connection('mysql')->rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la creation de la suggestion.'
                ], 500);
            }

            // gestion de l(historique de l'action
            // $this->logAction('create', 'suggestion', $saveSuggestion->uuid)

        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la creation de la suggestion.',
                'error' => $e->getMessage()
            ]);
        }
        
    }

    public function show(string $uuid)
    {
        try {
            $ESuggestion = ESuggestion::where('uuid', $uuid)->with('treatments')->first();

            if (!$ESuggestion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suggestion non trouvée.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Détails de la suggestion.',
                'data' => $ESuggestion,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la suggestion: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function changeEtat(string $uuid)
    {
        try {
            $ESuggestion = ESuggestion::where('uuid', $uuid)->first();

            if (!$ESuggestion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suggestion non trouvée.',
                ], 404);
            }

            // changement d'etat de la suggestion
            $ESuggestion->etat = $ESuggestion->etat === 'actif' ? 'inactif' : 'actif';
            $ESuggestion->save();

            return response()->json([
                'success' => true,
                'message' => 'État de la suggestion mis à jour avec succès.',
                'data' => $ESuggestion,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'état de la suggestion: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(string $uuid)
    {
        try {
            $ESuggestion = ESuggestion::where('uuid', $uuid)->first();

            if (!$ESuggestion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Suggestion non trouvée.',
                ], 404);
            }

            $ESuggestion->delete();

            return response()->json([
                'success' => true,
                'message' => 'Suggestion supprimée avec succès.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la suggestion: ' . $e->getMessage(),
            ], 500);
        }
    }


    // gestion de l(historique de l'action de la suggestion
    // $this->logAction('delete', 'suggestion', $ESuggestion->uuid)

    public function treatmentSuggestion(Request $request)
    {
        try {

            $code = Refgenerate(SuggestionTreatment::class, 'TRT', 'code');

            $treatment = SuggestionTreatment::create([
                'uuid' => Str::uuid(),
                'code' => $code,
                'uuid_suggestion' => $request->uuid_suggestion,
                'code_responsable' => $request->code_responsable,
                'action' => $request->action,
                'assigned_by' => $request->assigned_by ?? null,
                'etat' => 'actif',
            ]);

            if($treatment) {
                return response()->json([
                    'success' => true,
                    'message' => 'Suggestion assignée avec succès.',
                    'data' => $treatment,
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'assignation de la suggestion.',
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'assignation de la suggestion: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getTreatmentsByParam(Request $request)
    {
        try {

            $query = SuggestionTreatment::query();

            // Filtrage par uuid_suggestion
            if ($request->has('uuid_suggestion')) {
                $query->where('uuid_suggestion', $request->input('uuid_suggestion'));
            }
            // Filtrage par etat
            if ($request->has('etat')) {
                $query->where('etat', $request->input('etat'));
            }
            // Filtrage par code_responsable
            if ($request->has('code_responsable')) {
                $query->where('code_responsable', $request->input('code_responsable'));
            }

            // Pagination
            $perPage = $request->input('per_page', 10);

            $treatments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Liste des traitements pour la suggestion.',
                'total' => $treatments->total(),
                'data' => $treatments,
                'last_page' => $treatments->lastPage(),
                'per_page' => $treatments->perPage(),
                'total_pages' => $treatments->lastPage(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des traitements: ' . $e->getMessage(),
            ], 500);
        }
    }
}
