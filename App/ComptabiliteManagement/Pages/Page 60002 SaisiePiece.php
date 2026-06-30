<?php

/**
 * Page de saisie d'une pièce comptable (brouillon).
 * Type Document — une en-tête + un repeater de lignes.
 *
 * Flux : Saisie → Validation (bouton) → EcritureComptable (grand livre)
 */
class SaisiePiece extends Page {
    public function __construct() {
        parent::__construct(60002, 'SaisiePiece', PagesType::Document, 'Saisie de pièce');
        $this->sourceTable = new LignePiece();
        $this->setAction();
        $this->layout();
    }

    public function OnAfterGetRecord(Table &$record) {
        $record->CalcFields('Total_Debit_Piece', 'Total_Credit_Piece');
    }

    function setAction() {
        $this->actions(
            name: 'ValiderPiece',
            icon: 'check',
            caption: 'Valider la pièce',
            style: 'inverse-success',
            confirm: 'Valider cette pièce ? Les écritures seront définitivement enregistrées dans le grand livre.',
            onAction: function() {
                ComptabiliteManagement::validerPiece(
                    $this->rec->Journal_Code->_value,
                    $this->rec->Piece_No->_value
                );
                $this->Message('Pièce validée et enregistrée dans le grand livre.');
            }
        );

        $this->actions(
            name: 'AjouterLigne',
            icon: 'plus',
            caption: 'Ajouter une ligne',
            onAction: function() {
                // Le frontend gère l'ajout de ligne via l'API POST directement
            }
        );

        $this->actions(
            name: 'ContrepasserPiece',
            icon: 'undo',
            caption: 'Contrepasser',
            style: 'inverse-warning',
            confirm: 'Générer une écriture inverse pour annuler cette pièce ?',
            onAction: function() {
                ComptabiliteManagement::contrepasser(
                    $this->rec->Piece_No->_value,
                    date('Y-m-d')
                );
                $this->Message('Contrepassation générée.');
            }
        );
    }

    function layout() {
        // En-tête de la pièce
        $this->group('Entete', 'Pièce',
            new PageField('Journal_Code',       $this->rec->Journal_Code,       editable: true,  caption: 'Journal'),
            new PageField('Piece_No',           $this->rec->Piece_No,           editable: true,  caption: 'N° Pièce'),
            new PageField('Date_Ecriture',      $this->rec->Date_Ecriture,      editable: true,  caption: 'Date'),
        );

        // Lignes de la pièce + totaux de contrôle (FlowFields)
        $this->group('Controle', 'Contrôle',
            new PageField('Total_Debit_Piece',  $this->rec->Total_Debit_Piece,  editable: false, caption: 'Total Débit'),
            new PageField('Total_Credit_Piece', $this->rec->Total_Credit_Piece, editable: false, caption: 'Total Crédit'),
        );

        // Repeater des lignes
        $this->repeater('lignes', 'Lignes de la pièce',
            new PageField('Ligne_No',    $this->rec->Ligne_No,    editable: false, caption: 'N°'),
            new PageField('Compte_No',   $this->rec->Compte_No,   editable: true,  caption: 'Compte'),
            new PageField('Libelle',     $this->rec->Libelle,     editable: true,  caption: 'Libellé'),
            new PageField('Debit',       $this->rec->Debit,       editable: true,  caption: 'Débit'),
            new PageField('Credit',      $this->rec->Credit,      editable: true,  caption: 'Crédit'),
            new PageField('Type_TVA',    $this->rec->Type_TVA,    editable: true,  caption: 'TVA'),
            new PageField('Taux_TVA',    $this->rec->Taux_TVA,    editable: true,  caption: 'Taux %'),
            new PageField('Base_TVA',    $this->rec->Base_TVA,    editable: true,  caption: 'Base HT'),
        );
    }
}
?>
