<?php
    class csrfToken extends Table {
        public function __construct()
        {
            parent::__construct(9990999, 'csrftoken');

            $this->field(1, 'token', FieldType::text(128),caption:'token');
            $this->field(2, 'ip_address', FieldType::text(30), caption:'ip_address');
            $this->field(4, 'start_time', FieldType::text(50), caption: 'Heure de début');
            $this->field(6, 'end_time', FieldType::text(50), caption: 'Heure de fin');
            
            $this->Keys('token');
        }
        public function onInsert() {
        }
        public function onModify() {
        }
        public function onDelete() {
        }
    }
?>