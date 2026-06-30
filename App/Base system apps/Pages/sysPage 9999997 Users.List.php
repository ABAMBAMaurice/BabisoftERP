<?php
    
    class UserList extends Page {
        public function __construct()
        {
            parent::__construct(9999997, 'UsersList',PagesType::List,'Utilisateurs');
            $this->sourceTable = new User();
            $this->setAction();
            $this->layout();
            $this->editable = false;     
            $this->cardPageID = 9999996; 
            $this->AllowDelete = false;   
        }

        function setAction(){

        }
        function layout()
        {
            $this->repeater('utilisateur', 'Utilisateurs',
                new PageField(name:'Email', source:$this->rec->Email, editable: true, enabled: true, caption:'Code'),
                new PageField(name: 'Full_name', source: $this->rec->Full_name, editable: true, enabled: true, caption:'Nom complet'),
                new PageField(name: 'Password', source: $this->rec->Password, editable: true, enabled: true, visible:false, caption:'Mot de passe'),
                new PageField(name: 'Profile', source: $this->rec->Profile, editable: true, enabled: true, caption:'Profil')
            );
        }
    }


?>