<?php
/**
 * Modes de règlement.
 * Adapté au contexte Côte d'Ivoire : Mobile Money très répandu (Wave, OM, MTN).
 */
class ModeReglement extends Table {
    public function __construct() {
        parent::__construct(62002, 'mode_reglement');

        $this->field(1, 'Code',           FieldType::text(10, 'NOT NULL'));
        $this->field(2, 'Intitule',       FieldType::text(100, 'NOT NULL'), caption: 'Ex: Espèces, Virement, Chèque, Wave, Orange Money, MTN Money, Carte');
        $this->field(3, 'Type',           FieldType::text(30),              caption: 'Especes | Virement | Cheque | Mobile_Money | Carte | Autre');
        $this->field(4, 'Compte_No',      FieldType::text(20, 'NOT NULL'),  caption: 'Compte bancaire ou caisse (SYSCOA 52xxx/57xxx)');
        $this->field(5, 'Delai_Valeur',   FieldType::integer(),             caption: 'Délai de valeur en jours (virement)');
        $this->field(6, 'Commission_Pct', FieldType::decimal(),             caption: 'Commission perçue (Mobile Money)');
        $this->field(7, 'Tenant_code',    FieldType::text(30, 'NOT NULL'));

        $this->Keys('Code');
    }
}
?>
