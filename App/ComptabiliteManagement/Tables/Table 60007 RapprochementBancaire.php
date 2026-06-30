<?php

class RapprochementBancaire extends Table {
    public function __construct() {
        parent::__construct(60007, 'rapprochement_bancaire');

        $this->field(1,  'Code',                FieldType::text(20),  caption: 'N°');
        $this->field(2,  'Compte_Bancaire_No',  FieldType::text(20),  caption: 'Compte bancaire');
        $this->field(3,  'Date_Releve',         FieldType::date(),    caption: 'Date relevé');
        $this->field(4,  'Solde_Releve',        FieldType::decimal(), caption: 'Solde relevé (banque)');
        $this->field(5,  'Solde_Comptable',     FieldType::decimal(), caption: 'Solde comptable');
        $this->field(6,  'Montant_Rapproche',   FieldType::decimal(), caption: 'Montant rapproché');
        $this->field(7,  'Ecart',               FieldType::decimal(), caption: 'Écart résiduel');
        $this->field(8,  'Statut',              FieldType::text(20),  caption: 'Statut'); // EnCours | Valide
        $this->field(9,  'Tenant_code',         FieldType::text(50),  caption: 'Tenant');

        // FlowFields
        $this->flowField(20, 'Nb_Ecritures_Non_Rapprochees', FieldType::integer(),
            CalcFormula::Count(EcritureComptable::class, [
                'Compte_No'          => 'Compte_Bancaire_No',
                'Rapprochement_Code' => 'Code',   // filtre : celles liées à CE rapprochement
            ]),
            caption: 'Écritures rapprochées'
        );

        $this->Keys('Code');
    }

    public function onInsert() {
        $this->Validate('Statut', 'EnCours');
        $this->Validate('Montant_Rapproche', 0);
        $this->Validate('Ecart', 0);

        // Vérifier que le compte est bien un compte de trésorerie (classe 5)
        $cpt = new CompteComptable();
        if ($cpt->get($this->Compte_Bancaire_No->_value)) {
            if ($cpt->Classe->_value != 5)
                Error("Le compte '".$this->Compte_Bancaire_No->_value."' n'est pas un compte de trésorerie (classe 5).");
        }
    }

    public function onModify() {
        if ($this->Statut->_value === 'Valide')
            Error("Ce rapprochement est validé. Aucune modification n'est autorisée.");
    }

    public function onDelete() {
        if ($this->Statut->_value === 'Valide')
            Error("Impossible de supprimer un rapprochement validé.");
    }
}
?>
