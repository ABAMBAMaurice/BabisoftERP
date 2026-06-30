<?php

class ExerciceComptable extends Table {
    public function __construct() {
        parent::__construct(60001, 'exercice_comptable');

        $this->field(1, 'Code',        FieldType::text(20),  caption: 'Code');
        $this->field(2, 'Intitule',    FieldType::text(150), caption: 'Intitulé');
        $this->field(3, 'Date_Debut',  FieldType::date(),    caption: 'Date début');
        $this->field(4, 'Date_Fin',    FieldType::date(),    caption: 'Date fin');
        $this->field(5, 'Statut',      FieldType::text(20),  caption: 'Statut'); // StatutExercice
        $this->field(6, 'Est_Courant', FieldType::boolean(), caption: 'Exercice courant');
        $this->field(7, 'Tenant_code', FieldType::text(50),  caption: 'Tenant');

        $this->Keys('Code');
    }

    public function onInsert() {
        $this->Validate('Statut', StatutExercice::Ouvert->value);
        $this->Validate('Est_Courant', false);
    }

    public function onModify() {
        if ($this->Statut->_value === StatutExercice::Cloture->value)
            Error("L'exercice '".$this->Code->_value."' est clôturé. Aucune modification n'est autorisée.");
    }

    public function onDelete() {
        $ec = new EcritureComptable();
        $ec->setRange('Exercice_Code', $this->Code->_value);
        if ($ec->FindFirst())
            Error("Impossible de supprimer l'exercice '".$this->Code->_value."' : des écritures existent.");

        $p = new PeriodeComptable();
        $p->setRange('Exercice_Code', $this->Code->_value);
        if ($p->FindFirst())
            Error("Supprimez d'abord les périodes de l'exercice '".$this->Code->_value."'.");
    }
}
?>
