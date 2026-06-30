<?php
class Article extends Table {
    public function __construct() {
        parent::__construct(61002, 'Article', 'article');

        // ── Identification ──────────────────────
        $this->field(1,  'Code',                  FieldType::text(20, 'NOT NULL'));
        $this->field(2,  'Description',           FieldType::text(150, 'NOT NULL'));
        $this->field(3,  'Description2',          FieldType::text(150),  caption: 'Description complémentaire');
        $this->field(4,  'Type',                  FieldType::text(30, 'NOT NULL'));
        $this->field(5,  'Categorie_Code',        FieldType::text(20),   caption: 'Catégorie');
        $this->field(6,  'UM_Code',               FieldType::text(10, 'NOT NULL'), caption: 'Unité de mesure');
        $this->field(7,  'Code_Barre',            FieldType::text(50),   caption: 'Code barre (EAN)');
        $this->field(8,  'Reference_Fournisseur', FieldType::text(50),   caption: 'Référence fournisseur');

        // ── Suivi avancé ────────────────────────
        $this->field(10, 'Suivi_Lot',             FieldType::boolean(),  caption: 'Suivi par lot');
        $this->field(11, 'Suivi_Serie',           FieldType::boolean(),  caption: 'Suivi par numéro de série');
        $this->field(12, 'DLUO_Obligatoire',      FieldType::boolean(),  caption: 'Date limite utilisation optimale');

        // ── Valorisation ────────────────────────
        $this->field(20, 'Methode_Valorisation',  FieldType::text(20));
        $this->field(21, 'Cout_Standard',         FieldType::decimal(),  caption: 'Prix de revient standard');
        $this->field(22, 'CMUP_Actuel',           FieldType::decimal(),  caption: 'CMUP actuel (calculé)');
        $this->field(23, 'Prix_Achat_Dernier',    FieldType::decimal(),  caption: 'Dernier prix d\'achat');
        $this->field(24, 'Prix_Vente_HT',         FieldType::decimal(),  caption: 'Prix de vente HT');
        $this->field(25, 'Marge_Pct',             FieldType::decimal(),  caption: 'Marge cible %');

        // ── Seuils ──────────────────────────────
        $this->field(30, 'Stock_Min',             FieldType::decimal(),  caption: 'Stock minimum');
        $this->field(31, 'Stock_Max',             FieldType::decimal(),  caption: 'Stock maximum');
        $this->field(32, 'Stock_Reappro',         FieldType::decimal(),  caption: 'Point de réapprovisionnement');
        $this->field(33, 'Delai_Appro_Jours',     FieldType::integer(),  caption: 'Délai approvisionnement (jours)');

        // ── Comptes comptables ──────────────────
        $this->field(40, 'Compte_Stock_No',       FieldType::text(20),   caption: 'Compte stock (surcharge catégorie)');
        $this->field(41, 'Compte_Achat_No',       FieldType::text(20),   caption: 'Compte achats (surcharge)');
        $this->field(42, 'Compte_Vente_No',       FieldType::text(20),   caption: 'Compte ventes (surcharge)');

        // ── Statut ──────────────────────────────
        $this->field(50, 'Est_Actif',             FieldType::boolean());
        $this->field(51, 'Bloque_Achat',          FieldType::boolean());
        $this->field(52, 'Bloque_Vente',          FieldType::boolean());
        $this->field(99, 'Tenant_code',           FieldType::text(30, 'NOT NULL'));

        $this->Keys('Code');

        // ── FlowFields ──────────────────────────
        $this->flowField(100, 'Stock_Actuel', FieldType::decimal(),
            CalcFormula::Sum('EcritureStock', 'Quantite_Nette', ['Article_Code' => 'Code']),
            caption: 'Stock actuel'
        );
        $this->flowField(101, 'Valeur_Stock', FieldType::decimal(),
            CalcFormula::Sum('EcritureStock', 'Valeur_Nette', ['Article_Code' => 'Code']),
            caption: 'Valeur stock (FCFA)'
        );
        $this->flowField(102, 'Nb_Mouvements', FieldType::integer(),
            CalcFormula::Count('EcritureStock', ['Article_Code' => 'Code']),
            caption: 'Nb mouvements'
        );
        $this->flowField(103, 'Stock_Provisoire', FieldType::decimal(),
            CalcFormula::Sum('MouvementStockProvisoire', 'Quantite_Nette', ['Article_Code' => 'Code']),
            caption: 'En cours (non validé)'
        );
    }

    public function onInsert(): void {
        if (empty($this->UM_Code->_value))
            Error("L'unité de mesure est obligatoire.");
        if ($this->Type->_value === TypeArticle::Stock->value
            && empty($this->Methode_Valorisation->_value))
            $this->Methode_Valorisation->_value = MethodeValorisation::CMUP->value;
    }

    public function onModify(): void {
        $ec = new EcritureStock();
        $ec->setRange('Article_Code', $this->Code->_value);
        if ($ec->FindFirst() && $this->_original['Methode_Valorisation'] !== $this->Methode_Valorisation->_value)
            Error("Impossible de changer la méthode de valorisation : des mouvements existent déjà.");
    }

    public function onDelete(): void {
        $ec = new EcritureStock();
        $ec->setRange('Article_Code', $this->Code->_value);
        if ($ec->FindFirst())
            Error("L'article '".$this->Code->_value."' a des écritures de stock. Désactivez-le plutôt que de le supprimer.");
    }
}
?>
