<?php
    
    class licence extends Table {
        public function __construct()
        {
            parent::__construct(9999992, 'licence');

            $this->field(1, 'Code', FieldType::text(30),caption:'Licence Code');
            $this->field(2, 'start_date', FieldType::date(),caption:'Date debut');
            $this->field(3, 'end_date', FieldType::date(),caption:'Date fin');
            $this->field(4, 'active', FieldType::boolean(),caption:'Actif');
            $this->field(5, 'users_count', FieldType::integer(),caption:'Nombre d utilisateurs');
            $this->field(6, 'licence_key', FieldType::text(30),caption:'Clé de licence');

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