<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TblFacture;
use App\Models\TblPaiement;
use App\Services\EncaissementBisService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaiementController extends Controller
{

    public function __construct(protected EncaissementBisService $encaissementBis) {}
    // public function savePaiementOM(Request $request)
    // {
    //     Log::info('Data paiement OM :', $request->all());
    //     DB::beginTransaction();
    //     try {  
    //         // Récupérer les données depuis les paramètres de la requête
    //         $idTransaction = $request->om_id_transaction;
    //         $amount = $request->amount;
    //         $idcontrat = $request->id_contrat;
    //         $nombreDePrime = $request->nbre_facture;
    //         $dateTransaction = $request->date_transaction;
    //         $telpaiement = $request->tel_paiement;
    //         $payment_status = strtolower($request->status);

    //         $factures = $request->id_factures;

    //         // Data paiement OM : {"om_id_transaction":"transactionID27","amount":"10.00","id_contrat":"2757659","date_transaction":"2026-08-27T15:14:29.000Z","status":"SUCCESS","nbre_facture":2,"id_factures":["29656671","49656672"],"tel_paiement":"0757391917"}   

    //         $result = $this->encaissementBis->getContrat($idcontrat);
    //         $data = $result['data'];


    //         $saving = TblPaiement::create([
    //             'codePaiement' => $idTransaction,
    //             'montant' => $amount,
    //             'telpaiement' => $telpaiement,
    //             'datepaiement' => $dateTransaction,
    //             'etat' => 2,
    //             'payment_mode' => 'orange',
    //             'paid_sum' => $amount,
    //             'paid_amount' => $amount,
    //             'payment_token' => $idTransaction,
    //             'payment_status' => $payment_status,
    //             'command_number' => $idTransaction,
    //             'payment_validation_date' => $dateTransaction,
    //             'typePaiement' => 1,
    //             'typeReglement' => 'recoveryPrime',
    //             'idproposition' => $idcontrat,
    //             'referenceSource' => $idTransaction,
    //             'reglementSource' => 'MAXIT',
    //             'nombreDePrime' => (int) $nombreDePrime,
    //             'saisiele' => Carbon::now()->format('Y-m-d H:i:s'),
    //             'reponse_webhook' = ['webhook' => $request->all(), 'date_maj' => now()],

    //             ]);

    //         foreach ($factures as $ligne) {

    //             // Filtrer les factures par période
    //             $PrimeNonRegles = collect($data['enc']['nonRegle'] ?? [])
    //             ->where('IdPresentation', )
    //                 ->values()
    //                 ->map(function ($PrimeNonRegle) {
    //                     $typeFacture = $PrimeNonRegle['TypePresentation'] ?? null;
    //                     $dateCreation = $PrimeNonRegle['MaDate'] ?? null;
    //                     $dateFormatted = $this->parseDate($dateCreation);

    //                     return [
    //                         'IdFacture' => $PrimeNonRegle['IdPresentation'] ?? null,
    //                         'DateCreation' => $dateCreation,
    //                         'DateCreationFormatted' => $dateFormatted,
    //                         'MontantARegler' => (float) ($PrimeNonRegle['MontantNet'] ?? 0),
    //                         'TypeFacture' => $typeFacture,
    //                     ];
    //             })->toArray();

    //             TblFacture::create([
    //                 'idProposition' => ,
    //                 'codePaiement' => $idTransaction,
    //                 'prime' => ,
    //                 'typeFacture' => , 
    //                 'etat' => 2, 
    //                 'dateAjout' => Carbon::now()->format('Y-m-d H:i:s'),
    //                 'typePaiement' => 1, 
    //                 'idcontrat' => $idcontrat,
    //                 'saisiele' => Carbon::now()->format('Y-m-d H:i:s'),
    //             ]);
    //         }

    //         DB::commit();

    //         if (!$saving) {
    //             return response()->json([
    //                 'success' => false,
    //                 'code' => 500,
    //                 'message' => 'Une erreur est survenue lors de l’enregistrement du paiement'
    //             ], 500);
    //         }
    //         return response()->json([
    //             'success' => true,
    //             'callback_status' => $payment_status,
    //             'message' => 'Paiement enregistré avec succès',
    //             'code' => 200
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Une erreur est survenue: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }


    public function savePaiementOM(Request $request)
    {
        Log::info('Data paiement OM :', $request->all());

        $request->validate([
            'om_id_transaction' => 'required|string',
            'amount' => 'required',
            'id_contrat' => 'required',
            'date_transaction' => 'required',
            'status' => 'required|string',
            'nbre_facture' => 'required|integer|min:1',
            'id_factures' => 'required|array|min:1',
            'id_factures.*' => 'required',
            'tel_paiement' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $idTransaction = $request->input('om_id_transaction');
            $amount = (float) $request->input('amount');
            $idcontrat = $request->input('id_contrat');
            $nombreDePrime = (int) $request->input('nbre_facture');
            $dateTransaction = $request->input('date_transaction');
            $telpaiement = $request->input('tel_paiement');
            $paymentStatus = strtolower($request->input('status'));
            $factures = $request->input('id_factures', []);

            /*
        |--------------------------------------------------------------------------
        | Vérification statut paiement
        |--------------------------------------------------------------------------
        */

            if ($paymentStatus !== 'success') {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'callback_status' => $paymentStatus,
                    'message' => 'Le paiement Orange Money n\'est pas en succès.'
                ], 400);
            }

            /*
        |--------------------------------------------------------------------------
        | Vérification transaction déjà enregistrée
        |--------------------------------------------------------------------------
        */

            $existingPayment = TblPaiement::where('codePaiement', $idTransaction)
                ->orWhere('payment_token', $idTransaction)
                // ->orWhere('referenceSource', $idTransaction)
                ->first();

            if ($existingPayment) {

                DB::rollBack();

                Log::warning('Transaction OM déjà enregistrée', [
                    'transaction' => $idTransaction
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Cette transaction a déjà été traitée.',
                    'code' => 200
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | Récupération contrat
        |--------------------------------------------------------------------------
        */

            $result = $this->encaissementBis->getContrat($idcontrat);

            if (!$result['success']) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Impossible de récupérer le contrat.'
                ], 404);
            }

            $data = $result['data'];

            /*
        |--------------------------------------------------------------------------
        | Vérification factures
        |--------------------------------------------------------------------------
        */

            $facturesNonReglees = collect(
                $data['enc']['nonRegle'] ?? []
            );

            $facturesTrouvees = $facturesNonReglees
                ->whereIn(
                    'IdPresentation',
                    array_map('strval', $factures)
                )
                ->values();

            /*
        |--------------------------------------------------------------------------
        | Vérifier que toutes les factures existent
        |--------------------------------------------------------------------------
        */

            $idsTrouves = $facturesTrouvees
                ->pluck('IdPresentation')
                ->map(fn($id) => (string) $id)
                ->toArray();

            $idsDemandes = array_map('strval', $factures);

            $facturesManquantes = array_diff(
                $idsDemandes,
                $idsTrouves
            );

            if (!empty($facturesManquantes)) {

                DB::rollBack();

                Log::warning('Certaines factures OM sont introuvables', [
                    'transaction' => $idTransaction,
                    'factures_demandees' => $idsDemandes,
                    'factures_manquantes' => $facturesManquantes
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Certaines factures sont introuvables ou déjà réglées.',
                    'factures_manquantes' => array_values($facturesManquantes)
                ], 422);
            }

            /*
        |--------------------------------------------------------------------------
        | Calcul montant réel des factures
        |--------------------------------------------------------------------------
        */

            $montantFactures = $facturesTrouvees->sum(
                fn($facture) => (float) ($facture['MontantNet'] ?? 0)
            );

            /*
        |--------------------------------------------------------------------------
        | Enregistrement paiement
        |--------------------------------------------------------------------------
        */

            $saving = TblPaiement::create([

                'codePaiement' => $idTransaction,

                'montant' => $amount,

                'telpaiement' => $telpaiement,

                'datepaiement' => Carbon::parse($dateTransaction)
                    ->format('Y-m-d H:i:s'),

                'etat' => 2,

                'payment_mode' => 'orange',

                'paid_sum' => $amount,

                'paid_amount' => $amount,

                'payment_token' => $idTransaction,

                'payment_status' => $paymentStatus,

                'command_number' => $idTransaction,

                'payment_validation_date' => Carbon::parse($dateTransaction)
                    ->format('Y-m-d H:i:s'),

                'typePaiement' => 1,

                'typeReglement' => 'recoveryPrime',

                'idproposition' => $idcontrat,

                // 'referenceSource' => $idTransaction,

                'reglementSource' => 'MAXIT',

                'nombreDePrime' => $nombreDePrime,

                'saisiele' => Carbon::now()
                    ->format('Y-m-d H:i:s'),

                'reponse_webhook' => json_encode([
                    'webhook' => $request->all(),
                    'date_maj' => now()->toDateTimeString()
                ]),
            ]);

            /*
        |--------------------------------------------------------------------------
        | Enregistrement des factures
        |--------------------------------------------------------------------------
        */

            foreach ($facturesTrouvees as $facture) {

                TblFacture::create([

                    'idProposition' => $facture['IdPresentation'] ?? null,

                    'codePaiement' => $idTransaction,

                    'prime' => (float) ($facture['MontantNet'] ?? 0),

                    'typeFacture' => $facture['TypePresentation'] ?? null,

                    'etat' => 2,

                    'dateAjout' => Carbon::now()
                        ->format('Y-m-d H:i:s'),

                    'typePaiement' => 1,

                    'idcontrat' => $idcontrat,

                    'referenceSource' => $facture['IdPresentation'] ?? null,

                    'saisiele' => Carbon::now()
                        ->format('Y-m-d H:i:s'),
                ]);
            }

            DB::commit();

            Log::info('Paiement OM enregistré avec succès', [

                'transaction' => $idTransaction,

                'contrat' => $idcontrat,

                'montant_recu' => $amount,

                'montant_factures' => $montantFactures,

                'nombre_factures' => count($factures),

                'factures' => $idsDemandes
            ]);

            return response()->json([

                'success' => true,

                'callback_status' => $paymentStatus,

                'message' => 'Paiement enregistré avec succès',

                'transaction' => $idTransaction,

                'nombre_factures' => count($factures),

                'montant_paye' => $amount,

                'montant_factures' => $montantFactures,

                'code' => 200

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Erreur enregistrement paiement OM', [

                'message' => $e->getMessage(),

                'file' => $e->getFile(),

                'line' => $e->getLine(),

                'transaction' =>
                $request->input('om_id_transaction'),

                'data' => $request->all()
            ]);

            return response()->json([

                'success' => false,

                'message' => 'Une erreur est survenue lors du traitement du paiement.'

                // En développement uniquement :
                // 'error' => $e->getMessage()

            ], 500);
        }
    }

    public function savePaiementWave(Request $request)
    {
        Log::info('Data paiement Wave :', $request->all());

        $request->validate([
            'wave_id_transaction' => 'required|string',
            'amount' => 'required',
            'id_contrat' => 'required',
            'date_transaction' => 'required',
            'status' => 'required|string',
            'nbre_facture' => 'required|integer|min:1',
            'id_factures' => 'required|array|min:1',
            'id_factures.*' => 'required',
            'tel_paiement' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {

            $idTransaction = $request->input('wave_id_transaction');
            $amount = (float) $request->input('amount');
            $idcontrat = $request->input('id_contrat');
            $nombreDePrime = (int) $request->input('nbre_facture');
            $dateTransaction = $request->input('date_transaction');
            $telpaiement = $request->input('tel_paiement');
            $paymentStatus = strtolower($request->input('status'));
            $factures = $request->input('id_factures', []);

            /*
        |--------------------------------------------------------------------------
        | Vérification statut paiement
        |--------------------------------------------------------------------------
        */

            if ($paymentStatus !== 'success') {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'callback_status' => $paymentStatus,
                    'message' => 'Le paiement Orange Money n\'est pas en succès.'
                ], 400);
            }

            /*
        |--------------------------------------------------------------------------
        | Vérification transaction déjà enregistrée
        |--------------------------------------------------------------------------
        */

            $existingPayment = TblPaiement::where('codePaiement', $idTransaction)
                ->orWhere('payment_token', $idTransaction)
                // ->orWhere('referenceSource', $idTransaction)
                ->first();

            if ($existingPayment) {

                DB::rollBack();

                Log::warning('Transaction Wave déjà enregistrée', [
                    'transaction' => $idTransaction
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Cette transaction a déjà été traitée.',
                    'code' => 200
                ]);
            }

            /*
        |--------------------------------------------------------------------------
        | Récupération contrat
        |--------------------------------------------------------------------------
        */

            $result = $this->encaissementBis->getContrat($idcontrat);

            if (!$result['success']) {

                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'Impossible de récupérer les données du contrat.'
                ], 404);
            }

            $data = $result['data'];

            /*
        |--------------------------------------------------------------------------
        | Vérification factures
        |--------------------------------------------------------------------------
        */

            $facturesNonReglees = collect(
                $data['enc']['nonRegle'] ?? []
            );

            $facturesTrouvees = $facturesNonReglees
                ->whereIn(
                    'IdPresentation',
                    array_map('strval', $factures)
                )
                ->values();

            /*
        |--------------------------------------------------------------------------
        | Vérifier que toutes les factures existent
        |--------------------------------------------------------------------------
        */

            $idsTrouves = $facturesTrouvees
                ->pluck('IdPresentation')
                ->map(fn($id) => (string) $id)
                ->toArray();

            $idsDemandes = array_map('strval', $factures);

            $facturesManquantes = array_diff(
                $idsDemandes,
                $idsTrouves
            );

            if (!empty($facturesManquantes)) {

                DB::rollBack();

                Log::warning('Certaines factures Wave sont introuvables', [
                    'transaction' => $idTransaction,
                    'factures_demandees' => $idsDemandes,
                    'factures_manquantes' => $facturesManquantes
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Certaines factures sont introuvables ou déjà réglées.',
                    'factures_manquantes' => array_values($facturesManquantes)
                ], 422);
            }

            /*
        |--------------------------------------------------------------------------
        | Calcul montant réel des factures
        |--------------------------------------------------------------------------
        */

            $montantFactures = $facturesTrouvees->sum(
                fn($facture) => (float) ($facture['MontantNet'] ?? 0)
            );

            /*
        |--------------------------------------------------------------------------
        | Enregistrement paiement
        |--------------------------------------------------------------------------
        */

            $saving = TblPaiement::create([

                'codePaiement' => $idTransaction,

                'montant' => $amount,

                'telpaiement' => $telpaiement,

                'datepaiement' => Carbon::parse($dateTransaction)
                    ->format('Y-m-d H:i:s'),

                'etat' => 2,

                'payment_mode' => 'wave',

                'paid_sum' => $amount,

                'paid_amount' => $amount,

                'payment_token' => $idTransaction,

                'payment_status' => $paymentStatus,

                'command_number' => $idTransaction,

                'payment_validation_date' => Carbon::parse($dateTransaction)
                    ->format('Y-m-d H:i:s'),

                'typePaiement' => 1,

                'typeReglement' => 'recoveryPrime',

                'idproposition' => $idcontrat,

                // 'referenceSource' => $idTransaction,

                'reglementSource' => 'WAVE_DIRECT',

                'nombreDePrime' => $nombreDePrime,

                'saisiele' => Carbon::now()
                    ->format('Y-m-d H:i:s'),

                'reponse_webhook' => json_encode([
                    'webhook' => $request->all(),
                    'date_maj' => now()->toDateTimeString()
                ]),
            ]);

            /*
        |--------------------------------------------------------------------------
        | Enregistrement des factures
        |--------------------------------------------------------------------------
        */

            foreach ($facturesTrouvees as $facture) {

                TblFacture::create([

                    'idProposition' => $facture['IdPresentation'] ?? null,

                    'codePaiement' => $idTransaction,

                    'prime' => (float) ($facture['MontantNet'] ?? 0),

                    'typeFacture' => $facture['TypePresentation'] ?? null,

                    'etat' => 2,

                    'dateAjout' => Carbon::now()
                        ->format('Y-m-d H:i:s'),

                    'typePaiement' => 1,

                    'idcontrat' => $idcontrat,

                    'referenceSource' => $facture['IdPresentation'] ?? null,

                    'saisiele' => Carbon::now()
                        ->format('Y-m-d H:i:s'),
                ]);
            }

            DB::commit();

            Log::info('Paiement Wave enregistré avec succès', [

                'transaction' => $idTransaction,

                'contrat' => $idcontrat,

                'montant_recu' => $amount,

                'montant_factures' => $montantFactures,

                'nombre_factures' => count($factures),

                'factures' => $idsDemandes
            ]);

            return response()->json([

                'success' => true,

                'callback_status' => $paymentStatus,

                'message' => 'Paiement enregistré avec succès',

                'transaction' => $idTransaction,

                'nombre_factures' => count($factures),

                'montant_paye' => $amount,

                'montant_factures' => $montantFactures,

                'code' => 200

            ]);
        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Erreur enregistrement paiement OM', [

                'message' => $e->getMessage(),

                'file' => $e->getFile(),

                'line' => $e->getLine(),

                'transaction' =>
                $request->input('om_id_transaction'),

                'data' => $request->all()
            ]);

            return response()->json([

                'success' => false,

                'message' => 'Une erreur est survenue lors du traitement du paiement.'

            ], 500);
        }
    }
}
