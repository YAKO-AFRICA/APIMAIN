<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TypeOperation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class TypeOperationController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);

        $list = TypeOperation::orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Liste des types d\'opérations',
            'total' => $list->total(),
            'data' => $list
        ], 200);
    }

    public function store(Request $request)
    {

        Log::info($request->all());

        Log::info('debut store type operation API');

        DB::beginTransaction();
        Log::info('avant Db transact type operation API');
        try {
            Log::info('Db transact type operation API');
            $code = Refgenerate(TypeOperation::class, 'TO', 'code');
            $typeOperation = TypeOperation::create([
                'uuid' => Str::uuid(),
                'code' => $code,
                'libelle' => $request->libelle,
                'category' => $request->category,
                'mouvement' => $request->mouvement,
                'description' => $request->description ?? null,
                'isActive' =>  true
            ]);

            DB::commit();

            Log::info('Db transact commit  type operation API');

            return response()->json([
                'success' => true,
                'message' => 'Type opération créé avec succès.',
                'data' => $typeOperation
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $uuid)
    {
        $type = TypeOperation::where('uuid', $uuid)->first();

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Type opération introuvable.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $type->update(
                [
                'libelle' => $request->libelle ?? $type->libelle,
                'description' => $request->description ?? $type->description,
                'isActive' => $request->isActive ?? $type->isActive,
                ]
            );

            DB::commit(); 

            return response()->json([
                'success' => true,
                'message' => 'Type opération mis à jour avec succès.',
                'data' => $type
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $uuid)
     {
        $type = TypeOperation::where('uuid', $uuid)->first();

        Log::info($type);

        if (!$type) {
            Log::info('Type opération introuvable.');
            return response()->json([
                'success' => false,
                'message' => 'Type opération introuvable.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            Log::info('Suppression type opération API');
            $type->update([
                'isActive' => false
            ]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Type opération supprimé avec succès.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

   

    public function activate(string $uuid)
    {
        $type = TypeOperation::where('uuid', $uuid)->first();

        if (!$type) {
            return response()->json([
                'success' => false,
                'message' => 'Type opération introuvable.'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $type->update([
                'isActive' => true
            ]);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Type opération activé avec succès.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'activation.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
