<?php
/**
 * En-tête de livraison validée (posted shipment).
 * Créée automatiquement par VentesManagement::validerLivraison().
 * Immuable après création.
 */
class LivraisonEnTete extends Table {
    public function __construct() {
        parent::__construct(62006, 'livraison_entete');

        $this->field(1,  'No_',                 FieldType::text(20, 'NOT NULL'));
        $this->field(2,  'Commande_No',         FieldType::text(20, 'NOT NULL'), caption: 'Commande d\'origine');
        $this->field(3,  'Client_No',           FieldType::text(20, 'NOT NULL'));
        $this->field(4,  'Nom_Client',          FieldType::text(150));
        $this->field(5,  'Date_Livraison',      FieldType::date('NOT NULL'));
        $this->field(6,  'Entrepot_Code',       FieldType::text(20));
        $this->field(7,  'Adresse_Livraison',   FieldType::text(300));
        $this->field(8,  'Contact_Client',      FieldType::text(100));
        $this->field(9,  'Responsable_Code',    FieldType::text(20));
        $this->field(10, 'No_Reference_Client', FieldType::text(50));
        $this->field(11, 'Conditions_Livraison',FieldType::text(50));
        $this->field(12, 'Tenant_code',         FieldType::text(30, 'NOT NULL'));

        $this->Keys('No_');

        $this->flowField(100, 'Nb_Lignes', FieldType::integer(),
            CalcFormula::Count('LivraisonLigne', ['Livraison_No' => 'No_']),
            caption: 'Nb lignes livrées'
        );
    }

    public function onDelete(): void {
        Error("Les livraisons validées sont immuables. Créez un avoir pour corriger.");
    }
}
?>
