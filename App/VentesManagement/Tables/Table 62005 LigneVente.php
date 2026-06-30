<?php
/**
 * Lignes de document de vente.
 *
 * Champs clés pour le suivi des livraisons partielles (type Commande) :
 *   Quantite           → quantité commandée
 *   Qte_A_Livrer       → à expédier (modifiable, ≤ Quantite - Qte_Livree)
 *   Qte_Livree         → expédiée et validée (mis à jour après chaque livraison)
 *   Qte_A_Facturer     → à inclure dans la prochaine facture
 *   Qte_Facturee       → facturée définitivement
 */
class LigneVente extends Table {
    public function __construct() {
        parent::__construct(62005, 'ligne_vente');

        // ── Clé ─────────────────────────────────
        $this->field(1,  'Document_Type',        FieldType::text(30, 'NOT NULL'));
        $this->field(2,  'Document_No',          FieldType::text(20, 'NOT NULL'));
        $this->field(3,  'Ligne_No',             FieldType::integer('NOT NULL'));

        // ── Produit ──────────────────────────────
        $this->field(10, 'Type_Ligne',           FieldType::text(30),   caption: 'Article | Compte_GL | Frais | Commentaire');
        $this->field(11, 'No_',                  FieldType::text(20),   caption: 'Code article ou compte G/L');
        $this->field(12, 'Description',          FieldType::text(200,  'NOT NULL'));
        $this->field(13, 'Description2',         FieldType::text(200));
        $this->field(14, 'UM_Code',              FieldType::text(10));

        // ── Quantités ────────────────────────────
        $this->field(20, 'Quantite',             FieldType::decimal('(15,4) NOT NULL'));
        $this->field(21, 'Qte_A_Livrer',         FieldType::decimal(),  caption: 'Qté à expédier');
        $this->field(22, 'Qte_Livree',           FieldType::decimal(),  caption: 'Qté expédiée (cumulé)');
        $this->field(23, 'Qte_A_Facturer',       FieldType::decimal(),  caption: 'Qté à facturer');
        $this->field(24, 'Qte_Facturee',         FieldType::decimal(),  caption: 'Qté facturée (cumulé)');

        // ── Prix et remise ───────────────────────
        $this->field(30, 'Prix_Unitaire_HT',     FieldType::decimal('(15,2) NOT NULL'));
        $this->field(31, 'Remise_Pct',           FieldType::decimal(),  caption: 'Remise ligne (%)');
        $this->field(32, 'Montant_Remise',       FieldType::decimal());
        $this->field(33, 'Montant_Ligne_HT',     FieldType::decimal(),  caption: 'Net HT (après remise)');

        // ── TVA ─────────────────────────────────
        $this->field(40, 'Type_TVA',             FieldType::text(20),   caption: 'Normale | Exonere | Export');
        $this->field(41, 'Taux_TVA_Pct',         FieldType::decimal());
        $this->field(42, 'Montant_TVA',          FieldType::decimal());
        $this->field(43, 'Montant_Ligne_TTC',    FieldType::decimal());

        // ── Comptabilisation ────────────────────
        $this->field(50, 'Compte_Vente_No',      FieldType::text(20),   caption: 'Compte de produits (70xxx)');

        // ── Stock & traçabilité ──────────────────
        $this->field(60, 'Entrepot_Code',        FieldType::text(20));
        $this->field(61, 'Emplacement_Code',     FieldType::text(20));
        $this->field(62, 'No_Lot',               FieldType::text(50));
        $this->field(63, 'No_Serie',             FieldType::text(50));

        $this->field(99, 'Tenant_code',          FieldType::text(30, 'NOT NULL'));

        $this->Keys('Document_No', 'Ligne_No');
    }

    public function onInsert(): void {
        self::_calculerMontants($this);
        self::_initialiserQtesLivraison($this);
        self::_validerArticle($this);
    }

    public function onModify(): void {
        $doc = new EnTeteVente();
        if ($doc->get($this->Document_No->_value)) {
            $statut = StatutDocumentVente::from($doc->Statut->_value);
            if (!$statut->estModifiable())
                Error("Document '".$this->Document_No->_value."' non modifiable (statut : ".$doc->Statut->_value.").");
        }
        self::_calculerMontants($this);

        $resteALivrer = (float)$this->Quantite->_value - (float)$this->Qte_Livree->_value;
        if ((float)$this->Qte_A_Livrer->_value > $resteALivrer)
            Error("La quantité à livrer (".((float)$this->Qte_A_Livrer->_value).") dépasse le reliquat (".$resteALivrer.").");
    }

    // ── Helpers ─────────────────────────────────────────────

    private static function _calculerMontants(Table $rec): void {
        if ($rec->Type_Ligne->_value === 'Commentaire') return;

        $qte       = (float)$rec->Quantite->_value;
        $pu        = (float)$rec->Prix_Unitaire_HT->_value;
        $remisePct = (float)$rec->Remise_Pct->_value;

        $brutHT    = $qte * $pu;
        $remise    = round($brutHT * $remisePct / 100, 2);
        $netHT     = round($brutHT - $remise, 2);

        $tauxTVA    = (float)$rec->Taux_TVA_Pct->_value;
        $typeTVA    = $rec->Type_TVA->_value;
        $montantTVA = ($typeTVA === 'Exonere' || $typeTVA === 'Export')
                      ? 0.0
                      : round($netHT * $tauxTVA / 100, 2);

        $rec->Montant_Remise->_value    = $remise;
        $rec->Montant_Ligne_HT->_value  = $netHT;
        $rec->Montant_TVA->_value       = $montantTVA;
        $rec->Montant_Ligne_TTC->_value = round($netHT + $montantTVA, 2);
    }

    private static function _initialiserQtesLivraison(Table $rec): void {
        if ($rec->Type_Document->_value === TypeDocumentVente::Commande->value
            && $rec->Type_Ligne->_value  === 'Article') {
            $rec->Qte_A_Livrer->_value   = (float)$rec->Quantite->_value;
            $rec->Qte_A_Facturer->_value = (float)$rec->Quantite->_value;
        }
    }

    private static function _validerArticle(Table $rec): void {
        if ($rec->Type_Ligne->_value !== 'Article' || empty($rec->No_->_value)) return;

        $article = new Article();
        if (!$article->get($rec->No_->_value))
            Error("Article '".$rec->No_->_value."' introuvable.");
        if ($article->Bloque_Vente->_value)
            Error("L'article '".$rec->No_->_value."' est bloqué à la vente.");
        if ((float)$rec->Prix_Unitaire_HT->_value == 0 && (float)$article->Prix_Vente_HT->_value > 0)
            $rec->Prix_Unitaire_HT->_value = (float)$article->Prix_Vente_HT->_value;
        if (empty($rec->UM_Code->_value))
            $rec->UM_Code->_value = $article->UM_Code->_value;
        if (empty($rec->Compte_Vente_No->_value)) {
            $cat = new CategorieArticle();
            if ($cat->get($article->Categorie_Code->_value))
                $rec->Compte_Vente_No->_value = $article->Compte_Vente_No->_value
                    ?: $cat->Compte_Vente_No->_value;
        }
        if (empty($rec->Entrepot_Code->_value)) {
            $doc = new EnTeteVente();
            if ($doc->get($rec->Document_No->_value))
                $rec->Entrepot_Code->_value = $doc->Entrepot_Code->_value;
        }

        self::_calculerMontants($rec);
    }
}
?>
