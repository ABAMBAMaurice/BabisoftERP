<?php
/**
 * Fiche commande / devis — Page Document (la plus importante du module).
 *
 * Actions disponibles selon le statut :
 *   Ouvert      → Libérer
 *   Libéré      → Valider Livraison, Annuler
 *   Livré       → Valider Facture
 *   Facturé     → Créer Avoir
 *   Tout statut → Voir Livraisons, Voir Factures
 */
class CommandeCard extends Page {
    public function __construct() {
        parent::__construct(62003, 'CommandeCard', PagesType::Document, 'Commande de vente');
        $this->sourceTable = new EnTeteVente();
        $this->setAction();
        $this->layout();
    }

    public function OnAfterGetRecord(Table &$record): void {
        $record->CalcFields('Montant_HT', 'Montant_TVA', 'Montant_TTC', 'Montant_Remise', 'Nb_Lignes');
    }

    function setAction(): void {
        $this->actions(
            name: 'Liberer',
            icon: 'unlock',
            caption: 'Libérer',
            style: 'inverse-info',
            onAction: function() {
                VentesManagement::liberer($this->rec->No_->_value);
                $this->Message("Document libéré et prêt pour la livraison.");
            }
        );

        $this->actions(
            name: 'ValiderLivraison',
            icon: 'truck',
            caption: 'Valider la livraison',
            style: 'inverse-warning',
            confirm: 'Valider l\'expédition des quantités Qte_A_Livrer ? Le stock sera débité.',
            onAction: function() {
                $livraisonNo = VentesManagement::validerLivraison($this->rec->No_->_value);
                $this->Message("Livraison ".$livraisonNo." validée. Stock mis à jour.");
            }
        );

        $this->actions(
            name: 'ValiderFacture',
            icon: 'file-check',
            caption: 'Valider la facture',
            style: 'inverse-success',
            confirm: 'Valider la facturation ? Une facture définitive sera créée et la comptabilité mise à jour.',
            onAction: function() {
                $factureNo = VentesManagement::validerFacture($this->rec->No_->_value);
                $this->Message("Facture ".$factureNo." validée et enregistrée en comptabilité.");
            }
        );

        $this->actions(
            name: 'ConvertirEnCommande',
            icon: 'arrow-right',
            caption: 'Convertir en commande',
            onAction: function() {
                if ($this->rec->Type_Document->_value !== TypeDocumentVente::Devis->value)
                    Error("Uniquement disponible pour les devis.");
                $cmdNo = VentesManagement::convertirDevisEnCommande($this->rec->No_->_value);
                $this->Message("Commande ".$cmdNo." créée.");
            }
        );

        $this->actions(
            name: 'Annuler',
            icon: 'x',
            caption: 'Annuler',
            style: 'inverse-danger',
            confirm: 'Annuler ce document ? Cette action est irréversible pour les commandes livrées.',
            onAction: function() {
                $motif = $_POST['motif'] ?? 'Annulation demandée';
                VentesManagement::annuler($this->rec->No_->_value, $motif);
                $this->Message("Document annulé.");
            }
        );

        $this->actions(
            name: 'VoirLivraisons',
            icon: 'package',
            caption: 'Livraisons',
            onAction: function() {
                $liv = new LivraisonEnTete();
                $liv->setRange('Commande_No', $this->rec->No_->_value);
                $liv->FindAll();
                header('Content-Type: application/json');
                die(json_encode(['status'=>200,'data'=>$liv->recordSet ?? []]));
            }
        );
    }

