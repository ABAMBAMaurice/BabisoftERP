<?php

class JournalComptable extends Table {
    public function __construct() {
        parent::__construct(60003, 'journal_comptable');

        $this->field(1, 'Code',                    FieldType::text(20),  caption: 'Code');
        $this->field(2, 'Intitule',                FieldType::text(150), caption: 'Intitulé');
        $this->field(3, 'Type',                    FieldType::text(30),  caption: 'Type'); // TypeJournal
        $this->field(4, 'Compte_Contrepartie_No',  FieldType::text(20),  caption: 'Compte contrepartie');
        $this->field(5, 'NoSeries_Code',           FieldType::text(30),  caption: 'Souche de N°');
        $this->field(6, 'Tenant_code',             FieldType::text(50),  caption: 'Tenant');

        // FlowField : solde des pièces provisoires en attente de validation
        $this->flowField(20, 'Nb_Pieces_En_Attente', FieldType::integer(),
            CalcFormula::Count(LignePiece::class, ['Journal_Code' => 'Code']),
            caption: 'Pièces en attente'
        );

        $this->Keys('Code');
    }

    public function onInsert() {}
    public function onModify() {}

    public function onDelete() {
        $lp = new LignePiece();
        $lp->setRange('Journal_Code', $this->Code->_value);
        if ($lp->FindFirst())
            Error("Le journal '".$this->Code->_value."' contient des pièces non validées.");

        $ec = new EcritureComptable();
        $ec->setRange('Journal_Code', $this->Code->_value);
        if ($ec->FindFirst())
            Error("Le journal '".$this->Code->_value."' possède des écritures validées.");
    }
}
?>
