<?php

class group {
    private $_name;
    private $_caption;
    private $_SourceTableId = 0;
    private $_SourceTableName = '';
    private $_SourcePageId = 0;
    private $_SourcePageName = '';
    private $fieldsHTML = '';
    private $_html = '';
    private $_fields = array();

    private $_type = '';

    public static $_groupsCollection = array();


    public function __construct($name, $caption='') {
        $this->_name = $name;
        if($caption!='')
            $this->_caption = $caption;
        else
            $this->_caption = $name;

        $this->_fields = array();

        $this->_html = '
        <div class="row">
            <legend>'.$this->_caption.'</legend>
            <hr>
             
        </div>';
    }

    public function __get($name) {
        switch($name) {
            case 'SourceTableId':
                return $this->_SourceTableId;
                    break;
            case 'SourceTableName':
                return $this->_SourceTableName;
                    break;
            case 'fields':
                return $this->_fields;
                    break;
            case 'name':
            case '_name':
                return $this->_name;
                    break;
            case 'caption':
                return $this->_caption;
                    break;
            case 'type':
                return $this->_type;
                    break;

        }
    }
    public function __set($name,$value) {
        switch($name) {
            case 'SourceTableId':
                $this->_SourceTableId = $value;
                    break;
            case 'SourceTableName':
                $this->_SourceTableName = $value;
                    break;
            case 'SourcePageId':
                $this->_SourcePageId = $value;
                break;
            case 'SourcePageName':
                $this->_SourcePageName = $value;
                break;
            case 'type':
                $this->type = $value;
                break;
        }
    }

