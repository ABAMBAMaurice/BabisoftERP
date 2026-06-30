<?php
class Client extends Table {
    public function __construct() {
        parent::__construct(62003, 'client');

        // ── Identification ──────────────────────
        $this->field(1,  'No_',                      FieldType::text(20, 'NOT NULL'));
        $this->field(2,  'Nom',                      FieldType::text(150, 'NOT NULL'));
        $this->field(3,  'Nom2',                     FieldType::text(150),  caption: 'Nom complémentaire');
        $this->field(4,  'Type_Client',              FieldType::text(30),   caption: 'Societe | Particulier | Administration');

        // ── Coordonnées ─────────────────────────
        $this->field(10, 'Adresse',                  FieldType::text(200));
        $this->field(11, 'Adresse2',                 FieldType::text(200));
        $this->field(12, 'Ville',                    FieldType::text(100));
        $this->field(13, 'Quartier',                 FieldType::text(100),  caption: 'Quartier/Commune');
        $this->field(14, 'Pays',                     FieldType::text(50));
        $this->field(15, 'Tel',                      FieldType::text(20));
        $this->field(16, 'Tel2',                     FieldType::text(20));
        $this->field(17, 'Email',                    FieldType::text(150));
        $this->field(18, 'Site_Web',                 FieldType::text(200));

        // ── Identification fiscale (CI) ─────────
        $this->field(20, 'NIF',                      FieldType::text(30),   caption: 'Numéro d\'Identification Fiscale');
        $this->field(21, 'RC',                       FieldType::text(50),   caption: 'Registre du Commerce');
        $this->field(22, 'Compte_Contribuable',      FieldType::text(30),   caption: 'Compte contribuable DGI');

        // ── Comptabilisation ────────────────────
        $this->field(30, 'Groupe_Compta_Code',       FieldType::text(20, 'NOT NULL'), caption: 'Groupe de comptabilisation');
        $this->field(31, 'Compte_Client_No',         FieldType::text(20),   caption: 'Compte client (surcharge groupe)');

        // ── Conditions commerciales ──────────────
        $this->field(40, 'Conditions_Paiement_Code', FieldType::text(10),   caption: 'Conditions de paiement');
        $this->field(41, 'Mode_Reglement_Code',      FieldType::text(10),   caption: 'Mode de règlement préféré');
        $this->field(42, 'Remise_Ligne_Pct',         FieldType::decimal(),  caption: 'Remise ligne (%)');
        $this->field(43, 'Remise_Facture_Pct',       FieldType::decimal(),  caption: 'Remise globale facture (%)');
        $this->field(44, 'Limite_Credit',            FieldType::decimal(),  caption: 'Limite de crédit (0 = illimitée)');
        $this->field(45, 'Verification_Credit',      FieldType::boolean(),  caption: 'Vérifier la limite de crédit');
        $this->field(46, 'Devise_Code',              FieldType::text(5));
        $this->field(47, 'Entrepot_Code',            FieldType::text(20),   caption: 'Entrepôt de livraison préféré');

        // ── Adresse de livraison ─────────────────
        $this->field(50, 'Adresse_Livraison',        FieldType::text(200));
        $this->field(51, 'Ville_Livraison',          FieldType::text(100));
        $this->field(52, 'Contact_Livraison',        FieldType::text(100));

        // ── Statut ──────────────────────────────
        $this->field(60, 'Bloque',                   FieldType::boolean(),  caption: 'Bloqué (aucune transaction)');
        $this->field(61, 'Bloque_Vente',             FieldType::boolean());
        $this->field(62, 'Est_Actif',                FieldType::boolean());
        $this->field(63, 'Responsable_Code',         FieldType::text(20),   caption: 'Commercial responsable');
        $this->field(64, 'Notes',                    FieldType::text(1000));
        $this->field(99, 'Tenant_code',              FieldType::text(30, 'NOT NULL'));

        $this->Keys('No_');

        // ── FlowFields financiers ────────────────
        $this->flowField(100, 'Solde', FieldType::decimal(),
            CalcFormula::Sum('EcritureClient', 'Montant_Restant', ['Client_No' => 'No_']),
            caption: 'Solde total (FCFA)'
        );
        $this->flowField(101, 'Montant_Commandes_Ouvertes', FieldType::decimal(),
            CalcFormula::Sum('EnTeteVente', 'Montant_TTC', ['Client_No' => 'No_']),
            caption: 'Commandes ouvertes (TTC)'
        );
        $this->flowField(102, 'Nb_Factures', FieldType::integer(),
            CalcFormula::Count('EcritureClient', ['Client_No' => 'No_']),
            caption: 'Nb écritures client'
        );
    }

    public function onInsert(): void {
        if (empty($this->Groupe_Compta_Code->_value))
            Error("Le groupe de comptabilisation est obligatoire.");

        $groupe = new GroupeComptabilisationClient();
        if (!$groupe->get($this->Groupe_Compta_Code->_value))
            Error("Groupe de comptabilisation '".$this->Groupe_Compta_Code->_value."' introuvable.");

        if (empty($this->Compte_Client_No->_value))
            $this->Compte_Client_No->_value = $groupe->Compte_Client_No->_value;
    }

    public function onModify(): void {
        if ($this->Bloque->_value && !$this->_original['Bloque']) {
            $cmd = new EnTeteVente();
            $cmd->setRange('Client_No', $this->No_->_value);
            $cmd->setRange('Type_Document', TypeDocumentVente::Commande->value);
            if ($cmd->FindFirst())
                Error("Le client '".$this->No_->_value."' a des commandes en cours. Réglez-les avant de le bloquer.");
        }
    }

    public function onDelete(): void {
        $ec = new EcritureClient();
        $ec->setRange('Client_No', $this->No_->_value);
        if ($ec->FindFirst())
            Error("Le client '".$this->No_->_value."' a des écritures comptables. Désactivez-le.");
    }
}
?>
