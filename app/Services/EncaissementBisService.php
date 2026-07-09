<?php

namespace App\Services;

use App\Models\Contrat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Interroge https://api.yakoafricassur.com/oldweb/encaissement-bis pour :
 *  - récupérer les factures impayées / en attente d'un contrat (enc.nonRegle)
 *  - déterminer la prime récurrente et les frais d'adhésion à partir de "details"/"assures"
 *
 * Convention de montants : cette API renvoie des chaînes décimales (ex: "16050.0000").
 * On les caste en entier (XOF n'a pas de centimes) pour rester cohérent avec le reste du système.
 */
class EncaissementBisService
{
    protected string $endpoint;

    public function __construct()
    {
        $this->endpoint = config('services.api.encaissement_bis');
    }

    /**
     * @param string $idContrat Identifiant du contrat (CodeProposition ou idContrat métier)
     * @return array{
     *   success: bool,
     *   idProposition: ?string,
     *   codeProposition: ?string,
     *   souscripteur: ?string,
     *   primePrincipale: int,
     *   fraisAdhesion: int,
     *   devise: string,
     *   aDesImpayes: bool,
     *   facturesImpayees: array,
     *   codeConseiller: ?string,
     *   codeProduit: ?string,
     *   message?: string
     * }
     */
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
            return $this->failure('Ce contrat est arreté donc Impossible d’éffectuer un paiement !');
        }

        $assures = $data['assures'] ?? [];
        $fraisAdhesion = 0;
        foreach ($assures as $garantie) {
            $fraisAdhesion += (int) round((float) ($garantie['FraisAcces'] ?? 0));
        }

        $primePrincipale = (int) round((float) ($details['TotalPrime'] ?? 0));

        $nonRegle = $data['enc']['nonRegle'] ?? [];
        // Normalisation : seuls les champs utiles au widget/contrôleur sont exposés
        $facturesImpayees = array_map(static function (array $f) {
            return [
                'IdPresentation' => (string) ($f['IdPresentation'] ?? ''),
                'CodePresentation' => $f['CodePresentation'] ?? '',
                'MaDate' => $f['MaDate'] ?? null,
                'MontantNet' => (int) round((float) ($f['MontantNet'] ?? 0)),
            ];
        }, $nonRegle);

        return [
            'success' => true,
            'idProposition' => $details['IdProposition'] ?? null,
            'codeProposition' => $details['CodeProposition'] ?? null,
            'souscripteur' => trim(($details['nomSous'] ?? '') . ' ' . ($details['PrenomSous'] ?? '')),
            'numSouscripteur' => $details['CodeProposant'] ?? null,
            'primePrincipale' => $primePrincipale,
            'fraisAdhesion' => $fraisAdhesion,
            'devise' => 'XOF',
            'aDesImpayes' => count($facturesImpayees) > 0,
            'facturesImpayees' => $facturesImpayees,
            'codeConseiller' => $details['CodeConseiller'] ?? null,
            'codeProduit' => $details['codeProduit'] ?? null,
            'produit' => $details['produit'] ?? null,
        ];
    }
    public function recupDetailsContratWeb(string $idContrat, string $paymentType): array
    {
        try {
            $contrat = Contrat::where('id', $idContrat)->first();
            Log::debug('Contrat', ['idContrat' => $idContrat, 'contrat' => $contrat]);
            if (!$contrat) {
                Log::warning('Réponse recupDetailsContratWeb non successful', ['idContrat' => $idContrat ]);
                return $this->failure('Impossible de récuperer les details ce contrat pour le moment.');
            }

            return [
                'success' => true,
                'contratIdWeb' => $contrat->id ?? null,
                'primePrincipale' => (int) $contrat->primepricipale ?? 0,
                'fraisAdhesion' => (int) $contrat->fraisadhesion ?? 0,
                'devise' => 'XOF',
                'codeProduit' => $contrat->codeproduit ?? null,
                'produit' => $contrat->libelleproduit ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Erreur récuperation contrat', ['idContrat' => $idContrat, 'message' => $e->getMessage()]);
            return $this->failure('Service de vérification du contrat indisponible.');
        }
    }

    /**
     * Revérifie que les IdPresentation soumis par le client font bien partie des
     * impayés du contrat, et retourne le total recalculé côté serveur.
     *
     * @param string[] $selectedInvoiceIds
     */
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
            'success' => true,
            'contrat' => $contrat,
            'facturesSelectionnees' => array_values($selected),
            'totalCents' => $total, // conservé sous ce nom pour compat avec le payload Jeko (XOF = pas de centimes)
        ];
    }

    private function failure(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'aDesImpayes' => false,
            'facturesImpayees' => [],
            'primePrincipale' => 0,
            'fraisAdhesion' => 0,
        ];
    }
}