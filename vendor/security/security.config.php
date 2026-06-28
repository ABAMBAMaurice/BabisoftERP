<?php
/**
 * Configuration de sécurité de l'application
 */

// Désactiver l'affichage des erreurs en production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configuration de session sécurisée
//ini_set('session.cookie_httponly', 1);
// secure uniquement en HTTPS pour ne pas bloquer le développement local HTTP
$_isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
//ini_set('session.cookie_secure', $_isHttps ? '1' : '0');
//ini_set('session.use_strict_mode', 1);
//ini_set('session.cookie_samesite', 'Lax'); // Lax au lieu de Strict pour les requêtes fetch

// Configuration PHP sécurisée
ini_set('expose_php', 0);
ini_set('allow_url_fopen', 0);
ini_set('allow_url_include', 0);
ini_set('file_uploads', 1);
ini_set('upload_max_filesize', '2M');
ini_set('post_max_size', '8M');
ini_set('max_execution_time', 30);
ini_set('memory_limit', '128M');

// Headers de sécurité
class SecurityHeaders {
    public static function setSecurityHeaders() {
        // Protection contre le clickjacking
        header('X-Frame-Options: DENY');
        
        // Protection contre le MIME sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Protection XSS
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; object-src 'none'; frame-ancestors 'none';");
        
        // HSTS (à activer uniquement en HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Permissions Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // Référer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Désactiver le cache pour les pages sensibles
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
    
    public static function setCorsHeaders($allowedOrigins = []) {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
        }
        // Pas de wildcard avec credentials — si l'origine n'est pas listée, pas d'en-tête CORS
        // (les requêtes same-origin fonctionnent sans en-têtes CORS)

        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-CSRF-Token, csrf-token, X-Mobile-Client");
        header("Access-Control-Max-Age: 86400");
    }
}

// Configuration des domaines autorisés
define('ALLOWED_ORIGINS', [
    'http://localhost',
    'http://localhost:80',
    'http://localhost:3000',
    'http://localhost:8080',
    'http://localhost:8000',
    'http://127.0.0.1',
    'http://127.0.0.1:80',
    'http://127.0.0.1:8080',
    'https://bca.bambasolutions.fr',
    'https://app.tottem-boutique.com',
    'https://kiliebtp.ci',
]);

// Clé secrète pour CSRF (à changer en production)
define('CSRF_SECRET', 'votre-cle-secrete-csrf-unique-et-longue-2024');

// Clé de chiffrement pour les données sensibles
define('ENCRYPTION_KEY', 'votre-cle-de-chiffrement-unique-et-longue-2024');

// Configuration de hashage des mots de passe
define('PASSWORD_HASH_ALGO', PASSWORD_ARGON2ID);
define('PASSWORD_HASH_OPTIONS', [
    'memory_cost' => 65536, // 64 MB
    'time_cost' => 4,       // 4 iterations
    'threads' => 3          // 3 threads
]);

// Limite de tentatives de connexion
define('MAX_LOGIN_ATTEMPTS', 10);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

?>
