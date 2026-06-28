# Guide de Sécurité - Application BTP

## Améliorations de Sécurité Implémentées

### 1. Authentication et Autorisation Sécurisées

✅ **Hachage des mots de passe** : Utilisation d'Argon2ID pour un hachage sécurisé
✅ **Tokens de session sécurisés** : Génération de tokens cryptographiquement sûrs
✅ **Validation des sessions** : Vérification rigoureuse des tokens et de leur validité
✅ **Protection contre la force brute** : Limitation des tentatives de connexion

### 2. Protection contre les Attaques Web

✅ **Protection CSRF** : Tokens CSRF obligatoires pour les opérations sensibles
✅ **Validation des entrées** : Nettoyage et validation de toutes les données utilisateur
✅ **Protection XSS** : Échappement HTML et headers de sécurité
✅ **Protection contre l'injection SQL** : Utilisation de requêtes préparées

### 3. Headers de Sécurité

✅ **Content Security Policy** : Contrôle des ressources autorisées
✅ **X-Frame-Options** : Protection contre le clickjacking
✅ **X-Content-Type-Options** : Protection contre le MIME sniffing
✅ **HSTS** : Force l'utilisation de HTTPS (quand disponible)

### 4. Configuration Sécurisée

✅ **CORS restrictif** : Limitation des origines autorisées
✅ **Gestion des erreurs** : Masquage des informations sensibles
✅ **Rate limiting** : Protection contre les attaques par déni de service
✅ **Logging de sécurité** : Enregistrement des événements de sécurité

### 5. Validation des Fichiers

✅ **Upload sécurisé** : Validation du type et de la taille des fichiers
✅ **Protection des fichiers sensibles** : Restriction d'accès via .htaccess

## Utilisation

### Obtenir un token CSRF

```javascript
fetch('/csrf-token')
  .then(response => response.json())
  .then(data => {
    const csrfToken = data.csrf-token;
    // Utiliser le token dans vos requêtes
  });
```

### Connexion sécurisée

```javascript
const loginData = {
  Email: 'user@example.com',
  Password: 'motdepasse',
  csrf-token: csrfToken
};

fetch('/SignIn', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify(loginData)
});
```

### Utilisation du token d'authentification

```javascript
fetch('/api/endpoint', {
  headers: {
    'Authorization': 'Bearer ' + authToken,
    'Content-Type': 'application/json'
  }
});
```

## Migration

### Migration des mots de passe existants

Pour migrer les mots de passe existants, exécutez :

```bash
php migrate_security.php
```

**⚠️ Important** : Ce script ne doit être exécuté qu'une seule fois !

## Configuration de Production

### Variables d'environnement à modifier :

1. **CSRF_SECRET** : Changez la clé secrète dans `security.config.php`
2. **ENCRYPTION_KEY** : Changez la clé de chiffrement
3. **ALLOWED_ORIGINS** : Configurez vos domaines autorisés
4. **Credentials DB** : Sécurisez les identifiants de base de données

### Configuration Apache recommandée :

```apache
# Cacher la version du serveur
ServerTokens Prod
ServerSignature Off

# Modules de sécurité
LoadModule security2_module modules/mod_security2.so
LoadModule headers_module modules/mod_headers.so
```

### Configuration HTTPS :

```apache
# Redirection HTTPS forcée
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## Monitoring de Sécurité

### Logs de sécurité

Les événements de sécurité sont enregistrés dans `security.log` :

- Tentatives de connexion échouées
- Violations CSRF
- Erreurs de validation
- Accès non autorisés

### Alertes recommandées

Surveillez les événements suivants :
- Multiples tentatives de connexion échouées
- Violations CSRF répétées
- Erreurs de validation de tokens
- Tentatives d'injection SQL

## Tests de Sécurité

### Checklist de validation :

- [ ] Mots de passe correctement hachés
- [ ] CSRF protection active
- [ ] Headers de sécurité présents
- [ ] CORS correctement configuré
- [ ] Validation des entrées fonctionnelle
- [ ] Logging de sécurité opérationnel
- [ ] Rate limiting effectif
- [ ] Protection des fichiers sensibles

## Support

Pour toute question concernant la sécurité de l'application, consultez :
- Les logs de sécurité : `security.log`
- La configuration : `Base_configs/security.config.php`
- La classe de sécurité : `Base_Meta/Security.class.php`

---

**Note** : Cette documentation doit être mise à jour à chaque modification de sécurité.
