<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MotifTraitement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MotifController extends Controller
{
    public function index(Request $request)
    {
        $query = MotifTraitement::query();

        if($request->has('systeme_used')) {
            $query->where('systeme_used', $request->systeme_used);
        }

        if($request->has('code')) {
            $query->where('code', $request->code);
        }

        if($request->has('etat')) {
            $query->where('etat', $request->etat);
        }

        $query->paginate(15);

        $motifs = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Liste des motifs',
            'total' => $motifs->count(),
            'data' => $motifs,
        ]);
    }

    public function show($uuid)
    {
        $motif = MotifTraitement::where('uuid', $uuid)->first();

        if(!$motif) {
            return response()->json([
                'success' => false,
                'message' => 'Motif introuvable',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Motif trouvé',
            'data' => $motif,
        ]);
    }

    public function store()
    {
        try {
            DB::beginTransaction();

            $code = Refgenerate(MotifTraitement::class, 'MT', 'code');

            $motifsaved = MotifTraitement::create([
                'uuid' => Str::uuid(),
                'code' => $code,
                'libelle' => request('libelle'),
                'systeme_used' => request('systeme_used'),
                'description' => request('description'),
                'etat' => 'actif',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Motif cree avec success.',
                'data' => $motifsaved,
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ]);
        }
    }

    
}
