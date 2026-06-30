<?php
/**
 * PageRegistry — registre auto-découvrant des pages.
 *
 * Découverte automatique (deux modes) :
 *   Mode 1 — Descripteurs : scanne App/{module}/Pages/page.NNN.Name.php retournant return []
 *   Mode 2 — Classes PHP  : scanne toutes les classes déclarées étendant Page
 *             (chargées par vendor/app.main.php avant l'exécution des routes)
 *
 * Ajout d'un nouveau module :
 *   → Créer une classe PHP étendant Page dans App/MonModule/Pages/Page NNN Name.php
 *   → Aucune autre modification. Le moteur la découvre et la sert automatiquement.
 *
 * Coexistence : si un descripteur page.*.php et une classe PHP ont le même id,
 *   le descripteur prend la priorité (chargé en premier).
 */
class PageRegistry {

    private static array $_byId   = [];
    private static array $_byName = [];
    private static bool  $_discovered = false;

    // ── Découverte ────────────────────────────────────────────────────────

    public static function discover(): void {
        if (self::$_discovered) return;

        // ── Mode 1 : fichiers descripteurs page.*.php (return []) ─────────
        $base     = rtrim(defined('BASE_PATH') ? BASE_PATH : getcwd(), '/\\');
        $patterns = [
            $base . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . '*'
                  . DIRECTORY_SEPARATOR . 'Pages' . DIRECTORY_SEPARATOR . 'page.*.php',
            $base . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . '*'
                  . DIRECTORY_SEPARATOR . '*'
                  . DIRECTORY_SEPARATOR . 'Pages' . DIRECTORY_SEPARATOR . 'page.*.php',
        ];

        foreach ($patterns as $pattern) {
            foreach (glob($pattern) ?: [] as $file) {
                try {
                    $d = require $file;
                    if (!is_array($d) || !isset($d['id'])) continue;
                    PageDescriptor::validate($d);
                    $d = PageDescriptor::normalize($d);
                    self::_store($d);
                } catch (\Throwable $e) {
                    error_log('[PageRegistry] Échec chargement "'.$file.'" : '.$e->getMessage());
                }
            }
        }

        // ── Mode 2 : classes PHP étendant Page (chargées par app.main.php) ─
        self::_discoverNativeClasses();

        self::$_discovered = true;
    }

    // ── Lookup ────────────────────────────────────────────────────────────

    public static function findById(int $id): ?array {
        self::discover();
        return self::$_byId[$id] ?? null;
    }

    public static function findByName(string $name): ?array {
        self::discover();
        return self::$_byName[$name] ?? null;
    }

    public static function has(int $id): bool {
        self::discover();
        return isset(self::$_byId[$id]);
    }

    /** Retourne tous les descripteurs découverts. */
    public static function all(): array {
        self::discover();
        return array_values(self::$_byId);
    }

    // ── Enregistrement programmatique ─────────────────────────────────────

    public static function register(array $descriptor): void {
        PageDescriptor::validate($descriptor);
        $d = PageDescriptor::normalize($descriptor);
        self::_store($d);
    }

    /** Réinitialise le registre (utile pour les tests). */
    public static function reset(): void {
        self::$_byId      = [];
        self::$_byName    = [];
        self::$_discovered = false;
    }

    // ── Interne ───────────────────────────────────────────────────────────

    private static function _store(array $d): void {
        self::$_byId[(int)$d['id']]        = $d;
        self::$_byName[(string)$d['name']] = &self::$_byId[(int)$d['id']];
    }

    /**
     * Parcourt toutes les classes déclarées pour enregistrer celles qui
     * étendent Page et n'ont pas encore été enregistrées via un descripteur.
     */
    private static function _discoverNativeClasses(): void {
        foreach (get_declared_classes() as $className) {
            if ($className === 'Page' || $className === 'MetaPage') continue;
            if (!is_subclass_of($className, 'Page')) continue;
            try {
                $page = new $className();
                $pageId = $page->id;
                if (!$pageId) continue;
                if (isset(self::$_byId[$pageId])) continue; // déjà enregistré
                $d = self::_buildDescriptorFromPage($page, $className);
                self::_store($d);
            } catch (\Throwable) {
                // Ignore les classes ne pouvant pas être instanciées isolément
            }
        }
    }

    /**
     * Construit un descripteur à partir d'une instance de Page native.
     * Extrait id, type, caption, layout (groups/repeaters), actions et clés.
     */
    private static function _buildDescriptorFromPage(Page $page, string $className): array {
        // ── Layout ────────────────────────────────────────────────────────
        $layout = [];
        foreach ($page->groups as $name => $g) {
            $isRepeater = ($g->type === 'REPEATER');
            $fields = [];
            foreach ($g->fields as $fname => $pf) {
                $entry = [
                    'field'    => $fname,
                    'caption'  => $pf->caption ?? $fname,
                    'editable' => $pf->editable ?? true,
                ];
                if (!($pf->visible ?? true)) $entry['visible'] = false;
                $fields[] = $entry;
            }
            $layout[] = [
                'type'    => $isRepeater ? 'repeater' : 'group',
                'name'    => $name,
                'caption' => $g->caption ?? $name,
                'fields'  => $fields,
            ];
        }

        // ── Actions ───────────────────────────────────────────────────────
        $actions = [];
        foreach ($page->actions as $aname => $ctrl) {
            $confirm = $ctrl->confirm;
            $a = [
                'name'    => $aname,
                'caption' => $ctrl->caption ?? $aname,
                'icon'    => $ctrl->icon    ?? null,
                'style'   => $ctrl->style   ?? 'inverse-dark',
                'type'    => 'native',
            ];
            if ($confirm && $confirm !== 'null') $a['confirm'] = $confirm;
            $actions[] = $a;
        }

        // ── Clés primaires ────────────────────────────────────────────────
        $keys = [];
        $rec  = $page->rec;
        if ($rec !== null) {
            foreach ($rec->keysList as $kf) {
                $keys[] = $kf->_name;
            }
        }

        $raw = [
            'id'          => $page->id,
            'name'        => $page->pageName,
            'type'        => $page->type->value,
            'caption'     => $page->Caption,
            'sourceTable' => $rec !== null ? get_class($rec) : null,
            'editable'    => $page->editable,
            'allowInsert' => $page->AllowInsert,
            'allowDelete' => $page->AllowDelete,
            'cardPage'    => $page->cardPageID ?: null,
            'key'         => $keys,
            'searchFields' => [],
            'layout'      => $layout,
            'actions'     => $actions,
            'nativeClass' => $className,
        ];

        // Applique les valeurs par défaut de PageDescriptor
        return PageDescriptor::normalize($raw);
    }
}
?>
