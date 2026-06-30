<?php

/**
 * Moteur central du module stock.
 * Gère le cycle : Provisoire → Validation → EcritureStock (immuable)
 */
class StockManagement {

    // ─────────────────────────────────────────────
    //  VALIDATION D'UN MOUVEMENT
    // ─────────────────────────────────────────────

    /**
     * Valide toutes les lignes d'un document provisoire
     * et les transfère dans EcritureStock.
     *
     * @param string $documentType  Ex: 'Achat', 'Vente', 'Transfert', 'Ajustement'
     * @param string $documentNo    N° du document
     */
    public static function validerMouvement(string $documentType, string $documentNo): void {
        $lignes = new MouvementStockProvisoire();
        $lignes->setRange('Document_Type', $documentType);
        $lignes->setRange('Document_No',   $documentNo);

        if (!$lignes->FindAll() || empty($lignes->recordSet))
            Error("Aucune ligne provisoire trouvée pour le document '".$documentNo."'.");

        $nextNo = self::_nextEcritureNo();

        foreach ($lignes->recordSet as $ligne) {
            $article = new Article();
            $article->get($ligne->Article_Code->_value);

            // Vérifier le stock suffisant pour les sorties
            $typeMvt = TypeMouvement::from($ligne->Type_Mouvement->_value);
            if (!$typeMvt->estEntree()) {
                self::_verifierStockDisponible(
                    $ligne->Article_Code->_value,
                    $ligne->Entrepot_Code->_value,
                    (float)$ligne->Quantite->_value
                );
            }

            // Déterminer le coût unitaire selon la méthode de valorisation
            $coutUnitaire = ValorisationManagement::getCout(
                $article,
                $typeMvt,
                (float)$ligne->Quantite->_value,
                (float)$ligne->Cout_Unitaire->_value
            );

            $quantiteNette = (float)$ligne->Quantite->_value * $typeMvt->sensNumerique();
            $valeurNette   = round($quantiteNette * $coutUnitaire, 2);

            // Créer l'écriture définitive
            $ec = new EcritureStock();
            $ec->Validate('No_',                $nextNo++);
            $ec->Validate('Date_Ecriture',      $ligne->Date_Mouvement->_value);
            $ec->Validate('Document_Type',      $documentType);
            $ec->Validate('Document_No',        $documentNo);
            $ec->Validate('Article_Code',       $ligne->Article_Code->_value);
            $ec->Validate('Description',        $ligne->Description->_value);
            $ec->Validate('Type_Mouvement',     $ligne->Type_Mouvement->_value);
            $ec->Validate('Entrepot_Code',      $ligne->Entrepot_Code->_value);
            $ec->Validate('Emplacement_Code',   $ligne->Emplacement_Code->_value);
            $ec->Validate('No_Lot',             $ligne->No_Lot->_value);
            $ec->Validate('No_Serie',           $ligne->No_Serie->_value);
            $ec->Validate('Quantite',           (float)$ligne->Quantite->_value);
            $ec->Validate('Quantite_Nette',     $quantiteNette);
            $ec->Validate('Cout_Unitaire',      $coutUnitaire);
            $ec->Validate('Valeur_Totale',      abs($valeurNette));
            $ec->Validate('Valeur_Nette',       $valeurNette);
            $ec->Validate('Methode_Valorisation',$article->Methode_Valorisation->_value);
            $ec->Validate('Tenant_code',        $ligne->Tenant_code->_value);
            $ec->Insert(false); // onInsert de Table gère les validations

            // Mettre à jour la valorisation de l'article
            $cmupApres = ValorisationManagement::mettreAJour($article, $ec, $nextNo - 1);
            $ec->Validate('CMUP_Apres', $cmupApres);
            $ec->Modify(false);

            // Gérer les lots / numéros de série
            self::_mettreAJourLotSerie($ligne, $typeMvt, $ligne->Entrepot_Code->_value, $ligne->Emplacement_Code->_value);

            // Pour les transferts : créer l'écriture d'entrée dans l'entrepôt destination
            if ($typeMvt === TypeMouvement::Transfert_Sortant && !empty($ligne->Entrepot_Dest_Code->_value)) {
                self::_creerEntreeTransfert($ligne, $coutUnitaire, $nextNo++);
            }
        }

        // Supprimer les lignes provisoires
        foreach ($lignes->recordSet as $ligne) {
            $lp = new MouvementStockProvisoire();
            $lp->Validate('Document_Type', $ligne->Document_Type->_value);
            $lp->Validate('Document_No',   $ligne->Document_No->_value);
            $lp->Validate('Ligne_No',      $ligne->Ligne_No->_value);
            $lp->Delete(false);
        }
    }

