<?php
/**
 * En-tête de document de vente (Devis, Commande, Facture, Avoir).
 * Table pivot pour tous les types de documents pré-validés.
 *
 * Après validation :
 *   Commande  → LivraisonEnTete (stock) + FactureValideeEnTete (compta)
 *   Facture   → FactureValideeEnTete directement
 *   Avoir     → FactureValideeEnTete (type Avoir)
 */
class EnTeteVente extends Table {
    public function __construct() {
        parent::__construct(62004, 'entete_vente');

        // ── Identification ──────────────────────
        $this->field(1,  'No_',                      FieldType::text(20, 'NOT NULL'));
        $this->field(2,  'Type_Document',            FieldType::text(30, 'NOT NULL'));
        $this->field(3,  'Statut',                   FieldType::text(30));

        // ── Client ──────────────────────────────
        $this->field(10, 'Client_No',                FieldType::text(20, 'NOT NULL'));
        $this->field(11, 'Nom_Client',               FieldType::text(150));
        $this->field(12, 'NIF_Client',               FieldType::text(30));
        $this->field(13, 'Adresse_Livraison',        FieldType::text(300));
        $this->field(14, 'Contact_Client',           FieldType::text(100));

        // ── Dates ───────────────────────────────
        $this->field(20, 'Date_Document',            FieldType::date('NOT NULL'));
        $this->field(21, 'Date_Livraison_Prevue',    FieldType::date(),    caption: 'Date de livraison prévue');
        $this->field(22, 'Date_Echeance',            FieldType::date(),    caption: 'Date d\'échéance (calculée)');
        $this->field(23, 'Date_Validite',            FieldType::date(),    caption: 'Valide jusqu\'au (Devis)');

        // ── Conditions commerciales ──────────────
        $this->field(30, 'Conditions_Paiement_Code', FieldType::text(10));
        $this->field(31, 'Mode_Reglement_Code',      FieldType::text(10));
        $this->field(32, 'Conditions_Livraison',     FieldType::text(50),  caption: 'Incoterm (CIF, FOB, EXW...)');
        $this->field(33, 'Remise_Globale_Pct',       FieldType::decimal(), caption: 'Remise globale (%)');
        $this->field(34, 'Devise_Code',              FieldType::text(5));

        // ── Référence ───────────────────────────
        $this->field(40, 'No_Reference_Client',      FieldType::text(50),  caption: 'N° bon de commande client');
        $this->field(41, 'Responsable_Code',         FieldType::text(20),  caption: 'Commercial');
        $this->field(42, 'Entrepot_Code',            FieldType::text(20),  caption: 'Entrepôt de sortie');
        $this->field(43, 'Notes',                    FieldType::text(2000));

        // ── Origine / Avoir ──────────────────────
        $this->field(50, 'Devis_No',                 FieldType::text(20),  caption: 'Devis d\'origine');
        $this->field(51, 'Facture_Origine_No',       FieldType::text(20),  caption: 'Facture d\'origine (Avoir)');
        $this->field(52, 'Commande_No',              FieldType::text(20),  caption: 'Commande liée (Facture directe)');

        $this->field(99, 'Tenant_code',              FieldType::text(30, 'NOT NULL'));

        $this->Keys('No_');

        // ── FlowFields financiers (agrégats depuis LigneVente) ──
        $this->flowField(100, 'Montant_HT', FieldType::decimal(),
            CalcFormula::Sum('LigneVente', 'Montant_Ligne_HT',  ['Document_No' => 'No_']),
            caption: 'Total HT'
        );
        $this->flowField(101, 'Montant_Remise', FieldType::decimal(),
            CalcFormula::Sum('LigneVente', 'Montant_Remise',    ['Document_No' => 'No_']),
            caption: 'Total remises'
        );
        $this->flowField(102, 'Montant_TVA', FieldType::decimal(),
            CalcFormula::Sum('LigneVente', 'Montant_TVA',       ['Document_No' => 'No_']),
            caption: 'Total TVA'
        );
        $this->flowField(103, 'Montant_TTC', FieldType::decimal(),
            CalcFormula::Sum('LigneVente', 'Montant_Ligne_TTC', ['Document_No' => 'No_']),
            caption: 'Total TTC'
        );
        $this->flowField(104, 'Nb_Lignes', FieldType::integer(),
            CalcFormula::Count('LigneVente', ['Document_No' => 'No_']),
            caption: 'Nb lignes'
        );
    }

