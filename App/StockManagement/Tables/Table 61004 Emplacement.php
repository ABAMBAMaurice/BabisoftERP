<?php
class Emplacement extends Table {
    public function __construct() {
        parent::__construct(61004, 'Emplacement', 'emplacement');

        $this->field(1,  'Code',             FieldType::text(20, 'NOT NULL'));
        $this->field(2,  'Entrepot_Code',    FieldType::text(10, 'NOT NULL'));
        $this->field(3,  'Zone',             FieldType::text(20),   caption: 'Zone (A, B, C...)');
        $this->field(4,  'Rangee',           FieldType::text(10),   caption: 'Rangée');
        $this->field(5,  'Niveau',           FieldType::text(10),   caption: 'Niveau / étage');
        $this->field(6,  'Type',             FieldType::text(30),   caption: 'Type (Réception|Stockage|Expédition|Contrôle)');
        $this->field(7,  'Capacite_Max',     FieldType::decimal(),  caption: 'Capacité maximale');
        $this->field(8,  'Description',      FieldType::text(100));
        $this->field(9,  'Est_Actif',        FieldType::boolean());
        $this->field(10, 'Tenant_code',      FieldType::text(30, 'NOT NULL'));

        $this->Keys('Code', 'Entrepot_Code');

        $this->flowField(100, 'Stock_Total', FieldType::decimal(),
            CalcFormula::Sum('EcritureStock', 'Quantite_Nette',
                ['Emplacement_Code' => 'Code', 'Entrepot_Code' => 'Entrepot_Code']),
            caption: 'Stock total (toutes références)'
        );
    }

    public function onInsert(): void {
        $entrepot = new Entrepot();
        if (!$entrepot->get($this->Entrepot_Code->_value))
            Error("L'entrepôt '".$this->Entrepot_Code->_value."' n'existe pas.");
    }

    public function onDelete(): void {
        $ec = new EcritureStock();
        $ec->setRange('Emplacement_Code', $this->Code->_value);
        $ec->setRange('Entrepot_Code',    $this->Entrepot_Code->_value);
        if ($ec->FindFirst())
            Error("L'emplacement '".$this->Code->_value."' contient du stock. Transférez-le avant de le supprimer.");
    }
}
?>
