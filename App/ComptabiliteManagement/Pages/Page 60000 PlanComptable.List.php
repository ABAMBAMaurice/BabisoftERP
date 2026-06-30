<?php

class PlanComptableList extends Page {
    public function __construct() {
        parent::__construct(60000, 'PlanComptableList', PagesType::List, 'Plan Comptable');
        $this->sourceTable = new CompteComptable();
        $this->editable    = true;
        
        $this->setAction();
        $this->layout();
    }

    public function OnAfterGetRecord(Table &$record) {
        $record->CalcFields('Solde_Debit', 'Solde_Credit', 'Nb_Ecritures');
    }

    function setAction() {
        $this->actions(
            name: 'GrandLivre',
            icon: 'book',
            caption: 'Grand livre',
            onAction: function() {
                $gl = BilanManagement::getGrandLivre(
                    $this->rec->Numero->_value,
                    date('Y-01-01'),
                    date('Y-12-31')
                );
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'data' => $gl]));
            }
        );

        $this->actions(
            name: 'Balance',
            icon: 'scale',
            caption: 'Balance',
            onAction: function() {
                $balance = BilanManagement::getBalance(date('Y-01-01'), date('Y-12-31'));
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'data' => $balance]));
            }
        );

        $this->actions(
            name: 'Bilan',
            icon: 'chart',
            caption: 'Bilan',
            onAction: function() {
                $bilan = BilanManagement::getBilan(date('Y-m-d'));
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'data' => $bilan]));
            }
        );

        $this->actions(
            name: 'CompteResultat',
            icon: 'trending',
            caption: 'Compte de résultat',
            onAction: function() {
                $cdr = BilanManagement::getCompteResultat(date('Y-01-01'), date('Y-m-d'));
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'data' => $cdr]));
            }
        );
    }

    function layout() {
        $this->repeater('comptes', 'Plan comptable',
            new PageField('Numero',      $this->rec->Numero,      editable: false, caption: 'N°'),
            new PageField('Intitule',    $this->rec->Intitule,    editable: true,  caption: 'Intitulé'),
            new PageField('Classe',      $this->rec->Classe,      editable: false, caption: 'Cl.'),
            new PageField('Type',        $this->rec->Type,        editable: true,  caption: 'Type'),
            new PageField('Sens_Normal', $this->rec->Sens_Normal, editable: false, caption: 'Sens'),
            new PageField('Est_Actif',   $this->rec->Est_Actif,   editable: true,  caption: 'Actif'),
            new PageField('Solde_Debit', $this->rec->Solde_Debit, editable: false, caption: 'Débit cumulé'),
            new PageField('Solde_Credit',$this->rec->Solde_Credit,editable: false, caption: 'Crédit cumulé'),
            new PageField('Nb_Ecritures',$this->rec->Nb_Ecritures,editable: false, caption: 'Nb écritures')
        );
    }
}
?>
