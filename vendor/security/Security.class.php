<?php

/**
 * Classe de sécurité complète pour l'application
 */
class Security {
    
    /**
     * Valide et nettoie les données d'entrée
     */
    public static function sanitizeInput($data, $type = 'string') {
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $data);
        }
        
        // Suppression des espaces
        $data = trim($data);
        
        switch ($type) {
            case 'email':
                return filter_var($data, FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var($data, FILTER_SANITIZE_URL);
            case 'html':
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            case 'string':
            default:
                return htmlspecialchars(strip_tags($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    
    /**
     * Valide les données selon des règles spécifiques
     */
    public static function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Vérification requis
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "Le champ $field est requis";
                continue;
            }
            
            if (!empty($value)) {
                // Validation email
                if (isset($rule['email']) && $rule['email'] && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "Le champ $field doit être un email valide";
                }
                
                // Validation longueur minimale
                if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                    $errors[$field] = "Le champ $field doit contenir au moins {$rule['min_length']} caractères";
                }
                
                // Validation longueur maximale
                if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                    $errors[$field] = "Le champ $field ne peut pas dépasser {$rule['max_length']} caractères";
                }
                
                // Validation mot de passe fort
                if (isset($rule['strong_password']) && $rule['strong_password']) {
                    if (!self::isStrongPassword($value)) {
                        $errors[$field] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial";
                    }
                }
                
                // Validation pattern personnalisé
                if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                    $errors[$field] = $rule['pattern_message'] ?? "Le format du champ $field est invalide";
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Vérifie si un mot de passe est fort
     */
    public static function isStrongPassword($password) {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
    }
    
    /**
     * Hache un mot de passe de manière sécurisée
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_HASH_ALGO, PASSWORD_HASH_OPTIONS);
    }

    /**
     * Vérifie un mot de passe haché
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Génère un token CSRF
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION)) {  
            session_set_cookie_params(['secure' => true, 'httponly' => true]);
            session_start();
        }
        if(!isset($_SESSION['csrf-token'])){
            $token = bin2hex(random_bytes(32));
            $_SESSION['csrf-token'] = $token;
            $_SESSION['csrf-token_time'] = time();
        }else
            $token = $_SESSION['csrf-token'];       
        
        return $token;
    }
    
    /**
     * Vérifie un token CSRF
     */
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION)) {
            session_set_cookie_params(['secure' => true, 'httponly' => true]);
            session_start();
        }
        
        if(!isset($token))
            ErrorCode(403, 'Token CSRF non fourni');

        if (!isset($_SESSION['csrf-token'])) {
            ErrorCode(403, 'Token CSRF non défini');
        }
        
        if(!isset($_SESSION['csrf-token_time']))
            ErrorCode(403, 'Token CSRF non valide');
        
        // Vérification de l'expiration (30 minutes)
        if (time() - $_SESSION['csrf-token_time'] > 1800) {
            unset($_SESSION['csrf-token'], $_SESSION['csrf-token_time']);
            ErrorCode(403, 'Token CSRF expiré');
        }
        
        return hash_equals($_SESSION['csrf-token'], $token);
    }

    public static function verifyToken($token) {
        if (!isset($_SESSION)) {
            session_set_cookie_params(['secure' => true, 'httponly' => true]);
            session_start();
        }

        if (!isset($token))
            ErrorCode(403, 'Token non fourni');
        

        return AuthenticationManagement::auth($token);
    }

    /**
     * Génère un token de session sécurisé
     */
    public static function generateSecureToken($length = 64) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Chiffre des données sensibles
     */
    public static function encrypt($data) {
        $key = hash('sha256', ENCRYPTION_KEY, true);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Déchiffre des données
     */
    public static function decrypt($data) {
        $data = base64_decode($data);
        $key = hash('sha256', ENCRYPTION_KEY, true);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }
    
    /**
     * Vérifie les tentatives de connexion
     */
    public static function checkLoginAttempts($identifier) {
        $cacheFile = sys_get_temp_dir() . '/login_attempts_' . md5($identifier);
        
        if (!file_exists($cacheFile)) {
            return true;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        
        if (time() - $data['last_attempt'] > LOGIN_LOCKOUT_TIME) {
            unlink($cacheFile);
            return true;
        }
        
        return $data['attempts'] < MAX_LOGIN_ATTEMPTS;
    }
    
    /**
     * Enregistre une tentative de connexion
     */
    public static function recordLoginAttempt($identifier, $success = false) {
        $cacheFile = sys_get_temp_dir() . '/login_attempts_' . md5($identifier);
        
        if ($success) {
            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
            return;
        }
        
        $attempts = 1;
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            $attempts = $data['attempts'] + 1;
        }
        
        $data = [
            'attempts' => $attempts,
            'last_attempt' => time()
        ];
        
        file_put_contents($cacheFile, json_encode($data));
    }
    
    /**
     * Protection contre les attaques par force brute
     */
    public static function rateLimitCheck($identifier, $maxRequests = 60, $timeWindow = 3600) {
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($identifier);
        
        if (!file_exists($cacheFile)) {
            file_put_contents($cacheFile, json_encode(['count' => 1, 'window_start' => time()]));
            return true;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        
        if (time() - $data['window_start'] > $timeWindow) {
            $data = ['count' => 1, 'window_start' => time()];
        } else {
            $data['count']++;
        }
        
        file_put_contents($cacheFile, json_encode($data));
        
        return $data['count'] <= $maxRequests;
    }
    
    /**
     * Validation de l'origine de la requête
     */
    public static function validateOrigin() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        
        if (empty($origin)) {
            return false;
        }
        
        $parsedOrigin = parse_url($origin, PHP_URL_HOST);
        $allowedHosts = array_map(function($url) {
            return parse_url($url, PHP_URL_HOST);
        }, ALLOWED_ORIGINS);
        
        return in_array($parsedOrigin, $allowedHosts);
    }
    
    /**
     * Log des événements de sécurité
     */
    public static function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'event' => $event,
            'details' => $details
        ];
        
        $logFile = dirname(__FILE__) . '/../security.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Vérifie si l'IP est dans une liste noire
     */
    public static function isBlacklistedIP($ip = null) {
        $ip = $ip ?: ($_SERVER['REMOTE_ADDR'] ?? '');
        
        // Liste d'IPs blacklistées (à adapter selon vos besoins)
        $blacklistedIPs = [
            // Ajouter ici les IPs à bloquer
        ];
        
        return in_array($ip, $blacklistedIPs);
    }
    
    /**
     * Nettoie et valide les uploads de fichiers
     */
    public static function validateFileUpload($file, $allowedTypes = [], $maxSize = 2097152) {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new InvalidArgumentException('Erreur lors de l\'upload du fichier');
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Erreur lors de l\'upload du fichier');
        }
        
        if ($file['size'] > $maxSize) {
            throw new InvalidArgumentException('Le fichier est trop volumineux');
        }
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new InvalidArgumentException('Type de fichier non autorisé');
        }
        
        return true;
    }
}

?>
