<?php
    class LicencesList extends Page {
            public function __construct() {
                parent::__construct(999989,  'Licences',PagesType::List,'Liste des licences');
                $this->sourceTable = new licence();
                $this->setAction();
                $this->layout();
                $this->editable = false;
                $this->cardPageID = 999988; // ID of the card page for licences
            }
    
            function setAction(){
    
            }
    
            function layout(){
                $this->repeater('List', 'Liste',
                    new PageField('Code', $this->rec->Code, caption:'Code'),
                    new PageField('start_date', $this->rec->start_date, caption:'Date debut'),
                    new PageField('end_date', $this->rec->end_date, caption:'Date fin'),
                    new PageField('active', $this->rec->active, caption:'Actif'),
                    new PageField('users_count', $this->rec->users_count, caption:'Nombre d utilisateurs'),
                    new PageField('licence_key', $this->rec->licence_key, caption:'Clé de licence')
                );
            }
        }

?>