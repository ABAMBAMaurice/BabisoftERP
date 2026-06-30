<?php
class NumeroSerie extends Table {
    public function __construct() {
        parent::__construct(61006, 'NumeroSerie', 'numero_serie');

        $this->field(1,  'No_Serie',           FieldType::text(50, 'NOT NULL'), caption: 'N° de série');
        $this->field(2,  'Article_Code',        FieldType::text(20, 'NOT NULL'));
        $this->field(3,  'Statut',              FieldType::text(20),  caption: 'Disponible|Sorti|Retourne|Bloque');
        $this->field(4,  'Date_Entree',         FieldType::date());
        $this->field(5,  'Date_Sortie',         FieldType::date());
        $this->field(6,  'Entrepot_Code',       FieldType::text(20),  caption: 'Emplacement actuel');
        $this->field(7,  'Emplacement_Code',    FieldType::text(20));
        $this->field(8,  'No_Lot',              FieldType::text(50),  caption: 'Lot associé');
        $this->field(9,  'Fournisseur_Code',    FieldType::text(20));
        $this->field(10, 'Client_Code',         FieldType::text(20),  caption: 'Client actuel (si sorti)');
        $this->field(11, 'Document_Sortie_No',  FieldType::text(20),  caption: 'N° document de sortie');
        $this->field(12, 'Notes',               FieldType::text(500));
        $this->field(13, 'Tenant_code',         FieldType::text(30, 'NOT NULL'));

        $this->Keys('No_Serie', 'Article_Code');
    }

    public function onInsert(): void {
        $article = new Article();
        if (!$article->get($this->Article_Code->_value))
            Error("Article '".$this->Article_Code->_value."' introuvable.");
        if (!$article->Suivi_Serie->_value)
            Error("L'article '".$this->Article_Code->_value."' n'est pas configuré pour le suivi par numéro de série.");
        if (empty($this->Date_Entree->_value))
            $this->Date_Entree->_value = date('Y-m-d');
    }
}
?>
