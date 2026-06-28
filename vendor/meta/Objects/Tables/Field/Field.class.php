<?php

    require('Field.interface.php');
    
    class Field implements Fields{
        private $_id;
        private $_name;
        private $_type;
        private $_value;
        private $_tableRelation;
        private $_editatble = true;
        private $_enabled = true;
        private $_visible = true;
        private $_caption;
        private $_onValidate;
        private $_onLookUp;
        private $_options;

        public function __construct($id, $name, $type, $tableRelation = null, $editabled = true, $enabled = true, $onValidate = null, $onLookUp = null, $caption=null, $visible = true, $options=null){
            $this->_id = $id;
            $this->_name = $name;
            $this->_type = $type;
            $this->_tableRelation = $tableRelation;
            $this->_editatble = $editabled;
            $this->_enabled = $enabled;
            $this->_onValidate = $onValidate;
            $this->_onLookUp = $onLookUp;
            $this->_options = $options;
            $this->_visible = $visible;
            if($caption != null || $caption != "")
                $this->_caption = $caption;
            else
                $this->_caption = $this->_name;
        }
        
        public function __set($name, $val){
            switch($name){
                case '_value':
                    $this->_value = $val;
                    break;
                case 'tableRelation':
                    $this->_tableRelation = $val;
                    break;
                case 'editatble':
                    $this->_editatble = $val;
                    break;
                case 'enabled':
                    $this->_enabled = $val;
                    break;
            }
        }
        
        public function __get($name){
            switch($name){
                case '_name':
                    return $this->_name;
                case '_type':
                    return $this->_type;
                case '_id':
                    return $this->_id;
                case 'tableRelation':
                    return $this->_tableRelation;
                case 'editable':
                    return $this->_editatble;
                case 'caption':
                    return $this->_caption;
                case 'enabled':
                    return $this->_enabled;
                case 'options':
                    return $this->_options;
                case 'value':
                case '_value':
                default:
                    return $this->_value;
            }
        }
        
        public function onValidate(){
            if($this->_onValidate != null) {
                if (is_callable($this->_onValidate)) {
                    ($this->_onValidate)();
                }
            }
        }   
        
        
        public function onLookUp(){
            if($this->_onLookUp != null) {
                if (is_callable($this->_onLookUp)) {
                    ($this->_onLookUp)();
                }
            }
        }

        public function __toString(){
            return $this->_value == null ? "" : $this->_value;
        }
        
    } 
    

?>