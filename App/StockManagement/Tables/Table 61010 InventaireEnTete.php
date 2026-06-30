<?php
class InventaireEnTete extends Table {
    public function __construct() {
        parent::__construct(61010, 'inventaire_entete');

        $this->field(1,  'No_',              FieldType::text(20, 'NOT NULL'), caption: 'N° inventaire');
        $this->field(2,  'Entrepot_Code',    FieldType::text(20, 'NOT NULL'));
        $this->field(3,  'Date_Inventaire',  FieldType::date('NOT NULL'));
        $this->field(4,  'Date_Validation',  FieldType::date());
        $this->field(5,  'Statut',           FieldType::text(20));
        $this->field(6,  'Responsable',      FieldType::text(100));
        $this->field(7,  'Notes',            FieldType::text(500));
        $this->field(8,  'Filtre_Article',   FieldType::text(100),  caption: 'Filtre article (optionnel)');
        $this->field(9,  'Filtre_Categorie', FieldType::text(20),   caption: 'Filtre catégorie');
        $this->field(10, 'Tenant_code',      FieldType::text(30, 'NOT NULL'));

        $this->Keys('No_');

        $this->flowField(100, 'Nb_Lignes', FieldType::integer(),
            CalcFormula::Count('InventaireLigne', ['Inventaire_No' => 'No_']),
            caption: 'Nb lignes'
        );
        $this->flowField(101, 'Nb_Ecarts', FieldType::integer(),
            CalcFormula::Count('InventaireLigne', ['Inventaire_No' => 'No_', 'A_Un_Ecart' => 'A_Un_Ecart_Flag']),
            caption: 'Lignes avec écart'
        );
    }

    public function onModify(): void {
        if ($this->Statut->_value === StatutInventaire::Valide->value
            && $this->_original['Statut'] === StatutInventaire::Valide->value)
            Error("L'inventaire '".$this->No_->_value."' est validé et ne peut plus être modifié.");
    }

    public function onDelete(): void {
        if ($this->Statut->_value === StatutInventaire::Valide->value)
            Error("Impossible de supprimer un inventaire validé.");

        $lignes = new InventaireLigne();
        $lignes->setRange('Inventaire_No', $this->No_->_value);
        if ($lignes->FindAll()) {
            foreach ($lignes->recordSet as $l) $l->Delete(false);
        }
    }
}
?>
