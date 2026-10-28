<?php

namespace App\Services;

use App\Models\Contrat;
use Carbon\Carbon;
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
        // foreach ($assures as $garantie) {
        //     $fraisAdhesion += (int) round((float) ($garantie['FraisAcces'] ?? 0));
        // }

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
            // Log::debug('Contrat', ['idContrat' => $idContrat, 'contrat' => $contrat]);
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

    public function getContrat($idcontrat)
    {
        if (!$idcontrat) {
            return $this->failure('Aucun id contrat fourni.');
        }

        try {
            $response = Http::timeout(60)->post($this->endpoint, ['idContrat' => $idcontrat]);
        } catch (\Throwable $e) {
            Log::error('Erreur appel encaissement-bis', ['idContrat' => $idcontrat, 'message' => $e->getMessage()]);
            return $this->failure('Service de recuperation des informations du contrat indisponible.');
        }

        if (!$response->successful()) {
            Log::warning('Réponse encaissement-bis non successful', [
                'idContrat' => $idcontrat, 
                'status' => $response->status()
            ]);
            return $this->failure('Impossible de recuperer les informations de ce contrat pour le moment.');
        }

        $data = $response->json();

        $details = $data['details'][0] ?? null;
        if (!$details) {
            return $this->failure('Aucune information trouvée sur ce contrat.');
        }

        // Recupérer le nombre de primes encaissées
        $NbrencConfirmer = count($data['enc']['confirmer'] ?? []);

        $TotalEncaissementPartielle = array_sum(array_map(function ($item) {
            return isset($item['MontantNet']) ? (float) $item['MontantNet'] : 0;
        }, $data['enc']['partielle'] ?? []));

        $NbrencPartielle = count($data['enc']['partielle'] ?? []);



        // Recupérer le montant total des primes encaissées
        $TotalEncaissement = array_sum(array_map(function ($item) {
            return isset($item['RegltMontant']) ? (float) $item['RegltMontant'] : 0;
        }, $data['enc']['confirmer'] ?? []));

        // Mettre à jour les variables
        $data['details'][0]['NbreEncaissment'] = $NbrencConfirmer;
        $data['details'][0]['TotalEncaissement'] = $TotalEncaissement;

        $data['details'][0]['NbrencPartielle'] = $NbrencPartielle;
        $data['details'][0]['TotalEncaissementPartielle'] = $TotalEncaissementPartielle;
        


        // Recupérer le nombre de primes impayées ou en attente
        $NbreImpayes = count($data['enc']['nonRegle'] ?? []);

        // Recupérer le montant total des primes impayées ou en attente
        $TotalImpayes = array_sum(array_map(function ($item) {
            return isset($item['MontantNet']) ? (float) $item['MontantNet'] : 0;
        }, $data['enc']['nonRegle'] ?? []));

        $data['details'][0]['NbreImpayes'] = $NbreImpayes;
        $data['details'][0]['TotalImpayes'] = $TotalImpayes;
       

        // Mettre le type de la primes en float
        $prime = (float) ($data['details'][0]['TotalPrime'] ?? 0);
        $CapitalSouscrit = (float) ($data['details'][0]['CapitalSouscrit'] ?? 0);
        $CapitalRente = (float) ($data['details'][0]['CapitalRente'] ?? 0);
        $DureeCotisationAns = (float) ($data['details'][0]['DureeCotisationAns'] ?? 0);

        $data['details'][0]['CapitalSouscrit'] = $CapitalSouscrit ?? $CapitalRente;
        $data['details'][0]['CapitalRente'] = $CapitalRente;
        $data['details'][0]['DureeCotisationAns'] = $DureeCotisationAns;
        $data['details'][0]['TotalPrime'] = $prime;

        $TotalEmission = (float) ($data['details'][0]['TotalEmission'] ?? 0);
        $data['details'][0]['TotalEmission'] = $TotalEmission;

        $NbreEmission = (int) ($data['details'][0]['NbreEmission'] ?? 0);
        $data['details'][0]['NbreEmission'] = $NbreEmission;

        // Conversion des dates avec gestion d'erreurs
        if (isset($data['details'][0]['DateNaissance']) && $data['details'][0]['DateNaissance']) {
            try {
                $dateNaissance = Carbon::createFromFormat('d/m/Y', $data['details'][0]['DateNaissance'])->format('Y-m-d');
                $data['details'][0]['DateNaissance'] = $dateNaissance;
            } catch (\Exception $e) {
                Log::warning('Conversion date naissance échouée', ['date' => $data['details'][0]['DateNaissance']]);
            }
        }

        if (isset($data['details'][0]['DateEffetReel']) && $data['details'][0]['DateEffetReel']) {
            try {
                $dateEffetReel = Carbon::createFromFormat('d/m/Y', $data['details'][0]['DateEffetReel'])->format('Y-m-d');
                $data['details'][0]['DateEffetReel'] = $dateEffetReel;
            } catch (\Exception $e) {
                Log::warning('Conversion date effet reel échouée', ['date' => $data['details'][0]['DateEffetReel']]);
            }
        }

        if (isset($data['details'][0]['DateEffetSouhaite']) && $data['details'][0]['DateEffetSouhaite']) {
            try {
                $DateEffetSouhaite = Carbon::parse($data['details'][0]['DateEffetSouhaite'])->format('Y-m-d');
                $data['details'][0]['DateEffetSouhaite'] = $DateEffetSouhaite;
            } catch (\Exception $e) {
                Log::warning('Conversion date effet souhaite échouée', ['date' => $data['details'][0]['DateEffetSouhaite']]);
            }
        }
        
        if (isset($data['details'][0]['FinAdhesion']) && $data['details'][0]['FinAdhesion']) {
            try {
                $FinAdhesion = Carbon::createFromFormat('d/m/Y', $data['details'][0]['FinAdhesion'])->format('Y-m-d');
                $data['details'][0]['FinAdhesion'] = $FinAdhesion;
            } catch (\Exception $e) {
                Log::warning('Conversion fin adhesion échouée', ['date' => $data['details'][0]['FinAdhesion']]);
            }
        }


        if (!empty($data['error'])) {
            return $this->failure('Contrat introuvable ou en erreur.');
        }

        return [
            'success' => true,
            'code' => 'CONTRACT_FOUND',
            'message' => 'Contrat trouvée.',
            'data' => $data
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