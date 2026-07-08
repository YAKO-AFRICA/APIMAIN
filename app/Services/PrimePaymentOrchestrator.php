<?php

namespace App\Services;

use App\Models\TblFacture;
use App\Models\TblPaiement;
use App\Services\EncaissementBisService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Construit le montant à facturer et prépare les enregistrements tblpaiement / tblfacture
 * pour les 3 types de paiement (firstPayment, earlyPayment, recoveryPrime).
 *
 * Convention d'état (à adapter si une convention existe déjà côté métier) :
 *   tblpaiement.etat / tblfacture.etat : 0 = en attente, 1 = payé, 2 = échec
 */
class PrimePaymentOrchestrator
{
    public function __construct(protected EncaissementBisService $encaissementBis)
    {
    }

    /**
     * @throws \RuntimeException si les données ne permettent pas de calculer un montant valide
     */
    public function preparer(array $donnees): array
    {
        return match ($donnees['paymentType']) {
            'firstPayment' => $this->preparerFirstPayment($donnees),
            'earlyPayment' => $this->preparerEarlyPayment($donnees),
            'recoveryPrime' => $this->preparerRecoveryPrime($donnees),
            default => throw new \RuntimeException('Type de paiement inconnu.'),
        };
    }

    private function preparerFirstPayment(array $donnees): array
    {
        if (empty($donnees['contractId'])) {
            throw new \RuntimeException('contractId requis pour un premier paiement.');
        }
        $primeUnitaire = 0;
        $fraisAdhesion = 0;
        // $contractIdWeb = $donnees['contractIdWeb'] ?? null;

        // Si un contractId est fourni, on recoupe avec l'API legacy quand elle a des données
        // (un contrat tout juste validé peut ne pas encore y figurer : on ne bloque pas dans ce cas).
            $contrat = $this->encaissementBis->recupDetailsContratWeb($donnees['contractId'], $donnees['paymentType']);
        if ($contrat['success']) {
            $primeUnitaire = $contrat['primePrincipale'];
            $fraisAdhesion = $contrat['fraisAdhesion'];
        }

        

        if ($primeUnitaire <= 0) {
            throw new \RuntimeException('Montant de prime invalide pour le premier paiement.');
        }

        $nombreDePrimes = max(1, (int) ($donnees['numberOfPrimes'] ?? 1));
        $montantTotal = $fraisAdhesion + ($primeUnitaire * $nombreDePrimes);

        return [
            'montantTotal' => $montantTotal,
            'nombreDePrimes' => $nombreDePrimes,
            'contractId' => $donnees['contractId'],
            'referenceSource' => $donnees['contractIdWeb'] ?? $donnees['reference'],
            'primeUnitaire' => $primeUnitaire,
            'fraisAdhesion' => $fraisAdhesion,
            'facturesAGenerer' => $this->genererLignesFacturesAvenir($nombreDePrimes, $primeUnitaire, $fraisAdhesion),
        ];
    }

    private function preparerEarlyPayment(array $donnees): array
    {
        if (empty($donnees['contractId'])) {
            throw new \RuntimeException('contractId requis pour un paiement anticipé.');
        }

        $contrat = $this->encaissementBis->verifierContrat($donnees['contractId'], $donnees['paymentType']);
        if (!$contrat['success']) {
            throw new \RuntimeException($contrat['message'] ?? 'Contrat invalide.');
        }

        if ($contrat['aDesImpayes']) {
            throw new \RuntimeException(
                "Ce contrat a des primes impayées. Utilisez la régularisation (recoveryPrime) avant de payer en avance."
            );
        }

        $nombreDePrimes = max(1, (int) ($donnees['numberOfPrimes'] ?? 1));
        $montantTotal = $contrat['primePrincipale'] * $nombreDePrimes;

        return [
            'montantTotal' => $montantTotal,
            'nombreDePrimes' => $nombreDePrimes,
            'idProposition' => $contrat['idProposition'],
            'referenceSource' => $donnees['contractId'],
            'primeUnitaire' => $contrat['primePrincipale'],
            'fraisAdhesion' => 0,
            'facturesAGenerer' => $this->genererLignesFacturesAvenir($nombreDePrimes, $contrat['primePrincipale'], 0),
        ];
    }

