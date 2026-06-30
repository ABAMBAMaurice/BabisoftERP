<?php
    class LicencesCard extends Page {
            public function __construct() {
                parent::__construct(999988,  'Licences',PagesType::Card,'Détails de la licence');
                $this->sourceTable = new licence();
                $this->setAction();
                $this->layout();
            }
    
            function setAction(){
                $this->actions(
                    name: 'GenerateKey', 
                    icon: 'creation', 
                    caption: 'Générer une clé de licence', 
                    visible: true,
                    onAction: function() {
                       $this->rec->Validate('licence_key', LicenceKeyGen::generateKey());
                       $this->rec->Modify();
                    }
                );
            }
    
            function layout(){
                $this->group('General', 'Général',
                    new PageField('Code', $this->rec->Code, caption:'Code', editable:true, enabled:true),
                    new PageField('start_date', $this->rec->start_date, caption:'Date debut', editable:true, enabled:true),
                    new PageField('end_date', $this->rec->end_date, caption:'Date fin', editable:true, enabled:true),
                    new PageField('active', $this->rec->active, caption:'Actif', editable:true, enabled:true),
                    new PageField('users_count', $this->rec->users_count, caption:'Nombre d utilisateurs', editable:true, enabled:true),
                    new PageField('licence_key', $this->rec->licence_key, caption:'Clé de licence', editable:false, enabled:false)
                );
            }
        }

?>