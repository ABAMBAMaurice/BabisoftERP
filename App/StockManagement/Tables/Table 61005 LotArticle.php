<?php
class LotArticle extends Table {
    public function __construct() {
        parent::__construct(61005, 'LotArticle', 'lot_article');

        $this->field(1,  'No_Lot',             FieldType::text(50, 'NOT NULL'));
        $this->field(2,  'Article_Code',        FieldType::text(20, 'NOT NULL'));
        $this->field(3,  'Date_Fabrication',    FieldType::date(),   caption: 'Date de fabrication');
        $this->field(4,  'Date_Expiration',     FieldType::date(),   caption: 'Date d\'expiration (DLUO)');
        $this->field(5,  'Date_Entree',         FieldType::date(),   caption: 'Date de première entrée');
        $this->field(6,  'Fournisseur_Code',    FieldType::text(20), caption: 'Fournisseur d\'origine');
        $this->field(7,  'No_Lot_Fournisseur',  FieldType::text(50), caption: 'N° lot fournisseur');
        $this->field(8,  'Description',         FieldType::text(200));
        $this->field(9,  'Tenant_code',         FieldType::text(30, 'NOT NULL'));

        $this->Keys('No_Lot', 'Article_Code');

        $this->flowField(100, 'Stock_Lot', FieldType::decimal(),
            CalcFormula::Sum('EcritureStock', 'Quantite_Nette',
                ['Article_Code' => 'Article_Code', 'No_Lot' => 'No_Lot']),
            caption: 'Stock du lot'
        );
        $this->flowField(101, 'Valeur_Lot', FieldType::decimal(),
            CalcFormula::Sum('EcritureStock', 'Valeur_Nette',
                ['Article_Code' => 'Article_Code', 'No_Lot' => 'No_Lot']),
            caption: 'Valeur du lot (FCFA)'
        );
        $this->flowField(102, 'Nb_Mouvements', FieldType::integer(),
            CalcFormula::Count('EcritureStock',
                ['Article_Code' => 'Article_Code', 'No_Lot' => 'No_Lot']),
            caption: 'Nb mouvements'
        );
    }

    public function onInsert(): void {
        $article = new Article();
        if (!$article->get($this->Article_Code->_value))
            Error("Article '".$this->Article_Code->_value."' introuvable.");
        if (!$article->Suivi_Lot->_value)
            Error("L'article '".$this->Article_Code->_value."' n'est pas configuré pour le suivi par lot.");
        if (empty($this->Date_Entree->_value))
            $this->Date_Entree->_value = date('Y-m-d');
    }
}
?>
