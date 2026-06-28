<?php

class Profile extends Table {
    public function __construct()
    {
        parent::__construct(9999995, 'profile');

        $this->field(1, 'Code', FieldType::text(30),caption:'N°');
        $this->field(2, 'Name', FieldType::text(150),caption:'Nom');
        $this->field(3, 'Caption', FieldType::text(150),caption:'Nom affichage');
        $this->field(4, 'Dashboard', FieldType::text(150),caption:'Tableau de bord');

        $this->Keys('Code');
    }
    public function onInsert() {
    }
    public function onModify() {
    }
    public function onDelete() {
    }
}

?>