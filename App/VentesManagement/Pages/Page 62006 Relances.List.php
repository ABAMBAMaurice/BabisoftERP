<?php
class RelancesList extends Page {
    public function __construct() {
        parent::__construct(62006, 'RelancesList', PagesType::List, 'Relances clients');
        $this->sourceTable = new RelanceEnTete();
        $this->cardPageID  = 62007;
        $this->setAction();
        $this->layout();
    }

    public function OnAfterGetRecord(Table &$record): void {
        $record->CalcFields('Nb_Ecritures_En_Attente');
    }

    function setAction(): void {
        $this->actions(
            name: 'GenererRelances',
            icon: 'bell',
            caption: 'Générer les relances',
            confirm: 'Analyser toutes les factures échues et créer les relances nécessaires ?',
            onAction: function() {
                $result = RelanceManagement::genererRelances();
                $this->Message(
                    $result['nb_relances']." relance(s) générée(s) pour "
                    .$result['nb_clients']." client(s). "
                    ."Montant échu total : ".number_format($result['montant_total'],0,'.',',')." FCFA."
                );
            }
        );

        $this->actions(
            name: 'Emettre',
            icon: 'send',
            caption: 'Émettre',
            confirm: 'Passer cette relance au statut Émis ?',
            onAction: function() {
                RelanceManagement::emettre($this->rec->No_->_value);
                $this->Message("Relance ".$this->rec->No_->_value." émise.");
            }
        );

        $this->actions(
            name: 'BalanceAgee',
            icon: 'clock',
            caption: 'Balance âgée des créances',
            onAction: function() {
                $balance = RelanceManagement::getBalanceAgee();
                header('Content-Type: application/json');
                die(json_encode(['status'=>200,'data'=>$balance]));
            }
        );
    }

    function layout(): void {
        $this->repeater('relances', 'Relances',
            new PageField('No_',                   $this->rec->No_,                   editable: false),
            new PageField('Client_No',             $this->rec->Client_No,             editable: false),
            new PageField('Nom_Client',            $this->rec->Nom_Client,            editable: false),
            new PageField('Date_Relance',          $this->rec->Date_Relance,          editable: false),
            new PageField('Date_Echeance',         $this->rec->Date_Echeance,         editable: false, caption: 'Réponse attendue'),
            new PageField('Niveau_Relance',        $this->rec->Niveau_Relance,        editable: false, caption: 'Niveau'),
            new PageField('Statut',                $this->rec->Statut,                editable: false),
            new PageField('Montant_Echu',          $this->rec->Montant_Echu,          editable: false, caption: 'Montant échu'),
            new PageField('Frais_Relance',         $this->rec->Frais_Relance,         editable: false, caption: 'Frais'),
            new PageField('Interets_Retard',       $this->rec->Interets_Retard,       editable: false, caption: 'Intérêts'),
            new PageField('Nb_Ecritures_En_Attente',$this->rec->Nb_Ecritures_En_Attente,editable: false, caption: 'Nb factures'),
            new PageField('Responsable_Code',      $this->rec->Responsable_Code,      editable: true),
        );
    }
}
?>
