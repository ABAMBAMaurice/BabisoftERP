<?php

class ProfileCard extends Page {
    public function __construct()
    {
        parent::__construct(9999992, 'ProfileCard', PagesType::Card, 'Fiche Profil');
        $this->sourceTable = new Profile();
        $this->setAction();
        $this->layout();
    }

    function setAction() {
       $this->actions(
            name: 'saveAuthorization',
            icon: 'plus',
            caption: 'Ajouter toutes Autorisations de pages',
            onAction: function() {
                $pages = new Views();
                if($pages->FindAll()){
                    foreach($pages->recordSet as $page){
                        $auth = new Authorization();
                        $auth->Validate('Profile', $this->rec->Code->value);
                        $auth->Validate('Page_Id', $page->Id->value);
                        $auth->Validate('Page_Name', $page->caption->value);
                        $auth->Validate('Inserting', false);
                        $auth->Validate('Deleting', false);
                        $auth->Validate('Modifying', false);
                        $auth->Validate('Viewing', false);
                        $auth->Insert();
                    }
                }
            }
        );
    }

    function layout()
    {
        $this->group('General', 'Général',
            new PageField(name: 'Code', source: $this->rec->Code, caption: 'N°'),
            new PageField(name: 'Name', source: $this->rec->Name, editable: true, enabled: true, caption: 'Nom'),
            new PageField(name: 'Caption', source: $this->rec->Caption, editable: true, enabled: true, caption: 'Nom affichage'),
            new PageField(name: 'Dashboard', source: $this->rec->Dashboard, editable: true, enabled: true, caption: 'Tableau de bord')
        );  
                   
        $this->part('9999991', 
        array(
            "Code" => "Profile"
        ))  ;

    }
}

?>