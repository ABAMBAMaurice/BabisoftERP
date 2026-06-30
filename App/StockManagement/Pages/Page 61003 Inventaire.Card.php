<?php
/**
 * Gestion d'un inventaire physique complet.
 * Type Card avec repeater pour les lignes.
 */
class InventaireCard extends Page {
    public function __construct() {
        parent::__construct(61003, 'InventaireCard', PagesType::Card, 'Inventaire physique');
        $this->sourceTable = new InventaireEnTete();
        $this->setAction();
        $this->layout();
    }

    public function OnAfterGetRecord(Table &$record): void {
        $record->CalcFields('Nb_Lignes');
    }

    function setAction(): void {
        $this->actions(
            name: 'InitialiserLignes',
            icon: 'refresh',
            caption: 'Initialiser les lignes',
            style: 'inverse-info',
            confirm: 'Générer les lignes depuis le stock théorique actuel ? Les lignes existantes seront supprimées.',
            onAction: function() {
                $nb = InventaireManagement::initialiserLignes($this->rec->No_->_value);
                $this->Message($nb." ligne(s) générée(s) depuis le stock théorique.");
            }
        );

        $this->actions(
            name: 'ValiderInventaire',
            icon: 'check',
            caption: 'Valider l\'inventaire',
            style: 'inverse-success',
            confirm: 'Valider l\'inventaire ? Les ajustements seront générés pour tous les écarts.',
            onAction: function() {
                $result = InventaireManagement::valider($this->rec->No_->_value);
                $this->Message(
                    $result['ajustements_generes']." ajustement(s) générés. "
                   ."Écart total : ".number_format($result['total_ecart_valeur'], 2)." FCFA"
                );
            }
        );

        $this->actions(
            name: 'Rapport',
            icon: 'file',
            caption: 'Rapport d\'inventaire',
            onAction: function() {
                $rapport = InventaireManagement::getRapport($this->rec->No_->_value);
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'data' => $rapport]));
            }
        );
    }

    function layout(): void {
        $this->group('Entete', 'En-tête inventaire',
            new PageField('No_',             $this->rec->No_,             editable: false),
            new PageField('Entrepot_Code',   $this->rec->Entrepot_Code,   editable: true,  caption: 'Entrepôt'),
            new PageField('Date_Inventaire', $this->rec->Date_Inventaire, editable: true,  caption: 'Date inventaire'),
            new PageField('Statut',          $this->rec->Statut,          editable: false),
            new PageField('Responsable',     $this->rec->Responsable,     editable: true),
            new PageField('Filtre_Categorie',$this->rec->Filtre_Categorie,editable: true,  caption: 'Filtre catégorie'),
            new PageField('Nb_Lignes',       $this->rec->Nb_Lignes,       editable: false, caption: 'Nb lignes'),
        );

        // Repeater des lignes d'inventaire (source : InventaireLigne filtrée par Inventaire_No)
        $ligneRec = new InventaireLigne();
        $this->repeater('lignes', 'Lignes de comptage',
            new PageField('Ligne_No',        $ligneRec->Ligne_No,        editable: false),
            new PageField('Article_Code',    $ligneRec->Article_Code,    editable: false),
            new PageField('Description',     $ligneRec->Description,     editable: false),
            new PageField('Emplacement_Code',$ligneRec->Emplacement_Code,editable: false),
            new PageField('No_Lot',          $ligneRec->No_Lot,          editable: false),
            new PageField('Qte_Theorique',   $ligneRec->Qte_Theorique,   editable: false, caption: 'Stock théorique'),
            new PageField('Qte_Comptee',     $ligneRec->Qte_Comptee,     editable: true,  caption: 'Qté comptée'),
            new PageField('Ecart',           $ligneRec->Ecart,           editable: false),
            new PageField('Cout_Unitaire',   $ligneRec->Cout_Unitaire,   editable: false, caption: 'CMUP'),
            new PageField('Valeur_Ecart',    $ligneRec->Valeur_Ecart,    editable: false, caption: 'Valeur écart (FCFA)'),
            new PageField('A_Un_Ecart',      $ligneRec->A_Un_Ecart,      editable: false, caption: 'Écart ?'),
        );
    }
}
?>
