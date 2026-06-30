<?php

/**
 * Valorisation du stock.
 * Gère les méthodes CMUP, FIFO et Prix Standard.
 *
 * Règle SYSCOA/OHADA :
 *  Le CMUP est la méthode par défaut. Il est recalculé après chaque entrée.
 *  Les sorties sont valorisées au CMUP en vigueur au moment de la sortie.
 */
class ValorisationManagement {

    // ─────────────────────────────────────────────
    //  OBTENIR LE COÛT D'UN MOUVEMENT
    // ─────────────────────────────────────────────

    /**
     * Retourne le coût unitaire à appliquer pour ce mouvement.
     *
     * - Entrée : utilise le coût saisi (prix d'achat, etc.)
     * - Sortie CMUP : utilise CMUP_Actuel de l'article
     * - Sortie FIFO : prend la plus ancienne couche disponible
     * - Standard : utilise Cout_Standard (entrée ET sortie)
     */
    public static function getCout(
        Article $article,
        TypeMouvement $typeMvt,
        float $quantite,
        float $coutSaisi = 0.0
    ): float {
        $methode = MethodeValorisation::from($article->Methode_Valorisation->_value);

        if ($methode === MethodeValorisation::Standard)
            return (float)$article->Cout_Standard->_value;

        if ($typeMvt->estEntree())
            return $coutSaisi > 0 ? $coutSaisi : (float)$article->CMUP_Actuel->_value;

        // Sortie
        return match($methode) {
            MethodeValorisation::CMUP => (float)$article->CMUP_Actuel->_value,
            MethodeValorisation::FIFO => self::_coutFIFO($article->Code->_value, $quantite),
        };
    }

    // ─────────────────────────────────────────────
    //  MISE À JOUR APRÈS MOUVEMENT
    // ─────────────────────────────────────────────

    /**
     * Met à jour la valorisation de l'article après une écriture stock.
     * Retourne le CMUP après mise à jour.
     */
    public static function mettreAJour(Article $article, EcritureStock $ec, int $ecritureNo): float {
        $methode  = MethodeValorisation::from($article->Methode_Valorisation->_value);
        $typeMvt  = TypeMouvement::from($ec->Type_Mouvement->_value);

        // Mettre à jour le dernier prix d'achat
        if ($typeMvt->estEntree() && $typeMvt === TypeMouvement::Achat) {
            $article->Validate('Prix_Achat_Dernier', (float)$ec->Cout_Unitaire->_value);
            $article->Modify(false);
        }

        $cmupApres = (float)$article->CMUP_Actuel->_value;

        if ($methode === MethodeValorisation::CMUP && $typeMvt->estEntree()) {
            $cmupApres = self::_recalculerCMUP($article, $ec);
            $article->Validate('CMUP_Actuel', $cmupApres);
            $article->Modify(false);
        }

        if ($methode === MethodeValorisation::FIFO) {
            if ($typeMvt->estEntree()) {
                self::_creerCoucheFIFO($article->Code->_value, $ecritureNo, $ec);
            } else {
                self::_consommerCouchesFIFO($article->Code->_value, (float)$ec->Quantite->_value);
            }
        }

        return $cmupApres;
    }

    // ─────────────────────────────────────────────
    //  RAPPORT DE VALORISATION
    // ─────────────────────────────────────────────

    /**
     * Rapport de valorisation globale du stock à une date donnée.
     * Retourne : Article | Description | Qté | CMUP | Valeur totale
     */
    public static function getValorisationGlobale(string $dateArrete): array {
        $query = 'SELECT a.Code, a.Description, a.CMUP_Actuel, a.Methode_Valorisation,'
               . ' a.Cout_Standard,'
               . ' SUM(es.Quantite_Nette) AS stock_qte,'
               . ' SUM(es.Valeur_Nette)   AS stock_valeur'
               . ' FROM article a'
               . ' LEFT JOIN ecriture_stock es ON es.Article_Code = a.Code'
               . '   AND es.Date_Ecriture <= "'.$dateArrete.'"'
               . '   AND (es.deleted_at = "0000-00-00 00:00:00" OR es.deleted_at IS NULL)'
               . ' WHERE a.Est_Actif = 1 AND a.Type = "Stock"'
               . ' GROUP BY a.Code, a.Description, a.CMUP_Actuel, a.Methode_Valorisation, a.Cout_Standard'
               . ' HAVING stock_qte > 0'
               . ' ORDER BY a.Code ASC';

        $rows  = db->getResultAssoc($query);
        $total = 0;
        $lignes = [];

        foreach ($rows as $row) {
            $methode = MethodeValorisation::from($row['Methode_Valorisation']);
            $cmup    = $methode === MethodeValorisation::Standard
                       ? (float)$row['Cout_Standard']
                       : (float)$row['CMUP_Actuel'];

            $valeur  = round((float)$row['stock_qte'] * $cmup, 2);
            $total  += $valeur;

            $lignes[] = [
                'code'        => $row['Code'],
                'description' => $row['Description'],
                'qte'         => (float)$row['stock_qte'],
                'cmup'        => $cmup,
                'methode'     => $row['Methode_Valorisation'],
                'valeur'      => $valeur,
            ];
        }

        return [
            'lignes'        => $lignes,
            'total_valeur'  => $total,
            'date_arrete'   => $dateArrete,
            'nb_references' => count($lignes),
        ];
    }

