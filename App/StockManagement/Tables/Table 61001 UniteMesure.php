<?php
class UniteMesure extends Table {
    public function __construct() {
        parent::__construct(61001, 'UniteMesure', 'unite_mesure');

        $this->field(1, 'Code',        FieldType::text(10, 'NOT NULL'));
        $this->field(2, 'Intitule',    FieldType::text(50, 'NOT NULL'));
        $this->field(3, 'Symbole',     FieldType::text(10),  caption: 'Symbole (kg, L, m...)');
        $this->field(4, 'Decimales',   FieldType::integer(),  caption: 'Décimales autorisées');
        $this->field(5, 'Tenant_code', FieldType::text(30, 'NOT NULL'));

        $this->Keys('Code');
    }

    public function onDelete(): void {
        $a = new Article();
        $a->setRange('UM_Code', $this->Code->_value);
        if ($a->FindFirst())
            Error("L'unité de mesure '".$this->Code->_value."' est utilisée par des articles.");
    }
}
?>
