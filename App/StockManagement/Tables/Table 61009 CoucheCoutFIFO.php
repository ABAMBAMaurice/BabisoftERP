<?php
/**
 * Couches de coût FIFO.
 * Chaque entrée crée une couche. Les sorties consomment les couches les plus anciennes.
 * Utilisé uniquement pour les articles valorisés en FIFO.
 */
class CoucheCoutFIFO extends Table {
    public function __construct() {
        parent::__construct(61009, 'CoucheCoutFIFO', 'couche_cout_fifo');

        $this->field(1,  'No_',               FieldType::integer('NOT NULL'), caption: 'N° couche (auto)');
        $this->field(2,  'Article_Code',      FieldType::text(20, 'NOT NULL'));
        $this->field(3,  'No_Ecriture',       FieldType::integer('NOT NULL'), caption: 'N° écriture stock d\'entrée');
        $this->field(4,  'Date_Entree',       FieldType::date('NOT NULL'));
        $this->field(5,  'Cout_Unitaire',     FieldType::decimal('(15,4) NOT NULL'), caption: 'Coût unitaire de cette couche');
        $this->field(6,  'Quantite_Initiale', FieldType::decimal('(15,4) NOT NULL'));
        $this->field(7,  'Quantite_Restante', FieldType::decimal('(15,4) NOT NULL'), caption: 'Qté non encore consommée');
        $this->field(8,  'Entrepot_Code',     FieldType::text(20));
        $this->field(9,  'No_Lot',            FieldType::text(50));
        $this->field(10, 'Tenant_code',       FieldType::text(30, 'NOT NULL'));

        $this->Keys('No_');
    }
}
?>
