<?php

/**
 * Grand livre — écritures comptables validées et immuables.
 * Toute écriture passe d'abord par LignePiece, puis est validée ici
 * via ComptabiliteManagement::validerPiece().
 *
 * Règle fondamentale : on ne supprime jamais une écriture validée.
 * On contrepasse (écriture inverse de même montant).
 */
class EcritureComptable extends Table {
    public function __construct() {
        parent::__construct(60005, 'ecriture_comptable');

        $this->field(1,  'No_',                 FieldType::integer('NOT NULL'), caption: 'N°');
        $this->field(2,  'Date_Ecriture',       FieldType::date(),              caption: 'Date');
        $this->field(3,  'Journal_Code',        FieldType::text(20),            caption: 'Journal');
        $this->field(4,  'Piece_No',            FieldType::text(30),            caption: 'N° Pièce');
        $this->field(5,  'Exercice_Code',       FieldType::text(20),            caption: 'Exercice');
        $this->field(6,  'Periode_No',          FieldType::integer(),            caption: 'Période');
        $this->field(7,  'Compte_No',           FieldType::text(20),            caption: 'N° Compte');
        $this->field(8,  'Libelle',             FieldType::text(255),           caption: 'Libellé');
        $this->field(9,  'Debit',               FieldType::decimal(),           caption: 'Débit');
        $this->field(10, 'Credit',              FieldType::decimal(),           caption: 'Crédit');
        $this->field(11, 'Statut',              FieldType::text(20),            caption: 'Statut');
        $this->field(12, 'Lettrage_Code',       FieldType::text(20),            caption: 'Code lettrage');
        $this->field(13, 'Date_Lettrage',       FieldType::date(),              caption: 'Date lettrage');
        $this->field(14, 'Rapprochement_Code',  FieldType::text(20),            caption: 'N° rapprochement');
        $this->field(15, 'Date_Rapprochement',  FieldType::date(),              caption: 'Date rapprochement');
        $this->field(16, 'Type_TVA',            FieldType::text(20),            caption: 'Type TVA');
        $this->field(17, 'Taux_TVA',            FieldType::decimal(),           caption: 'Taux TVA (%)');
        $this->field(18, 'Base_TVA',            FieldType::decimal(),           caption: 'Base HT');
        $this->field(19, 'Source_Type',         FieldType::text(30),            caption: 'Type source');
        $this->field(20, 'Source_No',           FieldType::text(30),            caption: 'N° source');
        $this->field(21, 'Est_Contrepassation', FieldType::boolean(),           caption: 'Contrepassation');
        $this->field(22, 'Piece_Origine_No',    FieldType::text(30),            caption: 'Pièce d\'origine');
        $this->field(23, 'Tenant_code',         FieldType::text(50),            caption: 'Tenant');

        $this->Keys('No_');
    }

    public function onInsert() {
        $this->Validate('Statut', StatutEcriture::Valide->value);
    }

    public function onModify() {
        // Seuls lettrage et rapprochement sont modifiables après validation
    }

    public function onDelete() {
        Error("Les écritures validées sont immuables. Utilisez la contrepassation.");
    }
}
?>