    // ─────────────────────────────────────────────
    //  AJUSTEMENT DIRECT (sans provisoire)
    // ─────────────────────────────────────────────

    /**
     * Génère un ajustement de stock immédiat (bypass du provisoire).
     * Usage : corrections ponctuelles, intégrations externes.
     */
    public static function ajuster(
        string $articleCode,
        string $entrepotCode,
        float  $quantite,       // positive = entrée, négative = sortie
        string $motif = 'Ajustement manuel',
        string $emplacementCode = '',
        float  $coutUnitaire = 0.0
    ): void {
        if ($quantite == 0)
            Error("La quantité d'ajustement ne peut pas être nulle.");

        $article = new Article();
        if (!$article->get($articleCode))
            Error("Article '".$articleCode."' introuvable.");

        $typeMvt = $quantite > 0 ? TypeMouvement::Ajustement_Plus : TypeMouvement::Ajustement_Moins;

        if ($coutUnitaire <= 0)
            $coutUnitaire = (float)$article->CMUP_Actuel->_value;

        $docNo  = 'AJT-'.date('YmdHis');
        $nextNo = self::_nextEcritureNo();
        $qteAbs = abs($quantite);

        if (!$typeMvt->estEntree())
            self::_verifierStockDisponible($articleCode, $entrepotCode, $qteAbs);

        $qteNette  = $quantite;
        $valNette  = round($qteNette * $coutUnitaire, 2);

        $ec = new EcritureStock();
        $ec->Validate('No_',              $nextNo);
        $ec->Validate('Date_Ecriture',    date('Y-m-d'));
        $ec->Validate('Document_Type',    'Ajustement');
        $ec->Validate('Document_No',      $docNo);
        $ec->Validate('Article_Code',     $articleCode);
        $ec->Validate('Description',      $motif);
        $ec->Validate('Type_Mouvement',   $typeMvt->value);
        $ec->Validate('Entrepot_Code',    $entrepotCode);
        $ec->Validate('Emplacement_Code', $emplacementCode);
        $ec->Validate('Quantite',         $qteAbs);
        $ec->Validate('Quantite_Nette',   $qteNette);
        $ec->Validate('Cout_Unitaire',    $coutUnitaire);
        $ec->Validate('Valeur_Totale',    abs($valNette));
        $ec->Validate('Valeur_Nette',     $valNette);
        $ec->Insert(false);

        ValorisationManagement::mettreAJour($article, $ec, $nextNo);
    }

    // ─────────────────────────────────────────────
    //  REQUÊTES STOCK
    // ─────────────────────────────────────────────

    /**
     * Stock disponible d'un article dans un entrepôt.
     */
    public static function getStock(string $articleCode, string $entrepotCode = ''): float {
        $ec = new EcritureStock();
        $ec->setRange('Article_Code', $articleCode);
        if (!empty($entrepotCode))
            $ec->setRange('Entrepot_Code', $entrepotCode);
        return (float)($ec->aggregateSQL('SUM', 'Quantite_Nette') ?? 0);
    }

    /**
     * Valeur du stock d'un article.
     */
    public static function getValeurStock(string $articleCode, string $entrepotCode = ''): float {
        $ec = new EcritureStock();
        $ec->setRange('Article_Code', $articleCode);
        if (!empty($entrepotCode))
            $ec->setRange('Entrepot_Code', $entrepotCode);
        return (float)($ec->aggregateSQL('SUM', 'Valeur_Nette') ?? 0);
    }

