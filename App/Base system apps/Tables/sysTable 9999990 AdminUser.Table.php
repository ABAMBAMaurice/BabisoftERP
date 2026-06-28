<?php
class AdminUser extends Table {
    public function __construct() {
        parent::__construct(9999990, 'admin_user');

        $this->field(1, 'Email',     FieldType::text(100), caption: 'Email');
        $this->field(2, 'Full_name', FieldType::text(100), caption: 'Nom complet');
        $this->field(3, 'Password',  FieldType::text(200), caption: 'Mot de passe');
        $this->field(4, 'Role',      FieldType::text(20),  caption: 'Rôle'); // ADMIN | SUPERADMIN
        $this->field(5, 'Is_active', FieldType::boolean(), caption: 'Actif');

        $this->Keys('Email');
    }

    public function onInsert() {
        if (IsNullOrEmptyString($this->Role->value))
            $this->Validate('Role', 'ADMIN');
        if (IsNullOrEmptyString($this->Is_active->value))
            $this->Validate('Is_active', '1');
        if (IsNullOrEmptyString($this->Password->value))
            $this->Validate('Password', Security::hashPassword('Admin@2024!'));
    }
    public function onModify() {
    }
    public function onDelete() {
    }


}
?>
