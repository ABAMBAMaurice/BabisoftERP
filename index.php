<?php

use Random\Engine\Secure;
if (!isset($_SESSION)) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    session_set_cookie_params([
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

DEFINE("PARENTDIR", substr(dirname(__DIR__),strrpos(dirname(__DIR__),'/')));
DEFINE("DIR", PARENTDIR . '/'.basename(__DIR__,2));
DEFINE('ENV', 'vendor/env.php');
DEFINE('SECURITY_ROOT', 'vendor/security/');


include(ENV);
/**
 * Configuration de sécurité et initialisation sécurisée
 */

// Chargement conditionnel de la configuration de sécurité
$securityEnabled = false;
if (file_exists(SECURITY_ROOT.'security.config.php') && file_exists(SECURITY_ROOT.'Security.class.php')) {
    try {
        require_once(SECURITY_ROOT.'security.config.php');
        require_once(SECURITY_ROOT.'Security.class.php');
        $securityEnabled = true;
    } catch (Exception $e) {
        $securityEnabled = false;
    }
}

// Fallback pour les headers de sécurité si les classes ne sont pas disponibles
if (!$securityEnabled) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    // CORS avec credentials : on utilise l'origine de la requête (pas de wildcard *)
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '') {
        header("Access-Control-Allow-Origin: $origin");
        header("Access-Control-Allow-Credentials: true");
    }
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token, csrf-token, X-Mobile-Client");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    // Répondre immédiatement aux requêtes OPTIONS (preflight)
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit();
    }
} else {
    // Vérification de l'IP blacklistée
    if (class_exists('Security') && method_exists('Security', 'isBlacklistedIP') && Security::isBlacklistedIP()) {
        http_response_code(403);
        die(json_encode(array('status' => '403','message' => 'Accès refusé')));
    }    
    // Application des headers de sécurité
    if (class_exists('SecurityHeaders')) {
        SecurityHeaders::setSecurityHeaders();
        if (defined('ALLOWED_ORIGINS')) {
            SecurityHeaders::setCorsHeaders(ALLOWED_ORIGINS);
        }
    }
}

// Gestion sécurisée des erreurs
function verifierErreurFatale() {
    $erreur = error_get_last();
    if ($erreur !== null && in_array($erreur['type'], [E_ERROR, E_PARSE])) {
        // Log de l'erreur si possible
        if (class_exists('Security') && method_exists('Security', 'logSecurityEvent')) {
            Security::logSecurityEvent('fatal_error', [
                'message' => $erreur['message'],
                'file' => $erreur['file'],
                'line' => $erreur['line']
            ]);
        }
        
        // Réponse générique en production
        http_response_code(500);
       
        die(
            json_encode(array('status' => '500','message' => $erreur['message']))
        );
    }
}
register_shutdown_function("verifierErreurFatale");

function monGestionnaireErreur($errno, $errstr, $errfile, $errline) {
    // Log de l'erreur si possible
    if (class_exists('Security') && method_exists('Security', 'logSecurityEvent')) {
        Security::logSecurityEvent('php_error', [
            'errno' => $errno,
            'errstr' => $errstr,
            'errfile' => $errfile,
            'errline' => $errline
        ]);
    }
    
    // Ne pas exposer les détails des erreurs en production
    return true;
}

set_error_handler("monGestionnaireErreur");



/**
 * GETS THE ROUTE FILE. DO NOT MODIFY
 * Work directly in ROUTES.PHP
 */
require("vendor/meta/Router.php");

    
    


?>