    public function onInsert(): void {
        $client = new Client();
        if (!$client->get($this->Client_No->_value))
            Error("Client '".$this->Client_No->_value."' introuvable.");
        if ($client->Bloque->_value)
            Error("Le client '".$client->Nom->_value."' est bloqué.");
        if ($client->Bloque_Vente->_value && $this->Type_Document->_value !== TypeDocumentVente::Avoir->value)
            Error("Les ventes sont bloquées pour le client '".$client->Nom->_value."'.");

        $this->Nom_Client->_value       = $client->Nom->_value;
        $this->NIF_Client->_value       = $client->NIF->_value;
        $this->Adresse_Livraison->_value = $client->Adresse_Livraison->_value ?: $client->Adresse->_value;
        $this->Contact_Client->_value   = $client->Contact_Livraison->_value;

        if (empty($this->Conditions_Paiement_Code->_value))
            $this->Conditions_Paiement_Code->_value = $client->Conditions_Paiement_Code->_value;
        if (empty($this->Mode_Reglement_Code->_value))
            $this->Mode_Reglement_Code->_value = $client->Mode_Reglement_Code->_value;
        if (empty($this->Remise_Globale_Pct->_value))
            $this->Remise_Globale_Pct->_value = $client->Remise_Facture_Pct->_value;
        if (empty($this->Entrepot_Code->_value))
            $this->Entrepot_Code->_value = $client->Entrepot_Code->_value;

        if (empty($this->Date_Echeance->_value) && !empty($this->Conditions_Paiement_Code->_value)) {
            $cond = new ConditionsPaiement();
            if ($cond->get($this->Conditions_Paiement_Code->_value))
                $this->Date_Echeance->_value = $cond->calculerEcheance($this->Date_Document->_value);
        }

        if ($this->Type_Document->_value === TypeDocumentVente::Devis->value
            && empty($this->Date_Validite->_value))
            $this->Date_Validite->_value = date('Y-m-d', strtotime($this->Date_Document->_value.' +30 days'));

        if ($client->Verification_Credit->_value && (float)$client->Limite_Credit->_value > 0) {
            $client->CalcFields('Solde', 'Montant_Commandes_Ouvertes');
            $exposition = (float)$client->Solde->_value + (float)$client->Montant_Commandes_Ouvertes->_value;
            if ($exposition >= (float)$client->Limite_Credit->_value)
                Error("Limite de crédit atteinte pour '".$client->Nom->_value."'"
                    ." (Exposition : ".number_format($exposition,0,'.',',')." FCFA"
                    ." / Limite : ".number_format((float)$client->Limite_Credit->_value,0,'.',',')." FCFA).");
        }
    }

    public function onModify(): void {
        $statut = StatutDocumentVente::from($this->Statut->_value);
        if (!$statut->estModifiable())
            Error("Le document '".$this->No_->_value."' ne peut plus être modifié (statut : ".$this->Statut->_value.").");
    }

    public function onDelete(): void {
        if (!StatutDocumentVente::from($this->Statut->_value)->estModifiable())
            Error("Impossible de supprimer un document dont le statut est '".$this->Statut->_value."'."
                . " Utilisez la fonction Annuler.");

        $lignes = new LigneVente();
        $lignes->setRange('Document_No', $this->No_->_value);
        if ($lignes->FindAll())
            foreach ($lignes->recordSet as $l) $l->Delete(false);
    }
}
?>
