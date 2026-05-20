<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TblPaiement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaiementController extends Controller
{
    public function savePaiementOM(Request $request)
    {
        DB::beginTransaction();
        try {  
            // Récupérer les données depuis les paramètres de la requête
            $idTransaction = $request->input('om_id_transaction');
            $amount = $request->input('amount');
            $yakoAfricaIdClient = $request->input('yako_africa_id_client');
            $dateTransaction = $request->input('date_transaction');
            $callbackStatus = $request->input('callback_status');

            $saving = TblPaiement::create([
                'codePaiement' => $idTransaction,
                'montant' => $amount,
                'telpaiement' => $yakoAfricaIdClient,
                'datepaiement' => $dateTransaction,
                'etat' => 2,
                'payment_mode' => 'OM',
                'paid_sum' => $amount,
                'paid_amount' => $amount,
                'payment_token' => $idTransaction,
                'payment_status' => $callbackStatus,
                'command_number' => $idTransaction,
                'payment_validation_date' => $dateTransaction,
                'typePaiement' => 1,
                // 'idproposition' => 0,
                'typeReference' => 0,
                'referenceSource' => $idTransaction,
                // 'nombreDePrime' => 0,
                // 'num_souscripteur' => 0,
                // 'frais_adhesion' => 0,
                // 'code_produit' => 0,
                // 'idmembre' => 0,
                // 'emailpayeur' => 0,
                'saisiele' => Carbon::now()->format('Y-m-d H:i:s'),
                // 'callback_status' => $callbackStatus

                ]);

            DB::commit();
            
            if (!$saving) {
                return response()->json([
                    'success' => false,
                    'code' => 500,
                    'message' => 'Une erreur est survenue lors de l’enregistrement du paiement'
                ], 500);
            }
            return response()->json([
                'success' => true,
                'callback_status' => $callbackStatus,
                'message' => 'Paiement enregistré avec succès',
                'code' => 200
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }
    }
}