    function layout(): void {
        // ── En-tête ──────────────────────────────
        $this->group('Entete', 'Document',
            new PageField('No_',                    $this->rec->No_,                    editable: false),
            new PageField('Type_Document',          $this->rec->Type_Document,          editable: false),
            new PageField('Statut',                 $this->rec->Statut,                 editable: false),
            new PageField('Date_Document',          $this->rec->Date_Document,          editable: true),
            new PageField('No_Reference_Client',    $this->rec->No_Reference_Client,    editable: true, caption: 'N° BC client'),
        );

        // ── Client ──────────────────────────────
        $this->group('Client', 'Client',
            new PageField('Client_No',              $this->rec->Client_No,              editable: true),
            new PageField('Nom_Client',             $this->rec->Nom_Client,             editable: false),
            new PageField('NIF_Client',             $this->rec->NIF_Client,             editable: false),
            new PageField('Adresse_Livraison',      $this->rec->Adresse_Livraison,      editable: true),
            new PageField('Contact_Client',         $this->rec->Contact_Client,         editable: true),
        );

        // ── Conditions ──────────────────────────
        $this->group('Conditions', 'Conditions',
            new PageField('Conditions_Paiement_Code',$this->rec->Conditions_Paiement_Code,editable: true, caption: 'Paiement'),
            new PageField('Mode_Reglement_Code',    $this->rec->Mode_Reglement_Code,    editable: true, caption: 'Mode règlement'),
            new PageField('Date_Echeance',          $this->rec->Date_Echeance,          editable: true),
            new PageField('Date_Livraison_Prevue',  $this->rec->Date_Livraison_Prevue,  editable: true),
            new PageField('Conditions_Livraison',   $this->rec->Conditions_Livraison,   editable: true, caption: 'Incoterm'),
            new PageField('Entrepot_Code',          $this->rec->Entrepot_Code,          editable: true),
            new PageField('Responsable_Code',       $this->rec->Responsable_Code,       editable: true, caption: 'Commercial'),
            new PageField('Remise_Globale_Pct',     $this->rec->Remise_Globale_Pct,     editable: true, caption: 'Remise globale %'),
        );

        // ── Lignes ──────────────────────────────
        $ligneRec = new LigneVente();
        $this->repeater('lignes', 'Lignes',
            new PageField('Ligne_No',          $ligneRec->Ligne_No,          editable: false, caption: 'N°'),
            new PageField('No_',               $ligneRec->No_,               editable: true,  caption: 'Article'),
            new PageField('Description',       $ligneRec->Description,       editable: true),
            new PageField('UM_Code',           $ligneRec->UM_Code,           editable: true,  caption: 'UM'),
            new PageField('Quantite',          $ligneRec->Quantite,          editable: true,  caption: 'Qté'),
            new PageField('Qte_A_Livrer',      $ligneRec->Qte_A_Livrer,      editable: true,  caption: 'À livrer'),
            new PageField('Qte_Livree',        $ligneRec->Qte_Livree,        editable: false, caption: 'Livré'),
            new PageField('Qte_Facturee',      $ligneRec->Qte_Facturee,      editable: false, caption: 'Facturé'),
            new PageField('Prix_Unitaire_HT',  $ligneRec->Prix_Unitaire_HT,  editable: true,  caption: 'PU HT'),
            new PageField('Remise_Pct',        $ligneRec->Remise_Pct,        editable: true,  caption: 'Remise %'),
            new PageField('Montant_Ligne_HT',  $ligneRec->Montant_Ligne_HT,  editable: false, caption: 'Net HT'),
            new PageField('Taux_TVA_Pct',      $ligneRec->Taux_TVA_Pct,      editable: true,  caption: 'TVA %'),
            new PageField('Montant_Ligne_TTC', $ligneRec->Montant_Ligne_TTC, editable: false, caption: 'TTC'),
            new PageField('No_Lot',            $ligneRec->No_Lot,            editable: true,  caption: 'Lot'),
        );

        // ── Totaux ──────────────────────────────
        $this->group('Totaux', 'Totaux',
            new PageField('Montant_HT',     $this->rec->Montant_HT,     editable: false, caption: 'Total HT'),
            new PageField('Montant_Remise', $this->rec->Montant_Remise, editable: false, caption: 'Total remises'),
            new PageField('Montant_TVA',    $this->rec->Montant_TVA,    editable: false, caption: 'Total TVA 18%'),
            new PageField('Montant_TTC',    $this->rec->Montant_TTC,    editable: false, caption: 'Total TTC'),
        );

        $this->group('Notes', 'Notes internes',
            new PageField('Notes', $this->rec->Notes, editable: true),
        );
    }
}
?>
