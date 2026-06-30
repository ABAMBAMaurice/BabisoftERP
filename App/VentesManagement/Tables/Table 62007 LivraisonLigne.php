<?php
class LivraisonLigne extends Table {
    public function __construct() {
        parent::__construct(62007, 'livraison_ligne');

        $this->field(1,  'Livraison_No',       FieldType::text(20, 'NOT NULL'));
        $this->field(2,  'Ligne_No',           FieldType::integer('NOT NULL'));
        $this->field(3,  'Commande_No',        FieldType::text(20, 'NOT NULL'));
        $this->field(4,  'Commande_Ligne_No',  FieldType::integer('NOT NULL'));
        $this->field(5,  'Article_Code',       FieldType::text(20));
        $this->field(6,  'Description',        FieldType::text(200));
        $this->field(7,  'UM_Code',            FieldType::text(10));
        $this->field(8,  'Quantite_Livree',    FieldType::decimal('(15,4) NOT NULL'));
        $this->field(9,  'Entrepot_Code',      FieldType::text(20));
        $this->field(10, 'Emplacement_Code',   FieldType::text(20));
        $this->field(11, 'No_Lot',             FieldType::text(50));
        $this->field(12, 'No_Serie',           FieldType::text(50));
        $this->field(13, 'Prix_Unitaire_HT',   FieldType::decimal());
        $this->field(14, 'Tenant_code',        FieldType::text(30, 'NOT NULL'));

        $this->Keys('Livraison_No', 'Ligne_No');
    }

    public function onDelete(): void {
        Error("Les lignes de livraison validées sont immuables.");
    }
}
?>
