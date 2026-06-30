<?php

class PeriodeComptable extends Table {
    public function __construct() {
        parent::__construct(60002, 'periode_comptable');

        $this->field(1, 'Exercice_Code', FieldType::text(20),  caption: 'Exercice');
        $this->field(2, 'Numero',        FieldType::integer(),  caption: 'N° Période');
        $this->field(3, 'Intitule',      FieldType::text(50),   caption: 'Libellé');
        $this->field(4, 'Date_Debut',    FieldType::date(),     caption: 'Date début');
        $this->field(5, 'Date_Fin',      FieldType::date(),     caption: 'Date fin');
        $this->field(6, 'Statut',        FieldType::text(20),   caption: 'Statut'); // Ouverte | Fermee
        $this->field(7, 'Tenant_code',   FieldType::text(50),   caption: 'Tenant');

        $this->Keys('Exercice_Code', 'Numero');
    }

    public function onInsert() {
        $this->Validate('Statut', 'Ouverte');
    }

    public function onModify() {}
    public function onDelete() {}
}
?>
