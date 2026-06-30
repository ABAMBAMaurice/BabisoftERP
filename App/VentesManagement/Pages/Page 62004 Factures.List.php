<?php
class FacturesList extends Page {
    public function __construct() {
        parent::__construct(62004, 'FacturesList', PagesType::List, 'Factures & Avoirs validés');
        $this->sourceTable = new FactureValideeEnTete();
        $this->editable    = false;
        $this->AllowInsert = false;
        $this->AllowDelete = false;
        $this->cardPageID  = 62005;
        $this->setAction();
        $this->layout();
    }

    function setAction(): void {
        $this->actions(
            name: 'CreerAvoir',
            icon: 'rotate-ccw',
            caption: 'Créer un avoir',
            confirm: 'Créer un avoir basé sur cette facture ?',
            onAction: function() {
                $avoirNo = VentesManagement::creerAvoirDepuisFacture($this->rec->No_->_value);
                $this->Message("Avoir ".$avoirNo." créé. Ouvrez-le pour le compléter et le valider.");
            }
        );

        $this->actions(
            name: 'EnregistrerPaiement',
            icon: 'credit-card',
            caption: 'Enregistrer un paiement',
            onAction: function() {
                $montant = (float)($_POST['montant'] ?? $this->rec->Montant_Restant->_value);
                $mode    = $_POST['mode_reglement_code'] ?? '';
                if (!$mode) Error("Le mode de règlement est obligatoire.");
                $ec = PaiementManagement::enregistrerPaiement(
                    $this->rec->Client_No->_value,
                    $montant, date('Y-m-d'), $mode,
                    'Règlement facture '.$this->rec->No_->_value
                );
                PaiementManagement::appliquerPaiement((int)$ec->No_->_value, []);
                $this->Message("Paiement enregistré.");
            }
        );

        $this->actions(
            name: 'StatistiquesVentes',
            icon: 'bar-chart',
            caption: 'Statistiques de vente',
            onAction: function() {
                $query = 'SELECT'
                       . ' SUM(Montant_HT) AS total_ht,'
                       . ' SUM(Montant_TVA) AS total_tva,'
                       . ' SUM(Montant_TTC) AS total_ttc,'
                       . ' SUM(CASE WHEN Statut_Paiement="Paye" THEN Montant_TTC ELSE 0 END) AS encaisse,'
                       . ' SUM(Montant_Restant) AS restant,'
                       . ' COUNT(*) AS nb_factures'
                       . ' FROM facture_validee_entete'
                       . ' WHERE Type_Document="Facture"'
                       . ' AND YEAR(Date_Document)=YEAR(CURDATE())'
                       . ' AND (deleted_at="0000-00-00 00:00:00" OR deleted_at IS NULL)';
                $data = db->getResultAssoc($query);
                header('Content-Type: application/json');
                die(json_encode(['status'=>200,'data'=>$data[0] ?? []]));
            }
        );
    }

    function layout(): void {
        $this->repeater('factures', 'Factures et avoirs',
            new PageField('No_',              $this->rec->No_,              editable: false),
            new PageField('Type_Document',    $this->rec->Type_Document,    editable: false, caption: 'Type'),
            new PageField('Client_No',        $this->rec->Client_No,        editable: false),
            new PageField('Nom_Client',       $this->rec->Nom_Client,       editable: false),
            new PageField('Date_Document',    $this->rec->Date_Document,    editable: false),
            new PageField('Date_Echeance',    $this->rec->Date_Echeance,    editable: false),
            new PageField('Montant_HT',       $this->rec->Montant_HT,       editable: false, caption: 'HT'),
            new PageField('Montant_TVA',      $this->rec->Montant_TVA,      editable: false, caption: 'TVA'),
            new PageField('Montant_TTC',      $this->rec->Montant_TTC,      editable: false, caption: 'TTC'),
            new PageField('Montant_Restant',  $this->rec->Montant_Restant,  editable: false, caption: 'Restant dû'),
            new PageField('Statut_Paiement',  $this->rec->Statut_Paiement,  editable: false, caption: 'Paiement'),
            new PageField('Mode_Reglement_Code',$this->rec->Mode_Reglement_Code,editable: false, caption: 'Mode'),
            new PageField('Commande_No',      $this->rec->Commande_No,      editable: false, caption: 'N° commande'),
        );
    }
}
?>
