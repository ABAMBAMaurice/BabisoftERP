<?php
class FactureValideeLigne extends Table {
    public function __construct() {
        parent::__construct(62009,'facture_validee_ligne');

        $this->field(1,  'Facture_No',        FieldType::text(20, 'NOT NULL'));
        $this->field(2,  'Ligne_No',          FieldType::integer('NOT NULL'));
        $this->field(3,  'Type_Ligne',        FieldType::text(30));
        $this->field(4,  'No_',              FieldType::text(20),  caption: 'Article ou Compte');
        $this->field(5,  'Description',       FieldType::text(200));
        $this->field(6,  'UM_Code',           FieldType::text(10));
        $this->field(7,  'Quantite',          FieldType::decimal());
        $this->field(8,  'Prix_Unitaire_HT',  FieldType::decimal());
        $this->field(9,  'Remise_Pct',        FieldType::decimal());
        $this->field(10, 'Montant_Remise',    FieldType::decimal());
        $this->field(11, 'Montant_Ligne_HT',  FieldType::decimal());
        $this->field(12, 'Taux_TVA_Pct',      FieldType::decimal());
        $this->field(13, 'Type_TVA',          FieldType::text(20));
        $this->field(14, 'Montant_TVA',       FieldType::decimal());
        $this->field(15, 'Montant_Ligne_TTC', FieldType::decimal());
        $this->field(16, 'Compte_Vente_No',   FieldType::text(20));
        $this->field(17, 'Ecriture_Stock_No', FieldType::integer(), caption: 'N° écriture stock générée');
        $this->field(18, 'Tenant_code',       FieldType::text(30, 'NOT NULL'));

        $this->Keys('Facture_No', 'Ligne_No');
    }

    public function onDelete(): void {
        Error("Les lignes de facture validée sont immuables.");
    }
}
?>
