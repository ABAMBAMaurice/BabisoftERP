<?php
class RelanceLigne extends Table {
    public function __construct() {
        parent::__construct(62012, 'relance_ligne');

        $this->field(1,  'Relance_No',        FieldType::text(20, 'NOT NULL'));
        $this->field(2,  'Ligne_No',          FieldType::integer('NOT NULL'));
        $this->field(3,  'Type_Ligne',        FieldType::text(30),  caption: 'Ecriture | Frais | Interet | Commentaire');
        $this->field(4,  'Ecriture_Client_No', FieldType::integer(), caption: 'N° écriture client');
        $this->field(5,  'Document_No',       FieldType::text(20),  caption: 'N° facture');
        $this->field(6,  'Description',       FieldType::text(200));
        $this->field(7,  'Date_Document',     FieldType::date());
        $this->field(8,  'Date_Echeance',     FieldType::date());
        $this->field(9,  'Jours_Retard',      FieldType::integer());
        $this->field(10, 'Montant_Original',  FieldType::decimal());
        $this->field(11, 'Montant_Restant',   FieldType::decimal(), caption: 'Montant restant dû');
        $this->field(12, 'Montant_Interet',   FieldType::decimal());
        $this->field(13, 'Niveau_Relance',    FieldType::integer());
        $this->field(14, 'Tenant_code',       FieldType::text(30, 'NOT NULL'));

        $this->Keys('Relance_No', 'Ligne_No');
    }
}
?>
