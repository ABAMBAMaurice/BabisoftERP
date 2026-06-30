<?php 

    require('Table.interface.php');
    require('Field/Field.class.php');
    require('Field/FlowField.class.php');
    
    class Table implements Tables{
        private $_id;
        private $_name;
        private $_fields = array();
        private array $_flowFields = [];
        private $_recordSet = array();
        private $_keys = array();
        private $_current_keys = "";
        public static $_records = array();

        public static $_tableCollection = array();

        private $_rangeSetted = false;

        private $_filter='';



        
        public function __construct($id, $name){
            $this->_filter = '';
            $this->_id = $id;
            $this->_name = $name;

            //System tables field
            $this->field(2000000,'created_at', FieldType::datetime());
            $this->field(2000001,'modified_at', FieldType::datetime());
            $this->field(2000003,'deleted_at', FieldType::datetime());
            $this->field(9999999,'Id_slug', FieldType::text(128));
            $this->field(2000004,'Id_Incr', FieldType::integer('UNIQUE NOT NULL AUTO_INCREMENT'));

            if(!isset(Table::$_records[$this->_id])){
                Table::$_records[$this->_id] = array();
            }
            if(!isset(Table::$_tableCollection[$name])){
                Table::$_tableCollection[$name] = array();
            }


            Table::$_tableCollection[$name] = $this;

            

        }
        
        public function field($id, $name, $type, $tableRelation = null, $editable = true, $enabled = true, $onValidate = null, $onLookup = null, $caption = null, $visible=true, $options=null){
            
            if(!isset($this->_fields[$name])){
                $fld = new Field($id, $name, $type, $tableRelation, $editable, $enabled, $onValidate, $onLookup, $caption, $visible, $options);
                $this->_fields[$name] = $fld;
                return $this->_fields[$name];
            }else{
                Error('The field '.$name.' already exists in the table '. $this->_name);
            }
        }

        public function setCurrentKeys(...$keys){
            $this->_current_keys = '';
            foreach ($keys as $key) {
                $this->_current_keys .= $key.',';                
            }
        }
        public function addRecord($record){
            $this->_recordSet[] = $record;
        }
        
        public function getFilters(){
            return $this->_filter;
        }

        public function __get($name){
                switch($name){
                    case 'table_id':
                       return $this->_id;
                        break;
                    case 'table_name':
                        return $this->_name;
                        break;
                    case '_fields':
                        return $this->_fields;
                        break;
                    case 'recordSet':
                        return $this->_recordSet;
                        break;
                    case 'keysList':
                        return $this->_keys;
                        break;
                    case 'keys':
                        $stringKey = '';
                        foreach ($this->_keys as $key) {
                            $stringKey = $stringKey . $key->_value;
                        }
                        return $stringKey;
                        break;
                    case '_flowFields':
                        return $this->_flowFields;
                        break;
                    default:
                        if(isset($this->_fields[$name])){
                            return $this->_fields[$name];
                        }else if(isset($this->_flowFields[$name])){
                            return $this->_flowFields[$name];
                        }else{
                            Error("Le champs ".$name." n'existe pas dans la table ".$this->_id." - ".$this->_name);
                        }
                        break;
                }

        }
        
        public function __set($name, $value){
            if(isset($this->_fields[$name])){
                $this->_fields[$name]->_value = $value;
            }else{
                switch($name){
                    case 'id':
                        $this->_id = $value;
                        break;
                    case 'table_name':
                        $this->_name = $value;
                        break;
                    default:
                        if(isset($this->_fields[$name])){
                            $this->_fields[$name] = $value;
                        }else{
                            Error("Le champs ".$name." n'existe pas dans la table ".$this->_id." - ".$this->_name);
                        }
                        break;
                }
            }
        }

        public function unsetRecord($key){
            unset($this->_recordSet[$key]);
            array_values($this->_recordSet);
        }
        /*public function setRecord($key, $rec){
            $this->_recordSet[$key] = $rec;
            array_values($this->_recordSet);
        }*/
        
        public function Validate($field, $value=null){
                if(isset($this->_fields[$field])) {
                    if($value != null) {
                        $this->_fields[$field]->_value = $value;
                        $this->_fields[$field]->onValidate();
                    }else{                        
                        $this->_fields[$field]->onValidate();
                    }
                    
                    return true;
                }else{
                    Error('Erreur de Validation. Champs \''.$field.'\' inconnu dans la table \''. $this->_name.'\'');
                }
            
        }
        
        public function get(...$keys){
            $this->loadTable();
            if(count($keys) == count($this->_keys)){
                $stringKey = '';
                foreach($keys as $key){
                    $stringKey = $stringKey.$key;
                }
                if(isset(Table::$_records[$this->_id][$stringKey])) {
                    $this->copyRecord(Table::$_records[$this->_id][$stringKey]);
                    array_push($this->_recordSet, $this);
                    return Table::$_records[$this->_id][$stringKey];
                }else
                    return false;
            }else{
                Error('Nombre de clé(s) incorrect. Vous devirez avoir \''. count($this->_keys).'\' clé(s) pour la table \''. $this->_name.'\'');
            }
        }


        public function setFilter($field,$pattern,...$values){
            if(isset($this->_fields[$field])) {
                $s = $pattern;

                for ($i = 0; $i < count($values); $i++) {
                    $s = preg_replace('[%[' . ($i + 1) . ']]', " '" . $values[$i] . "' ", $s);
                }
                $s = str_replace("|", " OR " . $this->_fields[$field]->_name . " ", $s);
                $s = str_replace("&", " AND " . $this->_fields[$field]->_name . " ", $s);
                return $this->_filter .= ' AND (' . ($this->_fields[$field]->_name . " " . $s) . ')';
            }else{
                Error('Le champ '.$field.' n\'existe pas dans la table '.$this->_id.' - '.$this->_name);
            }
        }

/*
        public function setRange($field, $value){
            if(!$this->_rangeSetted) {
                $this->loadTable();
                $records = Table::$_records[$this->_id];
                $this->_recordSet = array();

                foreach ($records as $record) {
                    if ($record->_fields[$field]->_value == $value) {
                        array_push($this->_recordSet, $record);
                    }
                }
                $this->_rangeSetted = true;
            }
            if(!empty($this->_recordSet)) {
                $records = $this->_recordSet;
                $this->_recordSet = array();
                foreach ($records as $record) {
                    if ($record->_fields[$field]->_value == $value)
                        array_push($this->_recordSet, $record);
                }
            }
        }*/

        

        public function setRange($field, $value, $endValue='')
        {
            if(isset($this->_fields[$field])) {
                if($endValue != '')
                    $this->setFilter($field, ">=%1&<=%2", $value, $endValue);
                else
                    $this->setFilter($field, "=%1", $value);
            }
        }

        public function Find(){
            if($this->_filter == '')
                Error('Aucun filtre n\'est définit. Vous ne pouvez pas utiliser la foncition Find(). Utilisez plustôt la fonction FindAll().');
             else {
                return $this->FindAll();
             }
         }

        public function Exists(){
            $this->loadTable();

            $stringKey = '';
            if(!empty($this->_keys)) {

                $this->testKeys();

                foreach ($this->_keys as $key) {
                    $stringKey = $stringKey . $key->_value;
                }
                return isset(Table::$_records[$this->_id][$stringKey]) ? true : false;
            }else
                return false;

        }
        /*
        public function FindSet(){
            if(!empty($this->_recordSet)) {
                return $this->_recordSet;
            }
            else return false;
        }
        */
        
        public function FindSet(){
            $this->FindAll();
            if(!empty($this->_recordSet)) {
                return $this->_recordSet;
            }
            else return false;
        }

        public function FindFirst(){  
            $this->FindAll();          
            if(!empty($this->_recordSet)) {
                $this->copyRecord_internal(reset($this->_recordSet));
                array_push($this->_recordSet, $this);
                return true;
            } else
                return false;
        }
        
        public function FindLast(){            
            $this->FindAll();
            if(!empty($this->_recordSet)){
                $this->copyRecord(end($this->_recordSet));
                array_push($this->_recordSet, $this);
                return true;
            } else
                return false ;
        }

        public function FindAll()
        {
            $this->loadTable();
            if(!empty(Table::$_records[$this->_id])) {
                $this->_recordSet = Table::$_records[$this->_id];
                return true;
            }
            else
                return false;
        }

        Public function Count(){
            if($this->FindAll())
                return count($this->_recordSet);
            else
                return 0;
        }
        
        public function Insert($trigger=true){
            $this->loadTable();
            $stringKey = '';
            $slug = md5(uniqid(rand(), true));
            if(!empty($this->_keys)){
                /*$this->testKeys();*/

                foreach($this->_keys as $key){
                    $stringKey = $stringKey.$key->_value;
                    $slug = $slug.$key->_value;
                }

                if($trigger == true){
                    $this->onInsert();
                }

                if(!isset(Table::$_records[$this->_id][$stringKey])){
                    if(IsNullOrEmptyString($this->created_at->value))
                        $this->Validate("created_at",date('Y-m-d H:m:s'));

                    //if(IsNullOrEmptyString($this->modified_at->value))
                        $this->Validate("modified_at",date('Y-m-d H:m:s'));

                    if(IsNullOrEmptyString($this->Id_slug->value))
                        $this->Validate("Id_slug",value: $slug);
                    
                    Table::$_records[$this->_id][$stringKey] = $this;
                   
                    
                    db->executeQuery($this->MySQL_InsertQuery());

                    if(db->getError()[1] != 0)
                       Error("Erreur Code: ". db->getError()[1]." message ". db->getError()[2]);
                    else {
                        
                        //$this->setRecord($stringKey, $this);
                        return array("status" => 201, "message" => "Created", "result" => json_decode($this));
                    }
                }else{
                    Error("message: Duplicata pour la clé primaire '".$stringKey."' dans la table '". $this->_name."'");
                }
            }else{
                Error("message: ".' Vous devriez définir au moins une clé');
            }
        }

        public function Keys(...$names){

            foreach ($names as $name){
                array_push($this->_keys, $this->_fields[$name]);
            }
        }
        
        public function Modify($trigger=true)
        {
            $stringKey = '';            
            $slug = md5(uniqid(rand(), true));
            if (!empty($this->_keys)) {
                /*$this->testKeys();*/
                foreach ($this->_keys as $key) {
                   $stringKey = $stringKey . $key->_value;
                }
                /*if (isset(Table::$_records[$this->_id][$stringKey])) {*/

                    if ($trigger == true) {
                        $this->onModify();
                    }

                    $this->Validate("modified_at", date('Y-m-d H:m:s'));
                    if(IsNullOrEmptyString($this->Id_slug->value))
                        $this->Validate("Id_slug",value: $slug);
                    

                    //Table::$_records[$this->_id][$stringKey] = $this;
                    db->executeQuery($this->MySQL_UpdateQuery());
                    //Error($this->MySQL_UpdateQuery());
                    if(db->getError()[1] != 0)
                        Error("Erreur SQL N°: ".db->getError()[1]."<br>message: ".db->getError()[2]);
                    
                    //$this->setRecord($stringKey, $this);
                    //$this->refresh();
                return array("status" => 200, "message" => "Modified", "result" => json_decode($this));
                /*} else {
                    return false;
                }*/
            } else {
                return false;
            }
        }

        public function Delete($trigger=true){
            $stringKey = '';
            if(!empty($this->_keys)) {
                /*$this->testKeys();*/
                foreach ($this->_keys as $key) {
                    $stringKey = $stringKey . $key->_value;
                }
                if($trigger == true){
                    $this->onDelete();
                }
                //if(isset(Table::$_records[$this->_id][$stringKey])) {
                    db->executeQuery($this->MySQL_DeleteQuery());                          
                    
                    if(db->getError()[1] != 0)
                        Error("Erreur SQL N°: ".db->getError()[1]."<br>message: ".db->getError()[2]);
                    else{
                        foreach($this->recordSet as $k => $r){
                            if($r->keys == $this->keys)
                                $this->unsetRecord($k);
                        }
                        return true;
                    }
                /*}else{
                    return false;
                }*/
            }else
                return false;
        }
        
        public function onInsert(){}
        
        public function onDelete(){}
        
        public function onModify(){}
        
        public function onRename(){}

        public function testField($field, $testvalue=null)
        {
            if(isset($this->_fields[$field])){
                $field = $this->_fields[$field];
                if ($testvalue == null) {
                    if ($field == null)
                        Error('Le champ est null');
                    if ($field->_value == null || $field->_value == '')
                        Error("Le Champ '" . $field->_name . "' doit avoir une valeur dans l'enregistrement '" . $this->_name . "' clé '" . $this->keys . "'");
                }else{
                    if ($field->_value != $testvalue)
                        Error("La valeur du Champ '" . $field->_name . "' doit être '" . $testvalue . "' mais la valeur actuelle est '".$field->_value."'. clé '" . $this->keys . "'");
                }
            }else{
                Error('Le champ '.$field.' n\'existe pas');
            }
        }

        public function copyRecord($record)
        {
            $this->reset();
            foreach ($record->_fields as $key => $value) {
                if(isset($this->_fields[$key])){
                    $this->{$key}->_value =  $value->value;
                }else{
                    Error("Le champ ".$key." n'existe pas dans la table ". $this->_name);
                }
                $this->Id_slug->_value = '';
                $this->Id_Incr->_value = '';
            }
            $this->_recordSet = $record->_recordSet;
        }
        public function copyRecord_internal($record)
        {
            $this->reset();
            foreach ($record->_fields as $key => $value) {
                if(isset($this->_fields[$key])){
                    $this->{$key}->_value =  $value->value;
                }else{
                    Error("Le champ ".$key." n'existe pas dans la table ". $this->_name);
                }
                
                $slug = md5(uniqid(rand(), true));
                //$this->Id_slug->_value = $slug  ;
                //$this->Id_Incr->_value = '';
            }
            $this->_recordSet = $record->_recordSet;
        }
        public function clearFields()
        {
            $n = new ($this::class);
            $this->_fields = $n->_fields;
        }
        public function getRecordByJson($record)
        {   
            if(is_array($record)){
                foreach ($record as $rec) {    
                    $n = new ($this::class);                
                    foreach ($rec as $key => $value) {
                        $n->{$key} = $value;
                        $this->{$key} =  $value;
                    }
                    array_push($this->_recordSet, $n);
                }
            }else{  
                     
                    $n = new ($this::class);                
                    foreach ($record as $key => $value) {
                        $n->{$key} =  $value;
                        $this->{$key} = $value;
                    }
                    array_push($this->_recordSet, $n);
                    
                }
        }

        public function InsertRec($n){
            array_unshift($this->_recordSet, $n);
        }

        private function testKeys(){
            foreach ($this->_keys as $key) {
                $this->testField($key);
            }
        }

        public function AllKeysGiven(){
            $allGiven = true;
            foreach ($this->_keys as $key) {
                if($key->_value == null || $key->_value == '')
                    return false;
            }
            return $allGiven;
        }

        public function MySQL_CreateQuery(){
            if(count($this->_keys)<=0)
                Error("Vous devez définir au moins une clé primaire pour la table '".$this->_name."'");
            $query = 'CREATE TABLE IF NOT EXISTS `'.$this->_name.'` ( ';
            foreach ($this->_fields as $field) {
                $query .= '`'.$field->_name.'` '.$field->_type.',';
            }
            $query .= 'PRIMARY KEY(`Id_slug`), UNIQUE(';
            foreach ($this->_keys as $key) {
                $query .= '`'.$key->_name.'`,';
            }
            $query = substr($query, 0,strlen($query)-1). ')
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8; ALTER TABLE `'.$this->_name.'` AUTO_INCREMENT=1;'."\n";

            return $query;
        }

        public function MySQL_UpdateSchema(){
            $query = 'ALTER TABLE `'.$this->_name.'` ';
            foreach ($this->_fields as $field) {
                if(!db->column_exist($this->_name, $field->_name))
                    $query .= ' ADD COLUMN `'.$field->_name.'` '.$field->_type.',';
            }
            $query = substr($query, 0,strlen($query)-1). ';'."\n";


            return $query;
        }

        public function MySQL_InsertQuery(){
            $query = 'INSERT INTO `'.$this->_name.'`( ';
            foreach ($this->_fields as $field) {
                $query .=  '`'.$field->_name.'`,';
            }
            $query = substr($query, 0,strlen($query)-1);
            $query .=') VALUES ( ';
            foreach ($this->_fields as $field) {
                if($this->checkTableRelation($field, $field->_value))
                    $query .= '"'.$field->_value.'",';
            }
            $query = substr($query, 0,strlen($query)-1). '
            )';
            
            return $query;
        }

        public function MySQL_SelectQuery($details=""){
            $query = 'SELECT * FROM `'.$this->_name.'` WHERE (`deleted_at` = "0000-00-00 00:00:00" OR `deleted_at` = null) '.$this->_filter.' ORDER BY '.$this->_current_keys.'`Id_Incr` ASC '.$details;

            return $query;
        }

        public function MySQL_DeleteQuery(){
            //$query = 'UPDATE `'.$this->_name.'` SET `deleted_at` = "'.date('Y-m-d H:m:s').'" WHERE ';
            $query = 'DELETE FROM `'.$this->_name.'` WHERE ';
            foreach ($this->_keys as $key) {
                $query .= $key->_name.'="'.$key->_value.'" AND ';
            }
            $query = substr($query, 0,strlen($query)-4). ';';

            return $query;
        }

        public function MySQL_UpdateQuery(){
            $query = 'UPDATE `'.$this->_name.'` SET ';
            foreach ($this->_fields as $field) {
                if($field->_name != 'Id_Incr' && $field->_name != 'Id_slug' && $field->_name != 'created_at') {
                    //if($field->_value != null) {
                        //if($field->_value != '') {
                        if($this->checkTableRelation($field, $field->_value))
                            $query .= $field->_name . '="' . $field->_value . '",';
                        //}
                    //}
                }
            }
            $query = substr($query, 0,strlen($query)-1). '
            WHERE ';


            foreach ($this->_keys as $key) {
                $query .= $key->_name.'="'.$key->_value.'" AND ';
            }
            $query = substr($query, 0,strlen($query)-4). ';';

            return $query;
        }
        
        //public function OnAfterGetRecord(Table &$record){}

        public function loadTable(){
            Table::$_records[$this->_id] = array();
            $this->_recordSet = array();

            $data = db;
            $datas = $data->getResultAssoc($this->MySQL_SelectQuery());
            if($data->getError()[1] != 0)
                Error("Erreur SQL N°: ".$data->getError()[1]."<br>message: ".$data->getError()[2]);
            else if(!empty($datas)){
                $record = Table::$_tableCollection[$this->_name];
                foreach ($datas as $key => $value) {
                    $record = $record->reset();
                    foreach ($value as $key2 => $value2) {
                        $record->{$key2} = $value2 ;                           
                    }
                    //$this->OnAfterGetRecord($record);
                    Table::$_records[$this->_id][$record->keys] = $record;
                }

            }else{
                Table::$_records[$this->_id] = array();
                $this->_recordSet = array();
            }
        }

        public function reset(){
            return new ($this::Class);
        }

        public function __toString(){
            if(Count($this->_recordSet) <= 0)
                array_push($this->_recordSet, $this);

            $r = '[';
            foreach ($this->_recordSet as $record) {
                $r .= '{';
                foreach ($record->_fields as $field) {
                    $r .= '"' . $field->_name . '":"' . $field->_value . '",';
                }
                $r = substr($r, 0, strlen($r) - 1);
                $r .= '},';
            }
            $r = substr($r, 0, strlen($r) - 1);
            $r .= ']';

            return $r;
        }

        private function checkTableRelation($field, $value){
            if($field->tableRelation != null){
                if($value !== null && $value !== ''){
                    $relationTable = $field->tableRelation;
                    $relationTable->setRange($relationTable->keysList[0]->_name, $value);
                    if($relationTable->FindFirst()){
                        return true;
                    }else{
                        Error("Contrainte de clé étrangère pour le champs '".$field->_name."'. La valeur '".$value."' n'existe pas, dans la table associée '".$relationTable->table_name."'. Record: ".$this->keys);
                    }
                }else{
                    return true;
                }            
            }else{
                return true;
            }
        }

        public function preInsert($rec=null, $trigger=false){
            $stringKey = '';
            $slug = md5(uniqid(rand(), true));

            if($rec == null){
                foreach($this->_keys as $key){
                    $stringKey = $stringKey.$key->_value;
                    $slug = $slug.$key->_value;
                }

                if($trigger == true){
                    $this->onInsert();
                }

                if(!isset(Table::$_records[$this->_id][$stringKey])){
                    if(IsNullOrEmptyString($this->created_at->value))
                        $this->Validate("created_at",date('Y-m-d H:m:s'));

                    //if(IsNullOrEmptyString($this->modified_at->value))
                        $this->Validate("modified_at",date('Y-m-d H:m:s'));

                    if(IsNullOrEmptyString($this->Id_slug->value))
                        $this->Validate("Id_slug",$slug);

                    if(IsNullOrEmptyString($this->Id_Incr->value))
                        $this->Validate("Id_Incr","");

                    //Table::$_records[$this->_id][$stringKey] = $this;
                    $nw = $this;
                    array_push($this->_recordSet, $nw);
                    
                }else{
                    Error("message: Duplicata pour la clé primaire '".$stringKey."' dans la table '". $this->_name."'");
                }
            }else{
                foreach($rec->_keys as $key){
                $stringKey = $stringKey.$key->_value;
                $slug = $slug.$key->_value;
                }

                if($trigger == true){
                    $rec->onInsert();
                }

                if(!isset(Table::$_records[$rec->_id][$stringKey])){
                    if(IsNullOrEmptyString($rec->created_at->value))
                        $rec->Validate("created_at",date('Y-m-d H:m:s'));

                    //if(IsNullOrEmptyString($rec->modified_at->value))
                        $rec->Validate("modified_at",date('Y-m-d H:m:s'));

                    if(IsNullOrEmptyString($rec->Id_slug->value))
                        $rec->Validate("Id_slug",$slug);

                    if(IsNullOrEmptyString($rec->Id_Incr->value))
                        $rec->Validate("Id_Incr","");

                    //Table::$_records[$rec->_id][$stringKey] = $rec;                    
                    array_push($this->_recordSet, $rec);

                }else{
                    Error("message: Duplicata pour la clé primaire '".$stringKey."' dans la table '". $rec->_name."'");
                }                
            }
        }

        public function refresh(){           
            foreach($this->_keys as $key){
                $this->setFilter($key->_name,'=%1', $key->value);                
            }
            $this->FindFirst();
        }
        

        public function Init(){
            $new = new $this;
            $this->copyRecord($new);
            $this->_recordSet = $new->_recordSet;
            return $new;
        }

        /**
         * Déclare un FlowField (champ calculé, non persisté en base).
         *
         * @param int         $id       Identifiant unique du champ dans la table
         * @param string      $name     Nom du champ (ex: 'Nb_Autorisations')
         * @param string      $type     Type SQL indicatif (FieldType::integer(), etc.)
         * @param CalcFormula $formula  Formule de calcul (Count, Sum, Exist, Lookup…)
         * @param string|null $caption  Libellé affiché
         */
        public function flowField(int $id, string $name, string $type, CalcFormula $formula, ?string $caption = null): FlowField {
            $ff = new FlowField($id, $name, $type, $formula, $caption ?? $name);
            $this->_flowFields[$name] = $ff;
            return $ff;
        }

        /**
         * Calcule la valeur des FlowFields demandés sur l'enregistrement courant.
         * Doit être appelé explicitement, typiquement dans OnAfterGetRecord().
         *
         * Exemple :
         *   $profile->CalcFields('Nb_Autorisations', 'Solde');
         *   echo $profile->Nb_Autorisations->value; // → 5
         */
        public function CalcFields(string ...$fieldNames): void {
            foreach ($fieldNames as $name) {
                if (!isset($this->_flowFields[$name])) continue;

                $ff      = $this->_flowFields[$name];
                $formula = $ff->calcFormula;

                $rel = new $formula->tableClass();

                foreach ($formula->filters as $relField => $currentField) {
                    $rel->setRange($relField, $this->_fields[$currentField]->_value);
                }

                $ff->_value = match ($formula->type) {
                    'Count'   => $rel->aggregateSQL('COUNT'),
                    'Sum'     => $rel->aggregateSQL('SUM',   $formula->field),
                    'Min'     => $rel->aggregateSQL('MIN',   $formula->field),
                    'Max'     => $rel->aggregateSQL('MAX',   $formula->field),
                    'Average' => $rel->aggregateSQL('AVG',   $formula->field),
                    'Exist'   => $rel->FindFirst() ? '1' : '0',
                    'Lookup'  => $rel->FindFirst() ? $rel->{$formula->field}->_value : null,
                    default   => null,
                };
            }
        }

        /**
         * Exécute une requête SQL d'agrégation avec les filtres courants.
         * Utilisé en interne par CalcFields.
         *
         * @param string      $fn    Fonction SQL : COUNT, SUM, MIN, MAX, AVG
         * @param string|null $field Champ cible (null → COUNT(*))
         */
        public function aggregateSQL(string $fn, ?string $field = null): mixed {
            $col   = ($field === null) ? '*' : '`'.$field.'`';
            $query = 'SELECT '.$fn.'('.$col.') AS result'
                   . ' FROM `'.$this->_name.'`'
                   . ' WHERE (`deleted_at` = "0000-00-00 00:00:00" OR `deleted_at` IS NULL)'
                   . $this->_filter;
            $rows  = db->getResultAssoc($query);
            if (db->getError()[1] != 0)
                Error('Erreur SQL N°: '.db->getError()[1].'<br>message: '.db->getError()[2]);
            return $rows[0]['result'] ?? null;
        }
    }
    
    
    
?>