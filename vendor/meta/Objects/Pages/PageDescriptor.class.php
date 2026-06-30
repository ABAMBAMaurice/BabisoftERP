<?php
/**
 * PageDescriptor — validateur et normalisateur de descripteurs de pages.
 *
 * Un descripteur est un tableau PHP retourné par un fichier page.NNN.Name.php
 * situé dans App/°/Pages/. Il décrit entièrement une page (type, source, layout,
 * actions) sans aucune classe PHP supplémentaire.
 **/
class PageDescriptor {

    private static array $REQUIRED    = ['id', 'name', 'type', 'caption'];
    private static array $VALID_TYPES = ['List','Card','Document','RoleCenter','ListPart'];

    // ── Validation ────────────────────────────────────────────────────────

    public static function validate(array $d): void {
        foreach (self::$REQUIRED as $k) {
            if (!isset($d[$k]))
                Error("Descripteur de page invalide : clé '".$k."' manquante"
                    .(isset($d['name']) ? " (page '".$d['name']."')" : '').'.');
        }
        if (!in_array($d['type'], self::$VALID_TYPES, true))
            Error("Type de page inconnu : '".$d['type']."'. Valeurs acceptées : "
                .implode(', ', self::$VALID_TYPES).'.');
        if ($d['type'] !== 'RoleCenter' && empty($d['sourceTable']))
            Error("'sourceTable' est requis pour les pages de type '".$d['type']."'.");
    }

    /**
     * Applique les valeurs par défaut à un descripteur.
     * Doit être appelé après validate().
     */
    public static function normalize(array $d): array {
        return array_merge([
            'editable'       => true,
            'allowInsert'    => true,
            'allowDelete'    => true,
            'allowModify'    => true,
            'calcFields'     => [],
            'defaultFilters' => [],
            'defaultSort'    => [],
            'searchFields'   => [],
            'pageSize'       => 50,
            'cardPage'       => null,
            'key'            => [],
            'layout'         => [],
            'actions'        => [],
            'sourceTable'    => null,
        ], $d);
    }

    /**
     * Charge un descripteur depuis un fichier PHP qui retourne un tableau.
     */
    public static function fromFile(string $filePath): array {
        if (!file_exists($filePath))
            Error("Fichier descripteur introuvable : ".$filePath);
        $d = require $filePath;
        if (!is_array($d))
            Error("Le fichier '".$filePath."' doit retourner un tableau PHP (return [...]).");
        self::validate($d);
        return self::normalize($d);
    }
}
?>
