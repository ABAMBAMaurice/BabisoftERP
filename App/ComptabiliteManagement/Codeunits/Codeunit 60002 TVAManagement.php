<?php

/**
 * Gestion de la TVA (Taxe sur la Valeur Ajoutée).
 *
 * Taux en vigueur en Côte d'Ivoire (SYSCOA) :
 *  - TVA standard : 18%
 *  - Exportations  : 0%
 *
 * Comptes SYSCOA utilisés :
 *  - 44310 : TVA collectée (sur ventes)
 *  - 44540 : TVA déductible sur achats courants
 *  - 44520 : TVA déductible sur immobilisations
 *  - 44730 : Crédit de TVA reporté
 */
class TVAManagement {

    const TAUX_STANDARD = 18.0;

    /**
     * Génère une déclaration de TVA pour une période donnée.
     * Collecte les écritures TVA et calcule collectée - déductible.
     *
     * @param string $periodeDebut  Date début (Y-m-d)
     * @param string $periodeFin    Date fin (Y-m-d)
     * @param string $code          N° de déclaration (auto si vide)
     */
    public static function genererDeclaration(string $periodeDebut, string $periodeFin, string $code = ''): TVADeclaration {
        // Vérifier qu'il n'existe pas déjà une déclaration pour cette période
        $existante = new TVADeclaration();
        $existante->setFilter('Periode_Debut', '<=%1', $periodeFin);
        $existante->setFilter('Periode_Fin',   '>=%1', $periodeDebut);
        if ($existante->FindFirst() && $existante->Statut->_value !== 'Brouillon')
            Error("Une déclaration de TVA validée couvre déjà cette période.");

        if (empty($code))
            $code = 'TVA-'.date('Ym', strtotime($periodeDebut));

        $decl = new TVADeclaration();
        $decl->Validate('Code',          $code);
        $decl->Validate('Periode_Debut', $periodeDebut);
        $decl->Validate('Periode_Fin',   $periodeFin);
        $decl->Validate('Date_Echeance', self::_calculerEcheance($periodeFin));
        $decl->Insert();

        self::calculerDeclaration($code);

        return $decl;
    }

    /**
     * (Re)calcule les montants d'une déclaration en brouillon.
     * Lit les écritures comptables avec Type_TVA renseigné sur la période.
     */
    public static function calculerDeclaration(string $code): void {
        $decl = new TVADeclaration();
        if (!$decl->get($code))
            Error("Déclaration TVA '".$code."' introuvable.");
        if ($decl->Statut->_value !== 'Brouillon')
            Error("Seules les déclarations en brouillon peuvent être recalculées.");

        $debut = $decl->Periode_Debut->_value;
        $fin   = $decl->Periode_Fin->_value;

        // TVA collectée — écritures créditrices sur comptes Type_TVA = 'Collectee'
        $collectee = self::_aggregerTVA('Collectee', $debut, $fin);
        // TVA déductible — écritures débitrices sur comptes Type_TVA = 'Deductible'
        $deductible = self::_aggregerTVA('Deductible', $debut, $fin);

        $tvaDue = max(0, $collectee['tva'] - $deductible['tva']);

        $decl->Validate('Base_TVA_Collectee',  $collectee['base']);
        $decl->Validate('TVA_Collectee',       $collectee['tva']);
        $decl->Validate('Base_TVA_Deductible', $deductible['base']);
        $decl->Validate('TVA_Deductible',      $deductible['tva']);
        $decl->Validate('TVA_Due',             $tvaDue);
        $decl->Modify(false);
    }

