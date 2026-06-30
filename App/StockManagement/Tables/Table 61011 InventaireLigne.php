<?php
class InventaireLigne extends Table {
    public function __construct() {
        parent::__construct(61011, 'InventaireLigne', 'inventaire_ligne');

        $this->field(1,  'Inventaire_No',   FieldType::text(20, 'NOT NULL'));
        $this->field(2,  'Ligne_No',        FieldType::integer('NOT NULL'));
        $this->field(3,  'Article_Code',    FieldType::text(20, 'NOT NULL'));
        $this->field(4,  'Description',     FieldType::text(150));
        $this->field(5,  'Entrepot_Code',   FieldType::text(20, 'NOT NULL'));
        $this->field(6,  'Emplacement_Code',FieldType::text(20));
        $this->field(7,  'No_Lot',          FieldType::text(50));
        $this->field(8,  'No_Serie',        FieldType::text(50));

        // Stocks
        $this->field(20, 'Qte_Theorique',  FieldType::decimal(), caption: 'Qté théorique (calculée)');
        $this->field(21, 'Qte_Comptee',    FieldType::decimal(), caption: 'Qté comptée physiquement');
        $this->field(22, 'Ecart',          FieldType::decimal(), caption: 'Qté comptée - Qté théorique');
        $this->field(23, 'Cout_Unitaire',  FieldType::decimal());
        $this->field(24, 'Valeur_Ecart',   FieldType::decimal(), caption: 'Écart × Coût unitaire (FCFA)');

        // Flags
        $this->field(30, 'A_Un_Ecart',    FieldType::boolean());
        $this->field(31, 'Est_Compte',    FieldType::boolean(), caption: 'Comptage confirmé');
        $this->field(32, 'Tenant_code',   FieldType::text(30, 'NOT NULL'));

        $this->Keys('Inventaire_No', 'Ligne_No');
    }

    public function onInsert(): void {
        $inventaire = new InventaireEnTete();
        if (!$inventaire->get($this->Inventaire_No->_value))
            Error("Inventaire '".$this->Inventaire_No->_value."' introuvable.");
        if ($inventaire->Statut->_value === StatutInventaire::Valide->value)
            Error("L'inventaire est validé : impossible d'ajouter des lignes.");

        $article = new Article();
        if (!$article->get($this->Article_Code->_value))
            Error("Article '".$this->Article_Code->_value."' introuvable.");

        if (empty($this->Description->_value))
            $this->Description->_value = $article->Description->_value;

        $ec = new EcritureStock();
        $ec->setRange('Article_Code',  $this->Article_Code->_value);
        $ec->setRange('Entrepot_Code', $this->Entrepot_Code->_value);
        if (!empty($this->Emplacement_Code->_value))
            $ec->setRange('Emplacement_Code', $this->Emplacement_Code->_value);
        $qteTheorique = (float)($ec->aggregateSQL('SUM', 'Quantite_Nette') ?? 0);

        $this->Qte_Theorique->_value = $qteTheorique;
        $this->Cout_Unitaire->_value = (float)$article->CMUP_Actuel->_value;
    }

    public function onModify(): void {
        $inventaire = new InventaireEnTete();
        if ($inventaire->get($this->Inventaire_No->_value)
            && $inventaire->Statut->_value === StatutInventaire::Valide->value)
            Error("L'inventaire est validé : aucune modification possible.");

        $ecart = (float)$this->Qte_Comptee->_value - (float)$this->Qte_Theorique->_value;
        $this->Ecart->_value       = $ecart;
        $this->Valeur_Ecart->_value = round($ecart * (float)$this->Cout_Unitaire->_value, 2);
        $this->A_Un_Ecart->_value  = abs($ecart) > 0.001;
        $this->Est_Compte->_value  = true;
    }
}
?>
