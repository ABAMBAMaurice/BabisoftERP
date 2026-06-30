<?php

/**
 * Moteur central du module de comptabilité.
 * Gère le cycle de vie complet d'une pièce comptable :
 *   Saisie (LignePiece) → Validation → Grand livre (EcritureComptable)
 */
class ComptabiliteManagement {

    // ─────────────────────────────────────────────
    //  VALIDATION D'UNE PIÈCE
    // ─────────────────────────────────────────────

    /**
     * Valide toutes les lignes d'une pièce et les transfère dans EcritureComptable.
     *
     * Règles vérifiées :
     *  1. La pièce contient au moins 2 lignes
     *  2. Σ Débit = Σ Crédit (équilibre de la partie double)
     *  3. Aucune ligne avec Débit = 0 ET Crédit = 0
     *  4. Date dans une période ouverte
     *  5. Compte non collectif et existant
     *
     * @throws Error si une règle est violée
     */
    public static function validerPiece(string $journalCode, string $pieceNo): void {
        $lignes = new LignePiece();
        $lignes->setRange('Journal_Code', $journalCode);
        $lignes->setRange('Piece_No', $pieceNo);

        if (!$lignes->FindAll() || count($lignes->recordSet) < 2)
            Error("La pièce '".$pieceNo."' doit contenir au moins 2 lignes.");

        $totalDebit  = 0;
        $totalCredit = 0;

        foreach ($lignes->recordSet as $ligne) {
            $d = (float)$ligne->Debit->_value;
            $c = (float)$ligne->Credit->_value;

            if ($d == 0 && $c == 0)
                Error("La ligne ".$ligne->Ligne_No->_value." de la pièce '".$pieceNo."' a un montant nul.");
            if ($d > 0 && $c > 0)
                Error("La ligne ".$ligne->Ligne_No->_value." ne peut pas avoir Débit ET Crédit simultanément.");

            $totalDebit  += $d;
            $totalCredit += $c;
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2))
            Error("La pièce '".$pieceNo."' est déséquilibrée : Débit ".number_format($totalDebit,2)." ≠ Crédit ".number_format($totalCredit,2)." FCFA.");

        // Déterminer exercice et période
        $premiereLigne = reset($lignes->recordSet);
        $dateEcriture  = $premiereLigne->Date_Ecriture->_value;
        [$exerciceCode, $periodeNo] = self::getExercicePeriode($dateEcriture);

        // Générer le N° d'écriture séquentiel
        $nextNo = self::_nextEcritureNo();

        // Transférer chaque ligne vers EcritureComptable
        foreach ($lignes->recordSet as $ligne) {
            $ec = new EcritureComptable();
            $ec->Validate('No_',                 $nextNo++);
            $ec->Validate('Date_Ecriture',       $ligne->Date_Ecriture->_value);
            $ec->Validate('Journal_Code',        $ligne->Journal_Code->_value);
            $ec->Validate('Piece_No',            $ligne->Piece_No->_value);
            $ec->Validate('Exercice_Code',       $exerciceCode);
            $ec->Validate('Periode_No',          $periodeNo);
            $ec->Validate('Compte_No',           $ligne->Compte_No->_value);
            $ec->Validate('Libelle',             $ligne->Libelle->_value);
            $ec->Validate('Debit',               $ligne->Debit->_value);
            $ec->Validate('Credit',              $ligne->Credit->_value);
            $ec->Validate('Type_TVA',            $ligne->Type_TVA->_value);
            $ec->Validate('Taux_TVA',            $ligne->Taux_TVA->_value);
            $ec->Validate('Base_TVA',            $ligne->Base_TVA->_value);
            $ec->Validate('Source_Type',         $ligne->Source_Type->_value);
            $ec->Validate('Source_No',           $ligne->Source_No->_value);
            $ec->Validate('Est_Contrepassation', false);
            $ec->Validate('Tenant_code',         $ligne->Tenant_code->_value);
            $ec->Insert(true);
        }

