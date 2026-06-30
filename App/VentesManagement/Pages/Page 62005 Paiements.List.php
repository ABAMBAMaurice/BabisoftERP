<?php
class PaiementsList extends Page {
    public function __construct() {
        parent::__construct(62005, 'PaiementsList', PagesType::List, 'Encaissements & Paiements');
        $this->sourceTable = new EcritureClient();
        $this->editable    = false;
        $this->AllowInsert = false;
        $this->AllowDelete = false;
        $this->setAction();
        $this->layout();
    }

    function setAction(): void {
        $this->actions(
            name: 'NouveauPaiement',
            icon: 'plus',
            caption: 'Enregistrer un paiement',
            onAction: function() {
                $clientNo = $_POST['client_no']           ?? '';
                $montant  = (float)($_POST['montant']     ?? 0);
                $mode     = $_POST['mode_reglement_code'] ?? '';
                $ref      = $_POST['reference']           ?? '';
                if (!$clientNo || !$montant || !$mode)
                    Error("Client, montant et mode de règlement obligatoires.");
                $ec = PaiementManagement::enregistrerPaiement($clientNo, $montant, date('Y-m-d'), $mode, $ref);
                PaiementManagement::appliquerAutomatiquement((int)$ec->No_->_value);
                $this->Message("Paiement de ".number_format($montant,0,'.',',')." FCFA enregistré et affecté automatiquement.");
            }
        );

        $this->actions(
            name: 'AffecterManuellement',
            icon: 'link',
            caption: 'Affecter manuellement',
            onAction: function() {
                $paiementNo = (int)($_POST['paiement_no'] ?? 0);
                $factureNos = isset($_POST['facture_nos']) ? json_decode($_POST['facture_nos'], true) : [];
                if (!$paiementNo) Error("N° de paiement obligatoire.");
                PaiementManagement::appliquerPaiement($paiementNo, $factureNos);
                $this->Message("Paiement affecté aux factures sélectionnées.");
            }
        );

        $this->actions(
            name: 'SoldeClient',
            icon: 'user',
            caption: 'Solde client',
            onAction: function() {
                $clientNo = $this->rec->Client_No->_value;
                $solde = PaiementManagement::getSoldeClient($clientNo);
                header('Content-Type: application/json');
                die(json_encode(['status'=>200,'data'=>$solde]));
            }
        );
    }

    function layout(): void {
        $this->repeater('ecritures', 'Écritures client',
            new PageField('No_',             $this->rec->No_,             editable: false),
            new PageField('Type_Ecriture',   $this->rec->Type_Ecriture,   editable: false, caption: 'Type'),
            new PageField('Client_No',       $this->rec->Client_No,       editable: false),
            new PageField('Nom_Client',      $this->rec->Nom_Client,      editable: false),
            new PageField('Date_Ecriture',   $this->rec->Date_Ecriture,   editable: false, caption: 'Date'),
            new PageField('Date_Echeance',   $this->rec->Date_Echeance,   editable: false, caption: 'Échéance'),
            new PageField('Document_No',     $this->rec->Document_No,     editable: false, caption: 'N° document'),
            new PageField('Description',     $this->rec->Description,     editable: false),
            new PageField('Montant_Original',$this->rec->Montant_Original,editable: false, caption: 'Montant'),
            new PageField('Montant_Restant', $this->rec->Montant_Restant, editable: false, caption: 'Restant'),
            new PageField('Statut_Paiement', $this->rec->Statut_Paiement, editable: false, caption: 'Statut'),
            new PageField('Lettrage_Code',   $this->rec->Lettrage_Code,   editable: false, caption: 'Lettrage'),
            new PageField('Mode_Reglement_Code',$this->rec->Mode_Reglement_Code,editable: false, caption: 'Mode'),
            new PageField('Sens',            $this->rec->Sens,            editable: false),
        );
    }
}
?>
