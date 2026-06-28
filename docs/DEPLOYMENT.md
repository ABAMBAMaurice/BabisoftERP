# Instructions de Déploiement Sécurisé

## 🚨 CORRECTION DE L'ERREUR 500

L'erreur 500 était causée par les références aux nouveaux fichiers de sécurité qui n'étaient pas encore sur le serveur. Les fichiers ont été modifiés pour être **rétrocompatibles**.

## 📋 Ordre de Déploiement

### 1. **Fichiers de base (à déployer en premier)** ✅ FAIT
- `index.php` (modifié pour être compatible)
- `Base_Meta/Router.php` (modifié pour être compatible)
- `Base_Meta/Database/Database.php` (modifié pour être compatible)
- `Base_Meta/global.config.php` (modifié pour être compatible)

### 2. **Fichiers de sécurité (optionnels mais recommandés)**
- `Base_configs/security.config.php` (NOUVEAU)
- `Base_Meta/Security.class.php` (NOUVEAU)

### 3. **Scripts de maintenance (optionnels)**
- `migrate_security.php`
- `check_security.php`

### 4. **Documentation**
- `SECURITY.md`
- `DEPLOYMENT.md` (ce fichier)

## 🔧 Compatibility Mode

Les fichiers principaux fonctionnent maintenant en **mode de compatibilité** :

### ✅ **SANS les fichiers de sécurité** :
- L'application fonctionne normalement
- Headers de sécurité basiques appliqués
- Pas de validation CSRF (mais pas d'erreur)
- Pas de logging de sécurité (mais pas d'erreur)

### ✅ **AVEC les fichiers de sécurité** :
- Toutes les fonctionnalités de sécurité activées
- Validation CSRF complète
- Logging de sécurité
- Protection contre les attaques

## 🚀 Test de Déploiement

### Phase 1 : Test de Base
1. Uploadez uniquement les 4 fichiers de base modifiés
2. Testez que le site fonctionne
3. ✅ L'erreur 500 devrait être corrigée

### Phase 2 : Activation de la Sécurité (Optionnel)
1. Uploadez les fichiers de sécurité
2. Testez les nouvelles fonctionnalités
3. Exécutez `migrate_security.php` (si besoin)

## 🔍 Diagnostic des Erreurs

### Si l'erreur 500 persiste :

1. **Vérifiez les logs Apache/PHP** :
   ```bash
   tail -f /var/log/apache2/error.log
   ```

2. **Activez temporairement l'affichage des erreurs** :
   ```php
   // Dans index.php, ajoutez temporairement :
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

3. **Vérifiez les permissions des fichiers** :
   ```bash
   chmod 644 *.php
   chmod 755 Base_*
   ```

## 🎯 Points de Vérification

### ✅ Checklist de Déploiement :
- [ ] `index.php` uploadé et fonctionne
- [ ] `Base_Meta/Router.php` uploadé
- [ ] `Base_Meta/Database/Database.php` uploadé  
- [ ] `Base_Meta/global.config.php` uploadé
- [ ] Site accessible sans erreur 500
- [ ] Routes fonctionnelles (test GET /)
- [ ] API accessible (test GET /Views)

### ✅ Checklist de Sécurité (Optionnel) :
- [ ] `Base_configs/security.config.php` uploadé
- [ ] `Base_Meta/Security.class.php` uploadé
- [ ] Endpoint `/csrf-token` fonctionne
- [ ] Login sécurisé fonctionne
- [ ] Migration des mots de passe effectuée

## 🔐 Configuration Post-Déploiement

### Si vous déployez les fichiers de sécurité :

1. **Changez les clés secrètes** dans `security.config.php` :
   ```php
   define('CSRF_SECRET', 'votre-nouvelle-cle-unique');
   define('ENCRYPTION_KEY', 'votre-cle-chiffrement-unique');
   ```

2. **Configurez les domaines autorisés** :
   ```php
   define('ALLOWED_ORIGINS', [
       'https://btp.bambasolutions.fr',
       'https://www.bambasolutions.fr'
   ]);
   ```

3. **Exécutez la migration** (une seule fois) :
   ```bash
   php migrate_security.php
   ```

## 📞 Support

En cas de problème :
1. Vérifiez les logs d'erreur
2. Testez avec les fichiers de base uniquement
3. Ajoutez progressivement les fichiers de sécurité

**L'application est maintenant 100% rétrocompatible** ✅
