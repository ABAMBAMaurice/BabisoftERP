<?php
class ArticleCard extends Page {
    public function __construct() {
        parent::__construct(61001, 'ArticleCard', PagesType::Card, 'Fiche article');
        $this->sourceTable = new Article();
        $this->setAction();
        $this->layout();
    }

    public function OnAfterGetRecord(Table &$record): void {
        $record->CalcFields('Stock_Actuel', 'Valeur_Stock', 'Nb_Mouvements', 'Stock_Provisoire');
    }

    function setAction(): void {
        $this->actions(
            name: 'RecalculerCMUP',
            icon: 'refresh',
            caption: 'Recalculer CMUP',
            confirm: 'Recalculer le CMUP depuis toutes les écritures ?',
            onAction: function() {
                $cmup = ValorisationManagement::recalculerCMUPComplet($this->rec->Code->_value);
                $this->Message("CMUP recalculé : ".number_format($cmup, 2)." FCFA");
            }
        );

        $this->actions(
            name: 'VoirLots',
            icon: 'package',
            caption: 'Lots',
            onAction: function() {
                $lots = new LotArticle();
                $lots->setRange('Article_Code', $this->rec->Code->_value);
                $lots->FindAll();
                foreach ($lots->recordSet as $lot) $lot->CalcFields('Stock_Lot');
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'data' => $lots->recordSet]));
            }
        );

        $this->actions(
            name: 'VoirMouvements',
            icon: 'list',
            caption: 'Mouvements de stock',
            onAction: function() {
                $ec = new EcritureStock();
                $ec->setRange('Article_Code', $this->rec->Code->_value);
                $ec->FindAll();
                header('Content-Type: application/json');
                die(json_encode(['status' => 200, 'data' => $ec->recordSet]));
            }
        );

        $this->actions(
            name: 'AjusterStock',
            icon: 'edit',
            caption: 'Ajustement manuel',
            onAction: function() {
                $quantite = (float)($_POST['quantite'] ?? 0);
                $motif    = $_POST['motif'] ?? 'Ajustement';
                $entrepot = $_POST['entrepot_code'] ?? '';
                if (!$entrepot) Error("L'entrepôt est obligatoire.");
                StockManagement::ajuster($this->rec->Code->_value, $entrepot, $quantite, $motif);
                $this->Message("Ajustement de ".$quantite." effectué.");
            }
        );
    }

    function layout(): void {
        // ── Identification ──────────────────────────
        $this->group('Identification', 'Identification',
            new PageField('Code',                $this->rec->Code,                editable: false),
            new PageField('Description',         $this->rec->Description,         editable: true),
            new PageField('Description2',        $this->rec->Description2,        editable: true),
            new PageField('Type',                $this->rec->Type,                editable: true),
            new PageField('Categorie_Code',      $this->rec->Categorie_Code,      editable: true,  caption: 'Catégorie'),
            new PageField('UM_Code',             $this->rec->UM_Code,             editable: true,  caption: 'Unité de mesure'),
            new PageField('Code_Barre',          $this->rec->Code_Barre,          editable: true),
            new PageField('Reference_Fournisseur',$this->rec->Reference_Fournisseur, editable: true),
        );

        // ── Suivi ───────────────────────────────────
        $this->group('Suivi', 'Traçabilité',
            new PageField('Suivi_Lot',           $this->rec->Suivi_Lot,           editable: true),
            new PageField('Suivi_Serie',         $this->rec->Suivi_Serie,         editable: true),
            new PageField('DLUO_Obligatoire',    $this->rec->DLUO_Obligatoire,    editable: true),
        );

        // ── Valorisation ────────────────────────────
        $this->group('Valorisation', 'Valorisation & Prix',
            new PageField('Methode_Valorisation',$this->rec->Methode_Valorisation,editable: true),
            new PageField('Cout_Standard',       $this->rec->Cout_Standard,       editable: true),
            new PageField('CMUP_Actuel',         $this->rec->CMUP_Actuel,         editable: false),
            new PageField('Prix_Achat_Dernier',  $this->rec->Prix_Achat_Dernier,  editable: false),
            new PageField('Prix_Vente_HT',       $this->rec->Prix_Vente_HT,       editable: true),
            new PageField('Marge_Pct',           $this->rec->Marge_Pct,           editable: true),
        );

        // ── Seuils ──────────────────────────────────
        $this->group('Seuils', 'Gestion des seuils',
            new PageField('Stock_Min',           $this->rec->Stock_Min,           editable: true),
            new PageField('Stock_Max',           $this->rec->Stock_Max,           editable: true),
            new PageField('Stock_Reappro',       $this->rec->Stock_Reappro,       editable: true),
            new PageField('Delai_Appro_Jours',   $this->rec->Delai_Appro_Jours,  editable: true),
        );

        // ── Statistiques (FlowFields) ────────────────
        $this->group('Statistiques', 'Stock actuel',
            new PageField('Stock_Actuel',        $this->rec->Stock_Actuel,        editable: false),
            new PageField('Valeur_Stock',        $this->rec->Valeur_Stock,        editable: false),
            new PageField('Stock_Provisoire',    $this->rec->Stock_Provisoire,    editable: false),
            new PageField('Nb_Mouvements',       $this->rec->Nb_Mouvements,       editable: false),
        );
    }
}
?>
