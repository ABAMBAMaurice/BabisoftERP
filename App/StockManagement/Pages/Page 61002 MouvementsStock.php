<?php
/**
 * Saisie et validation des mouvements de stock.
 * Type Document : une pièce = un document de mouvement.
 *
 * Entrées, sorties, transferts inter-entrepôts.
 */
class MouvementsStock extends Page {
    public function __construct() {
        parent::__construct(61002, 'MouvementsStock', PagesType::Document, 'Mouvements de stock');
        $this->sourceTable = new MouvementStockProvisoire();
        $this->setAction();
        $this->layout();
    }

    function setAction(): void {
        $this->actions(
            name: 'ValiderMouvement',
            icon: 'check',
            caption: 'Valider le mouvement',
            style: 'inverse-success',
            confirm: 'Valider ce mouvement ? Le stock sera mis à jour immédiatement.',
            onAction: function() {
                StockManagement::validerMouvement(
                    $this->rec->Document_Type->_value,
                    $this->rec->Document_No->_value
                );
                $this->Message("Mouvement validé. Stock mis à jour.");
            }
        );

        $this->actions(
            name: 'VerifierStock',
            icon: 'search',
            caption: 'Vérifier disponibilité',
            onAction: function() {
                $stock = StockManagement::getStock(
                    $this->rec->Article_Code->_value,
                    $this->rec->Entrepot_Code->_value
                );
                $this->Message("Stock disponible : ".$stock." ".$this->rec->Article_Code->_value);
            }
        );
    }

    function layout(): void {
        $this->group('Entete', 'Document de mouvement',
            new PageField('Document_Type',     $this->rec->Document_Type,     editable: true,  caption: 'Type'),
            new PageField('Document_No',       $this->rec->Document_No,       editable: true,  caption: 'N° document'),
            new PageField('Date_Mouvement',    $this->rec->Date_Mouvement,    editable: true,  caption: 'Date'),
        );

        $this->repeater('lignes', 'Lignes de mouvement',
            new PageField('Ligne_No',          $this->rec->Ligne_No,          editable: false, caption: 'N°'),
            new PageField('Article_Code',      $this->rec->Article_Code,      editable: true,  caption: 'Article'),
            new PageField('Description',       $this->rec->Description,       editable: true),
            new PageField('Type_Mouvement',    $this->rec->Type_Mouvement,    editable: true,  caption: 'Type mvt'),
            new PageField('Entrepot_Code',     $this->rec->Entrepot_Code,     editable: true,  caption: 'Entrepôt'),
            new PageField('Emplacement_Code',  $this->rec->Emplacement_Code,  editable: true,  caption: 'Emplacement'),
            new PageField('Entrepot_Dest_Code',$this->rec->Entrepot_Dest_Code,editable: true,  caption: 'Entrepôt dest.'),
            new PageField('Quantite',          $this->rec->Quantite,          editable: true,  caption: 'Quantité'),
            new PageField('Cout_Unitaire',     $this->rec->Cout_Unitaire,     editable: true,  caption: 'Coût unitaire'),
            new PageField('No_Lot',            $this->rec->No_Lot,            editable: true,  caption: 'N° lot'),
            new PageField('No_Serie',          $this->rec->No_Serie,          editable: true,  caption: 'N° série'),
        );
    }
}
?>
