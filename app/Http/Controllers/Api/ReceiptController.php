<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TblFacture;
use App\Models\TblPaiement;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(string $referenceInterne): View
    {
        $libellesTypeFacture = [
            'N' => 'Prime principale',
            'F' => 'Frais d\'adhésion',
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
        
        $identifiantContrat = ($paiement->typeReglement === 'firstPayment') ? $paiement->idContrat : $paiement->idproposition;

        $fileName = 'recu-paiement-' . $paiement->codePaiement . '-' . $identifiantContrat . '.pdf';
        
        Log::info('fileName', ['fileName' => $fileName]);



        return view('paiement.recu', [
            'paiement' => $paiement,
            'factures' => $factures,
            'libelleType' => $libellesType[$paiement->typeReglement] ?? $paiement->typeReglement,
            'fileName' => $fileName
        ]);
    }
}
