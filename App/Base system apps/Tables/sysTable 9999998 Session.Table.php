<?php

class Session extends Table {
    public function __construct()
    {
        parent::__construct(9999998, 'session');

        $this->field(1, 'session_id', FieldType::text(150),caption:'N°');
        $this->field(2, 'session_token', FieldType::text(150),caption:'Token');
        $this->field(3, 'ip_adress', FieldType::text(150),caption:'Adresse IP');
        $this->field(4, 'start_time', FieldType::text(50), caption: 'Heure de début');
        $this->field(6, 'end_time', FieldType::text(50), caption: 'Heure de fin');
        $this->field(7, 'user_email', FieldType::text(50), caption: 'Email de l\'utilisateur');
        $this->field(8, 'last_activity', FieldType::date(), caption: 'Dernière activité');

        $this->Keys('session_id');
    }
    public function onInsert() {
    }
    public function onModify() {
    }
    public function onDelete() {
    }
}