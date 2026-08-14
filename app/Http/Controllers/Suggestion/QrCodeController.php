<?php

namespace App\Http\Controllers\Suggestion;

use App\Http\Controllers\Controller;
use App\Models\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;// ou le chemin correct
class QrCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {


        try {
            $etat = $request->query('etat');
            $search = $request->query('search');
            $agencyCode = $request->query('agency_code');
            $sortBy = $request->query('sort_by', 'created_at');
            $sortOrder = $request->query('sort_order', 'desc');
            $perPage = $request->query('per_page', 15);
            $all = $request->query('all', false);

            Log::info('Fetching QR codes', [
                'filters' => $request->all(),
            ]);

            $query = QrCode::query();

            // Filtre par état
            if ($etat && in_array($etat, ['actif', 'inactif'])) {
                $query->where('etat', $etat);
            }

            // Filtre par code d'agence
            if ($agencyCode) {
                $query->where('agency_code', $agencyCode);
            }

            // Recherche textuelle
            if ($search) {
                $query->search($search);
            }

            // Tri
            $allowedSorts = ['code', 'agency_code', 'scan_count', 'etat', 'created_at', 'updated_at'];
            if (in_array($sortBy, $allowedSorts)) {
                $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
            }

            // Sélection des champs
            $query->select([
                'uuid',
                'code',
                'agency_code',
                'link',
                'etat',
                'scan_count',
                'created_at',
                'updated_at'
            ]);

            // Pagination ou collection
            if (filter_var($all, FILTER_VALIDATE_BOOLEAN)) {
                $qrCodes = $query->get();
                return response()->json([
                    'success' => true,
                    'message' => 'QR codes récupérés avec succès',
                    'data' => $qrCodes
                ], 200);
            }

            $qrCodes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'QR codes récupérés avec succès',
                'data' => $qrCodes->items(),
                'meta' => [
                    'current_page' => $qrCodes->currentPage(),
                    'last_page' => $qrCodes->lastPage(),
                    'per_page' => $qrCodes->perPage(),
                    'total' => $qrCodes->total(),
                    'filters' => [
                        'etat' => $etat,
                        'agency_code' => $agencyCode,
                        'search' => $search,
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching QR codes: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des QR codes',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) // param code agence 'agency_code' dans le body de la requete
    {
        try {
            DB::beginTransaction();

            $agenceCode = $request->input('agency_code');

            $qrCodeAgenceExiste = QrCode::where('agency_code', $agenceCode)->first();
            if($qrCodeAgenceExiste){
                return response()->json([
                    'success' => false,
                    'message' => 'un qr code pour cette agence existe deja avec le code ',
                    'data' => $qrCodeAgenceExiste
                ], 400);
            }

            $code = Refgenerate(QrCode::class, 'QRCODE', 'code');
            $uuid = Str::uuid();
            Log::info('Generated QR code: ' . $code);
            $link = 'https://assfin.yakoafricassur.com/qr/' . $uuid;
            $qrCode = QrCode::create([
                'uuid' => $uuid,
                'code' => $code,
                'agency_code' => $agenceCode,
                'link' => $link,
            ]);

            DB::commit();

            if ($qrCode) {
                return response()->json([
                    'success' => true,
                    'message' => 'QR code generé avec success.',
                    'link' => $link,
                    'data' => $qrCode,
                ], 201);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors de la generation du QR code.',
                ], 500);
            }
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la generation du QR code.' . $e->getMessage(),
            ], 500);
        }
    }

    public function countScan(string $uuid)
    {
        try {
            $qrCode = QrCode::where('uuid', $uuid)->first();

            if (!$qrCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code non trouvé.',
                ], 404);
            }

            // Incrémenter le compteur de scans
            $qrCode->increment('scan_count');

            return response()->json([
                'success' => true,
                'message' => 'Compteur de scans mis à jour avec succès.',
                'data' => $qrCode,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du compteur de scans: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function changeEtat(string $uuid)
    {
        try {
            $qrCode = QrCode::where('uuid', $uuid)->first();

            if (!$qrCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code non trouvé.',
                ], 404);
            }

            // changement d'etat du QR code
            $qrCode->etat = $qrCode->etat === 'actif' ? 'inactif' : 'actif';
            $qrCode->save();

            return response()->json([
                'success' => true,
                'message' => 'État du QR code mis à jour avec succès.',
                'data' => $qrCode,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de l\'état du QR code: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {
            $qrCode = QrCode::where('uuid', $uuid)->first();

            if (!$qrCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'QR code non trouvé.',
                ], 404);
            }

            $qrCode->delete();

            return response()->json([
                'success' => true,
                'message' => 'QR code supprimé avec succès.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du QR code: ' . $e->getMessage(),
            ], 500);
        }
    }
}
