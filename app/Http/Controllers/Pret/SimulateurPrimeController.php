<?php

namespace App\Http\Controllers\Pret;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SimulateurPrimeController extends Controller
{

    // public function simulatePrime(Request $request)
    // {
    //     try {
    //         // Validation des données
    //         $request->validate([
    //             'montant_pret' => 'required|numeric|min:1',
    //             'formule'      => 'required|string', // Gard Or | Cash Auto
    //         ]);

    //         $montantPret = $request->montant_pret;
    //         $formule     = $request->formule;

    //         // Paramètres fixes
    //         $taux = 0.0035;
    //         $dureeMax = 6;

    //         if (!in_array($formule, ['Gard Or', 'Cash Auto'])) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Formule invalide. Choisissez entre "Gard Or" ou "Cash Auto".',
    //             ], 400);
    //         }

    //         if ($formule == 'Gard Or' && $montantPret > 5000000) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Le montant du prêt dépasse la limite maximale de 5 000 000 pour la formule Gard Or. Veuillez contactez YAKO AFRICA ',
    //             ], 400);
    //         }

    //         if ($formule == 'Cash Auto' && $montantPret > 10000000) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Le montant du prêt dépasse la limite maximale de 10 000 000 pour la formule Cash Auto. Veuillez contactez YAKO AFRICA ',
    //             ], 400);
    //         }

    //         // Calcul de la prime
    //         $prime = $montantPret * $taux;

    //         return response()->json([
    //             'success' => true,
    //             'formule' => $formule,
    //             'montant_pret' => $montantPret,
    //             'taux' => '0,35 %',
    //             'duree_max' => $dureeMax . ' mois',
    //             'prime' => round($prime, 2),
    //         ]);

    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Erreur lors du calcul de la prime',
    //             'error' => $th->getMessage(),
    //         ], 500);
    //     }
    // }

    public function simulatePrime(Request $request)
    {
        try {
            // Validation
            $request->validate([
                'montant_pret' => 'required|numeric|min:1',
                'formule'      => 'required|string', // Cash Or | Cash Auto
                'age'          => 'required|integer|min:1',
                'duree'        => 'required|integer|min:1', // en mois
            ]);

            $C       = $request->montant_pret;
            $formule = $request->formule;
            $age     = $request->age;
            $duree   = $request->duree;

            if (!in_array($formule, ['Cash Or', 'Cash Auto'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formule invalide. Choisissez entre "Cash Or" ou "Cash Auto".',
                ], 400);
            }

            // Règles âge
            if ($age < 18) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pas de prêt possible pour un âge inférieur à 18 ans.',
                ], 400);
            }

            if ($age > 55) {
                return response()->json([
                    'success' => false,
                    'message' => 'Âge supérieur à 55 ans. Veuillez voir YAKO AFRICA.',
                ], 400);
            }

            // Règles durée
            if ($duree > 6) {
                return response()->json([
                    'success' => false,
                    'message' => 'Durée supérieure à 6 mois. Veuillez voir YAKO AFRICA.',
                ], 400);
            }

            // Calcul de la prime selon la formule
            $taux = null;

            if ($formule === 'Cash Or') {

                if ($C <= 5_000_000) {
                    $taux = 0.0035;
                } elseif ($C <= 15_000_000) {
                    $taux = 0.004;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Montant supérieur à 15 000 000. Veuillez voir YAKO AFRICA.',
                    ], 400);
                }

            } elseif ($formule === 'Cash Auto') {

                if ($C <= 10_000_000) {
                    $taux = 0.0035;
                } elseif ($C <= 25_000_000) {
                    $taux = 0.004;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Montant supérieur à 25 000 000. Veuillez voir YAKO AFRICA.',
                    ], 400);
                }
            }

            $prime = $C * $taux;

            return response()->json([
                'success'       => true,
                'formule'       => $formule,
                'age'           => $age,
                'duree'         => $duree . ' mois',
                'montant_pret'  => $C,
                'taux'          => ($taux * 100) . ' %',
                'prime'         => round($prime, 2),
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul de la prime',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }


}
