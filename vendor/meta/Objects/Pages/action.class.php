<?php
    require('actions.interface.php');
    class action implements actions
    {
        private $_visible = true;
        private $_enabled = true;
        private $_name;
        private $_caption;
        private $_tooltipText;
        private $_confirm;

        public function onAction()
        {
            header('updatable: true');
        }

        public function __set($name, $value){
            switch($name) {
                case "visible":
                    $this->_visible = $value;
                    break;
                case "enabled":
                    $this->_enabled = $value;
                    break;
                case "caption":
                    $this->_caption = $value;
                    break;
                case "confirm":
                    $this->_confirm = $value;
                    break;
                case "tooltipText":
                    $this->_tooltipText = $value;
                    break;
            }
        }

        public function __get($name){
            switch($name) {
                case "visible":
                    return $this->_visible;
                case "enabled":
                    return $this->_enabled;
                case "caption":
                    return $this->_caption;
                case "confirm":
                    return $this->_confirm;
                case "tooltipText":
                    return $this->_tooltipText;
                case "action_name":
                    return $this->_name;
            }
        }
    }

?>