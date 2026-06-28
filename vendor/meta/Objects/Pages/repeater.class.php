<?php


class repeater
{
    private $_name;
    private $_caption;
    private $_SourceTableId = 0;
    private $_SourceTableName = '';
    private $_SourcePageId = 0;
    private $_SourcePageName = '';
    private $fieldsHTML = '';
    private $_dataPushHTML = '';
    private $_html = '';
    private $_fields = [];
    public static $_groupsCollection = array();

    public $_type = '';



    public function __construct($name, $caption = '')
    {
        $this->_name = $name;
        if ($caption != '')
            $this->_caption = $caption;
        else
            $this->_caption = $name;


        $this->_html = '
        <div class="row">
            <legend>' . $this->_caption . '</legend>
            <hr>
             
        </div>';
    }

    public function __get($name)
    {
        switch ($name) {
            case 'name':
                return $this->_name;
                break;
            case 'SourceTableId':
                return $this->_SourceTableId;
                break;
            case 'SourceTableName':
                return $this->_SourceTableName;
                break;
            case 'fields':
            case '_fields':
                return $this->_fields;
                break;
            case 'SourcePageId':
                return $this->_SourcePageId;
                break;
            case 'SourcePageName':
                return $this->_SourcePageName;
                break;
            case 'type':
                return $this->_type;
                break;

        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
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
                $this->_type = $value;
                break;

        }
    }

    public function fields($name, $fields)
    {
        if(isset($this->_fields[$name]))
            Error('Champ '.$name.' Existe déjà');
        $fields->parentGroup = $this->_name;
        $properties = '';
        if (!$fields->editable)
            $properties .= 'readonly=true';

        if (!$fields->enabled)
            $properties .= ' disabled=true';


        $this->fieldsHTML .= '
                                <th class="tr-table">
                                 ' . $fields->caption . '
                                </th>';
        if($fields->source != null)
            $this->_dataPushHTML .= 'cols.push({data:"'.$fields->source->_name.'"}); ';
        else
            Error("Le champs '".$name."' n'existe pas dans la table '".$this->SourceTableName."'");

        $this->_fields[$name] = $fields;
    }

