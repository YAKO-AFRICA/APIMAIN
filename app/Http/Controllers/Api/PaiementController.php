<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TblFacture;
use App\Models\TblPaiement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaiementController extends Controller
{
    public function savePaiementOM(Request $request)
    {
        Log::info('Data paiement OM :', $request->all());
        // DB::beginTransaction();
        // try {  
        //     // Récupérer les données depuis les paramètres de la requête
        //     $idTransaction = $request->input('om_id_transaction');
        //     $amount = $request->input('amount');
        //     $yakoAfricaIdClient = $request->input('yako_africa_id_client');
        //     $nombreDePrime = $request->input('nbre_facture');
        //     $dateTransaction = $request->input('date_transaction');
        //     $telpaiement = $request->input('tel_paiement');
        //     $payment_status = $request->input('status');
        //     // $callbackStatus = $request->input('callback_status');

        //     $factures = $request->input('id_factures');




        //     // $saving = TblPaiement::create([
        //     //     'codePaiement' => $idTransaction,
        //     //     'montant' => $amount,
        //     //     'telpaiement' => $telpaiement,
        //     //     'datepaiement' => $dateTransaction,
        //     //     'etat' => 2,
        //     //     'payment_mode' => 'orange',
        //     //     'paid_sum' => $amount,
        //     //     'paid_amount' => $amount,
        //     //     'payment_token' => $idTransaction,
        //     //     'payment_status' => $payment_status,
        //     //     'command_number' => $idTransaction,
        //     //     'payment_validation_date' => $dateTransaction,
        //     //     'typePaiement' => 1,
        //     //     // 'idproposition' => 0,
        //     //     // 'typeReference' => 0,
        //     //     'referenceSource' => $idTransaction,
        //     //     'reglementSource' => 'MAXIT',
        //     //     'nombreDePrime' => (int) $nombreDePrime,
        //     //     // 'num_souscripteur' => 0,
        //     //     // 'frais_adhesion' => 0,
        //     //     // 'code_produit' => 0,
        //     //     // 'idmembre' => 0,
        //     //     // 'emailpayeur' => 0,
        //     //     'saisiele' => Carbon::now()->format('Y-m-d H:i:s'),

        //     //     ]);

        //     // foreach ($factures as $ligne) {
        //     //     TblFacture::create([
        //     //         'idProposition' => $preparation['idProposition'] ?? $preparation['contractId'] ?? null,
        //     //         'codePaiement' => $referenceInterne,
        //     //         'prime' => $ligne['prime'],
        //     //         'typeFacture' => $ligne['type'],
        //     //         'etat' => 1, // en attente de confirmation webhook pour passer à 2
        //     //         'dateAjout' => Carbon::now()->format('Y-m-d H:i:s'),
        //     //         'typePaiement' => 1, 
        //     //         'referenceSource' => $ligne['referenceOrigine'],
        //     //         'idcontrat' => $donnees['contractId'] ?? $donnees['idProposition'] ?? null,
        //     //         'saisiele' => Carbon::now()->format('Y-m-d H:i:s'),
        //     //     ]);
        //     // }

        //     // DB::commit();
            
        //     // if (!$saving) {
        //     //     return response()->json([
        //     //         'success' => false,
        //     //         'code' => 500,
        //     //         'message' => 'Une erreur est survenue lors de l’enregistrement du paiement'
        //     //     ], 500);
        //     // }
        //     return response()->json([
        //         'success' => true,
        //         'callback_status' => $payment_status,
        //         'message' => 'Paiement enregistré avec succès',
        //         'code' => 200
        //     ]);

        // } catch (\Exception $e) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Une erreur est survenue: ' . $e->getMessage()
        //     ], 500);
        // }
    }

    
}
