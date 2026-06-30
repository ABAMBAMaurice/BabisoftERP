
<?php

    require('Pages.interface.php');
    require('PageField.class.php');
    require('action.class.php');
    require('groups.class.php');
    require('repeater.class.php');

    class Page implements Pages {
        private int $_id;
        private int $_cardPageID;
        private string $_name;
        private string $_caption;
        private PagesType $_type;
        private ?Table $_rec = null;
        public static $_pageCollection = array();
        private $_fieldsList = array();
        private $_actionsList = array();
        private $_groups = array();
        private $_repeaters = array();
        private array $_widgets = [];
        private $subPage = '{}';
        private $subPageLinks = array();
        private $_bodyHTML = '';
        private $_editable = true;
        private $_allowDelete = true;
        private $_allowInsert = true;
        private $_parent_pageID;


        public function __construct($id, $name, $type, $caption = ''){
            $this->_id = $id;
            $this->_name = $name;
            $this->_type = $type;
            $this->_caption = $caption;
            $this->_cardPageID = 0;
            Page::$_pageCollection[$id] = $this;

        }

        /**
         * @param $name
         * Nom du groupe. Unique, identitifie un groupe de la page
         * @param $caption
         * Libellé d'affichaqe de la page. Facultatif.
         * @param ...$fields
         * La liste des champs contenus dans le groupe créé
         * @return void
         *
         *
         * Cette methode permet de créer des groupes dans une page.
         * Il s'agit d'une methode obligatoire facilitant l'organisation des champs
         * de la page et de faciliter la maintenance de votre application
         */

        public function group($name, $caption='', ...$fields){
            if(isset($this->_groups[$name]))
                Error('Groupe '.$name.' existe déja');
            $nGroup = new group($name, $caption);
            $nGroup->SourceTableId = $this->rec->table_id;
            $nGroup->SourceTableName = $this->rec->table_name;
            $nGroup->SourcePageName = $this->pageName;
            $nGroup->SourcePageId = $this->id;

            foreach ($fields as $field){
                $nGroup->fields($field->_name, $field);
                $this->_fieldsList[$field->_name] = $field;
            }
            $this->_groups[$name] = $nGroup;
        }

        /**
         * @param $name
         * @param $caption
         * @param ...$fields
         * @return void
         *
         * Cette methode permet de créer des listes dans une page.
         * En réalité, cette methode vous permet de savoir qu'il s'agit d'une liste et peut être remplacée par
         * la methode groupe
         * * Il s'agit d'une methode obligatoire facilitant l'organisation des champs
         * * de la page et de faciliter la maintenance de votre application
         */
        public function repeater($name, $caption, ...$fields){
            if(isset($this->_repeaters[$name]))
                Error('Repeater '.$name.' existe déja');
            $nGroup = new repeater($name, $caption);
            $nGroup->SourceTableId = $this->rec->table_id;
            $nGroup->SourceTableName = $this->rec->table_name;
            $nGroup->SourcePageName = $this->pageName;
            $nGroup->SourcePageId = $this->id;
            $nGroup->type = 'REPEATER';

            foreach ($fields as $field){
                $nGroup->fields($field->_name, $field);
                $this->_fieldsList[$field->_name] = $field;
            }
            $this->_repeaters[$name] = $nGroup;
            $this->_groups[$name] = $nGroup;
        }

        /**
         * This function is marked for update
         */
        public function part($PageID, $subPageFieldLink){
            $pge = new Views();

            if($pge->get($PageID)){
                $currPage = new $pge->className->value;
                $currPage->subPageLinks = $subPageFieldLink;
                $currPage->parent_pageID = $this;
                
                $this->subPageLinks = $subPageFieldLink;
                $this->subPage = $currPage;
                $this->_groups[$currPage->name] = $currPage;
            }
        }

        

        /**
         * @param $name
         * @param $icon
         * @param $caption
         * @param $onAction
         * @param $html
         * @param $style
         * @return void
         *
         *
         * Cette methode permet de définir les différentes actions possibles sur cette page.
         * Actions, réalisable généralement à partir de boutons.
         */
        public function actions($name, $icon=null, $caption=null, $visible=true, $onAction = null, $html='', $style='inverse-dark', $confirm=null){
            if($onAction == null){
                $FctAction = function(){};
            }else{
                $FctAction = $onAction;
            }
            $this->_actionsList[$name] = new Control($name, $icon, $caption,$visible, $FctAction, $html, $style, $confirm);
        }

        /**
         * @param $name
         * @param $source
         * @param $onValidate
         * @param $onLookUp
         * @param $editable
         * @param $enabled
         * @param $visible
         * @param $caption
         * @param $html
         * @return void
         *
         * Cette methode permet d'ajouter des champs dans la page en vrac.
         * Elle peut être utilisée si vous ne souhaitez pas structurer votre page avec des groupes
         * ou des repeaters
         */

        public function field($name, $source, $onValidate = null, $onLookUp = null, $editable = true, $enabled = true, $visible = true, $caption = null, $html=''){
            $pgeField =  new PageField(
                name: $name,
                source: $source,
                onValidate: $onValidate,
                onLookUp: $onLookUp,
                editable: $editable,
                enabled: $enabled,
                visible: $visible,
                caption: $caption,
                html: $html
            );
            $this->_fieldsList[$name] = $pgeField;
        }

        public function setRecord($record){
            $this->_rec = $record;
        }
        public function __set($name, $value){
            switch ($name) {
                case 'sourceTable':
                case 'rec':
                    $this->_rec = $value;
                    $this->registerInViews();
                    break;
                case 'html':
                    $this->_bodyHTML = $value;
                    break;
                case 'editionMode':
                    $this->_editionMode = $value;
                    break;
                case 'cardPageID':
                    $this->_cardPageID = $value;
                    break;
                case 'subPageLinks':
                    $this->subPageLinks = $value;
                    break;
                case 'subPageFieldLink':
                    $this->_subPageFieldLink = $value;
                    break;
                case 'pageFieldLink':
                    $this->_pageFieldLink = $value;
                    break;
                case 'editable':
                    $this->_editable = $value;
                    break;
                case 'AllowDelete':
                    $this->_allowDelete = $value;
                    break;
                case 'AllowInsert':
                    $this->_allowInsert = $value;
                    break;
                case 'parent_pageID':
                    $this->_parent_pageID = $value;
                    break;
            }
        }

        public function update($filters=true){
            
            if($filters == true || $this->type == PagesType::Card)
                PageOpen($this);
            else{
                if($this->type == PagesType::List){
                    $this->rec->FindAll();
                    PageOpen($this);
                }
            }
        }

        public function __get($name){
            switch ($name) {
                case 'rec':
                    return $this->_rec;
                case 'pageName':
                    return $this->_name;
                case 'type':
                    return $this->_type;
                case 'actions':
                    return $this->_actionsList;
                case 'id':
                    return $this->_id;
                case 'editionMode':
                    return $this->_editionMode;
                case 'groups':
                    return $this->_groups;
                case 'repeaters':
                    return $this->_repeaters;
                case 'cardPageID':
                    return $this->_cardPageID;
                case 'subPageLinks':
                    return $this->subPageLinks;
                case 'subPage':
                    return $this->subPage;
                case 'pageFieldLink':
                    return $this->_pageFieldLink;
                case 'html':
                    return $this->_bodyHTML;
                case 'Caption':
                    return $this->_caption;
                case 'editable':
                    return $this->_editable;
                case 'AllowDelete':
                    return $this->_allowDelete;
                case 'AllowInsert':
                    return $this->_allowInsert;
                case 'parent_pageID':
                    return $this->_parent_pageID;
                case 'Fields':
                    return $this->_fieldsList;
                default:
                    if(isset($this->_fieldsList[$name])){
                        return $this->_fieldsList[$name];
                    }
                    break;
            }
        }



        /**
         * @return void
         *
         * Cette méthode permet d'exécuter des instrcutions à l'ouverture de la page
         */
        public function onOpenPage(){}


        /**
         * @param $Pageid
         * @return mixed
         *
         * Cette methode static, permet d'ouvrir la page avec l'id $Pageid
         */
        public static function open($Pageid)
        {
            return Page::$_pageCollection[$Pageid];
        }

        /**
         * @param Page $page
         * @param $record
         * @return void
         *
         * Cette methode permet d'ouvrir la page $page avec le record $record
         */
        // Function Marked for removal
        /*
        public static function Record_open(Page $page, $record)
            {
                $page->rec = $record;
                $page->open();
            }
        */

        /**
         * @return void
         *
         * Cette methode permet d'exécuter des instrcution lors de la fermetture de la page
         */
        public function OnClosePage(){}


        /**
         * @return string
         *
         *
         *
         * Cette methode sérialise la page et l'affiche sous forme de chaîne de caractères
         */
        public function __toString()
        {
            $this->onOpenPage();

            if ($this->_type === PagesType::RoleCenter) {
                $r  = '{';
                $r .= '"id":"'.$this->_id.'",';
                $r .= '"name":"'.$this->_name.'",';
                $r .= '"PageType":"RoleCenter",';
                $r .= '"Caption":"'.$this->_caption.'",';
                $r .= '"record":'.$this->show();
                $r .= '}';
                return $r;
            }

            if($this->subPage != '{}') {
                if(count($this->subPageLinks) > 0) {
                    foreach ($this->subPageLinks as $key => $field) {
                        $this->subPage->setRange($field, $this->rec->{$key});
                    }
                    $this->subPage->rec->FindSet();
                }
            }

            $r = '{';
                $r .='"id":"'.$this->_id.'",';
                $r .='"name":"'.$this->_name.'",';
                $r .='"PageType":"'.$this->_type->name.'",';
                $r .='"sourceTableID":"'.$this->_rec->table_id.'",';
                $r .='"sourceTableName":"'.$this->_rec->table_name.'",';
                if($this->_cardPageID != NULL || $this->_cardPage != "" || $this->_cardPage != 0)
                    $r .='"cardPageID":"'.$this->_cardPageID.'",';
                if($this->_parent_pageID != NULL)
                    $r .='"isSubForm":"1",';
                else
                    $r .='"isSubForm":"0",';

                if($this->_editable)
                    $r .='"editable":1,';
                else
                    $r .='"editable":0,';
                $r .= '"actions":[ ';
                    foreach ($this->_actionsList as $action){
                        $r .= '{';
                            $r .='"name":"'.$action->name.'",';
                            $r .='"caption":"'.$action->caption.'"';
                        $r .='},';
                        //$r = substr($r, 0, strlen($r) - 1);
                    }
                        $r = substr($r, 0, strlen($r) - 1);
                $r .='],';                
                $r .= '"record":'.$this->show();
                if($this->subPage != '{}')
                    $r .= ',"subPage":'.$this->subPage;

            $r .='}';
            return $r;
        }


        /**
         * @return void
         *
         * Cette fonction permet de structurer les différents groupes et repeater de la page. Implémentée dans votre page, elle est appelée
         * dans le constructeur de cette dernière
         */
        function layout(){}


        /**
         * @return void
         *
         *
         * Idem que la fonction "layout", cette fonction permet de structurer
         * les actions à définnir sur la page
         */
        function setActions(){}


        /**
         * @return void
         *
         * Permet d'initialiser un nouveau record dans la base de donnée
         */
        public function OnNewRecord(& $record){
           
        }


        /**
         * @param $field: Nom du champs. Attention sensible à la case
         * @param $value: Valeur du champs. Doit avoir le même type que le champ déclaré dans la table!
         * @return true|void
         *
         *Permet d'attribuer une valeur à un champs de la Page lié au record
         *
         */


        public function Validate($field, $value)
        {
            if($this->rec->Validate($field, $value)){
                $this->_fieldsList[$field]->_value = $value;
                $this->_fieldsList[$field]->onValidate();
                return true;
            }else{
                Error('Erreur lors de la validation des données.');
            }
        }
        function SetRange($field, $value){
            $this->rec->SetRange($field, $value);
        }
        function FindSet(){
            $this->rec->FindSet();
        }
        function setFilter($field,$pattern,...$values){
            $this->rec->setFilter($field,$pattern,$values);
        }

        function FindFirst(){
            $this->rec->FindFirst();
        }

        function FindAll()
        {
            $this->rec->FindAll();
        }

        function FindLast(){
            $this->rec->FindLast();
        }

        function Find(){
            $this->rec->Find();
        }


        function Modify($trigger = true){
          $this->rec->Modify($trigger);
        }
        function Insert($trigger = true){
          $this->rec->Insert($trigger);
        }
        function Delete($trigger = true){
          $this->rec->Delete($trigger);
        }

        public function OnAfterGetRecord(Table &$record){}

        /**
         * Ajoute un widget au RoleCenter (stat, shortcut, activity).
         * $value peut être une valeur scalaire ou un callable (évalué à l'affichage).
         */
        public function widget(string $name, string $type, string $caption, string $icon = '', mixed $value = null, int $linkedPageId = 0, string $style = 'primary') {
            $this->_widgets[$name] = [
                'name'         => $name,
                'type'         => $type,
                'caption'      => $caption,
                'icon'         => $icon,
                'value'        => $value,
                'linkedPageId' => $linkedPageId,
                'style'        => $style,
            ];
        }

        public function show(){
            if ($this->_type === PagesType::RoleCenter) {
                $widgets = [];
                foreach ($this->_widgets as $w) {
                    $w['value'] = is_callable($w['value']) ? ($w['value'])() : $w['value'];
                    $widgets[] = $w;
                }
                return json_encode($widgets);
            }

            if($this->_type == PagesType::List || $this->_type == PagesType::ListPart) {
                $r = '[';

                if(count($this->rec->recordSet)>0) {
                    foreach ($this->rec->recordSet as $record) {
                        $this->OnAfterGetRecord($record);
                        $r .= '{';
                        if (count($record->_fields)>0) {
                            foreach ($record->_fields as $field) {
                                $r .= '"' . $field->_name . '":"' . $field->_value . '",';
                            }
                            foreach ($record->_flowFields as $ff) {
                                if ($ff->_value !== null)
                                    $r .= '"' . $ff->_name . '":"' . $ff->_value . '",';
                            }
                            $r = substr($r, 0, strlen($r) - 1);
                        }
                        $r .= '},';
                    }
                    $r = substr($r, 0, strlen($r) - 1);
                }
                $r .= ']';
                return $r;
            }else if($this->_type == PagesType::Card || $this->_type == PagesType::Document){
                $this->OnAfterGetRecord($this->rec);
                $r = '{';
                if(count($this->rec->recordSet) > 0) {
                    foreach ($this->rec->recordSet[0]->_fields as $field) {
                        $r .= '"' . $field->_name . '":"' . $field->value . '",';
                    }
                    foreach ($this->rec->recordSet[0]->_flowFields as $ff) {
                        if ($ff->_value !== null)
                            $r .= '"' . $ff->_name . '":"' . $ff->_value . '",';
                    }
                    $r = substr($r, 0, strlen($r) - 1);
                }
                $r .= '}';
                return $r;
            }
        }

        public function Message($message){
            header("Content-type: application/json");
            $this->rec->refresh();
            die(json_encode(array("status"=>100, "message"=>$message, "page" => json_decode($this))));
        }

        /**
         * Enregistre (upsert) la page dans la table `views` à chaque instanciation.
         * Silencieux si la table n'existe pas encore (premier démarrage avant SystemUpdateSchema).
         * Appelé automatiquement via __set('sourceTable') et explicitement par les RoleCenter.
         */
        protected function registerInViews() {
            if (!class_exists('Views') || !defined('db')) return;
            if (!db->table_exists('views')) return;

            $view = new Views();
            $view->setRange('Id', $this->_id);
            if ($view->FindFirst()) {
                $view->Validate('className', get_class($this));
                $view->Validate('caption', $this->_caption);
                $view->Validate('pageType', $this->_type->name);
                if (isset($this->_rec)) {
                    $view->Validate('SourceTableID', $this->_rec->table_id);
                    $view->Validate('SourceTableName', $this->_rec->table_name);
                }
                $view->Modify(false);
            } else {
                $view->Validate('Id', $this->_id);
                $view->Validate('className', get_class($this));
                $view->Validate('caption', $this->_caption);
                $view->Validate('pageType', $this->_type->name);
                if (isset($this->_rec)) {
                    $view->Validate('SourceTableID', $this->_rec->table_id);
                    $view->Validate('SourceTableName', $this->_rec->table_name);
                }
                $view->Insert(false);
            }
        }
               
        /*public function Confirm($message){
            header("Content-type: application/json");
            die(json_encode(array("status"=>102, "message"=>$message, "page" => json_decode($this))));
        }*/       
        

    }
?>
