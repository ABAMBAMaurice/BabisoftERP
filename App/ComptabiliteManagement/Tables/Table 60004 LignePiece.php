<?php

/**
 * Lignes de pièces comptables provisoires (brouillon).
 * Une pièce = ensemble de lignes partageant le même Piece_No dans un Journal.
 * La validation (ComptabiliteManagement::validerPiece) transfère ces lignes
 * vers EcritureComptable et vide LignePiece.
 */
class LignePiece extends Table {
    public function __construct() {
        parent::__construct(60004, 'ligne_piece');

        $this->field(1,  'Journal_Code',   FieldType::text(20),  caption: 'Journal');
        $this->field(2,  'Piece_No',       FieldType::text(30),  caption: 'N° Pièce');
        $this->field(3,  'Ligne_No',       FieldType::integer(),  caption: 'N° Ligne');
        $this->field(4,  'Date_Ecriture',  FieldType::date(),    caption: 'Date');
        $this->field(5,  'Compte_No',      FieldType::text(20),  caption: 'N° Compte');
        $this->field(6,  'Libelle',        FieldType::text(255), caption: 'Libellé');
        $this->field(7,  'Debit',          FieldType::decimal(), caption: 'Débit');
        $this->field(8,  'Credit',         FieldType::decimal(), caption: 'Crédit');
        $this->field(9,  'Type_TVA',       FieldType::text(20),  caption: 'Type TVA');
        $this->field(10, 'Taux_TVA',       FieldType::decimal(), caption: 'Taux TVA (%)');
        $this->field(11, 'Base_TVA',       FieldType::decimal(), caption: 'Base HT');
        $this->field(12, 'Source_Type',    FieldType::text(30),  caption: 'Type source');
        $this->field(13, 'Source_No',      FieldType::text(30),  caption: 'N° source');
        $this->field(14, 'Tenant_code',    FieldType::text(50),  caption: 'Tenant');

        // FlowFields de contrôle de la pièce (même Piece_No)
        $this->flowField(20, 'Total_Debit_Piece', FieldType::decimal(),
            CalcFormula::Sum(LignePiece::class, 'Debit', ['Piece_No' => 'Piece_No']),
            caption: 'Total débit pièce'
        );
        $this->flowField(21, 'Total_Credit_Piece', FieldType::decimal(),
            CalcFormula::Sum(LignePiece::class, 'Credit', ['Piece_No' => 'Piece_No']),
            caption: 'Total crédit pièce'
        );

        $this->Keys('Journal_Code', 'Piece_No', 'Ligne_No');
    }

    public function onInsert() {
        if (empty($this->Debit->_value))   $this->Validate('Debit', 0);
        if (empty($this->Credit->_value))  $this->Validate('Credit', 0);
        if (empty($this->Type_TVA->_value)) $this->Validate('Type_TVA', 'Aucune');
        if (empty($this->Taux_TVA->_value)) $this->Validate('Taux_TVA', 0);
        if (empty($this->Base_TVA->_value)) $this->Validate('Base_TVA', 0);

        // Vérifier que le compte existe et est actif
        $cpt = new CompteComptable();
        if (!$cpt->get($this->Compte_No->_value))
            Error("Le compte '".$this->Compte_No->_value."' n'existe pas dans le plan comptable.");
        if ($cpt->Est_Collectif->_value)
            Error("Le compte '".$this->Compte_No->_value."' est un compte collectif. La saisie directe est interdite.");

        // Vérifier que la période est ouverte
        ComptabiliteManagement::verifierPeriodeOuverte($this->Date_Ecriture->_value);
    }

    public function onModify() {
        if (!empty($this->Compte_No->_value)) {
            $cpt = new CompteComptable();
            if (!$cpt->get($this->Compte_No->_value))
                Error("Compte '".$this->Compte_No->_value."' introuvable.");
        }
    }

    public function onDelete() {}
}
?>
