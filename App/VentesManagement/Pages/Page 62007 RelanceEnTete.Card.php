<?php
class RelanceEnTeteCard extends Page {
    public function __construct() {
        parent::__construct(62007, 'RelanceEnTeteCard', PagesType::Card, 'Fiche relance');
        $this->sourceTable = new RelanceEnTete();
        $this->setAction();
        $this->layout();
    }

    public function OnAfterGetRecord(Table &$record): void {
        $record->CalcFields('Nb_Ecritures_En_Attente');
    }

    function setAction(): void {
        $this->actions(
            name: 'Emettre',
            icon: 'send',
            caption: 'Émettre la relance',
            style: 'inverse-warning',
            confirm: 'Confirmer l\'émission de cette relance au client ?',
            onAction: function() {
                RelanceManagement::emettre($this->rec->No_->_value);
                $this->Message("Relance émise le ".date('d/m/Y').".");
            }
        );

        $this->actions(
            name: 'EnregistrerPaiement',
            icon: 'credit-card',
            caption: 'Paiement reçu',
            onAction: function() {
                $montant = (float)($_POST['montant']             ?? $this->rec->Montant_Echu->_value);
                $mode    = $_POST['mode_reglement_code']         ?? '';
                if (!$mode) Error("Mode de règlement obligatoire.");
                $ec = PaiementManagement::enregistrerPaiement(
                    $this->rec->Client_No->_value, $montant,
                    date('Y-m-d'), $mode, "Suite relance ".$this->rec->No_->_value
                );
                PaiementManagement::appliquerAutomatiquement((int)$ec->No_->_value);
                $this->rec->Validate('Statut', 'Acquitte');
                $this->rec->Modify(false);
                $this->Message("Paiement enregistré et factures soldées.");
            }
        );
    }

    function layout(): void {
        $this->group('Entete', 'Relance',
            new PageField('No_',            $this->rec->No_,            editable: false),
            new PageField('Client_No',      $this->rec->Client_No,      editable: false),
            new PageField('Nom_Client',     $this->rec->Nom_Client,     editable: false),
            new PageField('Date_Relance',   $this->rec->Date_Relance,   editable: false),
            new PageField('Date_Echeance',  $this->rec->Date_Echeance,  editable: true, caption: 'Réponse attendue'),
            new PageField('Niveau_Relance', $this->rec->Niveau_Relance, editable: false, caption: 'Niveau'),
            new PageField('Statut',         $this->rec->Statut,         editable: false),
            new PageField('Responsable_Code',$this->rec->Responsable_Code,editable: true),
        );

        $this->group('Montants', 'Montants',
            new PageField('Montant_Echu',       $this->rec->Montant_Echu,       editable: false, caption: 'Total échu'),
            new PageField('Frais_Relance',      $this->rec->Frais_Relance,      editable: true,  caption: 'Frais'),
            new PageField('Interets_Retard',    $this->rec->Interets_Retard,    editable: true,  caption: 'Intérêts'),
            new PageField('Nb_Ecritures_En_Attente',$this->rec->Nb_Ecritures_En_Attente,editable: false, caption: 'Nb factures'),
        );

        $ligneRec = new RelanceLigne();
        $this->repeater('lignes', 'Factures relancées',
            new PageField('Ligne_No',      $ligneRec->Ligne_No,      editable: false),
            new PageField('Type_Ligne',    $ligneRec->Type_Ligne,    editable: false, caption: 'Type'),
            new PageField('Document_No',   $ligneRec->Document_No,   editable: false, caption: 'N° facture'),
            new PageField('Description',   $ligneRec->Description,   editable: false),
            new PageField('Date_Document', $ligneRec->Date_Document, editable: false, caption: 'Date facture'),
            new PageField('Date_Echeance', $ligneRec->Date_Echeance, editable: false, caption: 'Échéance'),
            new PageField('Jours_Retard',  $ligneRec->Jours_Retard,  editable: false, caption: 'Jours retard'),
            new PageField('Montant_Original',$ligneRec->Montant_Original,editable: false, caption: 'Montant initial'),
            new PageField('Montant_Restant',$ligneRec->Montant_Restant,  editable: false, caption: 'Restant dû'),
            new PageField('Montant_Interet',$ligneRec->Montant_Interet,  editable: false, caption: 'Intérêts'),
            new PageField('Niveau_Relance',$ligneRec->Niveau_Relance,    editable: false, caption: 'Niveau'),
        );

        $this->group('Notes', 'Observations',
            new PageField('Notes', $this->rec->Notes, editable: true),
        );
    }
}
?>
