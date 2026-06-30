<?php
/**
 * Routes.php — BabiSoft Framework
 * API REST complète — tous modules
 */

header('Content-Type: application/json; charset=utf-8');

/**
 * Retourne une instance de page appropriée :
 *   - nativeClass → instancie directement la classe PHP (CalcFields, actions closures)
 *   - sinon        → MetaPage pilotée par descripteur
 */
function _pageInstance(array $desc): Page {
    $cls = $desc['nativeClass'] ?? null;
    if ($cls && class_exists($cls)) return new $cls();
    return new MetaPage($desc);
}

// ── Helper : authentification requise ────────────────────────────
function requireAuth(): void {
    AuthenticationManagement::auth(getAthorizationToken());
}

// ══════════════════════════════════════════════════════════════
//  HOME
// ══════════════════════════════════════════════════════════════
App::route('GET', '/', function () {
    header('location: public/erp/index.html');
});

// ══════════════════════════════════════════════════════════════
//  AUTH  /auth/...
// ══════════════════════════════════════════════════════════════

// Connexion (email + password → session token)
App::route('POST', '/auth/login', function () {
    echo UserManagement::connexion(getSecureJsonInput());
});

// Inscription — crée un nouveau tenant + utilisateur admin
App::route('POST', '/auth/signup', function () {
    echo UserManagement::signUp(getSecureJsonInput());
});

// Déconnexion — invalide la session
App::route('POST', '/auth/logout', function () {
    echo UserManagement::deconnexion(getAthorizationToken());
});

// Profil utilisateur courant + info tenant
App::route('GET', '/auth/me', function () {
    echo UserManagement::getCurrentUser();
});

// Changement de mot de passe
App::route('POST', '/auth/password', function () {
    echo UserManagement::changePassword(getSecureJsonInput());
});

// ══════════════════════════════════════════════════════════════
//  INSTALL  /install
//  À appeler une seule fois après ?SystemUpdateSchema
//  Crée les plans par défaut si absents.
// ══════════════════════════════════════════════════════════════
App::route('GET', '/install', function () {
    $defaultPlans = [
        [
            'Code' => 'Essai', 'Nom' => 'Essai gratuit (14 jours)',
            'Nb_users_max' => '2', 'Nb_projets_max' => '0', 'Montant_mensuel' => '0',
            'Comptabilite' => '1', 'Stock' => '1', 'Ventes' => '1',
            'Description' => 'Accès complet pendant 14 jours, sans carte bancaire.',
        ],
        [
            'Code' => 'Standard', 'Nom' => 'Standard',
            'Nb_users_max' => '10', 'Nb_projets_max' => '0', 'Montant_mensuel' => '29.99',
            'Comptabilite' => '1', 'Stock' => '1', 'Ventes' => '1',
            'Description' => 'Jusqu\'à 10 utilisateurs, tous les modules inclus.',
        ],
        [
            'Code' => 'Premium', 'Nom' => 'Premium',
            'Nb_users_max' => '0', 'Nb_projets_max' => '0', 'Montant_mensuel' => '99.99',
            'Comptabilite' => '1', 'Stock' => '1', 'Ventes' => '1',
            'Description' => 'Utilisateurs illimités, support prioritaire.',
        ],
    ];

    $created = [];
    foreach ($defaultPlans as $p) {
        $plan = new PlanAbonnement();
        if (!$plan->get($p['Code'])) {
            foreach ($p as $field => $value) $plan->Validate($field, $value);
            $plan->Insert();
            $created[] = $p['Code'];
        }
    }

    // Vérifier si des utilisateurs existent déjà
    $user = new User();
    $hasUsers = $user->FindFirst();

    db->commit();
    echo json_encode([
        'status'        => 200,
        'message'       => 'Installation terminée.',
        'plans_created' => $created,
        'has_users'     => (bool)$hasUsers,
        'next_step'     => $hasUsers
            ? 'Connectez-vous sur /public/login/index.html'
            : 'Créez votre premier compte sur /public/login/index.html',
    ], JSON_UNESCAPED_UNICODE);
});

// ══════════════════════════════════════════════════════════════
//  PAGES PILOTÉES PAR MÉTADONNÉES  /meta/pages/...
//  Mode 1 : fichier page.NNN.Name.php retournant return []
//  Mode 2 : classe PHP étendant Page dans App/{module}/Pages/
//  Aucune autre modification nécessaire dans ce fichier.
// ══════════════════════════════════════════════════════════════

// Catalogue de toutes les pages disponibles
App::route('GET', '/meta/pages', function () {
    requireAuth();
    $pages = array_map(fn($d) => [
        'id'      => $d['id'],
        'name'    => $d['name'],
        'type'    => $d['type'],
        'caption' => $d['caption'],
    ], PageRegistry::all());
    echo json_encode(['status' => 200, 'data' => $pages], JSON_UNESCAPED_UNICODE);
});

// Schéma d'une page — layout, colonnes, captions, actions (sans handlers internes)
App::route('GET', '/meta/pages/{id}/schema', function (string $id) {
    requireAuth();
    $desc = PageRegistry::findById((int)$id);
    if (!$desc) Error404("Page '".$id."' introuvable.");

    $actions = array_map(fn($a) => [
        'name'    => $a['name'],
        'caption' => $a['caption'],
        'icon'    => $a['icon']    ?? null,
        'style'   => $a['style']   ?? 'inverse-dark',
        'type'    => $a['type']    ?? 'codeunit',
        'confirm' => $a['confirm'] ?? null,
    ], $desc['actions'] ?? []);

    echo json_encode([
        'status' => 200,
        'schema' => [
            'id'           => $desc['id'],
            'name'         => $desc['name'],
            'type'         => $desc['type'],
            'caption'      => $desc['caption'],
            'editable'     => $desc['editable']    ?? true,
            'allowInsert'  => $desc['allowInsert'] ?? true,
            'allowDelete'  => $desc['allowDelete'] ?? true,
            'cardPage'     => $desc['cardPage']    ?? null,
            'key'          => $desc['key']         ?? [],
            'searchFields' => $desc['searchFields'] ?? [],
            'pageSize'     => $desc['pageSize']    ?? 50,
            'layout'       => $desc['layout']      ?? [],
            'actions'      => $actions,
        ],
    ], JSON_UNESCAPED_UNICODE);
});

