<?php

    class Control{

        public $_name;
        public $_icon;
        public $_caption;
        private $_designHTML = '';
        public $_onAction;
        public $_confirm;
        public $_visible;

        private $_style = 'inverse-dark';


        public function __construct($name, $icon = null, $caption=null,$visible=true, $onAction=null, $html='', $style='inverse-dark', $confirm=null)
        {
            $this->_name = $name;
            if($icon != null)
                $this->_icon = $icon;
            else
                $this->_icon = '';

            if($caption != null)
                $this->_caption = $caption;
            else
                $this->_caption = $name;

            if($onAction != null)
                $this->_onAction = $onAction;

            $this->_designHTML = $html;
            $this->_style = $style;
            $this->_visible = $visible;

            if($confirm != null)
                $this->_confirm = $confirm;
            else
                $this->_confirm = 'null';
        }

        public function HTML(){
            return $this->_designHTML;
        }

        public function onAction(){
            if($this->_onAction != null) {
                if (is_callable($this->_onAction)) {
                    return ($this->_onAction)();
                }
            }
        }

        public function __get($name){
            switch($name){
                case "name":
                    return $this->_name;
                    break;
                    case "icon":
                        return $this->_icon;
                        break;
                        case "caption":
                            return $this->_caption;
                            break;
                            case "style":
                                return $this->_style;
                                break;
                            case "confirm":
                                return $this->_confirm;
                                break;;
                            case "visible":
                                return $this->_visible;
                                break;
            }
        }

        public function __set($name, $value){
            switch($name){
                case "html":
                    $this->_designHTML = $value;
                    break;
                    case "style":
                        $this->_style = $value;
                        break;
                    case "confirm":
                        $this->_confirm = $value;
                        break;
            }
        }
        public function __toString(){
            return $this->HTML();
        }
    }

?>
