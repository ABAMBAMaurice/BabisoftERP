<?php

class AdminRoleCenter extends Page {
    public function __construct() {
        parent::__construct(999980, 'AdminRoleCenter', PagesType::RoleCenter, 'Administration Système');
        $this->layout();
        $this->registerInViews();
    }

    function layout() {
        // --- Widgets statistiques ---
        $this->widget(
            name: 'users_count',
            type: 'stat',
            caption: 'Utilisateurs',
            icon: 'users',
            value: function() { return (new User())->Count(); },
            linkedPageId: 9999997,
            style: 'primary'
        );

        $this->widget(
            name: 'profiles_count',
            type: 'stat',
            caption: 'Profils',
            icon: 'shield',
            value: function() { return (new Profile())->Count(); },
            linkedPageId: 9999993,
            style: 'info'
        );

        $this->widget(
            name: 'noseries_count',
            type: 'stat',
            caption: 'Souches de N°',
            icon: 'sort-numeric',
            value: function() { return (new NoSerie())->Count(); },
            linkedPageId: 9999995,
            style: 'warning'
        );

        $this->widget(
            name: 'licences_actives',
            type: 'stat',
            caption: 'Licences actives',
            icon: 'key',
            value: function() {
                $lic = new licence();
                $lic->setRange('active', '1');
                return $lic->FindAll() ? count($lic->recordSet) : 0;
            },
            linkedPageId: 999989,
            style: 'success'
        );

        // --- Widgets raccourcis ---
        $this->widget(
            name: 'shortcut_users',
            type: 'shortcut',
            caption: 'Gestion Utilisateurs',
            icon: 'user-group',
            linkedPageId: 9999997,
            style: 'dark'
        );

        $this->widget(
            name: 'shortcut_profiles',
            type: 'shortcut',
            caption: 'Gestion Profils',
            icon: 'shield',
            linkedPageId: 9999993,
            style: 'dark'
        );

        $this->widget(
            name: 'shortcut_noseries',
            type: 'shortcut',
            caption: 'Souches de N°',
            icon: 'sort-numeric',
            linkedPageId: 9999995,
            style: 'dark'
        );

        $this->widget(
            name: 'shortcut_licences',
            type: 'shortcut',
            caption: 'Licences',
            icon: 'certificate',
            linkedPageId: 999989,
            style: 'dark'
        );
    }

    function setActions() {}
}

?>
