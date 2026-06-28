<?php
/**
 * Script de migration pour hacher les mots de passe existants
 * À exécuter une seule fois pour migrer les mots de passe en clair vers des mots de passe hachés
 */

require_once('Base_Meta/global.config.php');
require_once('Base_configs/security.config.php');
require_once('Base_Meta/Security.class.php');

class PasswordMigration {
    
    public static function migratePasswords() {
        echo "Début de la migration des mots de passe...\n";
        
        try {
            // Récupérer tous les utilisateurs avec des mots de passe non hachés
            $query = "SELECT Email, Password FROM utilisateur WHERE LENGTH(Password) < 60"; // Les hash Argon2ID font plus de 60 caractères
            $users = db->getResult($query);
            
            if (empty($users)) {
                echo "Aucun mot de passe à migrer.\n";
                return;
            }
            
            $migratedCount = 0;
            
            foreach ($users as $userData) {
                $email = $userData['Email'];
                $plainPassword = $userData['Password'];
                
                // Vérifier que le mot de passe n'est pas déjà haché
                if (strlen($plainPassword) > 60 || password_verify('test', $plainPassword)) {
                    echo "Mot de passe déjà haché pour: $email\n";
                    continue;
                }
                
                // Hacher le mot de passe
                $hashedPassword = Security::hashPassword($plainPassword);
                
                // Mettre à jour en base
                $updateQuery = "UPDATE user SET Password = ? WHERE Email = ?";
                $success = db->executeQuery($updateQuery, [$hashedPassword, $email]);
                
                if ($success) {
                    echo "Mot de passe migré avec succès pour: $email\n";
                    $migratedCount++;
                } else {
                    echo "Erreur lors de la migration pour: $email\n";
                }
            }
            
            db->commit();
            echo "Migration terminée. $migratedCount mots de passe migrés.\n";
            
        } catch (Exception $e) {
            db->rollback();
            echo "Erreur lors de la migration: " . $e->getMessage() . "\n";
        }
    }
    
    public static function addSecurityColumns() {
        echo "Ajout des colonnes de sécurité...\n";
        
        try {
            // Ajouter la colonne last_activity à la table session si elle n'existe pas
            if (!db->column_exist('session', 'last_activity')) {
                $query = "ALTER TABLE session ADD COLUMN last_activity DATETIME NULL";
                db->executeQuery($query);
                echo "Colonne last_activity ajoutée à la table session.\n";
            }
            
            // Ajouter la colonne user_email à la table session si elle n'existe pas
            if (!db->column_exist('session', 'user_email')) {
                $query = "ALTER TABLE session ADD COLUMN user_email VARCHAR(255) NULL";
                db->executeQuery($query);
                echo "Colonne user_email ajoutée à la table session.\n";
            }
            
            // Ajouter des index pour améliorer les performances
            $indexes = [
                "CREATE INDEX IF NOT EXISTS idx_session_token ON session(session_token)",
                "CREATE INDEX IF NOT EXISTS idx_session_times ON session(start_time, end_time)",
                "CREATE INDEX IF NOT EXISTS idx_user_email ON user(Email)"
            ];
            
            foreach ($indexes as $indexQuery) {
                db->executeQuery($indexQuery);
            }
            
            echo "Index de sécurité créés.\n";
            db->commit();
            
        } catch (Exception $e) {
            db->rollback();
            echo "Erreur lors de l'ajout des colonnes: " . $e->getMessage() . "\n";
        }
    }
}

// Exécution du script si appelé directement
if (php_sapi_name() === 'cli') {
    echo "=== MIGRATION DE SÉCURITÉ ===\n";
    PasswordMigration::addSecurityColumns();
    PasswordMigration::migratePasswords();
    echo "=== MIGRATION TERMINÉE ===\n";
} else {
    // Pour la sécurité, ce script ne doit être exécuté qu'en ligne de commande
    die('Ce script ne peut être exécuté que via CLI');
}

?>
