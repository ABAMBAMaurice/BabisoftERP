<?php
/**
 * En-tête de facture ou avoir validé (posted invoice / posted credit memo).
 * Archive historique immuable. La donnée financière vit dans EcritureClient.
 */
class FactureValideeEnTete extends Table {
    public function __construct() {
        parent::__construct(62008, 'facture_validee_entete');

        $this->field(1,  'No_',                      FieldType::text(20, 'NOT NULL'));
        $this->field(2,  'Type_Document',            FieldType::text(30, 'NOT NULL'), caption: 'Facture | Avoir');
        $this->field(3,  'Client_No',                FieldType::text(20, 'NOT NULL'));
        $this->field(4,  'Nom_Client',               FieldType::text(150));
        $this->field(5,  'NIF_Client',               FieldType::text(30));
        $this->field(6,  'Date_Document',            FieldType::date('NOT NULL'));
        $this->field(7,  'Date_Echeance',            FieldType::date());
        $this->field(8,  'Date_Livraison',           FieldType::date());
        $this->field(9,  'No_Reference_Client',      FieldType::text(50));
        $this->field(10, 'Commande_No',              FieldType::text(20),  caption: 'Commande d\'origine');
        $this->field(11, 'Livraison_No',             FieldType::text(20),  caption: 'Livraison associée');
        $this->field(12, 'Facture_Origine_No',       FieldType::text(20),  caption: 'Facture d\'origine (Avoir)');
        $this->field(13, 'Conditions_Paiement_Code', FieldType::text(10));
        $this->field(14, 'Mode_Reglement_Code',      FieldType::text(10));
        $this->field(15, 'Adresse_Livraison',        FieldType::text(300));
        $this->field(16, 'Responsable_Code',         FieldType::text(20));

        // Montants archivés (snapshot au moment de la validation)
        $this->field(20, 'Montant_HT',      FieldType::decimal());
        $this->field(21, 'Montant_Remise',  FieldType::decimal());
        $this->field(22, 'Montant_TVA',     FieldType::decimal());
        $this->field(23, 'Montant_TTC',     FieldType::decimal());

        // Suivi paiement
        $this->field(30, 'Statut_Paiement', FieldType::text(30));
        $this->field(31, 'Montant_Regle',   FieldType::decimal(), caption: 'Montant encaissé');
        $this->field(32, 'Montant_Restant', FieldType::decimal());
        $this->field(33, 'Piece_Compta_No', FieldType::text(30),  caption: 'N° pièce comptable');
        $this->field(34, 'Tenant_code',     FieldType::text(30, 'NOT NULL'));

        $this->Keys('No_');

        $this->flowField(100, 'Nb_Lignes', FieldType::integer(),
            CalcFormula::Count('FactureValideeLigne', ['Facture_No' => 'No_']),
            caption: 'Nb lignes'
        );
    }

    public function onDelete(): void {
        Error("Les factures et avoirs validés sont immuables. Créez un avoir pour annuler.");
    }
}
?>
