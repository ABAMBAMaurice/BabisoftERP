<?php
/**
 * Groupe de comptabilisation client.
 * Lie chaque catégorie de clients aux comptes SYSCOA correspondants.
 * Ex : Clients locaux (41100), Clients export (41200), Clients douteux (41600)
 */
class GroupeComptabilisationClient extends Table {
    public function __construct() {
        parent::__construct(62000, 'groupe_compta_client');

        $this->field(1, 'Code',                  FieldType::text(30), caption: 'Code du groupe');
        $this->field(2, 'Intitule',              FieldType::text(100), caption: 'Intitulé du groupe');
        $this->field(3, 'Compte_Client_No',      FieldType::text(30),caption: 'Compte client');
        $this->field(4, 'Compte_Vente_HT_No',   FieldType::text(30), caption: 'Produit des ventes');
        $this->field(5, 'Compte_TVA_Collect_No', FieldType::text(30), caption: 'TVA collectée');
        $this->field(6, 'Compte_Remise_No',      FieldType::text(30), caption: 'Compte remises accordées ');
        $this->field(7, 'Compte_Ecart_Regl_No',  FieldType::text(30), caption: 'Écarts de règlement');
        $this->field(8, 'Tenant_code',           FieldType::text(30), caption: 'Code du locataire');

        $this->Keys('Code');
    }

    public function onDelete(): void {
        $c = new Client();
        $c->setRange('Groupe_Compta_Code', $this->Code->_value);
        if ($c->FindFirst())
            Error("Groupe '".$this->Code->_value."' utilisé par des clients. Impossible de le supprimer.");
    }
}
?>
