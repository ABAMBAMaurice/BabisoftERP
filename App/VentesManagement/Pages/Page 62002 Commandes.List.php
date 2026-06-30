<?php
class CommandesList extends Page {
    public function __construct() {
        parent::__construct(62002, 'CommandesList', PagesType::List, 'Devis & Commandes');
        $this->sourceTable = new EnTeteVente();
        $this->cardPageID  = 62003;
        $this->setAction();
        $this->layout();
    }

    public function OnAfterGetRecord(Table &$record): void {
        $record->CalcFields('Montant_HT', 'Montant_TVA', 'Montant_TTC', 'Nb_Lignes');
    }

    function setAction(): void {
        $this->actions(
            name: 'NouveauDevis',
            icon: 'file-plus',
            caption: 'Nouveau devis',
            onAction: function() {
                header('Content-Type: application/json');
                die(json_encode(['status'=>200,'redirect'=>'CommandeCard','type'=>TypeDocumentVente::Devis->value]));
            }
        );

        $this->actions(
            name: 'NouvelleCommande',
            icon: 'shopping-cart',
            caption: 'Nouvelle commande',
            onAction: function() {
                header('Content-Type: application/json');
                die(json_encode(['status'=>200,'redirect'=>'CommandeCard','type'=>TypeDocumentVente::Commande->value]));
            }
        );

        $this->actions(
            name: 'ConvertirDevis',
            icon: 'arrow-right',
            caption: 'Convertir en commande',
            confirm: 'Convertir ce devis en commande de vente ?',
            onAction: function() {
                if ($this->rec->Type_Document->_value !== TypeDocumentVente::Devis->value)
                    Error("Ce document n'est pas un devis.");
                $cmdNo = VentesManagement::convertirDevisEnCommande($this->rec->No_->_value);
                $this->Message("Commande ".$cmdNo." créée depuis le devis ".$this->rec->No_->_value.".");
            }
        );
    }

    function layout(): void {
        $this->repeater('documents', 'Documents de vente',
            new PageField('No_',             $this->rec->No_,             editable: false),
            new PageField('Type_Document',   $this->rec->Type_Document,   editable: false, caption: 'Type'),
            new PageField('Statut',          $this->rec->Statut,          editable: false),
            new PageField('Client_No',       $this->rec->Client_No,       editable: true,  caption: 'Client'),
            new PageField('Nom_Client',      $this->rec->Nom_Client,      editable: false),
            new PageField('Date_Document',   $this->rec->Date_Document,   editable: true),
            new PageField('Date_Echeance',   $this->rec->Date_Echeance,   editable: false),
            new PageField('No_Reference_Client',$this->rec->No_Reference_Client,editable: true, caption: 'N° BC client'),
            new PageField('Montant_HT',      $this->rec->Montant_HT,      editable: false, caption: 'HT'),
            new PageField('Montant_TVA',     $this->rec->Montant_TVA,     editable: false, caption: 'TVA'),
            new PageField('Montant_TTC',     $this->rec->Montant_TTC,     editable: false, caption: 'TTC'),
            new PageField('Nb_Lignes',       $this->rec->Nb_Lignes,       editable: false, caption: 'Lignes'),
            new PageField('Responsable_Code',$this->rec->Responsable_Code,editable: true,  caption: 'Commercial'),
        );
    }
}
?>
