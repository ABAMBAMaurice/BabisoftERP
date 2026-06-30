<?php
class RelanceEnTete extends Table {
    public function __construct() {
        parent::__construct(62011, 'relance_entete');

        $this->field(1,  'No_',              FieldType::text(20, 'NOT NULL'));
        $this->field(2,  'Client_No',        FieldType::text(20, 'NOT NULL'));
        $this->field(3,  'Nom_Client',       FieldType::text(150));
        $this->field(4,  'Date_Relance',     FieldType::date('NOT NULL'));
        $this->field(5,  'Date_Echeance',    FieldType::date(),    caption: 'Délai de réponse');
        $this->field(6,  'Niveau_Relance',   FieldType::integer(), caption: '1=1ère relance, 2=Mise en demeure, 3=Contentieux');
        $this->field(7,  'Statut',           FieldType::text(30),  caption: 'Brouillon | Emis | Acquitte | Sans_Suite');
        $this->field(8,  'Montant_Echu',     FieldType::decimal(),  caption: 'Total échu à la date');
        $this->field(9,  'Frais_Relance',    FieldType::decimal(),  caption: 'Frais de relance facturés');
        $this->field(10, 'Interets_Retard',  FieldType::decimal(),  caption: 'Intérêts de retard calculés');
        $this->field(11, 'Notes',            FieldType::text(1000));
        $this->field(12, 'Responsable_Code', FieldType::text(20));
        $this->field(13, 'Tenant_code',      FieldType::text(30, 'NOT NULL'));

        $this->Keys('No_');

        $this->flowField(100, 'Nb_Ecritures_En_Attente', FieldType::integer(),
            CalcFormula::Count('RelanceLigne', ['Relance_No' => 'No_']),
            caption: 'Nb factures relancées'
        );
    }

    public function onModify(): void {
        if ($this->Statut->_value === 'Emis' && $this->_original['Statut'] === 'Emis')
            Error("La relance '".$this->No_->_value."' est déjà émise. Créez une nouvelle relance.");
    }

    public function onDelete(): void {
        if ($this->Statut->_value === 'Emis')
            Error("Impossible de supprimer une relance émise.");
        $lignes = new RelanceLigne();
        $lignes->setRange('Relance_No', $this->No_->_value);
        if ($lignes->FindAll()) foreach ($lignes->recordSet as $l) $l->Delete(false);
    }
}
?>
