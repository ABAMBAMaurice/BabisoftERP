<?php
class Tenant extends Table {
    public function __construct() {
        parent::__construct(9999983, 'tenant');

        $this->field(1,  'Code',            FieldType::text(50),          caption: 'Code tenant');
        $this->field(2,  'Nom',             FieldType::text(100),         caption: 'Nom entreprise');
        $this->field(3,  'Email',           FieldType::text(100),         caption: 'Email');
        $this->field(4,  'Plan',            FieldType::text(30),          caption: 'Plan');
        $this->field(5,  'Statut',          FieldType::text(20),          caption: 'Statut');
        $this->field(6,  'Date_expiration', FieldType::text(10),          caption: 'Date expiration');
        $this->field(7,  'Admin_email',     FieldType::text(100),         caption: 'Admin');
        $this->field(8,  'Telephone',       FieldType::text(30),          caption: 'Téléphone');
        $this->field(9,  'Adresse',         FieldType::text(200),         caption: 'Adresse');
        $this->field(10, 'Ville',           FieldType::text(50),          caption: 'Ville');
        $this->field(11, 'Pays',            FieldType::text(50),          caption: 'Pays');
        $this->field(12, 'Nb_users_max',    FieldType::text(10),          caption: 'Utilisateurs max');
        $this->field(13, 'Nb_projets_max',  FieldType::text(10),          caption: 'Projets max');
        $this->field(14, 'Montant_mensuel', FieldType::decimal('(10,2)'), caption: 'Montant mensuel');

        $this->Keys('Code');
    }

    public function onInsert(): void {
        if (IsNullOrEmptyString($this->Statut->value))
            $this->Validate('Statut', 'Actif');
        if (IsNullOrEmptyString($this->Nb_users_max->value))
            $this->Validate('Nb_users_max', '5');
        if (IsNullOrEmptyString($this->Nb_projets_max->value))
            $this->Validate('Nb_projets_max', '0');
        if (IsNullOrEmptyString($this->Montant_mensuel->value))
            $this->Validate('Montant_mensuel', '0');
    }

    public function onModify(): void {}
    public function onDelete(): void {}
}
?>
