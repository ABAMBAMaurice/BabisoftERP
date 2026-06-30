<?php

/**
 * Gestion du lettrage des comptes de tiers (clients, fournisseurs).
 *
 * Le lettrage consiste à rapprocher des écritures en débit et crédit
 * sur un même compte de tiers pour identifier les créances soldées.
 *
 * Règle fondamentale : la somme algébrique des écritures lettrées = 0
 * (Σ Débit lettrées = Σ Crédit lettrées sur le même compte).
 */
class LettrageManagement {

    /**
     * Lettrage manuel d'un ensemble d'écritures.
     *
     * @param array $ecritureNos  Tableau de N° d'écritures (No_) à lettrer ensemble
     * @param string $lettreCode  Code de lettrage (ex: 'A', 'B', 'AA'...) — auto-généré si vide
     */
    public static function lettrer(array $ecritureNos, string $lettreCode = ''): string {
        if (count($ecritureNos) < 2)
            Error("Le lettrage nécessite au moins 2 écritures.");

        $ecritures = self::_chargerEcritures($ecritureNos);

        // Vérification : même compte pour toutes les lignes
        $comptes = array_unique(array_map(fn($e) => $e->Compte_No->_value, $ecritures));
        if (count($comptes) > 1)
            Error("Le lettrage ne peut porter que sur un seul compte. Comptes détectés : ".implode(', ', $comptes));

        // Vérification : aucune écriture déjà lettrée
        foreach ($ecritures as $ec) {
            if (!empty($ec->Lettrage_Code->_value))
                Error("L'écriture N°".$ec->No_->_value." est déjà lettrée sous le code '".$ec->Lettrage_Code->_value."'.");
        }

        // Vérification de l'équilibre (règle fondamentale du lettrage)
        $totalDebit  = array_sum(array_map(fn($e) => (float)$e->Debit->_value,  $ecritures));
        $totalCredit = array_sum(array_map(fn($e) => (float)$e->Credit->_value, $ecritures));

        if (round($totalDebit, 2) !== round($totalCredit, 2))
            Error("Le lettrage est déséquilibré : Débit ".number_format($totalDebit,2)." ≠ Crédit ".number_format($totalCredit,2)."."
                . " Écart : ".number_format(abs($totalDebit - $totalCredit),2)." FCFA.");

        // Générer le code de lettrage si non fourni
        if (empty($lettreCode))
            $lettreCode = self::_genererCodeLettrage($comptes[0]);

        // Appliquer le lettrage
        $today = date('Y-m-d');
        foreach ($ecritures as $ec) {
            $ec->Validate('Lettrage_Code',  $lettreCode);
            $ec->Validate('Date_Lettrage',  $today);
            $ec->Validate('Statut',         StatutEcriture::Lettre->value);
            $ec->Modify(false);
        }

        return $lettreCode;
    }

    /**
     * Délettrage : annule un lettrage existant.
     * Les écritures reprennent le statut Valide.
     */
    public static function delettrer(string $lettreCode): void {
        if (empty($lettreCode))
            Error("Code de lettrage invalide.");

        $ecritures = new EcritureComptable();
        $ecritures->setRange('Lettrage_Code', $lettreCode);

        if (!$ecritures->FindAll())
            Error("Aucune écriture trouvée pour le code de lettrage '".$lettreCode."'.");

        foreach ($ecritures->recordSet as $ec) {
            $ec->Validate('Lettrage_Code', '');
            $ec->Validate('Date_Lettrage', '');
            $ec->Validate('Statut',        StatutEcriture::Valide->value);
            $ec->Modify(false);
        }
    }

    /**
     * Suggestion automatique de lettrage pour un compte de tiers.
     * Identifie les groupes d'écritures dont Σ Débit = Σ Crédit.
     *
     * @return array  Liste de groupes suggérés : [['nos' => [...], 'total' => 0], ...]
     */
    public static function suggererLettrage(string $compteNo): array {
        $ecritures = new EcritureComptable();
        $ecritures->setRange('Compte_No',     $compteNo);
        $ecritures->setRange('Lettrage_Code', '');  // non lettrées uniquement

        if (!$ecritures->FindAll())
            return [];

        $suggestions = [];
        $lignes = $ecritures->recordSet;

        // Algorithme greedy : trier par montant, chercher les paires/groupes équilibrés
        usort($lignes, fn($a, $b) => (float)$a->Debit->_value <=> (float)$b->Debit->_value);

        $debits  = array_filter($lignes, fn($e) => (float)$e->Debit->_value  > 0);
        $credits = array_filter($lignes, fn($e) => (float)$e->Credit->_value > 0);

        foreach ($debits as $d) {
            $montant = (float)$d->Debit->_value;
            foreach ($credits as $k => $c) {
                if (round((float)$c->Credit->_value, 2) === round($montant, 2)) {
                    $suggestions[] = [
                        'nos'   => [$d->No_->_value, $c->No_->_value],
                        'total' => $montant,
                    ];
                    unset($credits[$k]);
                    break;
                }
            }
        }

        return $suggestions;
    }

    // ─── Helpers privés ───

    private static function _chargerEcritures(array $nos): array {
        $result = [];
        foreach ($nos as $no) {
            $ec = new EcritureComptable();
            if (!$ec->get($no))
                Error("Écriture N°".$no." introuvable dans le grand livre.");
            $result[] = $ec;
        }
        return $result;
    }

    private static function _genererCodeLettrage(string $compteNo): string {
        // Trouver le dernier code utilisé pour ce compte et incrémenter
        $query = 'SELECT MAX(Lettrage_Code) AS max_code FROM ecriture_comptable'
               . ' WHERE Compte_No = "'.$compteNo.'"'
               . ' AND Lettrage_Code != ""'
               . ' AND (deleted_at = "0000-00-00 00:00:00" OR deleted_at IS NULL)';
        $rows     = db->getResultAssoc($query);
        $lastCode = $rows[0]['max_code'] ?? '';

        return empty($lastCode) ? 'A' : self::_incrementLettre($lastCode);
    }

    private static function _incrementLettre(string $code): string {
        $len = strlen($code);
        $code = str_split($code);
        $i = $len - 1;
        while ($i >= 0) {
            if ($code[$i] < 'Z') {
                $code[$i] = chr(ord($code[$i]) + 1);
                return implode('', $code);
            }
            $code[$i] = 'A';
            $i--;
        }
        return 'A'.implode('', $code); // débordement : AA, AAA…
    }
}
?>
