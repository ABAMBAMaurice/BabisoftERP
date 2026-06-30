<?php
class CategorieArticle extends Table {
    public function __construct() {
        parent::__construct(61000, 'CategorieArticle', 'categorie_article');

        $this->field(1,  'Code',                         FieldType::text(20, 'NOT NULL'));
        $this->field(2,  'Intitule',                     FieldType::text(100, 'NOT NULL'));
        $this->field(3,  'Compte_Stock_No',              FieldType::text(20), caption: 'Compte stock (SYSCOA)');
        $this->field(4,  'Compte_Achat_No',              FieldType::text(20), caption: 'Compte achats');
        $this->field(5,  'Compte_Vente_No',              FieldType::text(20), caption: 'Compte ventes');
        $this->field(6,  'Compte_Variation_Stock_No',    FieldType::text(20), caption: 'Variation de stocks');
        $this->field(7,  'TVA_Achat_Pct',                FieldType::decimal(), caption: 'TVA achat %');
        $this->field(8,  'TVA_Vente_Pct',                FieldType::decimal(), caption: 'TVA vente %');
        $this->field(9,  'Tenant_code',                  FieldType::text(30, 'NOT NULL'));

        $this->Keys('Code');

        $this->flowField(100, 'Nb_Articles', FieldType::integer(),
            CalcFormula::Count('Article', ['Categorie_Code' => 'Code']),
            caption: "Nb d'articles"
        );
    }

    public function onDelete(): void {
        $a = new Article();
        $a->setRange('Categorie_Code', $this->Code->_value);
        if ($a->FindFirst())
            Error("Impossible de supprimer la catégorie '".$this->Code->_value."' : des articles y sont associés.");
    }
}
?>
