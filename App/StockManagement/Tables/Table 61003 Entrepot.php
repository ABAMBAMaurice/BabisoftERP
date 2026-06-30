<?php
class Entrepot extends Table {
    public function __construct() {
        parent::__construct(61003, 'Entrepot', 'entrepot');

        $this->field(1,  'Code',               FieldType::text(10, 'NOT NULL'));
        $this->field(2,  'Nom',                FieldType::text(100, 'NOT NULL'));
        $this->field(3,  'Adresse',            FieldType::text(200));
        $this->field(4,  'Ville',              FieldType::text(50));
        $this->field(5,  'Pays',               FieldType::text(50));
        $this->field(6,  'Responsable',        FieldType::text(100));
        $this->field(7,  'Tel',                FieldType::text(20));
        $this->field(8,  'Emplacements_Actif', FieldType::boolean(), caption: 'Gestion des emplacements');
        $this->field(9,  'Est_Actif',          FieldType::boolean());
        $this->field(10, 'Tenant_code',        FieldType::text(30, 'NOT NULL'));

        $this->Keys('Code');

        $this->flowField(100, 'Nb_Emplacements', FieldType::integer(),
            CalcFormula::Count('Emplacement', ['Entrepot_Code' => 'Code']),
            caption: "Nb d'emplacements"
        );
        $this->flowField(101, 'Nb_Articles_En_Stock', FieldType::integer(),
            CalcFormula::Count('EcritureStock', ['Entrepot_Code' => 'Code']),
            caption: 'Lignes d\'écritures stock'
        );
    }

    public function onDelete(): void {
        $ec = new EcritureStock();
        $ec->setRange('Entrepot_Code', $this->Code->_value);
        if ($ec->FindFirst())
            Error("L'entrepôt '".$this->Code->_value."' contient des écritures de stock.");
    }
}
?>
