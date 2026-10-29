<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contrat;
use App\Models\TblDocument;
use App\Models\TblFacture;
use App\Models\TblPaiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    /**
     * Affiche le reçu de paiement
     */
    public function show(string $referenceInterne): View
    {
        // Libellés des types de facture
        $libellesTypeFacture = [
            'N' => 'Prime principale',
            'F' => "Frais d'adhésion",
            'P' => 'Partielle (Reste à payer)',
            'U' => 'Unique',
            'B' => 'Participation aux Bénéfices',
            'E' => 'Exceptionnelle',
            'A' => 'Avance (Remboursement de prêts)',
        ];

        // Libellés des types de paiement
        $libellesType = [
            'firstPayment' => 'Premier paiement',
            'earlyPayment' => 'Paiement anticipé',
            'recoveryPrime' => 'Régularisation de primes',
        ];

        // Récupérer le paiement (par codePaiement ou command_number)
        $paiement = TblPaiement::where('codePaiement', $referenceInterne)
            ->orWhere('command_number', $referenceInterne)
            ->firstOrFail();

        // Récupérer les factures associées (par codePaiement)
        $factures = TblFacture::where('codePaiement', $paiement->codePaiement)
            ->orderBy('dateAjout')
            ->get()
            ->map(function ($facture) use ($libellesTypeFacture) {
                $facture->libelleTypeFacture = $libellesTypeFacture[$facture->typeFacture] ?? $facture->typeFacture;
                return $facture;
            });

        // Identifiant du contrat
        $identifiantContrat = $paiement->idContrat ?? $paiement->idproposition ?? '';

        $contrat = Contrat::where('id', $identifiantContrat)->first();

        // Récupérer le reçu de paiement du contrat dans la table tbldocuments
        $fileName = null;
        if ($contrat) {
            $recuPaiement = TblDocument::where('codecontrat', $contrat->id)
                ->where('filename', 'like', '%' . $paiement->codePaiement . '%')
                ->first();
            $fileName = $recuPaiement->filename ?? null;
        }

        Log::info('Affichage reçu', [
            'codePaiement' => $paiement->codePaiement,
            'command_number' => $paiement->command_number,
            'fileName' => $fileName
        ]);

        return view('paiement.recu', [
            'paiement' => $paiement,
            'factures' => $factures,
            'libelleType' => $libellesType[$paiement->typeReglement] ?? $paiement->typeReglement,
            'fileName' => $fileName,
            'identifiantContrat' => $identifiantContrat,
        ]);
    }

    /**
     * Génère et télécharge le PDF du reçu
     */
    public function download(string $referenceInterne)
    {
        $paiement = TblPaiement::where('codePaiement', $referenceInterne)
            ->orWhere('command_number', $referenceInterne)
            ->firstOrFail();

        // Vérifier si le PDF existe déjà
        $identifiantContrat = $paiement->idContrat ?? $paiement->idproposition ?? '';
        $fileName = 'recu-paiement-' . $paiement->codePaiement . '-' . $identifiantContrat . '.pdf';
        $externalUploadDir = base_path(env('UPLOADS_PATH'));
        $filePath = $externalUploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (file_exists($filePath)) {
            return response()->download($filePath, $fileName, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        // Générer le PDF si inexistant
        $pdf = $this->generatePdf($paiement);
        return $pdf->download($fileName);
    }

    /**
     * Génère le PDF du reçu
     */
    private function generatePdf(TblPaiement $paiement)
    {
        $libellesTypeFacture = [
            'N' => 'Prime principale',
            'F' => "Frais d'adhésion",
            'P' => 'Partielle (Reste à payer)',
            'U' => 'Unique',
            'B' => 'Participation aux Bénéfices',
            'E' => 'Exceptionnelle',
            'A' => 'Avance (Remboursement de prêts)',
        ];

        $libellesType = [
            'firstPayment' => 'Premier paiement',
            'earlyPayment' => 'Paiement anticipé',
            'recoveryPrime' => 'Régularisation de primes',
        ];

        $factures = TblFacture::where('codePaiement', $paiement->codePaiement)
            ->orderBy('dateAjout')
            ->get()
            ->map(function ($facture) use ($libellesTypeFacture) {
                $facture->libelleTypeFacture = $libellesTypeFacture[$facture->typeFacture] ?? $facture->typeFacture;
                return $facture;
            });

        $data = [
            'paiement' => $paiement,
            'factures' => $factures,
            'libelleType' => $libellesType[$paiement->typeReglement] ?? $paiement->typeReglement,
        ];

        $pdf = Pdf::loadView('paiement.recu_pdf', $data);
        $pdf->setPaper('A4', 'portrait');

        // Sauvegarder le PDF
        $identifiantContrat = $paiement->idContrat ?? $paiement->idproposition ?? '';
        $fileName = 'recu-paiement-' . $paiement->codePaiement . '-' . $identifiantContrat . '.pdf';
        $externalUploadDir = base_path(env('UPLOADS_PATH'));
        $filePath = $externalUploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!is_dir($externalUploadDir)) {
            mkdir($externalUploadDir, 0777, true);
        }

        file_put_contents($filePath, $pdf->output());

        return $pdf;
    }

    /**
     * Télécharge le PDF du reçu (méthode alternative pour la route existante)
     */
    public function downloadPDF(string $referenceInterne)
    {
        return $this->download($referenceInterne);
    }
}
