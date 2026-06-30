<?php
/**
 * Brouillon d'un mouvement de stock.
 * Valider → StockManagement::validerMouvement() → EcritureStock
 *
 * Groupé par (Document_Type, Document_No) pour les mouvements multi-lignes
 * (ex: réception d'achat avec 10 articles).
 */
class MouvementStockProvisoire extends Table {
    public function __construct() {
        parent::__construct(61007, 'mvt_stock_provisoire');

        $this->field(1,  'Document_Type',         FieldType::text(30, 'NOT NULL'), caption: 'Type doc (Achat|Vente|Transfert|Ajustement|Inventaire)');
        $this->field(2,  'Document_No',           FieldType::text(20, 'NOT NULL'), caption: 'N° document');
        $this->field(3,  'Ligne_No',              FieldType::integer('NOT NULL'),  caption: 'N° ligne');
        $this->field(4,  'Date_Mouvement',        FieldType::date('NOT NULL'));
        $this->field(5,  'Article_Code',          FieldType::text(20, 'NOT NULL'));
        $this->field(6,  'Description',           FieldType::text(150));
        $this->field(7,  'Type_Mouvement',        FieldType::text(30, 'NOT NULL'));
        $this->field(8,  'Entrepot_Code',         FieldType::text(20, 'NOT NULL'));
        $this->field(9,  'Emplacement_Code',      FieldType::text(20));
        $this->field(10, 'Entrepot_Dest_Code',    FieldType::text(20),  caption: 'Entrepôt destination (transfert)');
        $this->field(11, 'Emplacement_Dest_Code', FieldType::text(20),  caption: 'Emplacement destination');
        $this->field(12, 'Quantite',              FieldType::decimal('(15,4) NOT NULL'), caption: 'Qté (toujours positive)');
        $this->field(13, 'Quantite_Nette',        FieldType::decimal(),  caption: 'Qté nette (+ entrée / - sortie)');
        $this->field(14, 'Cout_Unitaire',         FieldType::decimal(),  caption: 'Coût unitaire HT');
        $this->field(15, 'No_Lot',                FieldType::text(50));
        $this->field(16, 'No_Serie',              FieldType::text(50),  caption: 'N° de série');
        $this->field(17, 'Tenant_code',           FieldType::text(30, 'NOT NULL'));

        $this->Keys('Document_Type', 'Document_No', 'Ligne_No');
    }

    public function onInsert(): void {
        $article = new Article();
        if (!$article->get($this->Article_Code->_value))
            Error("Article '".$this->Article_Code->_value."' introuvable.");
        if (!$article->Est_Actif->_value)
            Error("L'article '".$this->Article_Code->_value."' est désactivé.");

        $entrepot = new Entrepot();
        if (!$entrepot->get($this->Entrepot_Code->_value))
            Error("Entrepôt '".$this->Entrepot_Code->_value."' introuvable.");

        if ((float)$this->Quantite->_value <= 0)
            Error("La quantité doit être positive (Type_Mouvement détermine le sens).");

        if (empty($this->Description->_value))
            $this->Description->_value = $article->Description->_value;

        if ((float)$this->Cout_Unitaire->_value == 0) {
            $this->Cout_Unitaire->_value = match($article->Methode_Valorisation->_value) {
                MethodeValorisation::Standard->value => (float)$article->Cout_Standard->_value,
                MethodeValorisation::CMUP->value     => (float)$article->CMUP_Actuel->_value,
                default                              => (float)$article->Prix_Achat_Dernier->_value,
            };
        }

        $typeMvt = TypeMouvement::from($this->Type_Mouvement->_value);
        $this->Quantite_Nette->_value = (float)$this->Quantite->_value * $typeMvt->sensNumerique();

        if ($article->Suivi_Lot->_value && empty($this->No_Lot->_value))
            Error("L'article '".$this->Article_Code->_value."' requiert un numéro de lot.");
        if ($article->Suivi_Serie->_value && empty($this->No_Serie->_value))
            Error("L'article '".$this->Article_Code->_value."' requiert un numéro de série.");
    }
}
?>
