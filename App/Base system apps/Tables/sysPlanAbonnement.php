<?php
class PlanAbonnement extends Table {
    public function __construct() {
        parent::__construct(9999984, 'planabonnement');

        $this->field(1,  'Code',            FieldType::text(30),          caption: 'Code');
        $this->field(2,  'Nom',             FieldType::text(100),         caption: 'Nom du plan');
        $this->field(3,  'Nb_users_max',    FieldType::text(10),          caption: 'Utilisateurs max');
        $this->field(4,  'Nb_projets_max',  FieldType::text(10),          caption: 'Projets max');
        $this->field(5,  'Montant_mensuel', FieldType::decimal('(10,2)'), caption: 'Montant mensuel');
        $this->field(6,  'Comptabilite',    FieldType::boolean(),         caption: 'Module Comptabilité');
        $this->field(7,  'Stock',           FieldType::boolean(),         caption: 'Module Stock');
        $this->field(8,  'Ventes',          FieldType::boolean(),         caption: 'Module Ventes');
        $this->field(9,  'Description',     FieldType::text(500),         caption: 'Description');

        $this->Keys('Code');
    }

    public function onInsert(): void {
        if (IsNullOrEmptyString($this->Nb_users_max->value))
            $this->Validate('Nb_users_max', '5');
        if (IsNullOrEmptyString($this->Nb_projets_max->value))
            $this->Validate('Nb_projets_max', '0');
        if (IsNullOrEmptyString($this->Montant_mensuel->value))
            $this->Validate('Montant_mensuel', '0');
    }

    public function onModify(): void {}
    public function onDelete(): void {}
}
?>
