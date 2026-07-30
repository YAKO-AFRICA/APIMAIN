<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TblFacture;
use App\Models\TblPaiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
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

        $fileName = 'recu-paiement-' . $paiement->codePaiement . '-' . $paiement->idContrat . '.pdf';



        return view('paiement.recu', [
            'paiement' => $paiement,
            'factures' => $factures,
            'libelleType' => $libellesType[$paiement->typeReglement] ?? $paiement->typeReglement,
            'fileName' => $fileName
        ]);
    }

    /**
     * Génère et télécharge le reçu en PDF.
     */
    // public function downloadPDF(string $referenceInterne)
    // {
    //     try {
    //         $paiement = TblPaiement::where('codePaiement', $referenceInterne)->firstOrFail();
    //         $libellesTypeFacture = [
    //             'N' => 'Prime principale',
    //             'F' => 'Frais d\'adhésion',
    //         ];

    //         $libellesType = [
    //             'firstPayment' => 'Premier paiement',
    //             'earlyPayment' => 'Paiement anticipé',
    //             'recoveryPrime' => 'Régularisation de primes',
    //         ];
    //         $libelleType = $libellesType[$paiement->typeReglement] ?? $paiement->typeReglement;

    //         $factures = TblFacture::where('codePaiement', $referenceInterne)
    //             ->orderBy('dateAjout')
    //             ->get()
    //             ->map(function ($facture) use ($libellesTypeFacture) {
    //                 $facture->libelleTypeFacture =
    //                     $libellesTypeFacture[$facture->typeFacture]
    //                     ?? $facture->typeFacture;

    //                 return $facture;
    //             });

    //         $pdf = Pdf::loadView('paiement.recu_pdf', compact('paiement', 'factures', 'libelleType'));
    //         $pdf->setPaper('A4', 'portrait');
    //         $pdf->setOptions([
    //             'defaultFont' => 'DejaVu Sans',
    //             'isRemoteEnabled' => false,
    //             'isHtml5ParserEnabled' => true,
    //             'isPhpEnabled' => false,
    //         ]);
    //          // Log pour vérifier que tout fonctionne
    //         Log::info('PDF généré avec succès pour : ' . $referenceInterne);
    //         $fileName = 'recu-paiement-' . $paiement->codePaiement . '-' . time() . '.pdf';
    //         // $filePath = public_path() . '/' . $fileName;
    //         // $pdf->save($filePath);
    //         // return $pdf->stream($fileName);
    //         return $pdf->download($fileName);
    //     } catch (\Exception $e) {
    //         // Log l'erreur
    //         Log::error('Erreur génération PDF : ' . $e->getMessage());

    //         // Retourne une réponse d'erreur
    //         return back()->with('error', 'Erreur lors de la génération du PDF : ' . $e->getMessage());
    //     }
        
    // }

    public function downloadPDF(string $referenceInterne)
    {
        try {
            $paiement = TblPaiement::where('codePaiement', $referenceInterne)->firstOrFail();
            
            $libellesTypeFacture = [
                'N' => 'Prime principale',
                'F' => 'Frais d\'adhésion',
            ];

            $libellesType = [
                'firstPayment' => 'Premier paiement',
                'earlyPayment' => 'Paiement anticipé',
                'recoveryPrime' => 'Régularisation de primes',
            ];
            
            $libelleType = $libellesType[$paiement->typeReglement] ?? $paiement->typeReglement;

            $factures = TblFacture::where('codePaiement', $referenceInterne)
                ->orderBy('dateAjout')
                ->get()
                ->map(function ($facture) use ($libellesTypeFacture) {
                    $facture->libelleTypeFacture =
                        $libellesTypeFacture[$facture->typeFacture]
                        ?? $facture->typeFacture;
                    return $facture;
                });

            // Utiliser le Facade avec le nom complet
            $pdf = Pdf::loadView('paiement.recu_pdf', compact('paiement', 'factures', 'libelleType'));
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
            ]);
            
            Log::info('PDF généré avec succès pour : ' . $referenceInterne);
            
            
            
            // Télécharger le PDF
            return $pdf->download($fileName);
            
        } catch (\Exception $e) {
            Log::error('Erreur génération PDF : ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return back()->with('error', 'Erreur lors de la génération du PDF : ' . $e->getMessage());
        }
    }
}
