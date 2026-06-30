<?php
class ClientsList extends Page {
    public function __construct() {
        parent::__construct(62000, 'ClientsList', PagesType::List, 'Clients');
        $this->sourceTable = new Client();
        $this->cardPageID  = 62001;
        $this->setAction();
        $this->layout();
    }

    public function OnAfterGetRecord(Table &$record): void {
        $record->CalcFields('Solde', 'Nb_Factures');
    }

    function setAction(): void {
        $this->actions(
            name: 'BalanceAgee',
            icon: 'clock',
            caption: 'Balance âgée',
            onAction: function() {
                $balance = RelanceManagement::getBalanceAgee();
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'data' => $balance]));
            }
        );

        $this->actions(
            name: 'GenererRelances',
            icon: 'bell',
            caption: 'Générer les relances',
            confirm: 'Générer les relances pour tous les clients en retard ?',
            onAction: function() {
                $result = RelanceManagement::genererRelances();
                $this->Message(
                    $result['nb_relances']." relance(s) créée(s) pour "
                    .$result['nb_clients']." client(s). "
                    ."Montant échu : ".number_format($result['montant_total'],0,'.',',')." FCFA"
                );
            }
        );

        $this->actions(
            name: 'SoldeClient',
            icon: 'wallet',
            caption: 'Solde & échu',
            onAction: function() {
                $solde = PaiementManagement::getSoldeClient($this->rec->No_->_value);
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'data' => $solde]));
            }
        );
    }

    function layout(): void {
        $this->repeater('clients', 'Clients',
            new PageField('No_',             $this->rec->No_,             editable: false, caption: 'N° client'),
            new PageField('Nom',             $this->rec->Nom,             editable: true),
            new PageField('Ville',           $this->rec->Ville,           editable: true),
            new PageField('Tel',             $this->rec->Tel,             editable: true),
            new PageField('NIF',             $this->rec->NIF,             editable: true),
            new PageField('Groupe_Compta_Code',$this->rec->Groupe_Compta_Code, editable: true, caption: 'Groupe'),
            new PageField('Conditions_Paiement_Code',$this->rec->Conditions_Paiement_Code, editable: true, caption: 'Conditions pmt'),
            new PageField('Limite_Credit',   $this->rec->Limite_Credit,   editable: true,  caption: 'Limite crédit'),
            new PageField('Solde',           $this->rec->Solde,           editable: false, caption: 'Solde (FCFA)'),
            new PageField('Nb_Factures',     $this->rec->Nb_Factures,     editable: false, caption: 'Nb écritures'),
            new PageField('Bloque',          $this->rec->Bloque,          editable: true,  caption: 'Bloqué'),
            new PageField('Est_Actif',       $this->rec->Est_Actif,       editable: true,  caption: 'Actif'),
        );
    }
}
?>
