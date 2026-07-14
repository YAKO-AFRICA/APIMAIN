<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TblFacture;
use App\Models\TblPaiement;
use Illuminate\View\View;

/**
 * Affiche le reçu d'un paiement (firstPayment / earlyPayment / recoveryPrime).
 * La vue est imprimable (bouton "Télécharger" = window.print() -> "Enregistrer en PDF"),
 * ce qui évite d'imposer une dépendance PDF (dompdf, snappy...) si vous n'en avez pas déjà.
 * Si vous utilisez déjà barryvdh/laravel-dompdf, remplacez simplement le `return view(...)`
 * par un `PDF::loadView(...)->download(...)`.
 */
class ReceiptController extends Controller
{
    public function show(string $referenceInterne): View
    {
        $libellesTypeFacture = [
            'PRIME' => 'Prime principale',
            'FRAIS_ADHESION' => 'Frais d\'adhésion',
        ];

        $libellesType = [
            'firstPayment' => 'Premier paiement',
            'earlyPayment' => 'Paiement anticipé',
            'recoveryPrime' => 'Régularisation de primes',
        ];
        $paiement = TblPaiement::where('codePaiement', $referenceInterne)->firstOrFail();

        $factures = TblFacture::where('codePaiement', $referenceInterne)
            ->orderBy('dateAjout')
            ->get()
            ->map(function ($facture) use ($libellesTypeFacture) {
                $facture->libelleTypeFacture =
                    $libellesTypeFacture[$facture->typeFacture]
                    ?? $facture->typeFacture;

                return $facture;
            });

        

        return view('paiement.recu', [
            'paiement' => $paiement,
            'factures' => $factures,
            'libelleType' => $libellesType[$paiement->typePaiement] ?? $paiement->typePaiement,
        ]);
    }
}