    /**
     * Liste des articles dont le stock est en dessous du seuil minimum.
     */
    public static function getArticlesEnRupture(): array {
        $query = 'SELECT a.Code, a.Description, a.Stock_Min,'
               . ' SUM(es.Quantite_Nette) AS stock_actuel'
               . ' FROM article a'
               . ' LEFT JOIN ecriture_stock es ON es.Article_Code = a.Code'
               . '   AND (es.deleted_at = "0000-00-00 00:00:00" OR es.deleted_at IS NULL)'
               . ' WHERE a.Est_Actif = 1 AND a.Type = "Stock" AND a.Stock_Min > 0'
               . ' GROUP BY a.Code, a.Description, a.Stock_Min'
               . ' HAVING stock_actuel <= a.Stock_Min'
               . ' ORDER BY (a.Stock_Min - stock_actuel) DESC';
        return db->getResultAssoc($query);
    }

    // ─────────────────────────────────────────────
    //  HELPERS PRIVÉS
    // ─────────────────────────────────────────────

    private static function _nextEcritureNo(): int {
        $ec  = new EcritureStock();
        $max = $ec->aggregateSQL('MAX', 'No_');
        return ((int)$max) + 1;
    }

    private static function _verifierStockDisponible(string $articleCode, string $entrepotCode, float $quantite): void {
        $stock = self::getStock($articleCode, $entrepotCode);
        if ($stock < $quantite)
            Error("Stock insuffisant pour l'article '".$articleCode."'"
                . " (disponible : ".$stock.", demandé : ".$quantite.").");
    }

    private static function _mettreAJourLotSerie(
        MouvementStockProvisoire $ligne,
        TypeMouvement $typeMvt,
        string $entrepotCode,
        string $emplacementCode
    ): void {
        if (!empty($ligne->No_Serie->_value)) {
            $ns = new NumeroSerie();
            if ($ns->get($ligne->No_Serie->_value, $ligne->Article_Code->_value)) {
                $ns->Validate('Statut',           $typeMvt->estEntree() ? 'Disponible' : 'Sorti');
                $ns->Validate('Entrepot_Code',    $typeMvt->estEntree() ? $entrepotCode : '');
                $ns->Validate('Emplacement_Code', $typeMvt->estEntree() ? $emplacementCode : '');
                if (!$typeMvt->estEntree())
                    $ns->Validate('Date_Sortie', date('Y-m-d'));
                $ns->Modify(false);
            }
        }
    }

    private static function _creerEntreeTransfert(
        MouvementStockProvisoire $ligne,
        float $coutUnitaire,
        int $no
    ): void {
        $qteNette = (float)$ligne->Quantite->_value; // entrée dans l'entrepôt dest
        $ec = new EcritureStock();
        $ec->Validate('No_',             $no);
        $ec->Validate('Date_Ecriture',   $ligne->Date_Mouvement->_value);
        $ec->Validate('Document_Type',   'Transfert');
        $ec->Validate('Document_No',     $ligne->Document_No->_value.'-IN');
        $ec->Validate('Article_Code',    $ligne->Article_Code->_value);
        $ec->Validate('Description',     'Transfert reçu de '.$ligne->Entrepot_Code->_value);
        $ec->Validate('Type_Mouvement',  TypeMouvement::Transfert_Entrant->value);
        $ec->Validate('Entrepot_Code',   $ligne->Entrepot_Dest_Code->_value);
        $ec->Validate('Emplacement_Code',$ligne->Emplacement_Dest_Code->_value);
        $ec->Validate('No_Lot',          $ligne->No_Lot->_value);
        $ec->Validate('No_Serie',        $ligne->No_Serie->_value);
        $ec->Validate('Quantite',        (float)$ligne->Quantite->_value);
        $ec->Validate('Quantite_Nette',  $qteNette);
        $ec->Validate('Cout_Unitaire',   $coutUnitaire);
        $ec->Validate('Valeur_Totale',   $qteNette * $coutUnitaire);
        $ec->Validate('Valeur_Nette',    $qteNette * $coutUnitaire);
        $ec->Validate('Tenant_code',     $ligne->Tenant_code->_value);
        $ec->Insert(false);
    }
}
?>
