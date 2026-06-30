<?php

/**
 * Génération des états financiers SYSCOA/OHADA.
 *
 * États produits :
 *  - Balance générale (Trial Balance)
 *  - Grand livre par compte
 *  - Bilan (Actif / Passif)
 *  - Compte de résultat
 *
 * Toutes les méthodes retournent des tableaux PHP (sérialisables en JSON).
 */
class BilanManagement {

    // ─────────────────────────────────────────────
    //  BALANCE GÉNÉRALE
    // ─────────────────────────────────────────────

    /**
     * Balance des comptes sur une période.
     * Format : Compte | Intitulé | Mvt Débit | Mvt Crédit | Solde Débiteur | Solde Créditeur
     *
     * @param string $dateDebut
     * @param string $dateFin
     * @param bool   $avecSoldesNuls  Inclure les comptes avec solde = 0
     */
    public static function getBalance(string $dateDebut, string $dateFin, bool $avecSoldesNuls = false): array {
        $query = 'SELECT cc.Numero, cc.Intitule, cc.Sens_Normal,'
               . ' SUM(ec.Debit)  AS total_debit,'
               . ' SUM(ec.Credit) AS total_credit,'
               . ' (SUM(ec.Debit) - SUM(ec.Credit)) AS solde_net'
               . ' FROM ecriture_comptable ec'
               . ' JOIN compte_comptable cc ON cc.Numero = ec.Compte_No'
               . ' WHERE ec.Date_Ecriture BETWEEN "'.$dateDebut.'" AND "'.$dateFin.'"'
               . ' AND (ec.deleted_at = "0000-00-00 00:00:00" OR ec.deleted_at IS NULL)'
               . ' GROUP BY cc.Numero, cc.Intitule, cc.Sens_Normal'
               . ' ORDER BY cc.Numero ASC';

        $rows  = db->getResultAssoc($query);
        $lines = [];

        foreach ($rows as $row) {
            $soldeNet    = (float)$row['solde_net'];
            $soldDebit   = $soldeNet > 0 ? $soldeNet : 0;
            $soldCredit  = $soldeNet < 0 ? abs($soldeNet) : 0;

            if (!$avecSoldesNuls && $soldeNet == 0 && (float)$row['total_debit'] == 0) continue;

            $lines[] = [
                'numero'         => $row['Numero'],
                'intitule'       => $row['Intitule'],
                'mvt_debit'      => (float)$row['total_debit'],
                'mvt_credit'     => (float)$row['total_credit'],
                'solde_debiteur' => $soldDebit,
                'solde_crediteur'=> $soldCredit,
            ];
        }

        // Totaux de contrôle (Σ Débit = Σ Crédit dans une balance équilibrée)
        $totaux = [
            'total_mvt_debit'       => array_sum(array_column($lines, 'mvt_debit')),
            'total_mvt_credit'      => array_sum(array_column($lines, 'mvt_credit')),
            'total_solde_debiteur'  => array_sum(array_column($lines, 'solde_debiteur')),
            'total_solde_crediteur' => array_sum(array_column($lines, 'solde_crediteur')),
        ];

        return ['lignes' => $lines, 'totaux' => $totaux];
    }

    // ─────────────────────────────────────────────
    //  GRAND LIVRE
    // ─────────────────────────────────────────────

    /**
     * Grand livre d'un compte : toutes les écritures avec solde progressif.
     */
    public static function getGrandLivre(string $compteNo, string $dateDebut, string $dateFin): array {
        $cpt = new CompteComptable();
        if (!$cpt->get($compteNo))
            Error("Compte '".$compteNo."' introuvable.");

        // Solde d'ouverture (avant dateDebut)
        $ec0 = new EcritureComptable();
        $ec0->setRange('Compte_No', $compteNo);
        $ec0->setFilter('Date_Ecriture', '<%1', $dateDebut);
        $soldD0 = (float)($ec0->aggregateSQL('SUM', 'Debit')  ?? 0);
        $soldC0 = (float)($ec0->aggregateSQL('SUM', 'Credit') ?? 0);
        $soldeOuverture = $soldD0 - $soldC0;

        // Écritures de la période
        $query = 'SELECT No_, Date_Ecriture, Journal_Code, Piece_No, Libelle,'
               . ' Debit, Credit, Lettrage_Code, Statut'
               . ' FROM ecriture_comptable'
               . ' WHERE Compte_No = "'.$compteNo.'"'
               . ' AND Date_Ecriture BETWEEN "'.$dateDebut.'" AND "'.$dateFin.'"'
               . ' AND (deleted_at = "0000-00-00 00:00:00" OR deleted_at IS NULL)'
               . ' ORDER BY Date_Ecriture ASC, No_ ASC';

        $rows  = db->getResultAssoc($query);
        $solde = $soldeOuverture;
        $lignes = [];

        foreach ($rows as $row) {
            $solde += (float)$row['Debit'] - (float)$row['Credit'];
            $lignes[] = [
                'no'            => $row['No_'],
                'date'          => $row['Date_Ecriture'],
                'journal'       => $row['Journal_Code'],
                'piece_no'      => $row['Piece_No'],
                'libelle'       => $row['Libelle'],
                'debit'         => (float)$row['Debit'],
                'credit'        => (float)$row['Credit'],
                'solde'         => $solde,
                'lettrage'      => $row['Lettrage_Code'],
                'statut'        => $row['Statut'],
            ];
        }

        $totalDebit  = array_sum(array_column($lignes, 'debit'));
        $totalCredit = array_sum(array_column($lignes, 'credit'));

        return [
            'compte'          => ['numero' => $cpt->Numero->_value, 'intitule' => $cpt->Intitule->_value],
            'solde_ouverture' => $soldeOuverture,
            'lignes'          => $lignes,
            'totaux'          => [
                'debit'  => $totalDebit,
                'credit' => $totalCredit,
                'solde_cloture' => $soldeOuverture + $totalDebit - $totalCredit,
            ],
        ];
    }

