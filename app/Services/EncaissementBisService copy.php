<?php

namespace App\Services;

use App\Models\Contrat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EncaissementBisService
{
    protected string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.api.encaissement_bis');
    }

    public function verifierContrat(string $idContrat, string $paymentType): array
    {
        try {
            $response = Http::timeout(20)->post($this->endpoint, ['idContrat' => $idContrat]);
        } catch (\Throwable $e) {
            Log::error('Erreur appel encaissement-bis', ['idContrat' => $idContrat, 'message' => $e->getMessage()]);
            return $this->failure('Service de vérification du contrat indisponible.');
        }

        if (!$response->successful()) {
            Log::warning('Réponse encaissement-bis non successful', ['idContrat' => $idContrat, 'status' => $response->status()]);
            return $this->failure('Impossible de vérifier ce contrat pour le moment.');
        }

        $data = $response->json();

        if (!empty($data['error'])) {
            return $this->failure('Contrat introuvable ou en erreur.');
        }

        $details = $data['details'][0] ?? null;
        if (!$details) {
            return $this->failure('Aucune information trouvée pour ce contrat.');
        }

        if ($details['OnStdbyOff'] == "3") {
            return $this->failure('Ce contrat est arrêté donc impossible d\'effectuer un paiement.');
        }

        $assures = $data['assures'] ?? [];
        $fraisAdhesion = 0;
        foreach ($assures as $garantie) {
            $fraisAdhesion += (int) round((float) ($garantie['FraisAcces'] ?? 0));
        }

        $primePrincipale = (int) round((float) ($details['TotalPrime'] ?? 0));

        $nonRegle = $data['enc']['nonRegle'] ?? [];
        $facturesImpayees = array_map(static function (array $f) {
            return [
                'IdPresentation'   => (string) ($f['IdPresentation'] ?? ''),
                'CodePresentation' => $f['CodePresentation'] ?? '',
                'MaDate'           => $f['MaDate'] ?? null,
                'MontantNet'       => (int) round((float) ($f['MontantNet'] ?? 0)),
            ];
        }, $nonRegle);

        return [
            'success'          => true,
            'idProposition'    => $details['IdProposition'] ?? null,
            'codeProposition'  => $details['CodeProposition'] ?? null,
            'souscripteur'     => trim(($details['nomSous'] ?? '') . ' ' . ($details['PrenomSous'] ?? '')),
            'numSouscripteur'  => $details['CodeProposant'] ?? null,
            'primePrincipale'  => $primePrincipale,
            'fraisAdhesion'    => $fraisAdhesion,
            'devise'           => 'XOF',
            'aDesImpayes'      => count($facturesImpayees) > 0,
            'facturesImpayees' => $facturesImpayees,
            'codeConseiller'   => $details['CodeConseiller'] ?? null,
            'codeProduit'      => $details['codeProduit'] ?? null,
            'produit'          => $details['produit'] ?? null,
        ];
    }

    public function recupDetailsContratWeb(string $idContrat, string $paymentType): array
    {
        try {
            $contrat = Contrat::where('id', $idContrat)->first();
            if (!$contrat) {
                Log::warning('Contrat introuvable (web)', ['idContrat' => $idContrat]);
                return $this->failure('Impossible de récupérer les détails de ce contrat.');
            }

            return [
                'success'         => true,
                'contratIdWeb'    => $contrat->id ?? null,
                'primePrincipale' => (int) $contrat->primepricipale ?? 0,
                'fraisAdhesion'   => (int) $contrat->fraisadhesion ?? 0,
                'devise'          => 'XOF',
                'codeProduit'     => $contrat->codeproduit ?? null,
                'produit'         => $contrat->libelleproduit ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Erreur récupération contrat', ['idContrat' => $idContrat, 'message' => $e->getMessage()]);
            return $this->failure('Service de vérification du contrat indisponible.');
        }
    }

    public function recalculerTotalImpayes(string $idContrat, array $selectedInvoiceIds, string $paymentType): array
    {
        $contrat = $this->verifierContrat($idContrat, $paymentType);
        if (!$contrat['success']) {
            return $this->failure($contrat['message'] ?? 'Contrat invalide.');
        }

        $selected = array_filter(
            $contrat['facturesImpayees'],
            static fn (array $f) => in_array($f['IdPresentation'], $selectedInvoiceIds, true)
        );

        if (count($selected) === 0) {
            return $this->failure('Aucune facture sélectionnée valide pour ce contrat.');
        }

        $total = array_sum(array_column($selected, 'MontantNet'));

        return [
            'success'               => true,
            'contrat'               => $contrat,
            'facturesSelectionnees' => array_values($selected),
            'totalCents'            => $total,
        ];
    }

    private function failure(string $message): array
    {
        return [
            'success'          => false,
            'message'          => $message,
            'aDesImpayes'      => false,
            'facturesImpayees' => [],
            'primePrincipale'  => 0,
            'fraisAdhesion'    => 0,
        ];
    }
}