    private function preparerRecoveryPrime(array $donnees): array
    {
        if (empty($donnees['contractId'])) {
            throw new \RuntimeException('contractId requis pour une régularisation.');
        }
        if (empty($donnees['selectedInvoiceIds'])) {
            throw new \RuntimeException('Aucune facture sélectionnée.');
        }

        $resultat = $this->encaissementBis->recalculerTotalImpayes($donnees['contractId'], $donnees['selectedInvoiceIds'], $donnees['paymentType']);
        if (!$resultat['success']) {
            throw new \RuntimeException($resultat['message'] ?? 'Impossible de recalculer le montant.');
        }

        $facturesSelectionnees = $resultat['facturesSelectionnees'];

        return [
            'montantTotal' => $resultat['totalCents'],
            'nombreDePrimes' => count($facturesSelectionnees),
            'idProposition' => $resultat['contrat']['idProposition'],
            'referenceSource' => $donnees['contractId'],
            'primeUnitaire' => null,
            'fraisAdhesion' => 0,
            // Pour la régularisation, on rattache chaque facture au montant réel de l'impayé sélectionné
            'facturesAGenerer' => array_map(static fn (array $f) => [
                'prime' => $f['MontantNet'],
                'referenceOrigine' => $f['CodePresentation'],
            ], $facturesSelectionnees),
        ];
    }

    private function genererLignesFacturesAvenir(int $nombreDePrimes, int $primeUnitaire, int $fraisAdhesion): array
    {
        $lignes = [];
        for ($i = 0; $i < $nombreDePrimes; $i++) {
            $lignes[] = [
                'prime' => $primeUnitaire + ($i === 0 ? $fraisAdhesion : 0),
                'referenceOrigine' => null,
            ];
        }
        return $lignes;
    }

    /**
     * Enregistre tblpaiement + les lignes tblfacture associées dans une transaction.
     */
    public function enregistrer(array $donnees, array $preparation, string $referenceInterne, array $resultatJeko): TblPaiement
    {
        return DB::transaction(function () use ($donnees, $preparation, $referenceInterne, $resultatJeko) {
            $paiement = TblPaiement::create([
                'codePaiement' => $referenceInterne,
                'montant' => $preparation['montantTotal'],
                'etat' => 0, // en attente de confirmation webhook
                'datepaiement' => now()->format('Y-m-d H:i:s'),
                'payment_mode' => $donnees['paymentMethod'],
                'payment_status' => $resultatJeko['status'] ?? 'pending',
                'payment_token' => $resultatJeko['paymentId'] ?? null,
                'typePaiement' => $donnees['paymentType'],
                'idproposition' => $preparation['idProposition'],
                'typeReference' => 'CONTRAT',
                'referenceSource' => $preparation['referenceSource'],
                'nombreDePrime' => $preparation['nombreDePrimes'],
                'frais_adhesion' => $preparation['fraisAdhesion'] ?? 0,
                'emailpayeur' => $donnees['customerEmail'] ?? null,
                'saisiele' => now(),
                'estMigre' => 0,
            ]);

            foreach ($preparation['facturesAGenerer'] as $ligne) {
                TblFacture::create([
                    'idProposition' => $preparation['idProposition'],
                    'codePaiement' => $referenceInterne,
                    'prime' => $ligne['prime'],
                    'etat' => 0,
                    'dateAjout' => now()->format('Y-m-d H:i:s'),
                    'typePaiement' => $donnees['paymentType'],
                    'referenceSource' => $ligne['referenceOrigine'] ?? $preparation['referenceSource'],
                    'idcontrat' => $donnees['contractId'] ?? null,
                    'saisiele' => now(),
                ]);
            }

            return $paiement;
        });
    }
}