    // ─────────────────────────────────────────────
    //  BILAN SYSCOA
    // ─────────────────────────────────────────────

    /**
     * Bilan à une date donnée.
     * Structure SYSCOA : Actif (classes 2,3,4 client,5) / Passif (classes 1,4 fourn)
     *
     * @return array ['actif' => [...], 'passif' => [...], 'total_actif' => x, 'total_passif' => y]
     */
    public static function getBilan(string $dateArrete): array {
        $actif  = [];
        $passif = [];

        $comptes = new CompteComptable();
        $comptes->setRange('Est_Actif', '1');
        $comptes->FindAll();

        foreach ($comptes->recordSet as $cpt) {
            if ($cpt->Type->_value === 'Resultat') continue;

            $soldD = self::_soldeCompteADate($cpt->Numero->_value, $dateArrete, 'Debit');
            $soldC = self::_soldeCompteADate($cpt->Numero->_value, $dateArrete, 'Credit');
            $solde = $soldD - $soldC;

            if ($solde == 0) continue;

            $ligne = [
                'numero'   => $cpt->Numero->_value,
                'intitule' => $cpt->Intitule->_value,
                'classe'   => $cpt->Classe->_value,
                'montant'  => abs($solde),
            ];

            // Classification Actif / Passif selon le sens du solde et la classe SYSCOA
            $classe   = (int)$cpt->Classe->_value;
            $sousCl   = (int)substr($cpt->Numero->_value, 0, 2);
            $estActif = match(true) {
                $classe === 2              => true,               // Immobilisations
                $classe === 3              => true,               // Stocks
                $classe === 5              => true,               // Trésorerie
                $sousCl === 41            => true,               // Clients
                $sousCl === 40            => false,              // Fournisseurs
                $classe === 1              => false,              // Capitaux propres
                default                   => $solde > 0,        // Débiteur → Actif
            };

            if ($estActif) $actif[]  = $ligne;
            else           $passif[] = $ligne;
        }

        // Résultat net de l'exercice courant (intégré dans les capitaux propres)
        $ex       = ComptabiliteManagement::getExerciceCourant();
        $resultat = self::_calculerResultatNet($ex->Code->_value, $dateArrete);
        if ($resultat != 0) {
            $passif[] = [
                'numero'   => '12000',
                'intitule' => 'Résultat net de l\'exercice',
                'classe'   => 1,
                'montant'  => $resultat,
            ];
        }

        $totalActif  = array_sum(array_column($actif,  'montant'));
        $totalPassif = array_sum(array_column($passif, 'montant'));

        return [
            'actif'         => $actif,
            'passif'        => $passif,
            'total_actif'   => $totalActif,
            'total_passif'  => $totalPassif,
            'equilibre'     => round($totalActif - $totalPassif, 2) === 0.0,
            'date_arrete'   => $dateArrete,
        ];
    }

    // ─────────────────────────────────────────────
    //  COMPTE DE RÉSULTAT
    // ─────────────────────────────────────────────

    /**
     * Compte de résultat sur une période (SYSCOA simplifié).
     * Produits (classe 7) - Charges (classe 6) = Résultat net
     */
    public static function getCompteResultat(string $dateDebut, string $dateFin): array {
        $charges  = self::_aggregerClasse('6', $dateDebut, $dateFin);
        $produits = self::_aggregerClasse('7', $dateDebut, $dateFin);

        $totalCharges  = array_sum(array_column($charges,  'montant'));
        $totalProduits = array_sum(array_column($produits, 'montant'));
        $resultat      = $totalProduits - $totalCharges;

        return [
            'periode_debut'   => $dateDebut,
            'periode_fin'     => $dateFin,
            'produits'        => $produits,
            'total_produits'  => $totalProduits,
            'charges'         => $charges,
            'total_charges'   => $totalCharges,
            'resultat_net'    => $resultat,
            'nature_resultat' => $resultat >= 0 ? 'Bénéfice' : 'Déficit',
        ];
    }