    public function fields($name, $fields) {
        if(isset($this->_fields[$name]))
            Error('Champ '.$name.' Existe déjà');
        $fields->parentGroup = $this->_name;
        $properties = '';
        if(!$fields->editable)
            $properties .= 'readonly=true';

        if(!$fields->enabled)
            $properties .= ' disabled=true';


        if($fields->source->tableRelation != null) {
            $RelatedRecord = $fields->source->tableRelation;
            $RelatedRecord->FindAll();
            $this->fieldsHTML .= '
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label class="col-sm-4" style="font-size: 12px;">' . $fields->caption . '</label>
                                        <div class="col-sm-8">
                                            <select ' . $properties . ' id="' . $fields->_name . '" name="' . $fields->source->_name . '" class="form-control select2"  style="border-radius: 0px;" />
                                                ';

                                                    foreach ($RelatedRecord->recordSet as $record) {
                                                        if($record->keys == $fields->value)
                                                            $this->fieldsHTML .= '<option selected value="'.$record->keys.'">'.$record->keys.'</option>';
                                                        else
                                                            $this->fieldsHTML .= '<option value="'.$record->keys.'">'.$record->keys.'</option>';
                                                    }
                            $this->fieldsHTML .= '            
                                            </select>
                                            <script>
                                                $(document).ready(function() {
                                                    $(".select2").select2({
                                                      theme: "classic"
                                                    }); 
                                                });
                                            </script>
                                        </div>
                                    </div>
                                </div>';
        }else if($fields->source->_type == FieldType::boolean()){
            $this->fieldsHTML .= '
                <div class="col-md-6">
                    <div class="form-group row" style="display:flex;">
                        <label class="col-sm-4">'.$fields->caption.'</label>
                        ';

                        if($fields->source->value == 1)
                            $this->fieldsHTML .= '<div class="col-sm-8" style="margin-left: auto">
                                    <select class="form-control select2" '.$properties.' id="'.$fields->_name.'" name="'.$fields->source->_name.'" style="border-radius: 0px; border: none; background-color: transparent" />
                                        <option selected value="1">Oui</option>
                                        <option value="0">Non</option>
                                    </select>
                            </div>';
                        else if($fields->source->value == 0)
                            $this->fieldsHTML .= '<div class="col-sm-8" style="margin-left: auto">
                                    <select class="" '.$properties.' id="'.$fields->_name.'" name="'.$fields->source->_name.'" style="border-radius: 0px; border: none; background-color: transparent" />
                                        <option selected value="0">Non</option>
                                        <option value="1">Oui</option>
                                    </select>
                            </div>';
                        else
                            $this->fieldsHTML .= '<div class="col-sm-8" style="margin-left: auto">
                                    <select class="" '.$properties.' id="'.$fields->_name.'" name="'.$fields->source->_name.'" style="border-radius: 0px; border: none; background-color: transparent" />
                                        <option selected value="0">Non</option>
                                        <option value="1">Oui</option>
                                    </select>
                            </div>';
$this->fieldsHTML .='</div>
                </div>
                ';

        }else if($fields->source->_type == FieldType::decimal()) {

        $this->fieldsHTML .= '
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label class="col-sm-4">'.$fields->caption.'</label>
                                        <div class="col-sm-8"><input '.$properties.' id="'.$fields->_name.'" name="'.$fields->source->_name.'" type="number" step="0.01" class="form-control text-dark"  style="border-radius: 0px;" value="'.$fields->value.'" /></div>
                                    </div>
                                </div>
                                ';
        }else if($fields->source->_type == FieldType::integer()) {

        $this->fieldsHTML .= '
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label class="col-sm-4">'.$fields->caption.'</label>
                                        <div class="col-sm-8"><input '.$properties.' id="'.$fields->_name.'" name="'.$fields->source->_name.'" type="number" step="1" class="form-control text-dark"  style="border-radius: 0px;" value="'.$fields->value.'" /></div>
                                    </div>
                                </div>
                                ';
        }else if($fields->source->_type == FieldType::date()) {

        $this->fieldsHTML .= '
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label class="col-sm-4">'.$fields->caption.'</label>
                                        <div class="col-sm-8"><input '.$properties.' id="'.$fields->_name.'" name="'.$fields->source->_name.'" type="date" class="form-control text-dark"  style="border-radius: 0px;" value="'.$fields->value.'" /></div>
                                    </div>
                                </div>
                                ';
        }else if($fields->source->_type == FieldType::time()) {

        $this->fieldsHTML .= '
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label class="col-sm-4">'.$fields->caption.'</label>
                                        <div class="col-sm-8"><input '.$properties.' id="'.$fields->_name.'" name="'.$fields->source->_name.'" type="time" class="form-control text-dark"  style="border-radius: 0px;" value="'.$fields->value.'" /></div>
                                    </div>
                                </div>
                                ';
        }else {

        $this->fieldsHTML .= '
                                <div class="col-md-6">
                                    <div class="form-group row">
                                        <label class="col-sm-4">'.$fields->caption.'</label>
                                        <div class="col-sm-8"><input '.$properties.' id="'.$fields->_name.'" name="'.$fields->source->_name.'" type="text" class="form-control text-dark"  style="border-radius: 0px;" value="'.$fields->value.'" /></div>
                                    </div>
                                </div>
                                ';
        }

        $this->fieldsHTML .= ' <script>
                                    $("#'.$fields->source->_name.'").on("keyup",function(key){
                                        if(key.keyCode==13){                                                                                      
                                           submitForm("'.$this->_SourcePageId.'","'.$fields->source->_name.'","'. $fields->_name.'","'.$this->SourceTableId.'","'.$this->SourceTableName.'","'.$this->_name.'");                                                                                       
                                        }
                                    });
                                    $("#'.$fields->source->_name.'").on("change",function(){
                                           //var focusedElementId = $(document.activeElement);                                           
                                           submitForm("'.$this->_SourcePageId.'","'.$fields->source->_name.'","'. $fields->_name.'","'.$this->SourceTableId.'","'.$this->SourceTableName.'","'.$this->_name.'");                                                                                                                              
                                           //focusedElementId.focus();
                                    });
                                </script>';

        $this->_fields[$name] = $fields;
    }

    public function HTML() {
        $dhtml = '
            <fieldset class="row col-lg-12">
                <legend style="font-size: 20px;"><b>'.$this->_caption.'</b></legend>
                <hr>
                 '.$this->fieldsHTML.'
            </fieldset>';

        $this->_html = $dhtml;
        return $this->_html;
    }

}