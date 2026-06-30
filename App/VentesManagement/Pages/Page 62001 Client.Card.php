<?php
class ClientCard extends Page {
    public function __construct() {
        parent::__construct(62001, 'ClientCard', PagesType::Card, 'Fiche client');
        $this->sourceTable = new Client();
        $this->setAction();
        $this->layout();
    }

    public function OnAfterGetRecord(Table &$record): void {
        $record->CalcFields('Solde', 'Montant_Commandes_Ouvertes', 'Nb_Factures');
    }

    function setAction(): void {
        $this->actions(
            name: 'VoirFactures',
            icon: 'file-text',
            caption: 'Factures',
            onAction: function() {
                $q = 'SELECT * FROM facture_validee_entete'
                   . ' WHERE Client_No="'.$this->rec->No_->_value.'"'
                   . ' AND (deleted_at="0000-00-00 00:00:00" OR deleted_at IS NULL)'
                   . ' ORDER BY Date_Document DESC';
                $data = db->getResultAssoc($q);
                header('Content-Type: application/json');
                die(json_encode(['status'=>200,'data'=>$data]));
            }
        );

        $this->actions(
            name: 'SoldeDetail',
            icon: 'trending-up',
            caption: 'Solde & balance âgée',
            onAction: function() {
                $solde   = PaiementManagement::getSoldeClient($this->rec->No_->_value);
                $balance = RelanceManagement::getBalanceAgee();
                $clientRow = array_filter($balance['clients'], fn($r) => $r['No_'] === $this->rec->No_->_value);
                header('Content-Type: application/json');
                die(json_encode(['status'=>200,'solde'=>$solde,'balance'=>array_values($clientRow)]));
            }
        );

        $this->actions(
            name: 'NouvelleCommande',
            icon: 'plus',
            caption: 'Nouvelle commande',
            onAction: function() {
                header('Content-Type: application/json');
                die(json_encode(['status'=>200,'redirect'=>'CommandeCard','client_no'=>$this->rec->No_->_value]));
            }
        );

        $this->actions(
            name: 'EnregistrerPaiement',
            icon: 'credit-card',
            caption: 'Enregistrer un paiement',
            onAction: function() {
                $montant  = (float)($_POST['montant'] ?? 0);
                $mode     = $_POST['mode_reglement_code'] ?? '';
                $ref      = $_POST['reference'] ?? '';
                if ($montant <= 0) Error("Montant obligatoire.");
                $ec = PaiementManagement::enregistrerPaiement(
                    $this->rec->No_->_value, $montant, date('Y-m-d'), $mode, $ref
                );
                PaiementManagement::appliquerAutomatiquement((int)$ec->No_->_value);
                $this->Message("Paiement de ".number_format($montant,0,'.',',')." FCFA enregistré et affecté.");
            }
        );

        $this->actions(
            name: 'Bloquer',
            icon: 'lock',
            caption: 'Bloquer / Débloquer',
            confirm: 'Modifier le statut de blocage de ce client ?',
            onAction: function() {
                $this->rec->Validate('Bloque', !$this->rec->Bloque->_value);
                $this->rec->Modify();
                $this->Message($this->rec->Bloque->_value ? "Client bloqué." : "Client débloqué.");
            }
        );
    }

    function layout(): void {
        $this->group('Identification', 'Identification',
            new PageField('No_',               $this->rec->No_,               editable: false),
            new PageField('Nom',               $this->rec->Nom,               editable: true),
            new PageField('Nom2',              $this->rec->Nom2,              editable: true),
            new PageField('Type_Client',       $this->rec->Type_Client,       editable: true),
            new PageField('NIF',               $this->rec->NIF,               editable: true),
            new PageField('RC',                $this->rec->RC,                editable: true),
        );
        $this->group('Coordonnees', 'Coordonnées',
            new PageField('Adresse',           $this->rec->Adresse,           editable: true),
            new PageField('Quartier',          $this->rec->Quartier,          editable: true),
            new PageField('Ville',             $this->rec->Ville,             editable: true),
            new PageField('Tel',               $this->rec->Tel,               editable: true),
            new PageField('Tel2',              $this->rec->Tel2,              editable: true),
            new PageField('Email',             $this->rec->Email,             editable: true),
        );
        $this->group('Commercial', 'Conditions commerciales',
            new PageField('Groupe_Compta_Code',    $this->rec->Groupe_Compta_Code,    editable: true),
            new PageField('Conditions_Paiement_Code',$this->rec->Conditions_Paiement_Code,editable: true),
            new PageField('Mode_Reglement_Code',   $this->rec->Mode_Reglement_Code,   editable: true),
            new PageField('Remise_Ligne_Pct',      $this->rec->Remise_Ligne_Pct,      editable: true),
            new PageField('Remise_Facture_Pct',    $this->rec->Remise_Facture_Pct,    editable: true),
            new PageField('Limite_Credit',         $this->rec->Limite_Credit,         editable: true),
            new PageField('Verification_Credit',   $this->rec->Verification_Credit,   editable: true),
            new PageField('Responsable_Code',      $this->rec->Responsable_Code,      editable: true),
        );
        $this->group('Statistiques', 'Situation financière',
            new PageField('Solde',                 $this->rec->Solde,                 editable: false),
            new PageField('Montant_Commandes_Ouvertes',$this->rec->Montant_Commandes_Ouvertes,editable: false),
            new PageField('Nb_Factures',           $this->rec->Nb_Factures,           editable: false),
        );
        $this->group('Livraison', 'Adresse de livraison',
            new PageField('Adresse_Livraison',     $this->rec->Adresse_Livraison,     editable: true),
            new PageField('Ville_Livraison',       $this->rec->Ville_Livraison,       editable: true),
            new PageField('Contact_Livraison',     $this->rec->Contact_Livraison,     editable: true),
        );
    }
}
?>
