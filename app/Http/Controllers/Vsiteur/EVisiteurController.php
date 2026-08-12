<?php

namespace App\Http\Controllers\Vsiteur;

use App\Http\Controllers\Controller;
use App\Models\Evisite;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EVisiteurController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Evisite::query();

            // Recherche par nom ou prénoms
            if ($request->filled('search')) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('nom', 'like', '%' . $searchTerm . '%')
                    ->orWhere('prenoms', 'like', '%' . $searchTerm . '%');
                });
            }

            // Filtre par code
            if ($request->filled('code')) {
                $query->where('code', $request->code);
            }

            // Filtre par motif_uuid
            if ($request->filled('motif_uuid')) {
                $query->where('motif_uuid', $request->motif_uuid);
            }

            // Filtrer les visiteurs par plage de date de visite
            if ($request->filled('date_debut') && $request->filled('date_fin')) {
                $query->whereBetween('date_de_visite', [
                    Carbon::parse($request->date_debut)->startOfDay(),
                    Carbon::parse($request->date_fin)->endOfDay(),
                ]);
            }

            // Filtrer par date de visite (date unique)
            if ($request->filled('date')) {
                $query->whereDate('date_de_visite', Carbon::parse($request->date)->toDateString());
            }

            // Filtrer par statut (si votre modèle en a un)
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }

            // Ajouter une option de tri
            $sortField = $request->input('sort_by', 'created_at');
            $sortDirection = $request->input('sort_direction', 'desc');


            // Pagination ou récupération de tous les résultats
            if ($request->has('per_page')) {
                $perPage = $request->input('per_page', 15);
                $visites = $query->paginate($perPage);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Liste des visiteurs',
                    'data' => $visites->items(),
                    'pagination' => [
                        'current_page' => $visites->currentPage(),
                        'last_page' => $visites->lastPage(),
                        'per_page' => $visites->perPage(),
                        'total' => $visites->total(),
                        'from' => $visites->firstItem(),
                        'to' => $visites->lastItem(),
                    ]
                ]);
            }

            // Si pas de pagination, retourner tous les résultats
            $visites = $query->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Liste des visiteurs',
                'data' => $visites,
                'total' => $visites->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des visiteurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($uuid)
    {
        try {
            // Vérifier que l'UUID est valide
            if (!Str::isUuid($uuid)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Format d\'UUID invalide.',
                ], 400);
            }

            // Récupérer le visiteur avec ses relations (si nécessaire)
            $visite = Evisite::where('uuid', $uuid)->first();

            if (!$visite) {
                return response()->json([
                    'success' => false,
                    'message' => 'Visiteur non trouvé.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Visiteur trouvé avec succès.',
                'data' => $visite,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du visiteur.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Génération du code
            $code = Refgenerate(Evisite::class, 'VIS', 'code');
            
            
            $visite = Evisite::create([
                'uuid' => Str::uuid(),
                'code' => $code,
                'nom' => $request->input('nom'),
                'prenoms' => $request->input('prenoms'),
                'mobile' => $request->input('mobile'),
                'email' => $request->input('email'),
                'motif_uuid' => $request->input('motif_uuid'),
                'personne_visite' => $request->input('personne_visite'),
                'date_de_visite' => $request->input('date_de_visite'),
                'nature_piece' => $request->input('nature_piece'),
                'num_piece' => $request->input('num_piece'),
                'agence' => $request->input('agence'),
                'notes' => $request->input('notes'),
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Visiteur créé avec succès.',
                'data' => $visite
            ], 201);

        } catch (\Throwable $th) {
            DB::rollBack();
            
            // Log de l'erreur pour le debugging
            Log::error('Erreur création visiteur', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'trace' => $th->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Une erreur s\'est produite lors de la création du visiteur.',
                'error' => config('app.debug') ? $th->getMessage() : 'Erreur interne du serveur'
            ], 500);
        }
    }
}
