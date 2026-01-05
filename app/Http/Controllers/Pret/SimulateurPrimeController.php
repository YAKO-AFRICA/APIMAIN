<?php

namespace App\Http\Controllers\Pret;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SimulateurPrimeController extends Controller
{

    public function simulatePrime(Request $request)
    {
        try {
            // Validation des données
            $request->validate([
                'montant_pret' => 'required|numeric|min:1',
                'formule'      => 'required|string', // Gard Or | Cash Auto
            ]);

            $montantPret = $request->montant_pret;
            $formule     = $request->formule;

            // Paramètres fixes
            $taux = 0.0035;
            $dureeMax = 6;

            if (!in_array($formule, ['Gard Or', 'Cash Auto'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Formule invalide. Choisissez entre "Gard Or" ou "Cash Auto".',
                ], 400);
            }

            if ($formule == 'Gard Or' && $montantPret > 5000000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant du prêt dépasse la limite maximale de 5 000 000 pour la formule Gard Or. Veuillez contactez YAKO AFRICA ',
                ], 400);
            }

            if ($formule == 'Cash Auto' && $montantPret > 10000000) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant du prêt dépasse la limite maximale de 10 000 000 pour la formule Cash Auto. Veuillez contactez YAKO AFRICA ',
                ], 400);
            }

            // Calcul de la prime
            $prime = $montantPret * $taux;

            return response()->json([
                'success' => true,
                'formule' => $formule,
                'montant_pret' => $montantPret,
                'taux' => '0,35 %',
                'duree_max' => $dureeMax . ' mois',
                'prime' => round($prime, 2),
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul de la prime',
                'error' => $th->getMessage(),
            ], 500);
        }
    }

}
