<?php
    class User extends Table
    {
        public function __construct() {
            parent::__construct(9999997, 'utilisateur');

            $this->field(1, 'Email', FieldType::text(100), caption:'Code');
            $this->field(2, 'Full_name', FieldType::text(50));
            $this->field(3, 'Password', FieldType::text(200));
            $this->field(4, 'Profile',     FieldType::text(30),  tableRelation: new Profile(), caption: 'Profile');
            $this->field(5, 'Is_active',   FieldType::boolean(), caption: 'Actif');
            $this->field(6, 'Tenant_code', FieldType::text(50),  caption: 'Tenant');
            $this->field(7, 'Is_admin',      FieldType::boolean(), caption: 'Admin tenant');
            $this->field(8, 'Mobile_access', FieldType::boolean(), caption: 'Accès mobile autorisé');

            $this->Keys('Email');
        }

        public function onInsert()
        {
            // N'appliquer le mot de passe par défaut que si aucun n'a été fourni.
            // Sans ce test, onInsert() écrase systématiquement le mot de passe
            // choisi par l'utilisateur lors de l'inscription (signUp).
            if (IsNullOrEmptyString($this->Password->value))
                $this->Validate('Password', Security::hashPassword('1234@5678'));
            
            if (IsNullOrEmptyString($this->Is_active->value))
                $this->Validate('Is_active', '1');
        }  
        public function onModify() {
        }
        public function onDelete() {
        }

        public function resetPassword()
        {
            $this->Validate('Password', Security::hashPassword('1234@5678'));
            $this->Modify();
        }

        public function updatePassword($newPassword)
        {
            $this->Validate('Password', Security::hashPassword($newPassword));
            $this->Modify();
        }
        
    }
?>
