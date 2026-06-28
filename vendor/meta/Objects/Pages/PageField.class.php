<?php

    class PageField implements Fields {
        private string $_name;
        private Field $_source;
        private $_onValidate;
        private $_onLookUp;
        private bool $_editatble = true;
        private bool $_enabled = true;
        private bool $_visible = true;
        private string $_caption;
        private string $_designHTML ;
        private string $_parentGroup;
        private $_value;

        public  function __construct($name, $source, $onValidate = null, $onLookUp = null, $editable = true, $enabled = true, $visible = true, $caption = null, $html=''){
            $this->_name = $name;

            $this->_source = $source;

            $this->_onValidate = $onValidate;

            $this->_onLookUp = $onLookUp;

            if($enabled !== null)
                $this->_enabled = $enabled;
            else
                $this->_enabled = $source->enabled;

            if($editable !== null)
                $this->_editatble = $editable;
            else
                $this->_editatble = $source->editatble;

            if($caption != null || $caption != "")
                $this->_caption = $caption;
            else
                $this->_caption = $this->_name;
            $this->_visible = $visible;
            $this->_designHTML = $html;

        }



        public function onValidate()
        {
            if($this->_onValidate != null) {
                if (is_callable($this->_onValidate)) {
                    ($this->_onValidate)();
                    header('updatable: true');
                }
            }
        }

        public function onLookUp()
        {
            if($this->_onLookUp != null) {
                if (is_callable($this->_onLookUp)) {
                    ($this->_onLookUp)();
                }
            }
        }
        public function __set($name, $value){
           switch($name){
                case "html":
                   $this->_designHTML = $value;
                   break;
                case 'parentGroup':
                   $this->_parentGroup = $value;
                   break;
                case '_value':
                    $this->_value = $value;
                    break;
           }
        }
        public function __get($name){
            switch($name){
                case 'name':
                    return $this->_source->name;
                case '_name':
                    return $this->_name;
                    break;
                case '_type':
                    return $this->_source->_type;
                    break;
                case '_id':
                    return $this->_source->_id;
                    break;
                case 'tableRelation':
                    return $this->_source->_tableRelation;
                    break;
                case 'source':
                    return $this->_source;
                    break;
                case 'editable':
                    return $this->_editatble;
                    break;
                case 'caption':
                    return $this->_caption;
                    break;
                case 'enabled':
                    return $this->_enabled;
                    break;
                case 'visible':
                    return $this->_visible;
                    break;
                case 'html':
                    return $this->_designHTML;
                    break;
                case 'parentGroup':
                    return $this->_parentGroup;
                    break;
                case '_value':
                    return $this->_value;
                    break;
                case 'value':
                default:
                    return $this->_source->_value;
                    break;
            }
        }

        public function HTML(){
            $properties = '';
            if(!$this->_editatble){
                $properties .= 'readonly=true';
            }
            if(!$this->_enabled){
                $properties .= ' disabled=true';
            }
            if(!$this->_visible){
                $properties .= ' hidden';
            }
            return $this->_designHTML;
        }

        public function __toString(){
            return $this->_source->value;
        }
    }
