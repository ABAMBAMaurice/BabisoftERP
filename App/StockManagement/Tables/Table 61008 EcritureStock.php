<?php
/**
 * Grand livre du stock — immuable après validation.
 * Une écriture par ligne de mouvement validée.
 *
 * Quantite_Nette = +Qte pour les entrées, -Qte pour les sorties
 * Valeur_Nette   = +Valeur pour les entrées, -Valeur pour les sorties
 * → Stock_Actuel = SUM(Quantite_Nette) par Article
 * → Valeur_Stock = SUM(Valeur_Nette) par Article
 */
class EcritureStock extends Table {
    public function __construct() {
        parent::__construct(61008, 'EcritureStock', 'ecriture_stock');

        $this->field(1,  'No_',                  FieldType::integer('NOT NULL'), caption: 'N° écriture');
        $this->field(2,  'Date_Ecriture',        FieldType::date('NOT NULL'));
        $this->field(3,  'Document_Type',        FieldType::text(30, 'NOT NULL'));
        $this->field(4,  'Document_No',          FieldType::text(20, 'NOT NULL'));
        $this->field(5,  'Article_Code',         FieldType::text(20, 'NOT NULL'));
        $this->field(6,  'Description',          FieldType::text(150));
        $this->field(7,  'Type_Mouvement',       FieldType::text(30, 'NOT NULL'));
        $this->field(8,  'Entrepot_Code',        FieldType::text(20, 'NOT NULL'));
        $this->field(9,  'Emplacement_Code',     FieldType::text(20));
        $this->field(10, 'No_Lot',               FieldType::text(50));
        $this->field(11, 'No_Serie',             FieldType::text(50));

        // ── Quantités ──────────────────────────
        $this->field(20, 'Quantite',             FieldType::decimal('(15,4) NOT NULL'), caption: 'Qté absolue (positive)');
        $this->field(21, 'Quantite_Nette',       FieldType::decimal('(15,4) NOT NULL'), caption: 'Qté nette signée');

        // ── Valorisation ───────────────────────
        $this->field(30, 'Cout_Unitaire',        FieldType::decimal(), caption: 'Coût unitaire à la date');
        $this->field(31, 'Valeur_Totale',        FieldType::decimal(), caption: 'Valeur absolue (Qte × CU)');
        $this->field(32, 'Valeur_Nette',         FieldType::decimal(), caption: 'Valeur nette signée');
        $this->field(33, 'CMUP_Apres',           FieldType::decimal(), caption: 'CMUP après ce mouvement');
        $this->field(34, 'Methode_Valorisation', FieldType::text(20));

        // ── Comptabilisation ───────────────────
        $this->field(40, 'Est_Comptabilise',     FieldType::boolean());
        $this->field(41, 'Piece_Compta_No',      FieldType::text(30),  caption: 'N° pièce comptable générée');

        $this->field(50, 'Tenant_code',          FieldType::text(30, 'NOT NULL'));

        $this->Keys('No_');
    }

    public function onInsert(): void {
        if ((float)$this->Cout_Unitaire->_value < 0)
            Error("Le coût unitaire ne peut pas être négatif.");
    }

    public function onDelete(): void {
        Error("Les écritures de stock sont immuables. Utilisez un ajustement pour corriger.");
    }
}
?>
