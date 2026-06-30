<?php

class TVADeclaration extends Table {
    public function __construct() {
        parent::__construct(60006, 'tva_declaration');

        $this->field(1,  'Code',                  FieldType::text(20),  caption: 'N°');
        $this->field(2,  'Periode_Debut',          FieldType::date(),    caption: 'Période du');
        $this->field(3,  'Periode_Fin',            FieldType::date(),    caption: 'Au');
        $this->field(4,  'Statut',                 FieldType::text(20),  caption: 'Statut'); // Brouillon | Soumise | Validee
        $this->field(5,  'Base_TVA_Collectee',     FieldType::decimal(), caption: 'Base collectée HT');
        $this->field(6,  'TVA_Collectee',          FieldType::decimal(), caption: 'TVA collectée');
        $this->field(7,  'Base_TVA_Deductible',    FieldType::decimal(), caption: 'Base déductible HT');
        $this->field(8,  'TVA_Deductible',         FieldType::decimal(), caption: 'TVA déductible');
        $this->field(9,  'TVA_Due',                FieldType::decimal(), caption: 'TVA due (à reverser)');
        $this->field(10, 'Compte_TVA_Collectee_No',FieldType::text(20),  caption: 'Compte TVA collectée');
        $this->field(11, 'Compte_TVA_Deduc_No',    FieldType::text(20),  caption: 'Compte TVA déductible');
        $this->field(12, 'Date_Echeance',          FieldType::date(),    caption: 'Échéance de paiement');
        $this->field(13, 'Tenant_code',            FieldType::text(50),  caption: 'Tenant');

        $this->Keys('Code');
    }

    public function onInsert() {
        $this->Validate('Statut', 'Brouillon');
        $this->Validate('Base_TVA_Collectee',  0);
        $this->Validate('TVA_Collectee',       0);
        $this->Validate('Base_TVA_Deductible', 0);
        $this->Validate('TVA_Deductible',      0);
        $this->Validate('TVA_Due',             0);
        // SYSCOA : compte 4431 TVA collectée, 4454 TVA déductible sur achats
        $this->Validate('Compte_TVA_Collectee_No', '44310');
        $this->Validate('Compte_TVA_Deduc_No',     '44540');
    }

    public function onModify() {
        if ($this->Statut->_value === 'Validee')
            Error("La déclaration '".$this->Code->_value."' est validée. Aucune modification n'est possible.");
    }

    public function onDelete() {
        if ($this->Statut->_value !== 'Brouillon')
            Error("Seules les déclarations en brouillon peuvent être supprimées.");
    }
}
?>