    // ─────────────────────────────────────────────
    //  RAPPROCHEMENT BANCAIRE
    // ─────────────────────────────────────────────

    /**
     * Rapproche une écriture comptable sur un rapprochement bancaire.
     */
    public static function rapprocher(string $rapprochementCode, int $ecritureNo): void {
        $rap = new RapprochementBancaire();
        if (!$rap->get($rapprochementCode))
            Error("Rapprochement '".$rapprochementCode."' introuvable.");
        if ($rap->Statut->_value === 'Valide')
            Error("Ce rapprochement est déjà validé.");

        $ec = new EcritureComptable();
        if (!$ec->get($ecritureNo))
            Error("Écriture N°".$ecritureNo." introuvable.");
        if ($ec->Compte_No->_value !== $rap->Compte_Bancaire_No->_value)
            Error("L'écriture N°".$ecritureNo." ne concerne pas le compte bancaire '".$rap->Compte_Bancaire_No->_value."'.");
        if (!empty($ec->Rapprochement_Code->_value))
            Error("L'écriture N°".$ecritureNo." est déjà rapprochée sous '".$ec->Rapprochement_Code->_value."'.");

        $ec->Validate('Rapprochement_Code', $rapprochementCode);
        $ec->Validate('Date_Rapprochement', date('Y-m-d'));
        $ec->Validate('Statut',            StatutEcriture::Rapproche->value);
        $ec->Modify(false);

        // Mettre à jour le montant rapproché et l'écart
        $rap->CalcFields('Nb_Ecritures_Non_Rapprochees');
        $montantRap = (float)$rap->Montant_Rapproche->_value + (float)$ec->Debit->_value - (float)$ec->Credit->_value;
        $ecart      = round((float)$rap->Solde_Releve->_value - (float)$rap->Solde_Comptable->_value - $montantRap, 2);
        $rap->Validate('Montant_Rapproche', $montantRap);
        $rap->Validate('Ecart',             $ecart);
        $rap->Modify(false);
    }

    /**
     * Valide le rapprochement bancaire lorsque l'écart est nul.
     */
    public static function validerRapprochement(string $code): void {
        $rap = new RapprochementBancaire();
        if (!$rap->get($code))
            Error("Rapprochement '".$code."' introuvable.");
        if (round((float)$rap->Ecart->_value, 2) !== 0.0)
            Error("L'écart du rapprochement est de ".number_format((float)$rap->Ecart->_value, 2)." FCFA. Solde l'écart avant de valider.");

        $rap->Validate('Statut', 'Valide');
        $rap->Modify();
    }

    // ─── Helpers privés ───

    private static function _soldeCompteADate(string $compteNo, string $dateArrete, string $sens): float {
        $ec = new EcritureComptable();
        $ec->setRange('Compte_No', $compteNo);
        $ec->setFilter('Date_Ecriture', '<=%1', $dateArrete);
        return (float)($ec->aggregateSQL('SUM', $sens) ?? 0);
    }

    private static function _calculerResultatNet(string $exerciceCode, string $dateArrete): float {
        $query = 'SELECT SUM(CASE WHEN LEFT(cc.Numero,1)="7" THEN ec.Credit - ec.Debit'
               . '               WHEN LEFT(cc.Numero,1)="6" THEN ec.Debit - ec.Credit'
               . '               ELSE 0 END) AS resultat'
               . ' FROM ecriture_comptable ec'
               . ' JOIN compte_comptable cc ON cc.Numero = ec.Compte_No'
               . ' WHERE ec.Exercice_Code = "'.$exerciceCode.'"'
               . ' AND ec.Date_Ecriture <= "'.$dateArrete.'"'
               . ' AND (ec.deleted_at = "0000-00-00 00:00:00" OR ec.deleted_at IS NULL)';
        $rows = db->getResultAssoc($query);
        return (float)($rows[0]['resultat'] ?? 0);
    }

    private static function _aggregerClasse(string $classe, string $debut, string $fin): array {
        $query = 'SELECT cc.Numero, cc.Intitule,'
               . ' SUM(ec.Debit) - SUM(ec.Credit) AS montant'
               . ' FROM ecriture_comptable ec'
               . ' JOIN compte_comptable cc ON cc.Numero = ec.Compte_No'
               . ' WHERE LEFT(cc.Numero,1) = "'.$classe.'"'
               . ' AND ec.Date_Ecriture BETWEEN "'.$debut.'" AND "'.$fin.'"'
               . ' AND (ec.deleted_at = "0000-00-00 00:00:00" OR ec.deleted_at IS NULL)'
               . ' GROUP BY cc.Numero, cc.Intitule'
               . ' HAVING montant != 0'
               . ' ORDER BY cc.Numero ASC';
        $rows  = db->getResultAssoc($query);
        return array_map(fn($r) => [
            'numero'   => $r['Numero'],
            'intitule' => $r['Intitule'],
            'montant'  => abs((float)$r['montant']),
        ], $rows);
    }
}
?>
