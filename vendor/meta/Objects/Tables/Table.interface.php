<?php
    
    interface Tables{
        
        public function field($id, $name, $type);
        
        public function Validate($field, $value);
        
        public function get(...$key);
        
        public function setRange($field, $value);
        
        public function FindSet();
        
        public function FindFirst();
        
        public function FindLast();

        public function FindAll();
        
        public function Insert($trigger=true);
        
        public function Modify($trigger=true);

        public function keys(...$keys);
        
        public function onInsert();
        
        public function onDelete();
        
        public function onModify();
        
        public function onRename();

        public function testField($field);

        public function copyRecord($table);
        
        
    }    
    
?>