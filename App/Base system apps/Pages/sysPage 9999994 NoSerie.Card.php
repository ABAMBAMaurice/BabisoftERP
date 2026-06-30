<?php
    
    class NoSerieCard extends Page {
        public function __construct()
        {
            parent::__construct(9999994, 'NoSeriesCard',PagesType::Card,'Souche de N°'
            );
            $this->sourceTable = new NoSerie();
            $this->setAction();
            $this->layout();
        }

        function setAction(){

        }
        function layout()
        {
            $this->group('General', 'Général',
            new PageField(name:'Code', source:$this->rec->Code, caption: 'Code'),
            new PageField(name:'Prefix', source:$this->rec->Prefix, caption: 'Préfix'),
            new PageField(name: 'No_debut', source: $this->rec->No_debut, editable: true, enabled: true, caption: 'N° début'),
            new PageField(name: 'Suffix', source: $this->rec->Suffix, editable: true, enabled: true, caption: 'Suffix'),
            new PageField(name: 'No_fin', source: $this->rec->No_fin, editable: true, enabled: true, caption: 'N° fin'),
            new PageField(name: 'Last_Used_No', source: $this->rec->Last_Used_No, editable: true, enabled: true, caption: 'Dernier N° utilisé'),
            new PageField(name: 'Start_date', source: $this->rec->Start_date, editable: true, enabled: true, caption: 'Date début'),
            new PageField(name: 'End_date', source: $this->rec->End_date, editable: true, enabled: true, caption: 'Date fin'),
            new PageField(name: 'IsAuto', source: $this->rec->IsAuto, editable: true, enabled: true, caption: 'Automatique')
            );
        }

        function onOpenPage(){
        }
    }


?>