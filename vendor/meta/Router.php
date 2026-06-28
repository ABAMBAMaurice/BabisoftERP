<?php

//ini_set('display_errors', 1);

require_once ('vendor/meta/global.config.php');
require_once('vendor/meta/API/App.php');
require_once ('vendor/app.main.php');
require_once ('vendor/configs/configs.php');

// Chargement conditionnel des fichiers de sécurité
$securityLoaded = false;
if (file_exists('vendor/security/security.config.php') && file_exists('vendor/security/Security.class.php')) {
    try {
        require_once ('vendor/security/security.config.php');
        require_once ('vendor/security/Security.class.php');
        $securityLoaded = true;
    } catch (Exception $e) {
        $securityLoaded = false;
    }
}

//header('content-type:application/json');

// Application des headers de sécurité si disponibles
if ($securityLoaded && class_exists('SecurityHeaders')) {
    SecurityHeaders::setSecurityHeaders();
    if (defined('ALLOWED_ORIGINS'))
        SecurityHeaders::setCorsHeaders(ALLOWED_ORIGINS);
}

// Répondre aux preflight CORS (OPTIONS) avant toute route
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}


// Fonction sécurisée pour récupérer le token d'autorisation
function getAthorizationToken() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    
    if (empty($authHeader)) {
        return null;
    }
    
    // Extraction du token Bearer
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return trim($matches[1]);
    }
    
    return $authHeader;
}

// Fonction pour valider les données JSON d'entrée
function getSecureJsonInput() {
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        return null;
    }
    
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Log seulement si la classe Security existe
        if (class_exists('Security') && method_exists('Security', 'logSecurityEvent')) {
            Security::logSecurityEvent('invalid_json_input', [
                'error' => json_last_error_msg(),
                'input_length' => strlen($input)
            ]);
        }
        return null;
    }
    
    return $data;
}

/***
 *  Route pour index
 */
  
//region Routes d'authentification
    App::route('GET', '/X-CSRF-Token', function () {
        header('Content-Type: application/json');        

        // Générer le token CSRF si Security est disponible
        if (class_exists('Security') && method_exists('Security', 'generateCSRFToken')) {
            $csrfToken = AuthenticationManagement::generateCSRFToken($_SERVER['REMOTE_ADDR']);
        } else {
            // Fallback simple si Security n'est pas disponible
            if (!isset($_SESSION)) {
                session_set_cookie_params(['secure' => true, 'httponly' => true]);
                session_start();
            }
            $csrfToken = bin2hex(random_bytes(32));
            $_SESSION['X-CSRF-Token'] = $csrfToken;
        }
        
        echo json_encode([
            'status' => 200,
            'X-CSRF-Token' => $csrfToken
        ]);

        /*header('Content-Type: application/json');
        // Utilise Security::generateCSRFToken() qui stocke en $_SESSION
        // pour être compatible avec Security::verifyCSRFToken() appelé au login
        $token = class_exists('Security') ? Security::generateCSRFToken() : bin2hex(random_bytes(32));
        echo json_encode([
            'status' => 200,
            'csrf-token' => $token,
            'X-CSRF-Token' => $token
        ]);*/
    });
//endregion

//region routes diveres
    //header("Content-type: application/json");
    try {    
        include('Routes.php');   
    }catch(Exception $e){
        Error($e->getMessage());                       
    }
//endregion

//region not found
    http_response_code(404);
    echo json_encode(array("status" => 404, "message" => 'La ressource demandée n\'existe pas!'));
//endregion

?>