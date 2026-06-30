<?php
/**
 * MetaPage — page pilotée par un descripteur PHP, sans sous-classe dédiée.
 *
 * Étend Page directement et délègue tout le rendu, la sérialisation et
 * l'enregistrement dans Views à la classe Page existante.
 * Aucune logique de rendu dupliquée ici.
 *
 * Usage (dans une route) :
 *   $page = new MetaPage(PageRegistry::findById(62003));
 *   $page->rec->get('CMD-000001');   // Card / Document
 *   PageOpen($page);                 // utilise Page::__toString() existant
 *
 * Pour une List :
 *   $page->rec->FindAll();
 *   PageOpen($page);
 */
class MetaPage extends Page {

    private array $_calcFieldNames = [];

    public function __construct(array $descriptor) {
        parent::__construct(
            $descriptor['id'],
            $descriptor['name'],
            PagesType::from($descriptor['type']),
            $descriptor['caption']
        );

        // ── CalcFields ────────────────────────────────────────────────────
        $this->_calcFieldNames = $descriptor['calcFields'] ?? [];
        foreach ($descriptor['layout'] as $group) {
            foreach ($group['fields'] ?? [] as $f) {
                if (!empty($f['flowField'])) $this->_calcFieldNames[] = $f['field'];
            }
        }
        $this->_calcFieldNames = array_values(array_unique($this->_calcFieldNames));

        // ── Source table → déclenche registerInViews() dans Page::__set ──
        if (!empty($descriptor['sourceTable']))
            $this->sourceTable = new ($descriptor['sourceTable'])();

        // ── Navigation & droits ───────────────────────────────────────────
        if (!empty($descriptor['cardPage']))    $this->cardPageID  = $descriptor['cardPage'];
        if (!($descriptor['editable']    ?? true)) $this->editable    = false;
        if (!($descriptor['allowInsert'] ?? true)) $this->AllowInsert = false;
        if (!($descriptor['allowDelete'] ?? true)) $this->AllowDelete = false;

        // ── Layout → même appels qu'une sous-classe manuelle ─────────────
        foreach ($descriptor['layout'] as $group) {
            $this->_buildGroup($group);
        }

        // ── Actions ───────────────────────────────────────────────────────
        foreach ($descriptor['actions'] ?? [] as $action) {
            $this->_buildAction($action);
        }
    }

    /**
     * Appelé par Page::show() pour chaque enregistrement.
     * Charge les FlowFields déclarés dans le descripteur.
     */
    public function OnAfterGetRecord(Table &$record): void {
        if ($this->_calcFieldNames)
            $record->CalcFields(...$this->_calcFieldNames);
    }

    // ── Construction du layout ────────────────────────────────────────────

    private function _buildGroup(array $group): void {
        // Pour un repeater avec table liée (ex: LigneVente sur CommandeCard),
        // on instancie cette table pour récupérer les métadonnées des champs.
        $srcRec = !empty($group['sourceTable'])
            ? new ($group['sourceTable'])()
            : $this->rec;

        $fields = [];
        foreach ($group['fields'] ?? [] as $fm) {
            try {
                $fields[] = new PageField(
                    name:     $fm['field'],
                    source:   $srcRec->{$fm['field']},
                    editable: $fm['editable'] ?? true,
                    caption:  $fm['caption']  ?? null,
                );
            } catch (\Throwable) {
                // Champ introuvable — skip silencieux
            }
        }

        if (empty($fields)) return;

        $caption = $group['caption'] ?? $group['name'];

        if (($group['type'] ?? 'group') === 'repeater') {
            $this->repeater($group['name'], $caption, ...$fields);
        } else {
            $this->group($group['name'], $caption, ...$fields);
        }
    }

    // ── Construction des actions ──────────────────────────────────────────

    private function _buildAction(array $meta): void {
        $self = $this;

        $this->actions(
            name:     $meta['name'],
            icon:     $meta['icon']    ?? null,
            caption:  $meta['caption'],
            style:    $meta['style']   ?? 'inverse-dark',
            confirm:  $meta['confirm'] ?? null,
            onAction: function() use ($meta, $self) {
                // Valeurs du record courant (pour source: 'record')
                $record = [];
                try {
                    foreach ($self->rec->_fields as $f) $record[$f->_name] = $f->_value;
                } catch (\Throwable) {}

                $req = array_merge(
                    $_GET, $_POST,
                    json_decode(file_get_contents('php://input'), true) ?? [],
                    ['record' => $record]
                );

                $type = $meta['type'] ?? 'codeunit';

                // ── navigate ──────────────────────────────────────────────
                if ($type === 'navigate') {
                    $params = [];
                    foreach ($meta['params'] ?? [] as $key => $def) {
                        $params[$key] = MetaPage::_param($def, $req);
                    }
                    header('Content-Type: application/json');
                    die(json_encode(['status' => 200, 'navigate' => [
                        'pageId' => $meta['pageId'] ?? 0,
                        'params' => $params,
                    ]], JSON_UNESCAPED_UNICODE));
                }

                // ── codeunit & report ─────────────────────────────────────
                [$class, $method] = explode('::', $meta['handler'], 2);
                $args = array_map(
                    fn($d) => MetaPage::_param($d, $req),
                    $meta['params'] ?? []
                );

                if ($type === 'report') {
                    $result = $class::$method(...$args);
                    header('Content-Type: application/json');
                    die(json_encode(['status' => 200, 'data' => $result], JSON_UNESCAPED_UNICODE));
                } else {
                    // codeunit → exécute et retourne message via Page::Message()
                    $class::$method(...$args);
                    $self->Message($meta['successMessage'] ?? 'Opération effectuée avec succès.');
                }
            }
        );
    }

    /**
     * Résout un paramètre d'action selon sa source.
     *   record  → valeur du champ sur l'enregistrement courant
     *   post    → valeur POST/JSON, avec default optionnel (format date PHP)
     *   literal → valeur constante
     *   query   → paramètre GET
     */
    public static function _param(array $def, array $req): mixed {
        return match($def['source'] ?? 'post') {
            'record'  => $req['record'][$def['field'] ?? ''] ?? null,
            'post'    => $req[$def['param']  ?? '']
                            ?? (isset($def['default']) ? date($def['default']) : null),
            'literal' => $def['value'] ?? null,
            'query'   => $_GET[$def['param'] ?? ''] ?? null,
            default   => null,
        };
    }
}
?>
