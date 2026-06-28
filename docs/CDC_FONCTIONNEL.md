# KILIE IMMO — Cahier des Charges Fonctionnel
## Business Requirements Document (BRD) — v1.0 — Juin 2026
### Plateforme SaaS de Gestion Immobilière — Côte d'Ivoire & Afrique de l'Ouest

---

## TABLE DES MATIÈRES

1. [Périmètre et conventions](#périmètre)
2. [Glossaire métier](#glossaire)
3. [MODULE 01 — Authentification](#mod-auth)
4. [MODULE 02 — Gestion des utilisateurs](#mod-usr)
5. [MODULE 03 — Gestion immobilière](#mod-prop)
6. [MODULE 04 — Locataires](#mod-tenant)
7. [MODULE 05 — Propriétaires](#mod-owner)
8. [MODULE 06 — Contrats et baux](#mod-lease)
9. [MODULE 07 — Paiements](#mod-pay)
10. [MODULE 08 — Mobile Money](#mod-mm)
11. [MODULE 09 — Comptabilité](#mod-acc)
12. [MODULE 10 — Maintenance](#mod-maint)
13. [MODULE 11 — CRM](#mod-crm)
14. [MODULE 12 — GED](#mod-ged)
15. [MODULE 13 — Intelligence Artificielle](#mod-ai)
16. [MODULE 14 — Reporting & Analytics](#mod-rep)
17. [MODULE 15 — Notifications](#mod-notif)
18. [Matrice de traçabilité](#matrice)

---

## PÉRIMÈTRE ET CONVENTIONS <a name="périmètre"></a>

### Portée du document

Ce document définit les exigences fonctionnelles complètes de la plateforme KILIE IMMO. Il couvre la Phase 1 (MVP) et anticipe la Phase 2. Il ne contient aucune décision d'implémentation technique.

### Rôles utilisateurs

| Code Rôle | Libellé | Description |
|---|---|---|
| `SUPER_ADMIN` | Admin Plateforme | Administrateur Kilie Immo (opérateur SaaS) |
| `ORG_ADMIN` | Admin Organisation | Dirigeant/responsable de l'agence cliente |
| `MANAGER` | Gestionnaire | Agent immobilier, gestionnaire de bien |
| `ACCOUNTANT` | Comptable | Comptable de l'organisation |
| `OWNER` | Propriétaire | Bailleur avec accès portail |
| `TENANT` | Locataire | Locataire avec accès portail |
| `VENDOR` | Prestataire | Technicien/prestataire maintenance |

### Notation des permissions

| Symbole | Signification |
|---|---|
| ✓ | Autorisé — accès complet |
| R | Lecture seule |
| ~ | Conditionnel — ses propres données uniquement |
| ✗ | Refusé |

### Conventions de numérotation

- Cas d'utilisation : `UC-[MODULE]-NNN`
- Règles métier : `BR-[MODULE]-NNN`
- Validations : `VAL-[MODULE]-NNN`
- Exceptions : `EXC-[MODULE]-NNN`

---

## GLOSSAIRE MÉTIER <a name="glossaire"></a>

| Terme | Définition |
|---|---|
| **Bien** | Actif immobilier (villa, appartement, local commercial, terrain, immeuble) |
| **Unité** | Subdivision d'un bien (appartement dans un immeuble, bureau dans un plateau) |
| **Bail** | Contrat de location liant propriétaire et locataire |
| **Loyer** | Montant périodique dû par le locataire au titre du bail |
| **Appel de fonds** | Facture mensuelle émise vers le locataire |
| **Quittance** | Reçu officiel de paiement de loyer |
| **Dépôt de garantie** | Caution versée à l'entrée dans les lieux |
| **État des lieux** | Inventaire contradictoire de l'état du bien (entrée / sortie) |
| **Titre Foncier (TF)** | Document officiel de propriété délivré par la DGF (Côte d'Ivoire) |
| **ACD** | Arrêté de Concession Définitive |
| **AV** | Attestation Villageoise (titre informel) |
| **Mandat** | Autorisation écrite confiée à l'agence par le propriétaire |
| **TVA** | 18% applicable sur les loyers commerciaux (CI) |
| **XOF** | Franc CFA de l'Afrique de l'Ouest (valeur entière, sans centimes) |
| **Mobile Money** | Paiement via portefeuille mobile (MTN, Orange, Wave) |
| **KYC** | Know Your Customer — vérification d'identité et solvabilité |
| **Préavis** | Délai légal de notification avant résiliation d'un bail |
| **Tantième** | Quote-part d'un lot dans les charges communes d'une copropriété |
| **OI** | Ordre d'Intervention (demande de maintenance) |
| **Organisation** | Entité cliente du SaaS (agence, gestionnaire, propriétaire) |
| **Lead** | Prospect (locataire ou acheteur potentiel) |

---

## MODULE 01 — AUTHENTIFICATION <a name="mod-auth"></a>

### 1. Objectif

Garantir l'accès sécurisé et tracé à la plateforme pour tous les types d'utilisateurs (internes agence, portails externes locataire/propriétaire). Gérer les sessions, la double authentification, la récupération de compte et l'audit de sécurité.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| Tous utilisateurs | Connexion, déconnexion, récupération mot de passe |
| `ORG_ADMIN` | Invitation et configuration MFA pour son équipe |
| `SUPER_ADMIN` | Supervision des sessions, révocation, audit global |

### 3. Cas d'utilisation

**UC-AUTH-001 — Inscription d'une organisation**
Un `ORG_ADMIN` crée le compte de son organisation via le formulaire d'onboarding SaaS.
- Préconditions : Email professionnel valide, plan SaaS sélectionné
- Postconditions : Organisation créée, compte admin activé, email de bienvenue envoyé

**UC-AUTH-002 — Connexion standard (email + mot de passe)**
Un utilisateur saisit ses identifiants pour accéder à l'application.
- Préconditions : Compte actif, organisation active
- Postconditions : Token JWT émis, session créée, dernier login enregistré

**UC-AUTH-003 — Connexion via code OTP SMS (MFA)**
Après saisie du mot de passe, l'utilisateur reçoit un code à 6 chiffres par SMS valable 5 minutes.
- Préconditions : MFA activé sur le compte
- Postconditions : Authentification complète, token JWT émis

**UC-AUTH-004 — Récupération de mot de passe**
L'utilisateur demande un lien de réinitialisation envoyé à son email ou SMS.
- Préconditions : Email ou téléphone enregistré sur le compte
- Postconditions : Nouveau mot de passe défini, session actuelle invalidée

**UC-AUTH-005 — Déconnexion**
L'utilisateur met fin à sa session explicitement ou par expiration.
- Postconditions : Refresh token révoqué, session invalidée côté serveur

**UC-AUTH-006 — Connexion portail locataire / propriétaire**
Un locataire ou propriétaire se connecte via lien SMS ou email dédié (magic link) ou identifiants simplifiés.
- Postconditions : Token portail limité aux données propres à l'utilisateur

**UC-AUTH-007 — Révocation de session par l'admin**
Un `ORG_ADMIN` ou `SUPER_ADMIN` révoque la session active d'un utilisateur.
- Postconditions : Utilisateur déconnecté immédiatement, événement audité

**UC-AUTH-008 — Connexion par invitation**
Un utilisateur invité clique sur un lien d'invitation reçu par email pour activer son compte.
- Préconditions : Invitation valide (non expirée, < 72h)
- Postconditions : Mot de passe défini, compte activé

### 4. Règles métier

| # | Règle |
|---|---|
| BR-AUTH-001 | Un mot de passe doit contenir au moins 8 caractères, 1 majuscule, 1 chiffre, 1 caractère spécial |
| BR-AUTH-002 | Après 5 tentatives de connexion échouées, le compte est verrouillé 30 minutes |
| BR-AUTH-003 | Le token JWT expire après 15 minutes ; le refresh token expire après 7 jours |
| BR-AUTH-004 | Le lien de réinitialisation de mot de passe expire après 1 heure |
| BR-AUTH-005 | Un utilisateur ne peut avoir qu'une session active simultanée (configurable par organisation) |
| BR-AUTH-006 | Le code OTP SMS est valable 5 minutes et ne peut être utilisé qu'une seule fois |
| BR-AUTH-007 | Toute connexion réussie ou échouée est enregistrée dans le journal d'audit (IP, user-agent, horodatage) |
| BR-AUTH-008 | Un `TENANT` ou `OWNER` ne peut accéder qu'aux données de son organisation |
| BR-AUTH-009 | Le MFA par SMS est obligatoire pour les rôles `ORG_ADMIN`, `MANAGER`, `ACCOUNTANT` |
| BR-AUTH-010 | Un lien d'invitation est valable 72 heures et ne peut être utilisé qu'une seule fois |

### 5. Validations

| # | Champ | Règle de validation |
|---|---|---|
| VAL-AUTH-001 | Email | Format RFC 5322 valide, domaine résolvable |
| VAL-AUTH-002 | Téléphone | Format international E.164, opérateurs CI : +225 |
| VAL-AUTH-003 | Mot de passe | Min 8 chars, 1 majuscule, 1 chiffre, 1 spécial |
| VAL-AUTH-004 | Code OTP | 6 chiffres numériques uniquement |

### 6. Workflow — Connexion avec MFA

```
1. Utilisateur saisit email + mot de passe
2. Système vérifie les identifiants
   → Echec : incrémenter compteur tentatives → EXC-AUTH-001
3. Si MFA activé → générer OTP 6 chiffres → envoyer SMS
4. Utilisateur saisit le code OTP
   → Expiré ou incorrect → EXC-AUTH-002
5. Système émet JWT (15 min) + Refresh Token (7 jours)
6. Redirection vers tableau de bord selon rôle
7. Événement de connexion enregistré dans l'audit
```

### 7. Cas d'exception

| # | Exception | Traitement |
|---|---|---|
| EXC-AUTH-001 | Compte verrouillé (5 tentatives) | Afficher message verrouillage + durée restante, envoyer SMS alerte à l'utilisateur |
| EXC-AUTH-002 | Code OTP expiré ou invalide | Proposer renvoi d'un nouveau code (max 3 renvois / 30 min) |
| EXC-AUTH-003 | Organisation suspendue | Afficher message suspension, rediriger vers support |
| EXC-AUTH-004 | Compte désactivé | Message "Compte désactivé, contactez votre administrateur" |
| EXC-AUTH-005 | Lien d'invitation expiré | Afficher message d'expiration, proposer une nouvelle invitation |
| EXC-AUTH-006 | Token JWT expiré | Tenter renouvellement silencieux via refresh token ; sinon rediriger vers login |

### 8. Notifications déclenchées

| Événement | Canal | Destinataire |
|---|---|---|
| Connexion depuis nouvelle IP | Email | Utilisateur |
| Compte verrouillé | SMS + Email | Utilisateur |
| Code OTP | SMS | Utilisateur |
| Réinitialisation mot de passe demandée | Email | Utilisateur |
| Invitation envoyée | Email | Invité |
| Connexion suspecte (pays inhabituel) | Email + SMS | Utilisateur + ORG_ADMIN |

### 9. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Configurer MFA organisation | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Révoquer sessions équipe | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Consulter journal d'audit | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Modifier son propre mot de passe | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Désactiver un compte utilisateur | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |

---

## MODULE 02 — GESTION DES UTILISATEURS <a name="mod-usr"></a>

### 1. Objectif

Administrer les comptes des collaborateurs d'une organisation, définir leurs rôles et permissions, gérer les invitations, les profils et les limites d'accès selon le plan SaaS souscrit.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| `ORG_ADMIN` | Création, modification, désactivation des comptes de son organisation |
| `MANAGER` | Consultation de l'annuaire interne |
| `SUPER_ADMIN` | Administration globale de toutes les organisations |

### 3. Cas d'utilisation

**UC-USR-001 — Inviter un collaborateur**
L'`ORG_ADMIN` saisit l'email et le rôle d'un nouveau collaborateur. Le système envoie un email d'invitation.

**UC-USR-002 — Modifier le rôle d'un utilisateur**
L'`ORG_ADMIN` change le rôle assigné à un collaborateur existant.
- Préconditions : L'utilisateur cible est actif dans la même organisation
- Postconditions : Nouvelles permissions appliquées immédiatement, session actuelle invalidée

**UC-USR-003 — Désactiver un compte**
L'`ORG_ADMIN` désactive le compte d'un collaborateur ayant quitté l'organisation.
- Postconditions : Compte inaccessible, données conservées, activité historique préservée

**UC-USR-004 — Réactiver un compte**
Réactivation d'un compte précédemment désactivé.

**UC-USR-005 — Consulter le profil utilisateur**
Tout utilisateur peut consulter et modifier son propre profil (nom, téléphone, photo, langue, préférences de notification).

**UC-USR-006 — Gérer les permissions granulaires**
L'`ORG_ADMIN` ajuste des permissions spécifiques au-delà du rôle standard (ex : accès restreint à certains biens).

**UC-USR-007 — Consulter le journal d'activité d'un utilisateur**
L'`ORG_ADMIN` consulte l'historique des actions réalisées par un collaborateur.

**UC-USR-008 — Gérer les limites SaaS**
Le système bloque l'invitation d'un nouvel utilisateur si la limite du plan SaaS est atteinte.

### 4. Règles métier

| # | Règle |
|---|---|
| BR-USR-001 | Un `ORG_ADMIN` ne peut pas modifier son propre rôle |
| BR-USR-002 | Un utilisateur désactivé ne peut plus se connecter mais ses données restent liées à ses actions passées |
| BR-USR-003 | Il doit exister au moins 1 `ORG_ADMIN` actif par organisation à tout moment |
| BR-USR-004 | Le nombre d'utilisateurs actifs est limité par le plan SaaS souscrit |
| BR-USR-005 | Un `MANAGER` ne peut accéder qu'aux biens qui lui sont assignés (si restriction activée) |
| BR-USR-006 | Toute modification de rôle est enregistrée dans le journal d'audit avec l'auteur et l'horodatage |
| BR-USR-007 | Un `OWNER` et un `TENANT` ne peuvent pas être invités comme collaborateurs internes |

### 5. Validations

| # | Champ | Règle |
|---|---|---|
| VAL-USR-001 | Email invitation | Unique dans l'organisation, format valide |
| VAL-USR-002 | Rôle assigné | Doit être parmi les rôles définis du système |
| VAL-USR-003 | Nom / Prénom | Min 2 caractères, max 100, caractères alphabétiques et espaces |
| VAL-USR-004 | Téléphone | Format E.164, +225 pour CI |

### 6. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Inviter un collaborateur | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Modifier un rôle | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Désactiver un compte | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Modifier son profil | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Voir journal activité | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Voir liste utilisateurs organisation | ✓ | ✓ | R | R | ✗ | ✗ |

---

## MODULE 03 — GESTION IMMOBILIÈRE <a name="mod-prop"></a>

### 1. Objectif

Constituer et maintenir le catalogue de biens immobiliers de l'organisation : création, mise à jour, gestion des statuts, des médias, de la localisation, des documents fonciers et des mandats de gestion.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| `MANAGER` | CRUD complet des biens qui lui sont assignés |
| `ORG_ADMIN` | CRUD complet sur tous les biens de l'organisation |
| `OWNER` | Consultation de ses propres biens (portail) |
| `TENANT` | Consultation du bien loué |

### 3. Cas d'utilisation

**UC-PROP-001 — Créer un bien**
Le `MANAGER` saisit les caractéristiques d'un nouveau bien : type, adresse CI, surface, nombre de pièces, propriétaire associé, type de titre foncier.

**UC-PROP-002 — Ajouter des unités à un bien**
Pour un immeuble ou plateau de bureaux, ajouter des unités indépendantes (appartements, bureaux, commerces) avec leurs caractéristiques propres.

**UC-PROP-003 — Téléverser des photos et médias**
Ajouter des photos, plans, vidéos associés à un bien ou une unité. Définir la photo principale.
- Contraintes : max 20 photos par bien, formats JPG/PNG/WEBP, max 10 Mo par photo

**UC-PROP-004 — Géolocaliser un bien**
Associer des coordonnées GPS au bien via carte ou saisie manuelle. Saisir l'adresse CI (commune, quartier, ilot, description d'accès).

**UC-PROP-005 — Enregistrer les documents fonciers**
Attacher le document de propriété (Titre Foncier, ACD, ACP, Attestation Villageoise) avec numéro de référence, date de délivrance et scan.

**UC-PROP-006 — Créer un mandat de gestion**
Enregistrer le mandat signé par le propriétaire confiant la gestion à l'agence : type, durée, taux d'honoraires.

**UC-PROP-007 — Changer le statut d'un bien**
Modifier le statut selon les transitions autorisées : DISPONIBLE → EN_LOCATION, EN_LOCATION → LIBRE, DISPONIBLE → EN_VENTE, etc.

**UC-PROP-008 — Archiver un bien**
Archiver un bien sorti du portefeuille (vendu, retiré). Les données historiques sont conservées.

**UC-PROP-009 — Rechercher et filtrer les biens**
Rechercher dans le catalogue par commune, type, statut, surface, propriétaire, loyer, disponibilité.

**UC-PROP-010 — Dupliquer un bien**
Créer un nouveau bien à partir d'un bien existant (pour des propriétés similaires).

**UC-PROP-011 — Suivre les indicateurs d'un bien**
Consulter en un coup d'œil : taux d'occupation, revenus générés, charges, historique des locataires.

### 4. Règles métier

| # | Règle |
|---|---|
| BR-PROP-001 | Un bien doit obligatoirement être associé à au moins un propriétaire |
| BR-PROP-002 | Un bien avec un bail actif ne peut pas être mis en statut DISPONIBLE |
| BR-PROP-003 | Un bien vendu (statut VENDU) ne peut plus être modifié |
| BR-PROP-004 | Le taux de commission du mandat est exprimé en % TTC (0,5% à 15%) |
| BR-PROP-005 | L'adresse CI doit contenir au minimum la commune et le quartier |
| BR-PROP-006 | Un mandat de gestion expire automatiquement à sa date de fin ; une alerte est envoyée 30 jours avant |
| BR-PROP-007 | Un bien peut avoir plusieurs unités, chaque unité ayant son propre statut d'occupation indépendant |
| BR-PROP-008 | Un bien archivé n'apparaît pas dans le catalogue actif mais reste consultable dans l'historique |
| BR-PROP-009 | La photo principale est obligatoire avant de publier un bien sur le portail public |
| BR-PROP-010 | Les coordonnées GPS sont stockées en WGS 84 (latitude, longitude) |

### 5. Validations

| # | Champ | Règle |
|---|---|---|
| VAL-PROP-001 | Commune | Choisir parmi la liste des communes ivoiriennes (liste fixe) |
| VAL-PROP-002 | Surface | Décimal positif, min 5 m², max 100 000 m² |
| VAL-PROP-003 | Loyer indicatif | Entier positif en XOF, max 100 000 000 XOF |
| VAL-PROP-004 | Numéro TF | Alphanumérique, format validé selon les patterns DGF CI |
| VAL-PROP-005 | Taux commission mandat | Entre 0,5% et 15% |
| VAL-PROP-006 | Photos | JPG, PNG, WEBP — max 10 Mo chacune — max 20 par bien |

### 6. Statuts d'un bien

```
BROUILLON → DISPONIBLE → EN_LOCATION → DISPONIBLE
                        → EN_VENTE    → VENDU
DISPONIBLE → SUSPENDU  → DISPONIBLE
Tout statut → ARCHIVE
```

| Statut | Description |
|---|---|
| BROUILLON | En cours de saisie, non visible |
| DISPONIBLE | Libre, visible, peut être loué ou vendu |
| EN_LOCATION | Bail actif sur ce bien ou une de ses unités |
| EN_VENTE | Bien mis en vente, visible |
| VENDU | Transaction conclue |
| SUSPENDU | Temporairement indisponible (travaux, litige) |
| ARCHIVE | Retiré du portefeuille actif |

### 7. Cas d'exception

| # | Exception | Traitement |
|---|---|---|
| EXC-PROP-001 | Tentative de suppression d'un bien avec bail actif | Bloquer, afficher message "Un bail actif existe sur ce bien" |
| EXC-PROP-002 | Doublon de bien détecté (même TF) | Avertissement non bloquant avec lien vers le bien existant |
| EXC-PROP-003 | Upload photo > 10 Mo | Proposer compression automatique ou demander une image plus légère |
| EXC-PROP-004 | Mandat expiré non renouvelé | Passer le bien en statut SUSPENDU, notifier le `MANAGER` |

### 8. Notifications déclenchées

| Événement | Canal | Destinataire |
|---|---|---|
| Mandat de gestion expire dans 30 jours | Email + Push | MANAGER, ORG_ADMIN |
| Bien libéré (bail terminé) | Push + Email | MANAGER |
| Nouveau bien ajouté au portefeuille | Push | ORG_ADMIN |

### 9. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Créer un bien | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Modifier un bien | ✓ | ✓ | ~ | ✗ | ✗ | ✗ |
| Archiver un bien | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Voir tous les biens | ✓ | ✓ | ~ | R | ~ | ~ |
| Gérer mandats | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Uploader documents fonciers | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |

---

## MODULE 04 — LOCATAIRES <a name="mod-tenant"></a>

### 1. Objectif

Gérer le cycle de vie complet d'un locataire : création du dossier, collecte des pièces justificatives, vérification KYC, scoring de solvabilité, suivi des candidatures et historique de location.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| `MANAGER` | Création et gestion des dossiers locataires |
| `TENANT` | Soumission de son dossier, consultation de ses informations |
| `ORG_ADMIN` | Validation finale des dossiers, supervision |

### 3. Cas d'utilisation

**UC-TENANT-001 — Créer un dossier locataire**
Le `MANAGER` crée un dossier pour un candidat locataire : identité, coordonnées, situation professionnelle, revenus.

**UC-TENANT-002 — Soumettre les pièces justificatives**
Le candidat ou le `MANAGER` téléverse les documents requis selon la checklist du dossier.

**UC-TENANT-003 — Évaluer la solvabilité**
Le système calcule automatiquement un score de solvabilité basé sur les informations fournies. Le `MANAGER` peut affiner manuellement.

**UC-TENANT-004 — Valider ou rejeter un dossier**
Le `MANAGER` ou `ORG_ADMIN` approuve ou rejette la candidature avec un motif obligatoire en cas de refus.

**UC-TENANT-005 — Consulter le profil locataire**
Accéder à la fiche complète : coordonnées, baux en cours, historique de paiements, solde, incidents.

**UC-TENANT-006 — Envoyer un lien de dépôt de dossier au candidat**
Générer un lien sécurisé envoyé au candidat pour qu'il remplisse lui-même son dossier et téléverse ses documents.

**UC-TENANT-007 — Gérer les colocataires**
Ajouter plusieurs locataires sur un même bail (colocation) avec répartition des responsabilités.

**UC-TENANT-008 — Consulter l'historique de location**
Voir tous les baux passés et en cours d'un locataire, avec son bilan de paiement.

### 4. Règles métier

| # | Règle |
|---|---|
| BR-TENANT-001 | Le dossier locataire est considéré complet uniquement si toutes les pièces obligatoires sont téléversées |
| BR-TENANT-002 | Le ratio solvabilité standard est : loyer ≤ 33% des revenus mensuels nets |
| BR-TENANT-003 | Si les revenus sont insuffisants, un garant (caution solidaire) peut compléter le dossier |
| BR-TENANT-004 | Les revenus d'un locataire salarié sont justifiés par 3 bulletins de salaire récents |
| BR-TENANT-005 | Les revenus d'un indépendant sont justifiés par les 2 derniers bilans comptables |
| BR-TENANT-006 | Un dossier rejeté peut être resoumis avec de nouveaux documents (max 3 fois) |
| BR-TENANT-007 | Les données personnelles du locataire sont soumises à la Loi 2013-450 CI sur la protection des données |
| BR-TENANT-008 | Un locataire avec des impayés non résolus est marqué INCIDENT dans l'historique |

### 5. Documents obligatoires (KYC — Côte d'Ivoire)

**Pour un salarié :**
- Copie CNI ou passeport en cours de validité
- 3 derniers bulletins de salaire
- Attestation d'emploi datant de moins de 3 mois
- Relevés Mobile Money ou bancaires des 3 derniers mois

**Pour un travailleur indépendant / chef d'entreprise :**
- Copie CNI ou passeport
- RCCM ou registre du commerce
- 2 derniers bilans ou déclarations fiscales
- Relevés Mobile Money ou bancaires 3 mois

**Pour une personne morale (SCI, entreprise) :**
- Statuts de la société
- RCCM
- Dernier bilan
- Délégation de signature du représentant légal

### 6. Score de solvabilité

| Score | Niveau | Ratio Loyer/Revenus | Décision recommandée |
|---|---|---|---|
| A | Excellent | < 25% | Approuver |
| B | Bon | 25–33% | Approuver |
| C | Acceptable | 33–40% | Approuver avec caution |
| D | Insuffisant | 40–50% | Refuser ou caution bancaire obligatoire |
| E | Insuffisant | > 50% | Refuser |

### 7. Checklist dossier locataire

| Document | Obligatoire | Validité |
|---|---|---|
| Pièce d'identité (CNI / Passeport) | Oui | En cours de validité |
| 3 bulletins de salaire | Oui (salarié) | < 3 mois |
| Attestation d'emploi | Oui (salarié) | < 3 mois |
| Relevés de compte / MoMo | Oui | 3 derniers mois |
| Justificatif de domicile actuel | Non | < 3 mois |
| Formulaire de renseignements | Oui | Toujours |
| Engagement caution solidaire | Conditionnel | — |

### 8. Cas d'exception

| # | Exception | Traitement |
|---|---|---|
| EXC-TENANT-001 | CNI expirée téléversée | Avertissement non bloquant, demander renouvellement |
| EXC-TENANT-002 | Dossier incomplet soumis | Bloquer validation, afficher liste des pièces manquantes |
| EXC-TENANT-003 | Locataire en incident dans une autre organisation | Non visible (confidentialité), score interne uniquement |
| EXC-TENANT-004 | Solvabilité insuffisante sans caution | Proposition automatique d'alternative (caution bancaire) |

### 9. Notifications déclenchées

| Événement | Canal | Destinataire |
|---|---|---|
| Lien dépôt dossier envoyé | SMS + Email | Candidat |
| Dossier complet reçu | Push + Email | MANAGER |
| Dossier approuvé | SMS + Email | Candidat |
| Dossier rejeté | SMS + Email | Candidat (avec motif) |

### 10. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Créer dossier locataire | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Voir tous les dossiers | ✓ | ✓ | ✓ | R | ✗ | ✗ |
| Valider/rejeter dossier | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Voir score solvabilité | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Voir son propre dossier | ✗ | ✗ | ✗ | ✗ | ✗ | ~ |
| Envoyer lien dossier | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |

---

## MODULE 05 — PROPRIÉTAIRES <a name="mod-owner"></a>

### 1. Objectif

Gérer le référentiel des propriétaires (bailleurs), leur portail de consultation, leurs informations fiscales, leurs préférences de reversement et leur relation avec les biens et baux.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| `MANAGER` | Création et mise à jour des profils propriétaires |
| `OWNER` | Consultation de son portfolio (portail) |
| `ACCOUNTANT` | Consultation des informations fiscales |

### 3. Cas d'utilisation

**UC-OWNER-001 — Créer un profil propriétaire**
Saisir les coordonnées, statut fiscal, RIB ou compte Mobile Money, préférences de reversement.

**UC-OWNER-002 — Associer des biens à un propriétaire**
Lier un ou plusieurs biens au profil du propriétaire. Un bien peut appartenir à plusieurs propriétaires (copropriété, indivision) avec quote-part.

**UC-OWNER-003 — Consulter le tableau de bord propriétaire (portail)**
Le propriétaire accède à : liste de ses biens, statuts d'occupation, loyers encaissés, charges, solde à reverser, documents.

**UC-OWNER-004 — Consulter les relevés de compte propriétaire**
Accéder aux relevés mensuels/trimestriels détaillant : loyers perçus, honoraires de gestion déduits, charges imputées, net à reverser.

**UC-OWNER-005 — Télécharger les documents fiscaux**
Le propriétaire télécharge ses quittances annuelles, attestations de revenus fonciers pour déclaration fiscale.

**UC-OWNER-006 — Gérer les informations de reversement**
Définir le compte (Mobile Money ou bancaire) sur lequel les loyers nets sont reversés et la fréquence (mensuelle, trimestrielle).

**UC-OWNER-007 — Envoyer une instruction à l'agence**
Le propriétaire soumet via le portail une demande ou instruction à l'agence (ex : travaux à réaliser, augmentation de loyer souhaitée).

### 4. Règles métier

| # | Règle |
|---|---|
| BR-OWNER-001 | Un propriétaire peut être une personne physique ou morale (SCI, entreprise) |
| BR-OWNER-002 | Si un bien est en indivision, la somme des quote-parts doit égaler 100% |
| BR-OWNER-003 | Les honoraires de gestion sont déduits avant reversement (nets de charges) |
| BR-OWNER-004 | Le reversement propriétaire est effectué automatiquement selon la fréquence convenue |
| BR-OWNER-005 | Un propriétaire diaspora (non-résident CI) est identifié et ses documents fiscaux sont adaptés (retenue à la source 20%) |
| BR-OWNER-006 | L'accès portail propriétaire est restreint à ses propres données uniquement |
| BR-OWNER-007 | Le propriétaire doit valider toute modification d'informations de reversement via OTP SMS |

### 5. Retenue à la source (Fiscalité CI)

| Type de propriétaire | Traitement fiscal |
|---|---|
| Personne physique résidente CI | Contribution Foncière appliquée, quittance TVA si assujetti |
| Personne physique non-résidente | Retenue à la source 20% sur loyers bruts |
| SCI résidente | Imposition selon régime fiscal de la société |
| Entreprise assujettie TVA | TVA 18% collectée et reversée à la DGI |

### 6. Workflow — Reversement propriétaire

```
1. Fin de période (mois/trimestre)
2. Calcul : Total loyers encaissés − honoraires agence − charges imputées − retenue fiscale
3. Génération relevé propriétaire (PDF)
4. Envoi du relevé par email
5. Ordre de virement vers RIB ou Mobile Money propriétaire
6. Confirmation du reversement enregistrée
7. Notification propriétaire (reversement effectué)
```

### 7. Notifications déclenchées

| Événement | Canal | Destinataire |
|---|---|---|
| Relevé mensuel disponible | Email + Push | OWNER |
| Reversement effectué | SMS + Email | OWNER |
| Bail résilié sur son bien | Email | OWNER |
| Locataire en retard de paiement | Email | OWNER |
| Bien libéré et disponible | Email | OWNER |

### 8. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Créer profil propriétaire | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Modifier infos reversement | ✓ | ✓ | ✓ | ✗ | ~ | ✗ |
| Voir portfolio propriétaire | ✓ | ✓ | ~ | R | ~ | ✗ |
| Télécharger relevés fiscaux | ✓ | ✓ | ~ | ✓ | ~ | ✗ |
| Envoyer instruction | ✗ | ✗ | ✗ | ✗ | ~ | ✗ |

---

## MODULE 06 — CONTRATS ET BAUX <a name="mod-lease"></a>

### 1. Objectif

Gérer l'intégralité du cycle de vie des contrats de location : rédaction, signature électronique, génération automatique de l'échéancier, révisions, renouvellements, états des lieux et résiliation.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| `MANAGER` | Création et gestion des baux |
| `ORG_ADMIN` | Validation et supervision |
| `OWNER` | Signature et consultation |
| `TENANT` | Signature et consultation du bail actif |

### 3. Cas d'utilisation

**UC-LEASE-001 — Créer un bail**
Saisir les paramètres du bail : bien/unité, locataire, propriétaire, type de bail, dates, loyer, charges, dépôt de garantie, clauses spéciales.

**UC-LEASE-002 — Générer le document de bail PDF**
Générer automatiquement le contrat de bail conforme aux modèles légaux CI (bail d'habitation / bail commercial).

**UC-LEASE-003 — Envoyer pour signature électronique**
Envoyer le bail généré aux deux parties (locataire et propriétaire) pour signature électronique via lien sécurisé.

**UC-LEASE-004 — Enregistrer les signatures**
Capturer et horodater les signatures électroniques des deux parties. Le bail est considéré signé quand toutes les parties ont signé.

**UC-LEASE-005 — Activer le bail**
À la date de début définie, activer automatiquement le bail. Le bien passe en statut EN_LOCATION, l'échéancier est généré.

**UC-LEASE-006 — Générer l'échéancier de loyer**
Créer automatiquement la liste des échéances (loyer + charges) pour toute la durée du bail.

**UC-LEASE-007 — Saisir l'état des lieux d'entrée**
Enregistrer l'état des lieux d'entrée avec description pièce par pièce, photos, et signature des parties.

**UC-LEASE-008 — Proposer une révision de loyer**
Initier une révision du montant du loyer selon les conditions définies dans le bail (date anniversaire, indice).

**UC-LEASE-009 — Renouveler un bail**
Proposer et enregistrer le renouvellement d'un bail arrivant à échéance, avec éventuellement de nouvelles conditions.

**UC-LEASE-010 — Enregistrer un préavis**
Enregistrer la notification de préavis (locataire ou propriétaire) avec la date de réception et calcul de la date de fin effective.

**UC-LEASE-011 — Saisir l'état des lieux de sortie**
Enregistrer l'état des lieux de sortie, comparer avec l'entrée, identifier les dégâts locatifs.

**UC-LEASE-012 — Clôturer un bail**
Finaliser la résiliation : apurement du solde, calcul restitution dépôt de garantie, archivage du dossier.

**UC-LEASE-013 — Ajouter un avenant**
Créer un avenant modifiant une ou plusieurs clauses du bail actif (changement de loyer, ajout d'un locataire, etc.).

### 4. Règles métier

| # | Règle |
|---|---|
| BR-LEASE-001 | Deux types de bail sont gérés : HABITATION et COMMERCIAL, avec des règles légales distinctes |
| BR-LEASE-002 | Bail d'habitation : durée minimale 1 an, préavis locataire 1 mois, préavis propriétaire 3 mois |
| BR-LEASE-003 | Bail commercial : durée minimale 3 ans, préavis locataire 3 mois, préavis propriétaire 6 mois |
| BR-LEASE-004 | Le dépôt de garantie maximum est de 2 mois de loyer hors charges pour un bail habitation |
| BR-LEASE-005 | Le dépôt de garantie maximum est de 3 mois de loyer pour un bail commercial |
| BR-LEASE-006 | La TVA (18%) est appliquée automatiquement sur les loyers commerciaux si le propriétaire est assujetti |
| BR-LEASE-007 | Un bail ne peut être activé que si le dossier locataire est approuvé |
| BR-LEASE-008 | Un bail ne peut être activé que si le dépôt de garantie est encaissé |
| BR-LEASE-009 | L'échéancier est généré pour la durée totale du bail, ajustable en cas d'avenant |
| BR-LEASE-010 | Les loyers sont payables d'avance, exigibles le 1er de chaque mois (ou date personnalisée) |
| BR-LEASE-011 | La pénalité de retard est de 10% du montant dû par mois de retard (configurable) |
| BR-LEASE-012 | Un bail signé et actif ne peut être supprimé ; seule la résiliation est possible |
| BR-LEASE-013 | Un bail notarié est recommandé et déclenche un avertissement si la durée dépasse 3 ans |
| BR-LEASE-014 | En cas de restitution partielle du dépôt de garantie, un décompte détaillé est obligatoire |

### 5. Validations

| # | Champ | Règle |
|---|---|---|
| VAL-LEASE-001 | Date de début | Doit être dans le futur ou le jour même |
| VAL-LEASE-002 | Date de fin | Supérieure à date de début + durée minimale légale |
| VAL-LEASE-003 | Montant loyer | Entier positif en XOF > 0 |
| VAL-LEASE-004 | Dépôt de garantie | ≤ 2 mois loyer CC (habitation) ou ≤ 3 mois (commercial) |
| VAL-LEASE-005 | Taux révision | Entre 0% et 20% |
| VAL-LEASE-006 | Avenant | Doit référencer un bail actif existant |

### 6. Types de bail supportés

| Type | Sous-type | TVA | Durée min | Préavis locataire | Préavis propriétaire |
|---|---|---|---|---|---|
| Habitation | Non meublé | Non | 12 mois | 1 mois | 3 mois |
| Habitation | Meublé | Non | 6 mois | 1 mois | 3 mois |
| Commercial | Bureau | Oui (si assujetti) | 36 mois | 3 mois | 6 mois |
| Commercial | Local commercial | Oui (si assujetti) | 36 mois | 3 mois | 6 mois |
| Commercial | Entrepôt | Oui (si assujetti) | 36 mois | 3 mois | 6 mois |

### 7. Cas d'exception

| # | Exception | Traitement |
|---|---|---|
| EXC-LEASE-001 | Dossier locataire non approuvé | Bloquer création bail, renvoyer vers module locataires |
| EXC-LEASE-002 | Dépôt de garantie non encaissé à la date d'activation | Alerter le MANAGER, bloquer l'activation automatique |
| EXC-LEASE-003 | Bail arrivant à échéance sans renouvellement prévu | Alerte J-90, J-60, J-30 au MANAGER |
| EXC-LEASE-004 | Préavis insuffisant (délai légal non respecté) | Recalculer automatiquement la date de fin effective |
| EXC-LEASE-005 | Signature électronique expirée (> 30 jours) | Relancer les parties, générer un nouveau lien |
| EXC-LEASE-006 | Dégâts constatés dépassant le dépôt de garantie | Calculer et facturer la différence au locataire |

### 8. Notifications déclenchées

| Événement | Canal | Destinataire |
|---|---|---|
| Bail envoyé pour signature | Email + SMS | Locataire + Propriétaire |
| Bail signé par une partie | Email | Autre partie + MANAGER |
| Bail entièrement signé | Email + Push | MANAGER + ORG_ADMIN |
| Bail activé | SMS + Email | Locataire + Propriétaire |
| Échéance de bail dans 90 jours | Email + Push | MANAGER + OWNER |
| Échéance de bail dans 30 jours | Email + SMS | MANAGER + OWNER + TENANT |
| Préavis enregistré | Email | Toutes les parties |
| Bail résilié | Email + SMS | Locataire + Propriétaire + MANAGER |

### 9. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Créer un bail | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Signer un bail | ✗ | ✗ | ✗ | ✗ | ✓ | ✓ |
| Modifier bail actif | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Créer avenant | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Résilier un bail | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Consulter son bail | ✗ | ✗ | ✗ | ✗ | ~ | ~ |
| Générer état des lieux | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |

---

## MODULE 07 — PAIEMENTS <a name="mod-pay"></a>

### 1. Objectif

Gérer tout le cycle financier des encaissements locatifs : génération automatique des appels de fonds, enregistrement des paiements, gestion des retards, quittancement automatique, et suivi du solde par bail.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| `MANAGER` | Suivi des paiements, enregistrement manuel |
| `ACCOUNTANT` | Rapprochement, comptabilisation |
| `TENANT` | Paiement en ligne, consultation historique |
| `OWNER` | Consultation des encaissements (portail) |

### 3. Cas d'utilisation

**UC-PAY-001 — Générer les appels de fonds mensuels**
Le système génère automatiquement le 1er de chaque mois les appels de fonds pour tous les baux actifs.

**UC-PAY-002 — Envoyer les appels de fonds**
Notifier les locataires de leur loyer à payer via SMS, email, et portail avec le montant et la date d'échéance.

**UC-PAY-003 — Enregistrer un paiement reçu**
Enregistrer manuellement un paiement (espèces, chèque, virement) ou automatiquement via webhook Mobile Money.

**UC-PAY-004 — Gérer les paiements partiels**
Enregistrer un paiement inférieur au montant dû, avec suivi du reliquat.

**UC-PAY-005 — Émettre une quittance**
Générer automatiquement une quittance numérotée, horodatée et signée électroniquement après confirmation du paiement.

**UC-PAY-006 — Envoyer une relance de paiement**
Générer et envoyer des relances automatiques aux locataires en retard (G1, G2, G3 selon l'ancienneté du retard).

**UC-PAY-007 — Appliquer une pénalité de retard**
Calculer et appliquer automatiquement les pénalités contractuelles après dépassement de la tolérance.

**UC-PAY-008 — Enregistrer le dépôt de garantie**
Enregistrer l'encaissement du dépôt de garantie au moment de l'entrée dans les lieux.

**UC-PAY-009 — Restituer le dépôt de garantie**
Calculer le montant à restituer après déduction des éventuels dégâts locatifs et soldes impayés.

**UC-PAY-010 — Rechercher et filtrer les paiements**
Filtrer les paiements par bail, locataire, période, statut (payé, en retard, partiel, impayé).

**UC-PAY-011 — Exporter les paiements**
Exporter la liste des paiements en Excel ou CSV pour traitement comptable.

### 4. Règles métier

| # | Règle |
|---|---|
| BR-PAY-001 | Toute transaction financière est en XOF (entiers, sans centimes) |
| BR-PAY-002 | Un appel de fonds est généré automatiquement le 1er de chaque mois pour tous les baux actifs |
| BR-PAY-003 | Le délai de tolérance avant pénalité est de 5 jours calendaires (configurable par bail) |
| BR-PAY-004 | La pénalité de retard est de 10% du montant dû par mois de retard (configurable) |
| BR-PAY-005 | Une quittance ne peut être émise que pour un paiement intégralement confirmé |
| BR-PAY-006 | La numérotation des quittances est séquentielle, unique par organisation, non modifiable |
| BR-PAY-007 | Un paiement confirmé via Mobile Money est irrévocable (sauf remboursement explicite) |
| BR-PAY-008 | Les relances automatiques suivent le calendrier : G1 (J+3), G2 (J+10), G3 (J+15) |
| BR-PAY-009 | Un bail avec plus de 2 mois d'impayés cumulés passe en statut CONTENTIEUX |
| BR-PAY-010 | Le dépôt de garantie est comptabilisé séparément du loyer et ne génère pas de quittance ordinaire |

### 5. Validations

| # | Champ | Règle |
|---|---|---|
| VAL-PAY-001 | Montant | Entier positif en XOF, > 0, max 100 000 000 |
| VAL-PAY-002 | Date de paiement | Ne peut pas être dans le futur |
| VAL-PAY-003 | Référence Mobile Money | Alphanumérique, vérifié contre le webhook |
| VAL-PAY-004 | Canal de paiement | Doit être parmi : MTN_MOMO, ORANGE_MONEY, WAVE, MOOV, VIREMENT, ESPECES, CHEQUE |

### 6. États d'un appel de fonds

| État | Description |
|---|---|
| GENERE | Créé, non encore envoyé |
| ENVOYE | Notifié au locataire |
| PARTIEL | Paiement partiel reçu |
| SOLDE | Paiement intégral confirmé |
| EN_RETARD | Date d'échéance dépassée, non soldé |
| CONTENTIEUX | Retard > 2 mois, dossier contentieux |
| ANNULE | Annulé manuellement (avoir, rectification) |

### 7. Calendrier de relance automatique

| Relance | Déclencheur | Canal | Message type |
|---|---|---|---|
| G1 | J+3 après échéance | SMS + Email | Rappel courtois |
| G2 | J+10 après échéance | SMS + Email + WhatsApp | Mise en demeure amiable |
| G3 | J+15 après échéance | SMS + Email (avec pénalité) | Formal notice |
| CONTENTIEUX | J+60 cumulé | Email + Notification MANAGER | Passage en contentieux |

### 8. Cas d'exception

| # | Exception | Traitement |
|---|---|---|
| EXC-PAY-001 | Webhook Mobile Money reçu en doublon | Idempotence : ignorer si référence déjà traitée |
| EXC-PAY-002 | Montant reçu différent du montant attendu | Enregistrer comme paiement partiel, signaler au MANAGER |
| EXC-PAY-003 | Paiement reçu pour un bail résilié | Alerter le MANAGER, suspendre le traitement |
| EXC-PAY-004 | Erreur de reversement Mobile Money | Alerter ACCOUNTANT, créer ticket de réconciliation |
| EXC-PAY-005 | Quittance déjà émise pour cette période | Bloquer, afficher la quittance existante |

### 9. Notifications déclenchées

| Événement | Canal | Destinataire |
|---|---|---|
| Appel de fonds émis | SMS + Email | Locataire |
| Paiement confirmé | SMS | Locataire |
| Quittance disponible | Email + Push | Locataire |
| Relance G1, G2, G3 | Selon plan | Locataire |
| Impayé signalé | Email | OWNER, MANAGER |
| Passage en contentieux | Email + Push | MANAGER, ORG_ADMIN, OWNER |

### 10. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Enregistrer paiement manuellement | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Émettre quittance | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Annuler un paiement | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |
| Voir historique paiements | ✓ | ✓ | ~ | ✓ | ~ | ~ |
| Configurer pénalités | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Exporter paiements | ✓ | ✓ | ✓ | ✓ | ~ | ✗ |

---

## MODULE 08 — MOBILE MONEY <a name="mod-mm"></a>

### 1. Objectif

Intégrer les opérateurs de paiement mobile dominants en Côte d'Ivoire (MTN MoMo, Orange Money, Wave, Moov Money) via l'agrégateur CinetPay pour permettre la collecte de loyers en ligne avec confirmation automatique et traçabilité complète.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| `TENANT` | Initie le paiement depuis le portail |
| `MANAGER` | Suit le statut des transactions |
| `ACCOUNTANT` | Rapprochement des transactions |
| CinetPay | Agrégateur tiers (système externe) |
| MTN / Orange / Wave / Moov | Opérateurs Mobile Money (systèmes externes) |

### 3. Cas d'utilisation

**UC-MM-001 — Initier un paiement Mobile Money**
Le locataire sélectionne son opérateur, saisit son numéro de téléphone et confirme le montant à payer. Le système génère une demande de paiement via CinetPay.

**UC-MM-002 — Confirmer un paiement via webhook**
CinetPay notifie le système du succès ou de l'échec d'une transaction via un appel HTTP sécurisé (webhook).

**UC-MM-003 — Traiter un paiement en attente**
Vérifier le statut d'une transaction en attente après un délai configurable.

**UC-MM-004 — Générer un code de paiement USSD**
Pour les locataires sans accès internet, générer un code USSD à composer sur téléphone.

**UC-MM-005 — Générer un QR code de paiement (Wave)**
Générer un QR code Wave scannablepar le locataire pour initier le paiement.

**UC-MM-006 — Consulter l'historique des transactions**
Lister toutes les transactions Mobile Money avec statuts, montants, opérateurs et références.

**UC-MM-007 — Rapprocher les transactions**
Comparer les transactions reçues via webhook avec les paiements enregistrés dans le système.

**UC-MM-008 — Initier un remboursement Mobile Money**
Déclencher un remboursement vers le wallet du locataire (ex : dépôt de garantie).

### 4. Règles métier

| # | Règle |
|---|---|
| BR-MM-001 | Seul l'agrégateur CinetPay est utilisé ; les intégrations directes opérateurs ne sont pas requises en Phase 1 |
| BR-MM-002 | Toutes les transactions sont en XOF |
| BR-MM-003 | Chaque paiement initiée a une référence unique générée par le système (idempotence) |
| BR-MM-004 | Un webhook de confirmation est traité de façon idempotente (rejeter les doublons) |
| BR-MM-005 | La signature HMAC du webhook CinetPay est vérifiée avant tout traitement |
| BR-MM-006 | Un paiement en statut PENDING expire après 15 minutes |
| BR-MM-007 | Les frais de transaction Mobile Money sont à la charge du locataire (affichés avant confirmation) |
| BR-MM-008 | Une transaction échouée peut être relancée (max 3 tentatives par session) |
| BR-MM-009 | Les remboursements doivent être validés par un `ORG_ADMIN` ou `ACCOUNTANT` avant exécution |
| BR-MM-010 | Le montant remboursable ne peut dépasser le montant initialement perçu |

### 5. Flux de paiement Mobile Money

```
Locataire → Sélectionne opérateur + saisit numéro
   ↓
Système → Génère référence unique → Appel CinetPay API
   ↓
CinetPay → Envoie notification USSD/App au wallet locataire
   ↓
Locataire → Confirme paiement sur son téléphone
   ↓
Opérateur → Débite wallet → Notifie CinetPay
   ↓
CinetPay → Webhook POST /webhooks/payment {status, ref, montant}
   ↓
Système → Vérifier signature HMAC → Idempotence check
   ↓
Si SUCCESS → Enregistrer paiement → Émettre quittance → Notifier locataire
Si FAILED  → Marquer échec → Notifier locataire → Permettre nouvelle tentative
```

### 6. Opérateurs supportés

| Opérateur | Méthode | Préfixes CI | Frais approx. |
|---|---|---|---|
| MTN Mobile Money | USSD push + App | 05, 25 | 1% |
| Orange Money | USSD push + App | 07, 27 | 1% |
| Wave | QR Code + App | 01 | 0.5% |
| Moov Money | USSD push + App | 01, 21 | 1% |

### 7. Statuts d'une transaction Mobile Money

| Statut | Description |
|---|---|
| INITIATED | Demande envoyée à CinetPay |
| PENDING | En attente de confirmation utilisateur |
| SUCCESS | Confirmé, fonds reçus |
| FAILED | Échec (solde insuffisant, refus, timeout) |
| EXPIRED | Timeout 15 min sans confirmation |
| REFUNDED | Remboursé |

### 8. Cas d'exception

| # | Exception | Traitement |
|---|---|---|
| EXC-MM-001 | Webhook signature invalide | Rejeter silencieusement, logger l'anomalie, alerter ACCOUNTANT |
| EXC-MM-002 | Transaction en doublon (même référence) | Idempotence : renvoyer 200 OK sans retraitement |
| EXC-MM-003 | Montant webhook ≠ montant attendu | Suspendre, alerter ACCOUNTANT pour vérification manuelle |
| EXC-MM-004 | CinetPay API indisponible | File d'attente (retry avec backoff exponentiel), alerter MANAGER |
| EXC-MM-005 | Locataire annule sur téléphone | Marquer FAILED, notifier locataire, proposer nouvelle tentative |
| EXC-MM-006 | Remboursement refusé par l'opérateur | Alerter ACCOUNTANT, proposer virement bancaire alternatif |

### 9. Notifications déclenchées

| Événement | Canal | Destinataire |
|---|---|---|
| Demande de paiement initiée | SMS | Locataire (instructions) |
| Paiement confirmé | SMS + Email | Locataire |
| Paiement échoué | SMS | Locataire |
| Transaction en attente > 10 min | Push | Locataire |
| Remboursement initié | SMS + Email | Locataire |
| Anomalie transaction | Email | ACCOUNTANT, MANAGER |

### 10. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Initier paiement | ✗ | ✗ | ✗ | ✗ | ✗ | ✓ |
| Voir toutes transactions | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Voir ses propres transactions | ✗ | ✗ | ✗ | ✗ | ✗ | ~ |
| Déclencher remboursement | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |
| Configurer CinetPay | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Rapprocher transactions | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |

---

## MODULE 09 — COMPTABILITÉ <a name="mod-acc"></a>

### 1. Objectif

Assurer la tenue d'une comptabilité locative simplifiée : enregistrement automatique des écritures issues des paiements, gestion des charges, calcul de la TVA, production des relevés propriétaires et préparation des documents fiscaux conformes à la législation ivoirienne.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| `ACCOUNTANT` | Saisie manuelle, rapprochement, export |
| `ORG_ADMIN` | Supervision et validation |
| `OWNER` | Consultation des relevés (portail) |
| `MANAGER` | Consultation des soldes |

### 3. Cas d'utilisation

**UC-ACC-001 — Enregistrement automatique des encaissements**
Chaque paiement confirmé génère automatiquement une écriture comptable (débit compte de trésorerie, crédit compte loyer).

**UC-ACC-002 — Saisir une charge locative**
Enregistrer une dépense liée à un bien (travaux, assurance, taxe foncière, honoraires prestataire).

**UC-ACC-003 — Gérer la TVA sur les loyers commerciaux**
Calculer automatiquement la TVA (18%) sur les baux commerciaux assujettis. Générer le bordereau de déclaration TVA mensuel.

**UC-ACC-004 — Gérer la retenue à la source**
Appliquer et comptabiliser la retenue à la source (20%) sur les loyers versés aux propriétaires non-résidents.

**UC-ACC-005 — Générer le relevé propriétaire**
Produire mensuellement ou trimestriellement le relevé détaillé : encaissements, charges, honoraires, retenues, net à reverser.

**UC-ACC-006 — Rapprocher les comptes**
Comparer les paiements enregistrés dans le système avec les relevés Mobile Money et bancaires.

**UC-ACC-007 — Clôturer un exercice**
Clôturer la période comptable mensuelle/annuelle, générer les états de synthèse.

**UC-ACC-008 — Exporter la comptabilité**
Exporter les écritures comptables en format compatible (Excel, CSV, FEC pour logiciels comptables tiers).

**UC-ACC-009 — Consulter le tableau de bord financier**
Vue consolidée : total encaissé, total charges, honoraires, taux d'encaissement, impayés.

### 4. Règles métier

| # | Règle |
|---|---|
| BR-ACC-001 | Toutes les transactions sont en XOF ; aucune devise étrangère n'est gérée nativement |
| BR-ACC-002 | La TVA de 18% est automatiquement calculée sur les loyers commerciaux si le propriétaire est assujetti |
| BR-ACC-003 | La retenue à la source de 20% est prélevée sur les loyers bruts des propriétaires non-résidents |
| BR-ACC-004 | Les honoraires de gestion de l'agence sont calculés sur le loyer brut hors charges |
| BR-ACC-005 | Les charges récupérables sont refacturées au locataire ; les non-récupérables restent à la charge du propriétaire |
| BR-ACC-006 | Une écriture comptable validée ne peut pas être supprimée (principe d'immuabilité) |
| BR-ACC-007 | Le grand livre est constitué d'écritures automatiques + manuelles horodatées et tracées |
| BR-ACC-008 | Le système produit une comptabilité de trésorerie (encaissements/décaissements), pas une comptabilité d'engagement |

### 5. Plan comptable simplifié (adapté CI)

| Compte | Libellé |
|---|---|
| 411xxx | Locataires — Débiteurs |
| 462xxx | Dépôts de garantie reçus |
| 512xxx | Banques et comptes Mobile Money |
| 613xxx | Locations et charges locatives |
| 627xxx | Honoraires de gestion |
| 706xxx | Loyers perçus |
| 4455xx | TVA collectée (loyers commerciaux) |
| 4423xx | Retenues à la source |

### 6. Calcul du relevé propriétaire

```
Loyer brut mensuel
− Charges déduites
− Honoraires agence (% du loyer brut)
− Retenue à la source (si applicable — 20%)
= Net à reverser au propriétaire
```

### 7. Cas d'exception

| # | Exception | Traitement |
|---|---|---|
| EXC-ACC-001 | Paiement partiel reçu | Comptabilisation du montant reçu, solde en attente |
| EXC-ACC-002 | Refus de l'opérateur Mobile Money après écriture provisoire | Contrepasser l'écriture provisoire, alerter ACCOUNTANT |
| EXC-ACC-003 | Erreur de calcul TVA (statut assujetti modifié) | Recalcul possible sur la période non clôturée, avertissement si clôturée |
| EXC-ACC-004 | Export échoué (fichier trop grand) | Segmenter par période, proposer le téléchargement par lot |

### 8. Notifications déclenchées

| Événement | Canal | Destinataire |
|---|---|---|
| Relevé propriétaire disponible | Email + Push | OWNER |
| Reversement effectué | Email + SMS | OWNER |
| Déclaration TVA à soumettre | Email + Push | ACCOUNTANT, ORG_ADMIN |
| Anomalie de rapprochement | Email | ACCOUNTANT |

### 9. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Saisir une charge | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Valider une écriture | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |
| Générer relevé propriétaire | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Voir son relevé | ✗ | ✗ | ✗ | ✗ | ~ | ✗ |
| Exporter comptabilité | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |
| Clôturer exercice | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |

---

## MODULE 10 — MAINTENANCE <a name="mod-maint"></a>

### 1. Objectif

Gérer les demandes d'intervention sur les biens : signalement, qualification, affectation aux prestataires, suivi des devis, réalisation et réception des travaux, facturation et imputation des charges.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| `TENANT` | Soumet des demandes d'intervention |
| `MANAGER` | Qualifie, assigne, suit les OI |
| `VENDOR` | Reçoit les OI, soumet devis et factures |
| `OWNER` | Valide les devis dépassant un seuil (portail) |
| `ACCOUNTANT` | Valide les factures et impute les charges |

### 3. Cas d'utilisation

**UC-MAINT-001 — Soumettre une demande d'intervention (OI)**
Un locataire ou un gestionnaire signale un problème (panne, dégradation, urgence) avec description et photos.

**UC-MAINT-002 — Qualifier l'ordre d'intervention**
Le `MANAGER` détermine la priorité (URGENTE, HAUTE, NORMALE, BASSE) et le type d'intervention.

**UC-MAINT-003 — Sélectionner et affecter un prestataire**
Choisir un prestataire dans l'annuaire interne ou en saisir un nouveau. Lui envoyer l'OI.

**UC-MAINT-004 — Soumettre un devis**
Le prestataire soumet son devis (lignes, quantités, prix unitaires, total TTC).

**UC-MAINT-005 — Valider ou rejeter un devis**
Le `MANAGER` ou `OWNER` (si montant > seuil) approuve ou rejette le devis.

**UC-MAINT-006 — Planifier et réaliser l'intervention**
Définir la date d'intervention, notifier le locataire. Le prestataire marque l'OI comme démarré puis terminé avec rapport.

**UC-MAINT-007 — Réceptionner les travaux**
Le `MANAGER` valide la réception des travaux (avec éventuelles réserves).

**UC-MAINT-008 — Enregistrer et valider la facture prestataire**
Saisir ou importer la facture du prestataire et la valider pour paiement.

**UC-MAINT-009 — Imputer la charge sur le bail ou le bien**
Déterminer si la charge est récupérable sur le locataire ou non-récupérable (à la charge du propriétaire).

**UC-MAINT-010 — Gérer l'annuaire des prestataires**
CRUD des prestataires : spécialité, tarifs, contacts, historique d'interventions, évaluation.

### 4. Règles métier

| # | Règle |
|---|---|
| BR-MAINT-001 | Tout OI doit être qualifié dans les 24h suivant sa création |
| BR-MAINT-002 | Un OI URGENTE doit être assigné dans les 4 heures |
| BR-MAINT-003 | Un devis est obligatoire si le montant estimé dépasse 50 000 XOF (seuil configurable) |
| BR-MAINT-004 | Un devis dépassant 200 000 XOF nécessite la validation du propriétaire (seuil configurable) |
| BR-MAINT-005 | Les charges locatives récupérables (ex : réparation dégât locataire) sont refacturées au locataire |
| BR-MAINT-006 | Les charges non-récupérables (ex : vétusté) sont imputées au propriétaire |
| BR-MAINT-007 | Un OI ne peut être clôturé que si la facture prestataire est validée |
| BR-MAINT-008 | L'historique complet de maintenance d'un bien est conservé indéfiniment |
| BR-MAINT-009 | Un prestataire évalué < 2/5 sur 3 dernières interventions est signalé comme peu fiable |

### 5. Niveaux de priorité

| Priorité | Description | SLA Assignation | SLA Résolution |
|---|---|---|---|
| URGENTE | Risque sécurité, fuite, panne totale | 4 heures | 24 heures |
| HAUTE | Dysfonctionnement majeur | 24 heures | 3 jours |
| NORMALE | Réparation courante | 48 heures | 7 jours |
| BASSE | Amélioration, embellissement | 5 jours | 30 jours |

### 6. Cas d'exception

| # | Exception | Traitement |
|---|---|---|
| EXC-MAINT-001 | OI urgente sans prestataire disponible | Alerter `ORG_ADMIN` immédiatement pour affectation manuelle |
| EXC-MAINT-002 | Devis rejeté par le propriétaire | Notifier prestataire, chercher une alternative |
| EXC-MAINT-003 | Facture prestataire > devis validé | Bloquer validation, demander justification et approbation supplémentaire |
| EXC-MAINT-004 | OI non résolue au-delà du SLA | Escalader automatiquement au niveau supérieur + alerte `ORG_ADMIN` |

### 7. Notifications déclenchées

| Événement | Canal | Destinataire |
|---|---|---|
| OI créée | Push + Email | MANAGER |
| OI assignée | SMS + Email | VENDOR |
| Date intervention confirmée | SMS | Locataire |
| Devis soumis | Push + Email | MANAGER |
| Devis approuvé | SMS + Email | VENDOR |
| Intervention terminée | Push | MANAGER, Locataire |
| Facture validée et paiement initié | Email | VENDOR |

### 8. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT | VENDOR |
|---|---|---|---|---|---|---|---|
| Créer OI | ✓ | ✓ | ✓ | ✗ | ✗ | ✓ | ✗ |
| Qualifier OI | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Assigner prestataire | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Soumettre devis | ✗ | ✗ | ✗ | ✗ | ✗ | ✗ | ✓ |
| Valider devis | ✓ | ✓ | ✓ | ✗ | ~ | ✗ | ✗ |
| Valider facture | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ | ✗ |
| Voir ses OI | ✗ | ✗ | ✗ | ✗ | ✗ | ~ | ~ |

---

## MODULE 11 — CRM <a name="mod-crm"></a>

### 1. Objectif

Gérer la relation client et la prospection commerciale : suivi des leads (locataires et acheteurs potentiels), pipeline de vente/location, historique des visites, relances commerciales et suivi des opportunités.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| `MANAGER` | Gestion du pipeline et des leads |
| `ORG_ADMIN` | Supervision, objectifs, attribution |

### 3. Cas d'utilisation

**UC-CRM-001 — Créer un lead**
Enregistrer un prospect (locataire ou acheteur potentiel) : source, coordonnées, critères de recherche.

**UC-CRM-002 — Qualifier un lead**
Évaluer le sérieux et la solvabilité du prospect, lui attribuer un score de chaleur (Hot/Warm/Cold).

**UC-CRM-003 — Planifier et enregistrer une visite**
Créer un RDV de visite pour un bien donné, envoyer les confirmations aux parties. Enregistrer le compte-rendu post-visite.

**UC-CRM-004 — Gérer le pipeline de location**
Suivre l'avancement d'un lead locataire dans le pipeline : Prospect → Visite → Dossier → Bail.

**UC-CRM-005 — Gérer le pipeline de vente**
Suivre un lead acheteur : Prospect → Visite → Offre → Compromis → Acte.

**UC-CRM-006 — Effectuer des relances commerciales**
Planifier et suivre des relances automatiques ou manuelles vers les prospects inactifs.

**UC-CRM-007 — Consulter le tableau de bord commercial**
Visualiser : leads actifs, visites planifiées, taux de conversion, performance par agent.

**UC-CRM-008 — Gérer les sources de leads**
Configurer et tracker les sources de leads (site web, recommandation, agences partenaires, réseaux sociaux).

### 4. Règles métier

| # | Règle |
|---|---|
| BR-CRM-001 | Un lead sans activité depuis 30 jours passe automatiquement en statut FROID |
| BR-CRM-002 | Un lead sans activité depuis 90 jours est archivé automatiquement |
| BR-CRM-003 | Une visite doit être confirmée 24h avant sa date par SMS automatique |
| BR-CRM-004 | Chaque visite doit faire l'objet d'un compte-rendu saisi dans les 24h |
| BR-CRM-005 | Un lead peut être associé à plusieurs biens (multi-prospection) |
| BR-CRM-006 | La conversion d'un lead en locataire crée automatiquement un dossier locataire pré-rempli |

### 5. Pipeline de location

```
NOUVEAU → CONTACT_ETABLI → VISITE_PLANIFIEE → VISITE_REALISEE
→ DOSSIER_SOUMIS → DOSSIER_APPROUVE → BAIL_SIGNE → CONVERTI
→ PERDU (à tout moment)
```

### 6. Notifications déclenchées

| Événement | Canal | Destinataire |
|---|---|---|
| Nouvelle demande de visite reçue | Push + Email | MANAGER |
| Rappel visite J-1 | SMS | Lead + MANAGER |
| Rappel compte-rendu non saisi | Push | MANAGER (J+1 après visite) |
| Lead inactif 30 jours | Push | MANAGER |

### 7. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Créer/modifier lead | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Voir pipeline équipe | ✓ | ✓ | ~ | ✗ | ✗ | ✗ |
| Configurer pipeline | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Voir stats CRM | ✓ | ✓ | ~ | ✗ | ✗ | ✗ |

---

## MODULE 12 — GED (GESTION ÉLECTRONIQUE DE DOCUMENTS) <a name="mod-ged"></a>

### 1. Objectif

Centraliser, organiser et sécuriser tous les documents produits ou collectés par la plateforme : contrats, quittances, pièces KYC, états des lieux, documents fonciers, relevés, factures. Permettre la génération PDF automatique et la signature électronique.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| Tous rôles | Accès selon périmètre défini |
| `MANAGER` | Upload, organisation, partage |
| `TENANT` | Consultation de ses documents |
| `OWNER` | Consultation de ses documents |

### 3. Cas d'utilisation

**UC-GED-001 — Téléverser un document**
Uploader un document (PDF, JPG, PNG) et l'associer à une entité (bien, bail, locataire, propriétaire).

**UC-GED-002 — Générer un document PDF automatiquement**
Générer à partir d'un template : bail, quittance, état des lieux, relevé propriétaire, courrier de relance.

**UC-GED-003 — Organiser les documents par dossier**
Classer les documents dans une arborescence logique : Biens / Baux / Locataires / Propriétaires / Finances.

**UC-GED-004 — Rechercher un document**
Recherche full-text par nom, type, entité associée, date.

**UC-GED-005 — Signer électroniquement un document**
Envoyer un document pour signature électronique (bail, avenant, état des lieux) via le module dédié.

**UC-GED-006 — Partager un document de façon sécurisée**
Générer un lien de partage temporaire et sécurisé (expiration configurable, téléchargement limité).

**UC-GED-007 — Archiver des documents**
Archiver automatiquement les documents liés à des bails terminés selon la politique de rétention.

**UC-GED-008 — Consulter ses propres documents (Portail)**
Le locataire ou propriétaire accède à ses documents uniquement via son portail.

### 4. Règles métier

| # | Règle |
|---|---|
| BR-GED-001 | Formats acceptés : PDF, JPG, PNG, WEBP, DOCX. Taille max : 20 Mo par fichier |
| BR-GED-002 | Chaque document est associé à une entité (bail, locataire, bien) — pas de document orphelin |
| BR-GED-003 | Les quittances générées sont numérotées séquentiellement et immuables après émission |
| BR-GED-004 | Les documents KYC locataires sont chiffrés at-rest (AES-256) |
| BR-GED-005 | La politique de rétention légale est de 10 ans pour les contrats et quittances |
| BR-GED-006 | Un document signé électroniquement contient un certificat d'audit (qui, quand, IP) |
| BR-GED-007 | Un lien de partage temporaire expire après la durée configurée (défaut : 7 jours) |
| BR-GED-008 | Les accès aux documents sont tracés (log d'audit : qui a consulté quoi, quand) |

### 5. Templates de documents gérés

| Template | Module source | Format |
|---|---|---|
| Bail d'habitation | Leasing | PDF |
| Bail commercial | Leasing | PDF |
| Avenant | Leasing | PDF |
| Quittance de loyer | Financial | PDF |
| État des lieux d'entrée | Leasing | PDF |
| État des lieux de sortie | Leasing | PDF |
| Relevé propriétaire | Accounting | PDF |
| Lettre de relance G1/G2/G3 | Financial | PDF |
| Ordre d'intervention | Maintenance | PDF |
| Devis prestataire | Maintenance | PDF |

### 6. Cas d'exception

| # | Exception | Traitement |
|---|---|---|
| EXC-GED-001 | Fichier > 20 Mo | Rejeter avec message, proposer compression |
| EXC-GED-002 | Format non supporté | Message d'erreur explicite avec liste des formats acceptés |
| EXC-GED-003 | Signature électronique expirée | Renvoyer pour nouvelle signature |
| EXC-GED-004 | Lien de partage expiré | Afficher page d'expiration, inviter à contacter l'agence |

### 7. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Uploader document | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Générer PDF | ✓ | ✓ | ✓ | ✓ | ✗ | ✗ |
| Supprimer document | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Partager un document | ✓ | ✓ | ✓ | ✓ | ~ | ✗ |
| Voir ses propres docs | ✗ | ✗ | ✗ | ✗ | ~ | ~ |
| Voir tous les docs | ✓ | ✓ | ~ | ~ | ✗ | ✗ |

---

## MODULE 13 — INTELLIGENCE ARTIFICIELLE <a name="mod-ai"></a>

### 1. Objectif

Augmenter la productivité de la plateforme via des fonctionnalités d'IA : scoring de solvabilité locataire, estimation de loyer/prix de vente, OCR automatique des documents KYC, détection des risques d'impayés, génération de contenus et assistance contextuelle.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| `MANAGER` | Utilise les recommandations IA, peut les corriger |
| `TENANT` | Bénéficie du scoring lors du dépôt de dossier |
| `ORG_ADMIN` | Configure les seuils et paramètres IA |

### 3. Cas d'utilisation

**UC-AI-001 — Scoring automatique de solvabilité locataire**
Lors de la soumission d'un dossier locataire, le système calcule un score (A–E) basé sur les revenus déclarés, le ratio loyer/revenus, l'historique de paiement (si disponible), et la qualité des documents.

**UC-AI-002 — OCR et extraction automatique des documents KYC**
Analyser automatiquement les documents téléversés (CNI, bulletins de salaire) et pré-remplir les champs du formulaire locataire.

**UC-AI-003 — Estimation automatique du loyer**
Sur la base des caractéristiques du bien (type, surface, commune, quartier, équipements) et des données marché, suggérer une fourchette de loyer.

**UC-AI-004 — Estimation du prix de vente**
Analyser le bien et le marché local pour suggérer un prix de vente indicatif.

**UC-AI-005 — Prédiction du risque d'impayé**
Analyser le comportement de paiement d'un locataire actif (retards, montants partiels) et calculer un score de risque mensuel.

**UC-AI-006 — Génération automatique de descriptions de biens**
À partir des caractéristiques saisies, générer automatiquement une description d'annonce attractive.

**UC-AI-007 — Génération de courriers de relance personnalisés**
Rédiger des lettres de relance adaptées au contexte (montant, délai, ton).

**UC-AI-008 — Détection de doublons**
Identifier automatiquement les doublons potentiels de biens, locataires ou propriétaires.

**UC-AI-009 — Assistant contextuel (Chatbot)**
Répondre aux questions fréquentes des locataires et propriétaires sur le portail (solde, procédures, etc.).

### 4. Règles métier

| # | Règle |
|---|---|
| BR-AI-001 | Tout score ou estimation IA est affiché avec une mention "Indicatif — à valider par un professionnel" |
| BR-AI-002 | Le MANAGER peut toujours corriger manuellement un score ou une estimation IA |
| BR-AI-003 | Les décisions métier (refus de dossier, fixation de loyer) restent humaines ; l'IA ne fait que recommander |
| BR-AI-004 | Les données utilisées par l'IA sont anonymisées pour l'entraînement des modèles |
| BR-AI-005 | Le locataire est informé que son dossier fait l'objet d'un traitement automatisé (conformité Loi CI 2013-450) |
| BR-AI-006 | L'OCR suggère des valeurs ; l'utilisateur doit valider chaque champ extrait |
| BR-AI-007 | La précision minimale acceptable pour l'OCR est de 85% ; en dessous, le champ reste vide |

### 5. Scoring de solvabilité — Algorithme (Phase 1)

```
Score basé sur règles (rule-based) :

1. Ratio loyer/revenus :
   < 25%  → +30 points
   25–33% → +20 points
   33–40% → +10 points
   > 40%  → +0 points

2. Stabilité emploi :
   CDI > 2 ans   → +25 points
   CDI < 2 ans   → +15 points
   CDD           → +10 points
   Indépendant   → +10 points
   Sans emploi   → +0 points

3. Qualité documents :
   Complets + récents → +20 points
   Incomplets         → +0 points

4. Historique paiement (si disponible) :
   0 incident sur 24 mois → +25 points
   1–2 incidents          → +10 points
   > 2 incidents          → +0 points

Score total / 100 → Grade A (80+), B (60–79), C (40–59), D (20–39), E (<20)
```

### 6. Cas d'exception

| # | Exception | Traitement |
|---|---|---|
| EXC-AI-001 | OCR ne peut pas lire le document | Afficher message "Lecture impossible, saisie manuelle requise" |
| EXC-AI-002 | Données insuffisantes pour l'estimation | Afficher "Données insuffisantes pour une estimation fiable" |
| EXC-AI-003 | Score IA contesté par le MANAGER | Permettre override manuel avec motif obligatoire, loguer la décision |

### 7. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Voir score IA | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Override score | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Configurer seuils IA | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Utiliser chatbot | ✗ | ✗ | ✗ | ✗ | ✓ | ✓ |
| Voir estimation loyer | ✓ | ✓ | ✓ | ✗ | ~ | ✗ |

---

## MODULE 14 — REPORTING & ANALYTICS <a name="mod-rep"></a>

### 1. Objectif

Fournir à toutes les parties prenantes les indicateurs de performance, tableaux de bord et rapports nécessaires à la prise de décision : occupation, revenus, charges, taux d'encaissement, performance commerciale.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| `ORG_ADMIN` | Tableau de bord global organisation |
| `MANAGER` | Reporting de son portefeuille |
| `ACCOUNTANT` | Rapports financiers |
| `OWNER` | Reporting de son portfolio (portail) |

### 3. Cas d'utilisation

**UC-REP-001 — Tableau de bord agence**
Vue globale : nombre de biens gérés, taux d'occupation, loyers encaissés du mois, impayés en cours, OI ouvertes.

**UC-REP-002 — Rapport de revenus locatifs**
Rapport détaillé par bien, propriétaire, période : loyers bruts, charges, honoraires, net versé.

**UC-REP-003 — Rapport de taux d'occupation**
Taux d'occupation par bien, par type de bien, par commune, avec historique sur 12–24 mois.

**UC-REP-004 — Rapport des impayés**
Liste des appels de fonds en retard avec ancienneté, montant, statut de relance.

**UC-REP-005 — Rapport de performance commerciale**
Leads traités, taux de conversion, visites réalisées, baux signés par agent.

**UC-REP-006 — Rapport fiscal propriétaire**
Document annuel récapitulatif pour déclaration fiscale : loyers perçus, charges, retenues à la source.

**UC-REP-007 — Exporter un rapport**
Télécharger tout rapport en PDF, Excel ou CSV.

**UC-REP-008 — Planifier un rapport récurrent**
Configurer l'envoi automatique d'un rapport par email à une fréquence définie.

**UC-REP-009 — Dashboard propriétaire (Portail)**
Vue synthétique : biens, statuts, loyers encaissés, solde à percevoir, incidents en cours.

### 4. Règles métier

| # | Règle |
|---|---|
| BR-REP-001 | Les données de reporting sont mises à jour en temps quasi-réel (< 5 minutes de latence) |
| BR-REP-002 | Un OWNER ne voit que les données relatives à ses propres biens et baux |
| BR-REP-003 | Les données historiques sont conservées sur 5 ans minimum dans le module reporting |
| BR-REP-004 | Un rapport planifié envoyé par email est au format PDF uniquement |
| BR-REP-005 | Le taux d'occupation est calculé en jours (nombre de jours occupés / nombre de jours total de la période) |

### 5. KPIs principaux

| KPI | Définition | Fréquence |
|---|---|---|
| Taux d'occupation | Jours occupés / Jours total × 100 | Mensuel |
| Taux d'encaissement | Loyers payés / Loyers appelés × 100 | Mensuel |
| Délai moyen de paiement | Moy. (Date paiement − Date échéance) | Mensuel |
| Taux de conversion leads | Baux signés / Leads traités × 100 | Mensuel |
| Revenu moyen par bien | Total loyers / Nombre biens actifs | Mensuel |
| Taux d'impayés | Loyers impayés / Loyers appelés × 100 | Mensuel |

### 6. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Dashboard global | ✓ | ✓ | ~ | ~ | ✗ | ✗ |
| Rapport financier complet | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |
| Rapport son portefeuille | ✓ | ✓ | ~ | ✓ | ~ | ✗ |
| Rapport fiscal propriétaire | ✓ | ✓ | ✓ | ✓ | ~ | ✗ |
| Exporter rapport | ✓ | ✓ | ✓ | ✓ | ~ | ✗ |
| Planifier rapport récurrent | ✓ | ✓ | ✗ | ✓ | ✗ | ✗ |

---

## MODULE 15 — NOTIFICATIONS <a name="mod-notif"></a>

### 1. Objectif

Centraliser l'ensemble des communications sortantes de la plateforme : SMS, email, WhatsApp, notifications push, et in-app. Gérer les templates, la planification, les préférences utilisateurs et le suivi de délivrabilité.

### 2. Acteurs

| Acteur | Interaction |
|---|---|
| Tous modules | Émettent des demandes de notification |
| Tous utilisateurs | Reçoivent les notifications, configurent leurs préférences |
| `ORG_ADMIN` | Configure les templates et préférences globales |

### 3. Cas d'utilisation

**UC-NOTIF-001 — Envoyer une notification automatique**
Un module déclenche une notification (ex : paiement reçu). Le module Notifications sélectionne le canal approprié et envoie le message.

**UC-NOTIF-002 — Envoyer une notification manuelle**
Le `MANAGER` compose et envoie un message personnalisé à un locataire, propriétaire ou groupe.

**UC-NOTIF-003 — Configurer les templates de notification**
L'`ORG_ADMIN` personnalise les modèles de messages (ton, signature, contenu) par type d'événement.

**UC-NOTIF-004 — Gérer les préférences de notification**
Chaque utilisateur choisit ses canaux préférés et désactive certains types de notifications non essentiels.

**UC-NOTIF-005 — Planifier une notification**
Programmer l'envoi d'une notification à une date/heure précise.

**UC-NOTIF-006 — Consulter l'historique des notifications**
Voir toutes les notifications envoyées avec statuts de délivrabilité (envoyé, délivré, lu, échoué).

**UC-NOTIF-007 — Gérer les notifications en masse**
Envoyer un message à tous les locataires d'un bien ou d'un portefeuille (ex : travaux planifiés).

**UC-NOTIF-008 — Gérer les tentatives de renvoi**
En cas d'échec de délivrance, le système tente automatiquement le renvoi sur un canal alternatif.

### 4. Canaux supportés

| Canal | Usage | Opérateur/Service |
|---|---|---|
| SMS | Urgent, factuel, locataires sans smartphone | Infobip (couverture CI) |
| Email | Détaillé, documents, relevés | Amazon SES |
| WhatsApp Business | Rich media, locataires actifs sur WhatsApp | Infobip / Cloud API |
| Push (Web/App) | In-app, web push | Firebase FCM |
| In-App | Notifications dans l'interface | Natif |

### 5. Règles métier

| # | Règle |
|---|---|
| BR-NOTIF-001 | Toute notification est enregistrée dans le journal avec statut de délivrabilité |
| BR-NOTIF-002 | Les notifications critiques (relance G3, résiliation) sont envoyées en SMS obligatoirement |
| BR-NOTIF-003 | L'utilisateur peut désactiver les notifications non-critiques mais pas les notifications légales |
| BR-NOTIF-004 | En cas d'échec SMS, le système tente l'email ; en cas d'échec email, une alerte est créée pour le MANAGER |
| BR-NOTIF-005 | Les SMS ne peuvent être envoyés qu'entre 07h00 et 21h00 (heure locale CI) |
| BR-NOTIF-006 | Les templates incluent obligatoirement le nom de l'organisation émettrice |
| BR-NOTIF-007 | Aucune donnée financière sensible (numéro de compte) ne doit apparaître dans un SMS |
| BR-NOTIF-008 | Le désinscription (opt-out) SMS doit être respecté en < 24h (conformité ARTCI) |

### 6. Classification des notifications

| Type | Exemples | Désactivable par l'utilisateur |
|---|---|---|
| CRITIQUE | Compte verrouillé, fraude détectée | Non |
| LEGALE | Relance G3, résiliation bail | Non |
| FINANCIERE | Appel de fonds, quittance, paiement | Non |
| OPERATIONNELLE | OI assignée, visite planifiée | Oui |
| INFORMATIVE | Nouvelle fonctionnalité, newsletters | Oui |

### 7. Cas d'exception

| # | Exception | Traitement |
|---|---|---|
| EXC-NOTIF-001 | Numéro de téléphone invalide | Logger l'échec, alerter MANAGER pour mise à jour |
| EXC-NOTIF-002 | Email bounced | Marquer email invalide sur le profil, notifier MANAGER |
| EXC-NOTIF-003 | Quota SMS dépassé (plan SaaS) | Basculer sur email uniquement, alerter ORG_ADMIN |
| EXC-NOTIF-004 | Infobip indisponible | File d'attente avec retry, fallback email |
| EXC-NOTIF-005 | Opt-out locataire sur SMS | Marquer le profil opt-out, n'utiliser que l'email |

### 8. Permissions (RBAC)

| Action | SUPER_ADMIN | ORG_ADMIN | MANAGER | ACCOUNTANT | OWNER | TENANT |
|---|---|---|---|---|---|---|
| Envoyer notif manuelle | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |
| Configurer templates | ✓ | ✓ | ✗ | ✗ | ✗ | ✗ |
| Configurer ses préférences | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Voir journal notifications | ✓ | ✓ | ~ | ✗ | ~ | ~ |
| Envoi en masse | ✓ | ✓ | ✓ | ✗ | ✗ | ✗ |

---

## MATRICE DE TRAÇABILITÉ <a name="matrice"></a>

### Couverture modules / phases

| Module | Phase 1 MVP | Phase 2 | Priorité |
|---|---|---|---|
| Authentification | ✓ | — | P0 |
| Gestion utilisateurs | ✓ | — | P0 |
| Gestion immobilière | ✓ | — | P0 |
| Locataires | ✓ | — | P0 |
| Propriétaires | ✓ | — | P0 |
| Contrats & Baux | ✓ | — | P0 |
| Paiements | ✓ | — | P0 |
| Mobile Money | ✓ | — | P0 |
| Comptabilité | Basique ✓ | Avancée | P1 |
| Maintenance | — | ✓ | P1 |
| CRM | Basique ✓ | Avancée | P1 |
| GED | ✓ | — | P0 |
| IA | Scoring ✓ | OCR, Chatbot | P2 |
| Reporting | Basique ✓ | Avancée | P1 |
| Notifications | ✓ | — | P0 |

### Synthèse des cas d'utilisation

| Module | Nombre de UC |
|---|---|
| Authentification | 8 |
| Gestion utilisateurs | 8 |
| Gestion immobilière | 11 |
| Locataires | 8 |
| Propriétaires | 7 |
| Contrats & Baux | 13 |
| Paiements | 11 |
| Mobile Money | 8 |
| Comptabilité | 9 |
| Maintenance | 10 |
| CRM | 8 |
| GED | 8 |
| IA | 9 |
| Reporting | 9 |
| Notifications | 8 |
| **TOTAL** | **135 UC** |

### Synthèse des règles métier

| Module | Nombre de BR |
|---|---|
| Authentification | 10 |
| Gestion utilisateurs | 7 |
| Gestion immobilière | 10 |
| Locataires | 8 |
| Propriétaires | 7 |
| Contrats & Baux | 14 |
| Paiements | 10 |
| Mobile Money | 10 |
| Comptabilité | 8 |
| Maintenance | 9 |
| CRM | 6 |
| GED | 8 |
| IA | 7 |
| Reporting | 5 |
| Notifications | 8 |
| **TOTAL** | **127 BR** |

---

*Document rédigé par : Business Analysis Team — KILIE IMMO*
*Version : 1.0 — Juin 2026*
*Approuvé par : Direction Produit*
*Prochaine révision : Avant sprint 1 — Phase 1*
