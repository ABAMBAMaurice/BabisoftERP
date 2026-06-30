<?php
class ArticlesList extends Page {
    public function __construct() {
        parent::__construct(61000, 'ArticlesList', PagesType::List, 'Articles');
        $this->sourceTable = new Article();
        $this->cardPageID  = 61001;
        $this->setAction();
        $this->layout();
    }

    public function OnAfterGetRecord(Table &$record): void {
        $record->CalcFields('Stock_Actuel', 'Valeur_Stock', 'Nb_Mouvements');
    }

    function setAction(): void {
        $this->actions(
            name: 'ValoriserStock',
            icon: 'chart',
            caption: 'Valorisation du stock',
            onAction: function() {
                $valorisation = ValorisationManagement::getValorisationGlobale(date('Y-m-d'));
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'data' => $valorisation]));
            }
        );

        $this->actions(
            name: 'ArticlesEnRupture',
            icon: 'alert',
            caption: 'Articles en rupture / alerte',
            onAction: function() {
                $ruptures = StockManagement::getArticlesEnRupture();
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'data' => $ruptures]));
            }
        );

        $this->actions(
            name: 'NouvelEntrepot',
            icon: 'plus',
            caption: 'Gérer les entrepôts',
            onAction: function() {
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'redirect' => 'Entrepots']));
            }
        );
    }

    function layout(): void {
        $this->repeater('articles', 'Catalogue des articles',
            new PageField('Code',          $this->rec->Code,          editable: false, caption: 'Code'),
            new PageField('Description',   $this->rec->Description,   editable: true,  caption: 'Description'),
            new PageField('Categorie_Code',$this->rec->Categorie_Code,editable: true,  caption: 'Catégorie'),
            new PageField('UM_Code',       $this->rec->UM_Code,       editable: true,  caption: 'UM'),
            new PageField('Type',          $this->rec->Type,          editable: false, caption: 'Type'),
            new PageField('Methode_Valorisation',$this->rec->Methode_Valorisation, editable: false, caption: 'Méthode'),
            new PageField('CMUP_Actuel',   $this->rec->CMUP_Actuel,   editable: false, caption: 'CMUP'),
            new PageField('Prix_Vente_HT', $this->rec->Prix_Vente_HT, editable: true,  caption: 'Prix vente HT'),
            new PageField('Stock_Actuel',  $this->rec->Stock_Actuel,  editable: false, caption: 'Stock'),
            new PageField('Valeur_Stock',  $this->rec->Valeur_Stock,  editable: false, caption: 'Valeur (FCFA)'),
            new PageField('Stock_Min',     $this->rec->Stock_Min,     editable: true,  caption: 'Stock min'),
            new PageField('Est_Actif',     $this->rec->Est_Actif,     editable: true,  caption: 'Actif'),
        );
    }
}
?>