    public function HTML()
    {
        $dhtml = '
            <fieldset class="row col-lg-12">
                <legend style="font-size: 15px;"><b>' . $this->_caption . '</b></legend>
                <hr>
                <table class="table table-hover" id="'.$this->_SourcePageName.'DataRepeter'.$this->_SourceTableName.'">
                    <thead>
                        '.$this->fieldsHTML.'
                    </thead>
                    <tbody id="'.$this->_SourcePageName.'DataRepeterBody'.$this->_SourceTableName.'" style="height:1
                    250px;">
                        ';
                            $currPage = Page::$_pageCollection[$this->_SourcePageId];
                            if(count($currPage->rec->recordSet) <= 0) {
                                $currPage->rec->FindAll();
                            }
                            foreach ($currPage->rec->recordSet as $pk => $rec) {

                                $dhtml .= '<tr style="height:40px;" id="'.$rec->keys.'"  class="DataRepeterRow">';
                                foreach ($this->_fields as $field) {

                                    $properties = '';

                                    if(!$field->editable)
                                        $properties = 'readonly=true';

                                    if(!$field->enabled)
                                        $properties = ' readonly=true';

                                    $currPage->Validate($field->source->_name, $rec->{$field->source->_name});
                                    if($field->source->_type == FieldType::boolean()) {
                                        $val  = $field->value == '1' ? "oui" : "Non";
                                        $dhtml .= '<td data-name="' . $field->source->_name . '" id="' . $rec->keys . $field->source->_name . '">' . $val . '</td>';
                                    }
                                    else
                                        $dhtml .= '<td data-name="'.$field->source->_name.'" id="'.$rec->keys.$field->source->_name.'">'. $field->value . '</td>';

                                    $dhtml .= '<script>';

                                            if($currPage->cardPageID == '') {
                                                $dhtml .= 'clicked = false;
                                                   $("#' . $rec->keys . $field->source->_name . '").dblclick(function(){
                                                        if(!clicked){
                                                            clicked = true;
                                                            let cell = $(this);
                                                            let originalContent = cell.text();
                                                            
                                                            // Remplace le texte par un champ d édition
                                                            ';
                                                        if($field->source->tableRelation != null) {
                                                            $RelatedRecord = $field->source->tableRelation;
                                                            $RelatedRecord->FindAll();
                                                            $dhtml .= 'cell.html(`<select id="inputSelectd" ' . $properties . ' class="form-control select2" style="border-radius: 0px; border: none;"><option value="`+originalContent+`" selected></option>';

                                                            foreach ($RelatedRecord->recordSet as $record) {
                                                                    if($record->keys == $field->value)
                                                                        $dhtml .= '<option selected value="'.$record->keys.'">'.$record->keys.'</option>';
                                                                    else
                                                                        $dhtml .= '<option value="'.$record->keys.'">'.$record->keys.'</option>';
                                                                }
                                                            $dhtml .='<select>`);  ';
                                                        }else if($field->source->_type == FieldType::boolean()) {
                                                            $dhtml .= 'cell.html(`<select id="inputSelectd" ' . $properties . ' class="form-control select2" style="border-radius: 0px; border: none;">';
                                                                if($field->source->value == '0') {
                                                                    $dhtml .= '<option selected value="0">Non</option>';
                                                                    $dhtml .= '<option value="1">Oui</option>';
                                                                }else{
                                                                    $dhtml .= '<option value="0">Non</option>';
                                                                    $dhtml .= '<option selected value="1">Oui</option>';
                                                                }
                                                            $dhtml .='<select>`);  ';
                                                        }else if($field->source->_type == FieldType::integer()) {
                                                            $dhtml .= 'cell.html(`<input type="number" step="1" id="inputSelectd" ' . $properties . ' value="`+originalContent+`" class="form-control" style="border-radius: 0px; border: none;"/>`)';
                                                        }else if($field->source->_type == FieldType::decimal()) {
                                                            $dhtml .= 'cell.html(`<input type="number" step="0.01" id="inputSelectd" ' . $properties . ' value="`+originalContent+`" class="form-control" style="border-radius: 0px; border: none;"/>`)';
                                                        }else if($field->source->_type == FieldType::date()) {
                                                            $dhtml .= 'cell.html(`<input type="date" id="inputSelectd" ' . $properties . ' value="`+originalContent+`" class="form-control" style="border-radius: 0px; border: none;"/>`)';
                                                        }else {
                                                            $dhtml .= 'cell.html(`<input type="text" id="inputSelectd" ' . $properties . ' value="`+originalContent+`" class="form-control" style="border-radius: 0px; border: none;"/>`);
                                                            ';
                                                        }
                                                         $dhtml .='  // Quand lutilisateur quitte le champ dédition
                                                            cell.find("input").focus().blur(function() {                                  
                                                                validate(cell, $("#inputSelectd"),originalContent)
                                                            });
                                                            cell.find("select").focus().blur(function() {                                  
                                                                validate(cell, $("#inputSelectd"),originalContent)
                                                            });
                                                              
                                                            // Quand lutilisateur quitte le champ dédition
                                                            cell.find("input").keyup(function(e) {
                                                                if(e.keyCode == 13){
                                                                    validate(cell, $("#inputSelectd"), originalContent)
                                                                }   
                                                            });   
                                                            // Quand lutilisateur quitte le champ dédition
                                                            cell.find("select").keyup(function(e) {
                                                                if(e.keyCode == 13){
                                                                    validate(cell, $("#inputSelectd"), originalContent)
                                                                }   
                                                            });                                                          
                                                        }                                                           
                                                   }); 
                                                   
                                                   function validate(cell, elem, oldValue) {
                                                       
                                                        let newContent = elem.val();
                                                        //alert(newContent);
                                                        cell.html(newContent); // Remet le nouveau contenu dans la cellule
                                                        clicked = false;
                                                        
                                                        if(oldValue != newContent)
                                                            submitFormRepeater("' . $this->_SourcePageId . '","' . $field->source->_name . '","' . $field->_name . '","' . $this->SourceTableId . '","' . $this->SourceTableName . '","' . $this->_name . '",cell);
                                                              
                                                   }
                                                   function submitFormRepeater(formId, pageField, pageFieldname, tableId, tableName, groupeName, elem){
                                                        let row = elem.closest("tr");
                                                        
                                                        // Crée un objet pour stocker les données
                                                        let rowData = [];
                                                        //let line = 0;
                                                          // Parcourt chaque cellule et récupère le nom et la valeur
                                                         
                                                          row.find("td[data-name]").each(function() {                                                                   
                                                            let nom = $(this).data("name");
                                                            let value = $(this).text();
                                                            
                                                            rowData.push({"name":nom,"value":value});
                                                          });
                                                        document.body.style.cursor = "wait";
                                                        $.ajax({
                                                            type: "POST",
                                                            url: "Base_app/app.main.php",  // Remplacez par votre URL serveur
                                                            data: {
                                                                record:rowData,
                                                                PageField:pageField,
                                                                pageFieldname:pageFieldname,
                                                                tableId:tableId,
                                                                tableName:tableName,
                                                                page:formId,
                                                                groupeName: groupeName
                                                            },
                                                            success: function (data, textStatus, xhr) {
                                                                    if(data.length>0) {
                                                                        const updatable = xhr.getResponseHeader("updatable");
                                                                        updatePage(data,updatable);
                                                                    }
                                                                    document.body.style.cursor = "default";
                                                            },
                                                            error: function (xhr, status, error) {
                                                                swal.fire({
                                                                    html: error
                                                                });
                                                            }
                                                        });
                                                   }
                                        
                                            /*$("#' . $rec->keys . $field->_name . '").on("keyup",function(key){
                                                if(key.keyCode==13){                                        
                                                   submitForm("' . $this->_SourcePageId . '","' . $field->source->_name . '","' . $field->_name . '","' . $this->SourceTableId . '","' . $this->SourceTableName . '","' . $this->_name . '");                                                                                      
                                                }
                                            });
                                            $("#' . $field->source->_name . '").on("change",function(){
                                                   submitForm("' . $this->_SourcePageId . '","' . $field->source->_name . '","' . $field->_name . '","' . $this->SourceTableId . '","' . $this->SourceTableName . '","' . $this->_name . '");                                                                                                                              
                                            });*/
                                            ';
                                            }else{
                                                $dhtml .= '
                                                    $("#'.$rec->keys.$field->source->_name.'").click(function(){
                                                    document.body.style.cursor = "wait";
                                                        $.ajax({
                                                            type: "POST",
                                                            url: "Base_app/app.main.php",
                                                            data: {
                                                                RepeaterSelectedRecordID:"'.$rec->keys.'",
                                                                cardPage:"'.$currPage->cardPageID.'",
                                                                page:"'.$currPage->cardPageID.'"                                                                        
                                                            },
                                                            success: function (data, textStatus, xhr) {
                                                                    if(data.length>0) {
                                                                        const updatable = xhr.getResponseHeader("updatable");
                                                                        updatePage(data,updatable);
                                                                    }
                                                                    document.body.style.cursor = "default";
                                                            },
                                                            error: function (xhr, status, error) {
                                                                swal.fire({
                                                                    html: error
                                                                });
                                                                document.body.style.cursor = "default";
                                                            }
                                                        });
                                                    })
                                                ';
                                            }
                                            $dhtml .= '
                                        </script>
                                    ';
                                }
                                $dhtml .= '</tr>';
                            }
        $dhtml .= '
                    </tbody>
                </table>                 
            </fieldset>
            <script> 
                    $(".select2").select2({theme: "classic"});                    
                    repeaterData();                    
                    function repeaterData(){
                        
                       $("#'.$this->_SourcePageName.'DataRepeter'.$this->_SourceTableName.'").DataTable({
                            //"lengthMenu": [[10, 25, 50, 100,-1], [10, 25, 50, 100, "Tout"]], 
                            "lengthChange": false,
                            "paging": false, 
                            "select": { 
                                "style": "single"
                            },
                            "scrollY": "36vh", // La hauteur de la table (ajustez selon vos besoins)
                            "scroller": true, // Active le scroller  
                            "scrollCollapse": true,     // Permet le collapse du scroll si nécessaire
                            "deferRender": true, 
                            "info": false,
                            "buttons": [                                
                                {"extend":"copy", "text":" Copier"}, 
                                {"extend": "csv",  "text": " CSV"}, 
                                {"extend": "excel",  "text":" Excel"}, 
                                {"extend": "pdf",   "text":" PDF"}, 
                                {"extend":"print", "text":" Imprimer"}
                            ],
                            "language": {
                                "lengthMenu": "Lignes par page:&nbsp;&nbsp;&nbsp; _MENU_",
                                "zeroRecords": "Aucune donnée dans cette vue",
                                "info": "page _PAGE_ sur _PAGES_",
                                "infoEmpty": "Aucune donnée dans cette vue",
                                "infoFiltered": "(Filtré sur _MAX_ d\'enregistrements)",
                                "search": "Recherche rapide:",
                               /*"paginate": {
                                    "first": "Premier",
                                    "last": "Dernier",
                                    "next": "Suivant",
                                    "previous": "Précédent"
                                },*/                                
                            },
                        });
                    }
            </script>';

        $this->_html = $dhtml;
        return $this->_html;
    }

}