    /**
     * Valide une déclaration et génère l'écriture de décaissement de TVA.
     * Après validation, la déclaration est immuable.
     */
    public static function validerDeclaration(string $code, string $journalCode = 'OD'): void {
        $decl = new TVADeclaration();
        if (!$decl->get($code))
            Error("Déclaration TVA '".$code."' introuvable.");
        if ($decl->Statut->_value !== 'Brouillon')
            Error("La déclaration '".$code."' n'est plus en brouillon.");

        self::calculerDeclaration($code);
        $decl = new TVADeclaration();
        $decl->get($code);

        $tvaDue = (float)$decl->TVA_Due->_value;

        // Générer l'écriture de paiement TVA si montant > 0
        if ($tvaDue > 0) {
            $lp = new LignePiece();

            // Ligne 1 : Débit TVA collectée (solde le compte 44310)
            $lp->Validate('Journal_Code',  $journalCode);
            $lp->Validate('Piece_No',      'TVA-'.$code);
            $lp->Validate('Ligne_No',      1);
            $lp->Validate('Date_Ecriture', date('Y-m-d'));
            $lp->Validate('Compte_No',     $decl->Compte_TVA_Collectee_No->_value);
            $lp->Validate('Libelle',       'TVA collectée période '.$decl->Periode_Debut->_value.' au '.$decl->Periode_Fin->_value);
            $lp->Validate('Debit',         (float)$decl->TVA_Collectee->_value);
            $lp->Validate('Credit',        0);
            $lp->Insert(false);

            // Ligne 2 : Crédit TVA déductible (solde le compte 44540)
            $lp2 = new LignePiece();
            $lp2->Validate('Journal_Code',  $journalCode);
            $lp2->Validate('Piece_No',      'TVA-'.$code);
            $lp2->Validate('Ligne_No',      2);
            $lp2->Validate('Date_Ecriture', date('Y-m-d'));
            $lp2->Validate('Compte_No',     $decl->Compte_TVA_Deduc_No->_value);
            $lp2->Validate('Libelle',       'TVA déductible période '.$decl->Periode_Debut->_value);
            $lp2->Validate('Debit',         0);
            $lp2->Validate('Credit',        (float)$decl->TVA_Deductible->_value);
            $lp2->Insert(false);

            // Ligne 3 : Crédit État — TVA due (compte 44730 ou 44310 solde)
            $lp3 = new LignePiece();
            $lp3->Validate('Journal_Code',  $journalCode);
            $lp3->Validate('Piece_No',      'TVA-'.$code);
            $lp3->Validate('Ligne_No',      3);
            $lp3->Validate('Date_Ecriture', date('Y-m-d'));
            $lp3->Validate('Compte_No',     '44730'); // TVA due à l'État SYSCOA
            $lp3->Validate('Libelle',       'TVA due État période '.$decl->Periode_Debut->_value);
            $lp3->Validate('Debit',         0);
            $lp3->Validate('Credit',        $tvaDue);
            $lp3->Insert(false);

            ComptabiliteManagement::validerPiece($journalCode, 'TVA-'.$code);
        }

        $decl->Validate('Statut', 'Validee');
        $decl->Modify();
    }

    /**
     * Calcule le montant de TVA depuis un montant HT.
     */
    public static function calculerTVA(float $montantHT, float $taux = self::TAUX_STANDARD): array {
        $tva = round($montantHT * $taux / 100, 2);
        return [
            'ht'  => $montantHT,
            'tva' => $tva,
            'ttc' => $montantHT + $tva,
        ];
    }

    /**
     * Calcule le montant HT depuis un montant TTC.
     */
    public static function htDepuisTTC(float $montantTTC, float $taux = self::TAUX_STANDARD): array {
        $ht  = round($montantTTC / (1 + $taux / 100), 2);
        $tva = $montantTTC - $ht;
        return ['ht' => $ht, 'tva' => $tva, 'ttc' => $montantTTC];
    }

    // ─── Helpers privés ───

    private static function _aggregerTVA(string $typeTVA, string $debut, string $fin): array {
        $sensColonne = ($typeTVA === 'Collectee') ? 'Credit' : 'Debit';
        $query = 'SELECT SUM(Base_TVA) AS base, SUM('.$sensColonne.') AS tva'
               . ' FROM ecriture_comptable'
               . ' WHERE Type_TVA = "'.$typeTVA.'"'
               . ' AND Date_Ecriture BETWEEN "'.$debut.'" AND "'.$fin.'"'
               . ' AND (deleted_at = "0000-00-00 00:00:00" OR deleted_at IS NULL)';
        $rows = db->getResultAssoc($query);
        return [
            'base' => (float)($rows[0]['base'] ?? 0),
            'tva'  => (float)($rows[0]['tva']  ?? 0),
        ];
    }

    private static function _calculerEcheance(string $periodeFin): string {
        // En Côte d'Ivoire : TVA due le 15 du mois suivant la période
        $date = new DateTime($periodeFin);
        $date->modify('first day of next month');
        $date->setDate((int)$date->format('Y'), (int)$date->format('m'), 15);
        return $date->format('Y-m-d');
    }
}
?>
