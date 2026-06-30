<?php

/**
 * Grand livre — visualisation des écritures validées par compte.
 * Page en lecture seule. Les actions renvoient du JSON pour les états financiers.
 */
class GrandLivreList extends Page {
    public function __construct() {
        parent::__construct(60003, 'GrandLivreList', PagesType::List, 'Grand Livre');
        $this->sourceTable = new EcritureComptable();
        $this->editable    = false;
        $this->AllowDelete = false;
        $this->AllowInsert = false;
        $this->setAction();
        $this->layout();
    }

    function setAction() {
        $this->actions(
            name: 'Lettrer',
            icon: 'link',
            caption: 'Lettrer la sélection',
            style: 'inverse-info',
            onAction: function() {
                // Les N° d'écritures sélectionnées sont passés par le frontend
                $nos = isset($_POST['selection']) ? json_decode($_POST['selection'], true) : [];
                if (empty($nos))
                    Error("Sélectionnez au moins 2 écritures à lettrer.");
                $code = LettrageManagement::lettrer($nos);
                $this->Message("Écritures lettrées sous le code '".$code."'.");
            }
        );

        $this->actions(
            name: 'Delettrer',
            icon: 'unlink',
            caption: 'Délettrer',
            style: 'inverse-warning',
            confirm: 'Annuler le lettrage de cette écriture ?',
            onAction: function() {
                $code = $this->rec->Lettrage_Code->_value;
                if (empty($code))
                    Error("Cette écriture n'est pas lettrée.");
                LettrageManagement::delettrer($code);
                $this->Message("Lettrage '".$code."' annulé.");
            }
        );

        $this->actions(
            name: 'SuggererLettrage',
            icon: 'magic',
            caption: 'Suggestion de lettrage',
            onAction: function() {
                $suggestions = LettrageManagement::suggererLettrage($this->rec->Compte_No->_value);
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'suggestions' => $suggestions]));
            }
        );

        $this->actions(
            name: 'ExporterGrandLivre',
            icon: 'download',
            caption: 'Exporter Grand Livre',
            onAction: function() {
                $debut = $_POST['date_debut'] ?? date('Y-01-01');
                $fin   = $_POST['date_fin']   ?? date('Y-m-d');
                $gl    = BilanManagement::getGrandLivre($this->rec->Compte_No->_value, $debut, $fin);
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'data' => $gl]));
            }
        );
    }

    function layout() {
        $this->repeater('ecritures', 'Écritures comptables',
            new PageField('No_',               $this->rec->No_,               editable: false, caption: 'N°'),
            new PageField('Date_Ecriture',     $this->rec->Date_Ecriture,     editable: false, caption: 'Date'),
            new PageField('Journal_Code',      $this->rec->Journal_Code,      editable: false, caption: 'Journal'),
            new PageField('Piece_No',          $this->rec->Piece_No,          editable: false, caption: 'N° Pièce'),
            new PageField('Compte_No',         $this->rec->Compte_No,         editable: false, caption: 'Compte'),
            new PageField('Libelle',           $this->rec->Libelle,           editable: false, caption: 'Libellé'),
            new PageField('Debit',             $this->rec->Debit,             editable: false, caption: 'Débit'),
            new PageField('Credit',            $this->rec->Credit,            editable: false, caption: 'Crédit'),
            new PageField('Statut',            $this->rec->Statut,            editable: false, caption: 'Statut'),
            new PageField('Lettrage_Code',     $this->rec->Lettrage_Code,     editable: false, caption: 'Lettrage'),
            new PageField('Rapprochement_Code',$this->rec->Rapprochement_Code,editable: false, caption: 'Rapproch.'),
        );
    }
}
?>
