<?php

class Profiles extends Page {
    public function __construct()
    {
        parent::__construct(9999993, 'Profiles', PagesType::List, 'Profils');
        $this->sourceTable = new Profile(); // Assurez-vous que la classe Profile existe
        $this->setAction();
        $this->layout();
        $this->editable = false;
        $this->cardPageID = 9999992; // ID de la page fiche profil, à adapter si besoin
    }

    function setAction()
    {
        
    }

    function layout()
    {
        $this->repeater('ProfilesList', 'Profils',
            new PageField(name: 'Code', source: $this->rec->Code, editable: true, enabled: true, caption: 'Code'),
            new PageField(name: 'Name', source: $this->rec->Name, editable: true, enabled: true, caption: 'Nom'),
            new PageField(name: 'Caption', source: $this->rec->Caption, editable: true, enabled: true, caption: 'Affichage'),
            new PageField(name: 'Dashboard', source: $this->rec->Dashboard, editable: true, enabled: true, caption: 'Tableau de bord'),
        );
    }
}

?>