// Créer un nouvel enregistrement
App::route('POST', '/meta/pages/{id}/record', function (string $id) {
    requireAuth();
    $desc = PageRegistry::findById((int)$id);
    if (!$desc) Error404("Page '".$id."' introuvable.");
    if (!($desc['allowInsert'] ?? true)) {
        http_response_code(403);
        echo json_encode(['status' => 403, 'message' => 'Insertion non autorisée sur cette page.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $page = _pageInstance($desc);
    foreach ($body as $field => $value) {
        try { $page->rec->Validate($field, $value); } catch (\Throwable) {}
    }
    $page->rec->Insert(true);
    echo json_encode(['status' => 200, 'message' => 'Enregistrement créé avec succès.'], JSON_UNESCAPED_UNICODE);
});

// Modifier un enregistrement existant
App::route('PUT', '/meta/pages/{id}/record', function (string $id) {
    requireAuth();
    $desc = PageRegistry::findById((int)$id);
    if (!$desc) Error404("Page '".$id."' introuvable.");
    if (!($desc['editable'] ?? true)) {
        http_response_code(403);
        echo json_encode(['status' => 403, 'message' => 'Modification non autorisée sur cette page.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $page = _pageInstance($desc);
    $keyValues = array_values(array_map(fn($k) => $body[$k] ?? null, $desc['key'] ?? []));
    $keyValues = array_filter($keyValues, fn($v) => $v !== null);
    if (!$keyValues) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'Clé primaire manquante dans le corps de la requête.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $page->rec->get(...array_values($keyValues));
    foreach ($body as $field => $value) {
        try { $page->rec->Validate($field, $value); } catch (\Throwable) {}
    }
    $page->rec->Modify(true);
    echo json_encode(['status' => 200, 'message' => 'Enregistrement modifié avec succès.'], JSON_UNESCAPED_UNICODE);
});

// Supprimer un enregistrement
App::route('DELETE', '/meta/pages/{id}/record', function (string $id) {
    requireAuth();
    $desc = PageRegistry::findById((int)$id);
    if (!$desc) Error404("Page '".$id."' introuvable.");
    if (!($desc['allowDelete'] ?? true)) {
        http_response_code(403);
        echo json_encode(['status' => 403, 'message' => 'Suppression non autorisée sur cette page.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $page = _pageInstance($desc);
    $keyValues = array_values(array_map(fn($k) => $body[$k] ?? null, $desc['key'] ?? []));
    $keyValues = array_filter($keyValues, fn($v) => $v !== null);
    if (!$keyValues) {
        http_response_code(400);
        echo json_encode(['status' => 400, 'message' => 'Clé primaire manquante.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $page->rec->get(...array_values($keyValues));
    $page->rec->Delete(true);
    echo json_encode(['status' => 200, 'message' => 'Enregistrement supprimé.'], JSON_UNESCAPED_UNICODE);
});

// Rendu d'une page (GET) — List, Card, Document, RoleCenter
App::route('GET', '/meta/pages/{id}', function (string $id) {
    requireAuth();
    $desc = PageRegistry::findById((int)$id);
    if (!$desc) Error404("Page métadonnée '".$id."' introuvable.");

    $page = _pageInstance($desc);

    // RoleCenter — rendu direct des widgets
    if ($desc['type'] === 'RoleCenter') {
        PageOpen($page);
        return;
    }

    // Filtres optionnels depuis la query string (?filters[Statut]=Actif)
    foreach ($_GET['filters'] ?? [] as $field => $value) {
        $page->rec->setRange($field, $value);
    }

    if (in_array($desc['type'], ['List', 'ListPart'], true)) {
        // List → FindAll() + PageOpen()
        $page->update(false);
    } else {
        // Card / Document → charger l'enregistrement par clé primaire
        $keyValues = array_values(array_map(fn($k) => $_GET[$k] ?? null, $desc['key'] ?? []));
        $keyValues = array_filter($keyValues, fn($v) => $v !== null);
        if ($keyValues) {
            $page->rec->get(...array_values($keyValues));
        } else {
            $page->rec->FindFirst();
        }
        PageOpen($page);
    }
});

// Exécution d'une action nommée
App::route('POST', '/meta/pages/{id}/action/{action}', function (string $id, string $action) {
    requireAuth();
    $desc = PageRegistry::findById((int)$id);
    if (!$desc) Error404("Page métadonnée '".$id."' introuvable.");

    $req = array_merge($_POST, json_decode(file_get_contents('php://input'), true) ?? []);

    $page = _pageInstance($desc);

    // Charger l'enregistrement courant (clé dans body ou query string)
    $keyValues = array_values(array_map(fn($k) => $req[$k] ?? $_GET[$k] ?? null, $desc['key'] ?? []));
    $keyValues = array_filter($keyValues, fn($v) => $v !== null);
    if ($keyValues) {
        try { $page->rec->get(...array_values($keyValues)); } catch (\Throwable) {}
    }

    $actions = $page->actions;
    if (!isset($actions[$action]))
        Error("Action '".$action."' introuvable sur la page '".$desc['name']."'.");

    $actions[$action]->onAction();
});

?>
