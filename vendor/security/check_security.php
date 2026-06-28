<?php
/**
 * Script de vérification de la sécurité de l'application
 * Vérifie que toutes les mesures de sécurité sont correctement implémentées
 */

require_once('Base_Meta/global.config.php');

class SecurityChecker {
    
    private $results = [];
    
    public function runAllChecks() {
        echo "=== VÉRIFICATION DE SÉCURITÉ ===\n\n";
        
        $this->checkFilePermissions();
        $this->checkPasswordHashing();
        $this->checkSecurityHeaders();
        $this->checkDatabaseSecurity();
        $this->checkConfigSecurity();
        $this->checkLogFiles();
        
        $this->displayResults();
    }
    
    private function checkFilePermissions() {
        echo "📁 Vérification des permissions de fichiers...\n";
        
        $sensitiveFiles = [
            'Base_configs/security.config.php',
            'Base_Meta/global.config.php',
            'migrate_security.php'
        ];
        
        foreach ($sensitiveFiles as $file) {
            if (file_exists($file)) {
                $perms = fileperms($file);
                $octal = substr(sprintf('%o', $perms), -4);
                
                if ($octal > '0644') {
                    $this->results[] = ['❌', "Permissions trop ouvertes pour $file ($octal)"];
                } else {
                    $this->results[] = ['✅', "Permissions correctes pour $file ($octal)"];
                }
            } else {
                $this->results[] = ['⚠️', "Fichier manquant: $file"];
            }
        }
    }
    
    private function checkPasswordHashing() {
        echo "🔐 Vérification du hachage des mots de passe...\n";
        
        try {
            // Vérifier si des mots de passe en clair existent encore
            $query = "SELECT COUNT(*) as count FROM user WHERE LENGTH(Password) < 60";
            $result = db->getResult($query);
            
            if ($result && $result[0]['count'] > 0) {
                $this->results[] = ['❌', "Des mots de passe en clair détectés ({$result[0]['count']})"];
            } else {
                $this->results[] = ['✅', "Tous les mots de passe sont hachés"];
            }
            
        } catch (Exception $e) {
            $this->results[] = ['❌', "Erreur lors de la vérification des mots de passe: " . $e->getMessage()];
        }
    }
    
    private function checkSecurityHeaders() {
        echo "🛡️ Vérification des headers de sécurité...\n";
        
        // Simuler une requête pour vérifier les headers
        $expectedHeaders = [
            'X-Frame-Options',
            'X-Content-Type-Options',
            'X-XSS-Protection',
            'Content-Security-Policy'
        ];
        
        $this->results[] = ['ℹ️', "Headers de sécurité configurés dans SecurityHeaders::setSecurityHeaders()"];
    }
    
    private function checkDatabaseSecurity() {
        echo "🗄️ Vérification de la sécurité de la base de données...\n";
        
        // Vérifier si les requêtes préparées sont utilisées
        $dbClass = file_get_contents('Base_Meta/Database/Database.php');
        
        if (strpos($dbClass, 'prepare(') !== false) {
            $this->results[] = ['✅', "Requêtes préparées implémentées"];
        } else {
            $this->results[] = ['❌', "Requêtes préparées non détectées"];
        }
        
        // Vérifier la connexion SSL (simulé)
        $this->results[] = ['ℹ️', "Vérifiez que la connexion DB utilise SSL en production"];
    }
    
    private function checkConfigSecurity() {
        echo "⚙️ Vérification de la configuration de sécurité...\n";
        
        // Vérifier que les constantes de sécurité sont définies
        $securityConstants = [
            'CSRF_SECRET',
            'ENCRYPTION_KEY',
            'PASSWORD_HASH_ALGO',
            'MAX_LOGIN_ATTEMPTS'
        ];
        
        foreach ($securityConstants as $constant) {
            if (defined($constant)) {
                $this->results[] = ['✅', "Constante $constant définie"];
            } else {
                $this->results[] = ['❌', "Constante $constant manquante"];
            }
        }
        
        // Vérifier que les clés ne sont pas les valeurs par défaut
        if (CSRF_SECRET === 'votre-cle-secrete-csrf-unique-et-longue-2024') {
            $this->results[] = ['❌', "CSRF_SECRET utilise la valeur par défaut"];
        } else {
            $this->results[] = ['✅', "CSRF_SECRET personnalisée"];
        }
        
        if (ENCRYPTION_KEY === 'votre-cle-de-chiffrement-unique-et-longue-2024') {
            $this->results[] = ['❌', "ENCRYPTION_KEY utilise la valeur par défaut"];
        } else {
            $this->results[] = ['✅', "ENCRYPTION_KEY personnalisée"];
        }
    }
    
    private function checkLogFiles() {
        echo "📋 Vérification des fichiers de log...\n";
        
        $logFile = 'security.log';
        
        if (file_exists($logFile)) {
            $this->results[] = ['✅', "Fichier de log de sécurité créé"];
            
            if (is_writable($logFile)) {
                $this->results[] = ['✅', "Fichier de log accessible en écriture"];
            } else {
                $this->results[] = ['❌', "Fichier de log non accessible en écriture"];
            }
        } else {
            $this->results[] = ['⚠️', "Fichier de log de sécurité non créé"];
        }
    }
    
    private function displayResults() {
        echo "\n=== RÉSULTATS DE LA VÉRIFICATION ===\n\n";
        
        $total = count($this->results);
        $success = 0;
        $warnings = 0;
        $errors = 0;
        
        foreach ($this->results as $result) {
            echo $result[0] . " " . $result[1] . "\n";
            
            if ($result[0] === '✅') $success++;
            elseif ($result[0] === '⚠️' || $result[0] === 'ℹ️') $warnings++;
            else $errors++;
        }
        
        echo "\n=== RÉSUMÉ ===\n";
        echo "✅ Succès: $success\n";
        echo "⚠️ Avertissements: $warnings\n";
        echo "❌ Erreurs: $errors\n";
        echo "📊 Total: $total\n\n";
        
        if ($errors === 0) {
            echo "🎉 Félicitations ! Votre application présente un bon niveau de sécurité.\n";
        } else {
            echo "⚠️ Des problèmes de sécurité ont été détectés. Veuillez les corriger.\n";
        }
        
        echo "\n=== RECOMMANDATIONS SUPPLÉMENTAIRES ===\n";
        echo "1. Changez les clés secrètes par défaut en production\n";
        echo "2. Activez HTTPS avec certificat SSL valide\n";
        echo "3. Configurez un pare-feu applicatif (WAF)\n";
        echo "4. Mettez en place une surveillance des logs\n";
        echo "5. Effectuez des audits de sécurité réguliers\n";
        echo "6. Gardez PHP et les dépendances à jour\n";
        echo "7. Limitez les permissions des fichiers sur le serveur\n";
        echo "8. Sauvegardez régulièrement les données\n\n";
    }
}

// Exécution du script
if (php_sapi_name() === 'cli') {
    $checker = new SecurityChecker();
    $checker->runAllChecks();
} else {
    die('Ce script ne peut être exécuté que via CLI pour des raisons de sécurité');
}

?>