        // Supprimer les lignes provisoires
        foreach ($lignes->recordSet as $ligne) {
            $lp = new LignePiece();
            $lp->Validate('Journal_Code', $ligne->Journal_Code->_value);
            $lp->Validate('Piece_No',     $ligne->Piece_No->_value);
            $lp->Validate('Ligne_No',     $ligne->Ligne_No->_value);
            $lp->Delete(false);
        }
    }

    // ─────────────────────────────────────────────
    //  CONTREPASSATION
    // ─────────────────────────────────────────────

    /**
     * Génère une écriture inverse (débit↔crédit) pour annuler une pièce existante.
     * L'écriture de contrepassation est immédiatement validée dans le grand livre.
     *
     * @param string $pieceNo    N° de la pièce à contrepasser
     * @param string $dateContrepassation  Date de l'écriture inverse
     * @param string $journalCode          Journal cible (peut différer du journal d'origine)
     */
    public static function contrepasser(string $pieceNo, string $dateContrepassation, string $journalCode = ''): void {
        $ecritures = new EcritureComptable();
        $ecritures->setRange('Piece_No', $pieceNo);

        if (!$ecritures->FindAll())
            Error("Pièce '".$pieceNo."' introuvable dans le grand livre.");

        $premiereLigne = reset($ecritures->recordSet);
        $jCode = $journalCode ?: $premiereLigne->Journal_Code->_value;

        self::verifierPeriodeOuverte($dateContrepassation);
        [$exerciceCode, $periodeNo] = self::getExercicePeriode($dateContrepassation);

        $newPieceNo = 'CTPS-'.$pieceNo;
        $nextNo     = self::_nextEcritureNo();

        foreach ($ecritures->recordSet as $orig) {
            $ec = new EcritureComptable();
            $ec->Validate('No_',                 $nextNo++);
            $ec->Validate('Date_Ecriture',       $dateContrepassation);
            $ec->Validate('Journal_Code',        $jCode);
            $ec->Validate('Piece_No',            $newPieceNo);
            $ec->Validate('Exercice_Code',       $exerciceCode);
            $ec->Validate('Periode_No',          $periodeNo);
            $ec->Validate('Compte_No',           $orig->Compte_No->_value);
            $ec->Validate('Libelle',             'Contrepassation '.$orig->Libelle->_value);
            // Inversion débit ↔ crédit
            $ec->Validate('Debit',               $orig->Credit->_value);
            $ec->Validate('Credit',              $orig->Debit->_value);
            $ec->Validate('Type_TVA',            $orig->Type_TVA->_value);
            $ec->Validate('Taux_TVA',            $orig->Taux_TVA->_value);
            $ec->Validate('Base_TVA',            $orig->Base_TVA->_value);
            $ec->Validate('Est_Contrepassation', true);
            $ec->Validate('Piece_Origine_No',    $pieceNo);
            $ec->Validate('Tenant_code',         $orig->Tenant_code->_value);
            $ec->Insert(true);
        }
    }

    // ─────────────────────────────────────────────
    //  CLÔTURE DE PÉRIODE
    // ─────────────────────────────────────────────

    /**
     * Ferme une période comptable : bloque toute saisie sur cette période.
     */
    public static function cloturerPeriode(string $exerciceCode, int $periodeNo): void {
        $periode = new PeriodeComptable();
        if (!$periode->get($exerciceCode, $periodeNo))
            Error("Période ".$periodeNo." de l'exercice '".$exerciceCode."' introuvable.");
        if ($periode->Statut->_value === 'Fermee')
            Error("La période ".$periodeNo." est déjà fermée.");

        $periode->Validate('Statut', 'Fermee');
        $periode->Modify();
    }

    // ─────────────────────────────────────────────
    //  CLÔTURE D'EXERCICE
    // ─────────────────────────────────────────────

    /**
     * Clôture un exercice comptable et génère les écritures "À Nouveaux" (AN)
     * dans le prochain exercice pour les comptes de bilan.
     *
     * Processus SYSCOA :
     *  1. Solder les comptes de résultat (classe 6 et 7) vers 12 (Résultat net)
     *  2. Reporter les soldes des comptes de bilan (1,2,3,4,5) en AN
     *  3. Passer l'exercice en statut Clôturé
     */
    public static function cloturerExercice(string $exerciceCode, string $exerciceSuivantCode): void {
        $exercice = new ExerciceComptable();
        if (!$exercice->get($exerciceCode))
            Error("Exercice '".$exerciceCode."' introuvable.");
        if ($exercice->Statut->_value !== StatutExercice::Ouvert->value)
            Error("L'exercice '".$exerciceCode."' n'est pas ouvert.");

        $exerciceSuivant = new ExerciceComptable();
        if (!$exerciceSuivant->get($exerciceSuivantCode))
            Error("L'exercice suivant '".$exerciceSuivantCode."' n'existe pas.");

        // Calculer le résultat net (Σ Produits - Σ Charges)
        $totalProduits = self::_soldeClasseSQL('7', $exerciceCode);
        $totalCharges  = self::_soldeClasseSQL('6', $exerciceCode);
        $resultatNet   = $totalProduits - $totalCharges;

        $dateAN    = $exerciceSuivant->Date_Debut->_value;
        $nextNo    = self::_nextEcritureNo();
        $pieceAN   = 'AN-'.$exerciceSuivantCode;

        // ── Écriture de résultat : solder les comptes 6 et 7 ──
        self::_genererSoldageResultat($exerciceCode, $dateAN, $pieceAN, $nextNo);

        // ── Écritures À Nouveaux : reporter les comptes de bilan ──
        $comptes = new CompteComptable();
        $comptes->FindAll();

        foreach ($comptes->recordSet as $cpt) {
            if (!in_array($cpt->Type->_value, ['Bilan', 'Tresorerie'])) continue;

            $cpt->CalcFields('Solde_Debit', 'Solde_Credit');
            // Filtrer sur l'exercice fermé
            $soldD = self::_soldeCompteSQL($cpt->Numero->_value, $exerciceCode, 'Debit');
            $soldC = self::_soldeCompteSQL($cpt->Numero->_value, $exerciceCode, 'Credit');
            $solde = $soldD - $soldC;

            if ($solde == 0) continue;

            $ec = new EcritureComptable();
            $ec->Validate('No_',           $nextNo++);
            $ec->Validate('Date_Ecriture', $dateAN);
            $ec->Validate('Journal_Code',  'AN');
            $ec->Validate('Piece_No',      $pieceAN);
            $ec->Validate('Exercice_Code', $exerciceSuivantCode);
            $ec->Validate('Periode_No',    1);
            $ec->Validate('Compte_No',     $cpt->Numero->_value);
            $ec->Validate('Libelle',       'À Nouveau '.$exerciceCode);
            $ec->Validate('Debit',         $solde > 0 ? $solde : 0);
            $ec->Validate('Credit',        $solde < 0 ? abs($solde) : 0);
            $ec->Validate('Source_Type',   'AN');
            $ec->Validate('Source_No',     $exerciceCode);
            $ec->Insert(false);
        }

        // Fermer toutes les périodes
        $periodes = new PeriodeComptable();
        $periodes->setRange('Exercice_Code', $exerciceCode);
        if ($periodes->FindAll()) {
            foreach ($periodes->recordSet as $p) {
                $p->Validate('Statut', 'Fermee');
                $p->Modify(false);
            }
        }

        // Marquer l'exercice comme clôturé
        $exercice->Validate('Statut', StatutExercice::Cloture->value);
        $exercice->Validate('Est_Courant', false);
        $exercice->Modify();

        // Marquer le suivant comme courant
        $exerciceSuivant->Validate('Est_Courant', true);
        $exerciceSuivant->Modify();
    }

    // ─────────────────────────────────────────────
    //  UTILITAIRES PUBLICS
    // ─────────────────────────────────────────────

    /**
     * Vérifie que la date fournie tombe dans une période ouverte.
     * Appelé dans LignePiece::onInsert().
     */
    public static function verifierPeriodeOuverte(string $date): void {
        $periode = new PeriodeComptable();
        $periode->setFilter('Date_Debut', '<=%1', $date);
        $periode->setFilter('Date_Fin',   '>=%1', $date);
        $periode->setRange('Statut', 'Ouverte');
        if (!$periode->FindFirst())
            Error("Aucune période ouverte ne correspond à la date ".$date.".");
    }

    /**
     * Retourne [exerciceCode, periodeNo] pour une date donnée.
     */
    public static function getExercicePeriode(string $date): array {
        $periode = new PeriodeComptable();
        $periode->setFilter('Date_Debut', '<=%1', $date);
        $periode->setFilter('Date_Fin',   '>=%1', $date);
        $periode->setRange('Statut', 'Ouverte');
        if (!$periode->FindFirst())
            Error("Date ".$date." hors d'une période ouverte.");
        return [$periode->Exercice_Code->_value, (int)$periode->Numero->_value];
    }

    /**
     * Retourne l'exercice marqué comme courant.
     */
    public static function getExerciceCourant(): ExerciceComptable {
        $ex = new ExerciceComptable();
        $ex->setRange('Est_Courant', '1');
        if (!$ex->FindFirst())
            Error("Aucun exercice comptable courant n'est défini.");
        return $ex;
    }

    // ─────────────────────────────────────────────
    //  HELPERS PRIVÉS
    // ─────────────────────────────────────────────

    private static function _nextEcritureNo(): int {
        $ec = new EcritureComptable();
        $max = $ec->aggregateSQL('MAX', 'No_');
        return ((int)$max) + 1;
    }

    /** Solde Débit ou Crédit d'un compte sur un exercice (SQL direct). */
    private static function _soldeCompteSQL(string $compteNo, string $exerciceCode, string $sens): float {
        $ec = new EcritureComptable();
        $ec->setRange('Compte_No',    $compteNo);
        $ec->setRange('Exercice_Code', $exerciceCode);
        return (float)($ec->aggregateSQL('SUM', $sens) ?? 0);
    }

    /** Solde net d'une classe de comptes (Produits ou Charges) sur un exercice. */
    private static function _soldeClasseSQL(string $classe, string $exerciceCode): float {
        $query = 'SELECT SUM(ec.Credit) - SUM(ec.Debit) AS solde'
               . ' FROM ecriture_comptable ec'
               . ' JOIN compte_comptable cc ON cc.Numero = ec.Compte_No'
               . ' WHERE ec.Exercice_Code = "'.$exerciceCode.'"'
               . ' AND LEFT(cc.Numero, 1) = "'.$classe.'"'
               . ' AND (ec.deleted_at = "0000-00-00 00:00:00" OR ec.deleted_at IS NULL)';
        $rows = db->getResultAssoc($query);
        return (float)($rows[0]['solde'] ?? 0);
    }

    private static function _genererSoldageResultat(string $exerciceCode, string $dateAN, string $pieceAN, int &$nextNo): void {
        // Les comptes de résultat (6 et 7) sont soldés vers 12 Résultat net
        // Classe 6 → solde débiteur → on crédite le compte, on débite 12
        // Classe 7 → solde créditeur → on débite le compte, on crédite 12
        $comptes = new CompteComptable();
        $comptes->FindAll();

        $totalVerse12Debit  = 0;
        $totalVerse12Credit = 0;

        foreach ($comptes->recordSet as $cpt) {
            if ($cpt->Type->_value !== 'Resultat') continue;

            $soldD = self::_soldeCompteSQL($cpt->Numero->_value, $exerciceCode, 'Debit');
            $soldC = self::_soldeCompteSQL($cpt->Numero->_value, $exerciceCode, 'Credit');
            $solde = $soldD - $soldC; // positif = débiteur (charge), négatif = créditeur (produit)

            if ($solde == 0) continue;

            // Contrepasser le solde du compte de résultat
            $ec = new EcritureComptable();
            $ec->Validate('No_',           $nextNo++);
            $ec->Validate('Date_Ecriture', $dateAN);
            $ec->Validate('Journal_Code',  'AN');
            $ec->Validate('Piece_No',      $pieceAN.'-RES');
            $ec->Validate('Exercice_Code', $exerciceCode);
            $ec->Validate('Periode_No',    12);
            $ec->Validate('Compte_No',     $cpt->Numero->_value);
            $ec->Validate('Libelle',       'Clôture résultat '.$exerciceCode);
            $ec->Validate('Debit',         $solde < 0 ? abs($solde) : 0);  // créditeur = on débite pour solder
            $ec->Validate('Credit',        $solde > 0 ? $solde : 0);        // débiteur = on crédite pour solder
            $ec->Insert(false);

            if ($solde > 0) $totalVerse12Credit += $solde;  // charge → crédit du résultat
            else            $totalVerse12Debit  += abs($solde); // produit → débit du résultat
        }

        // Contrepartie sur compte 12 (Résultat net de l'exercice)
        if ($totalVerse12Debit > 0 || $totalVerse12Credit > 0) {
            $ec = new EcritureComptable();
            $ec->Validate('No_',           $nextNo++);
            $ec->Validate('Date_Ecriture', $dateAN);
            $ec->Validate('Journal_Code',  'AN');
            $ec->Validate('Piece_No',      $pieceAN.'-RES');
            $ec->Validate('Exercice_Code', $exerciceCode);
            $ec->Validate('Periode_No',    12);
            $ec->Validate('Compte_No',     '12000'); // Résultat net SYSCOA
            $ec->Validate('Libelle',       'Résultat net '.$exerciceCode);
            $ec->Validate('Debit',         $totalVerse12Debit);
            $ec->Validate('Credit',        $totalVerse12Credit);
            $ec->Insert(false);
        }
    }
}
?>
