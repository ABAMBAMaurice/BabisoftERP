<?php

class AuthorizationCard extends Page {
    public function __construct()
    {
        parent::__construct(9999990, 'Fiche Autorisation', PagesType::Card, 'Autorisation');
        $this->sourceTable = new Authorization();
        $this->setAction();
        $this->layout();
        $this->cardPageID = 9999991; // ID de la page fiche autorisation, à adapter si besoin
    }

    function setAction() {
        // Actions spécifiques à la page (si besoin)
    }

    function layout()
    {
        $this->group('General', 'Général',
            new PageField(name: 'Profile', source: $this->rec->Profile, caption: 'Profile'),
            new PageField(name: 'Page_Id', source: $this->rec->Page_Id, caption: 'Id page'),
            new PageField(name: 'Page_Name', source: $this->rec->Page_Name, caption: 'Nom de la page'),
            new PageField(name: 'Inserting', source: $this->rec->Inserting, caption: 'Insertion'),
            new PageField(name: 'Deleting', source: $this->rec->Deleting, caption: 'Suppression'),
            new PageField(name: 'Modifying', source: $this->rec->Modifying, caption: 'Modification'),
            new PageField(name: 'Viewing', source: $this->rec->Viewing, caption: 'Consultation')
        );
    }

    
    function OnNewRecord(& $rec){
        $rec->Validate('Profile', $this->rec->Profile->value);
    }

    function onOpenPage() {
        // Code à exécuter à l'ouverture de la page (si besoin)
    }
}

?>