    /**
     * Recalcule le CMUP depuis zéro (utile après correction d'une écriture via ajustement).
     * Retraite toutes les écritures dans l'ordre chronologique.
     */
    public static function recalculerCMUPComplet(string $articleCode): float {
        $query = 'SELECT No_, Quantite_Nette, Cout_Unitaire, Type_Mouvement'
               . ' FROM ecriture_stock'
               . ' WHERE Article_Code = "'.$articleCode.'"'
               . ' AND (deleted_at = "0000-00-00 00:00:00" OR deleted_at IS NULL)'
               . ' ORDER BY Date_Ecriture ASC, No_ ASC';

        $rows  = db->getResultAssoc($query);
        $cmup  = 0.0;
        $qte   = 0.0;
        $valeur= 0.0;

        foreach ($rows as $row) {
            $typeMvt = TypeMouvement::from($row['Type_Mouvement']);
            $qteN    = (float)$row['Quantite_Nette'];
            $cu      = (float)$row['Cout_Unitaire'];

            if ($typeMvt->estEntree() && $qteN > 0) {
                $valeur += $qteN * $cu;
                $qte    += $qteN;
                $cmup    = $qte > 0 ? $valeur / $qte : 0;
            } else {
                $valeur += $qteN * $cmup; // négatif car $qteN < 0
                $qte    += $qteN;
                if ($qte <= 0) { $cmup = 0; $valeur = 0; $qte = 0; }
            }
        }

        $article = new Article();
        if ($article->get($articleCode)) {
            $article->Validate('CMUP_Actuel', $cmup);
            $article->Modify(false);
        }

        return $cmup;
    }

    // ─── Helpers privés ───

    private static function _recalculerCMUP(Article $article, EcritureStock $ec): float {
        // CMUP = (Stock_avant × CMUP_avant + Qté_entrée × Coût_entrée) / (Stock_avant + Qté_entrée)
        $stockAvant = StockManagement::getStock($article->Code->_value);
        $cmupAvant  = (float)$article->CMUP_Actuel->_value;
        $qteEntree  = (float)$ec->Quantite->_value;
        $coutEntree = (float)$ec->Cout_Unitaire->_value;

        // Le stock retourné par getStock inclut déjà cette écriture → soustraire
        $stockAvant = $stockAvant - $qteEntree;

        $valeurAvant  = max(0, $stockAvant) * $cmupAvant;
        $valeurEntree = $qteEntree * $coutEntree;
        $stockTotal   = max(0, $stockAvant) + $qteEntree;

        if ($stockTotal <= 0) return 0.0;

        return round(($valeurAvant + $valeurEntree) / $stockTotal, 6);
    }

    private static function _coutFIFO(string $articleCode, float $quantiteRequise): float {
        // Lire les couches FIFO dans l'ordre (la plus ancienne en premier)
        $query = 'SELECT Cout_Unitaire, Quantite_Restante FROM couche_cout_fifo'
               . ' WHERE Article_Code = "'.$articleCode.'"'
               . '   AND Quantite_Restante > 0'
               . '   AND (deleted_at = "0000-00-00 00:00:00" OR deleted_at IS NULL)'
               . ' ORDER BY Date_Entree ASC, No_ ASC';

        $couches      = db->getResultAssoc($query);
        $qteRestante  = $quantiteRequise;
        $valeurTotale = 0.0;

        foreach ($couches as $couche) {
            if ($qteRestante <= 0) break;
            $qteCouche    = min((float)$couche['Quantite_Restante'], $qteRestante);
            $valeurTotale += $qteCouche * (float)$couche['Cout_Unitaire'];
            $qteRestante  -= $qteCouche;
        }

        if ($qteRestante > 0)
            Error("Stock FIFO insuffisant : ".$qteRestante." unité(s) sans couche de coût.");

        return $quantiteRequise > 0 ? round($valeurTotale / $quantiteRequise, 6) : 0;
    }

    private static function _creerCoucheFIFO(string $articleCode, int $ecritureNo, EcritureStock $ec): void {
        $couche = new CoucheCoutFIFO();
        $max    = $couche->aggregateSQL('MAX', 'No_');
        $couche->Validate('No_',               ((int)$max) + 1);
        $couche->Validate('Article_Code',      $articleCode);
        $couche->Validate('No_Ecriture',       $ecritureNo);
        $couche->Validate('Date_Entree',       $ec->Date_Ecriture->_value);
        $couche->Validate('Cout_Unitaire',     (float)$ec->Cout_Unitaire->_value);
        $couche->Validate('Quantite_Initiale', (float)$ec->Quantite->_value);
        $couche->Validate('Quantite_Restante', (float)$ec->Quantite->_value);
        $couche->Validate('Entrepot_Code',     $ec->Entrepot_Code->_value);
        $couche->Validate('No_Lot',            $ec->No_Lot->_value);
        $couche->Validate('Tenant_code',       $ec->Tenant_code->_value);
        $couche->Insert(false);
    }

    private static function _consommerCouchesFIFO(string $articleCode, float $quantite): void {
        $query = 'SELECT No_, Quantite_Restante FROM couche_cout_fifo'
               . ' WHERE Article_Code = "'.$articleCode.'" AND Quantite_Restante > 0'
               . '   AND (deleted_at = "0000-00-00 00:00:00" OR deleted_at IS NULL)'
               . ' ORDER BY Date_Entree ASC, No_ ASC';

        $couches     = db->getResultAssoc($query);
        $qteRestante = $quantite;

        foreach ($couches as $row) {
            if ($qteRestante <= 0) break;
            $qtePrise  = min((float)$row['Quantite_Restante'], $qteRestante);
            $nouvQte   = (float)$row['Quantite_Restante'] - $qtePrise;

            $couche = new CoucheCoutFIFO();
            $couche->Validate('No_',               (int)$row['No_']);
            $couche->Validate('Quantite_Restante', $nouvQte);
            $couche->Modify(false);

            $qteRestante -= $qtePrise;
        }
    }
}
?>
