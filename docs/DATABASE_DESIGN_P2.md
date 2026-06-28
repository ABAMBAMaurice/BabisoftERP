# KILIE IMMO — Modèle Physique MySQL
## Partie 2 : Toutes les tables, colonnes, clés, index, contraintes

---

## CONVENTIONS DE LECTURE

```
PK  = Primary Key
UK  = Unique Key
FK  = Foreign Key
IDX = Index ordinaire
FT  = FULLTEXT index
VG  = Colonne virtuelle générée (VIRTUAL GENERATED)
NN  = NOT NULL
D   = DEFAULT
AI  = AUTO_INCREMENT
```

---

## DOMAINE 1 — IAM & SÉCURITÉ

---

### `organisations`
> Tenants SaaS — racine de toute isolation multi-tenant

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | Identifiant interne |
| `uuid` | CHAR(36) | UK NN | UUID v4 exposé aux APIs |
| `name` | VARCHAR(255) | NN | Nom de l'organisation |
| `slug` | VARCHAR(100) | UK NN | Identifiant URL unique |
| `type` | ENUM('AGENCE','GESTIONNAIRE','PARTICULIER','SYNDIC') | NN | Type d'organisation |
| `status` | ENUM('TRIAL','ACTIVE','SUSPENDED','CANCELLED','ARCHIVED') | NN D='TRIAL' | Statut SaaS |
| `subscription_plan_id` | BIGINT UNSIGNED | FK NN | Plan souscrit |
| `trial_ends_at` | DATETIME | NULL | Date fin période d'essai |
| `max_users` | SMALLINT UNSIGNED | NN D=5 | Limite utilisateurs |
| `max_properties` | INT UNSIGNED | NN D=50 | Limite biens |
| `max_units` | INT UNSIGNED | NN D=200 | Limite unités |
| `logo_url` | VARCHAR(500) | NULL | URL logo CDN |
| `primary_color` | VARCHAR(7) | NULL D='#1E40AF' | Couleur marque (hex) |
| `country_code` | CHAR(2) | NN D='CI' | Code pays ISO |
| `timezone` | VARCHAR(50) | NN D='Africa/Abidjan' | Fuseau horaire |
| `currency` | CHAR(3) | NN D='XOF' | Devise |
| `settings` | JSON | NULL | Préférences configurables |
| `billing_email` | VARCHAR(255) | NULL | Email facturation SaaS |
| `billing_phone` | VARCHAR(20) | NULL | Téléphone facturation |
| `created_by` | BIGINT UNSIGNED | NULL | Utilisateur créateur |
| `created_at` | DATETIME | NN D=NOW() | Date création |
| `updated_at` | DATETIME | NULL | Dernière modification |
| `deleted_at` | DATETIME | NULL | Soft delete |

**Clés & Index :**
```
PRIMARY KEY (id)
UNIQUE KEY uk_organisations_uuid (uuid)
UNIQUE KEY uk_organisations_slug (slug)
INDEX idx_organisations_status (status)
INDEX idx_organisations_deleted_at (deleted_at)
```
**FK :** `subscription_plan_id → subscription_plans.id`

---

### `users`
> Tous les utilisateurs internes de la plateforme

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | Identifiant interne |
| `organisation_id` | BIGINT UNSIGNED | FK NN | Organisation d'appartenance |
| `uuid` | CHAR(36) | UK NN | UUID v4 API |
| `email` | VARCHAR(255) | NN | Email (login) |
| `phone` | VARCHAR(20) | NULL | Téléphone E.164 |
| `password_hash` | VARCHAR(255) | NN | bcrypt hash |
| `first_name` | VARCHAR(100) | NN | Prénom |
| `last_name` | VARCHAR(100) | NN | Nom de famille |
| `avatar_url` | VARCHAR(500) | NULL | Photo de profil CDN |
| `locale` | VARCHAR(10) | NN D='fr-CI' | Langue interface |
| `timezone` | VARCHAR(50) | NN D='Africa/Abidjan' | Fuseau horaire |
| `status` | ENUM('PENDING','ACTIVE','INACTIVE','SUSPENDED') | NN D='PENDING' | Statut compte |
| `is_mfa_enabled` | TINYINT(1) | NN D=0 | MFA activé |
| `mfa_secret` | VARCHAR(100) | NULL | Secret TOTP (chiffré) |
| `login_attempts` | TINYINT UNSIGNED | NN D=0 | Tentatives échouées |
| `locked_until` | DATETIME | NULL | Verrouillage temporaire |
| `last_login_at` | DATETIME | NULL | Dernière connexion |
| `last_login_ip` | VARCHAR(45) | NULL | IP dernière connexion |
| `email_verified_at` | DATETIME | NULL | Date vérification email |
| `phone_verified_at` | DATETIME | NULL | Date vérification téléphone |
| `created_by` | BIGINT UNSIGNED | NULL FK | Créé par |
| `created_at` | DATETIME | NN D=NOW() | Date création |
| `updated_at` | DATETIME | NULL | Dernière modification |
| `deleted_at` | DATETIME | NULL | Soft delete |

**Clés & Index :**
```
PRIMARY KEY (id)
UNIQUE KEY uk_users_uuid (uuid)
UNIQUE KEY uk_users_org_email (organisation_id, email)
INDEX idx_users_org_status (organisation_id, status)
INDEX idx_users_email (email)
INDEX idx_users_phone (phone)
INDEX idx_users_deleted_at (deleted_at)
```
**FK :** `organisation_id → organisations.id (RESTRICT)`

---

### `roles`
> Rôles prédéfinis (système) et personnalisés par organisation

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NULL | NULL = rôle système global |
| `name` | VARCHAR(100) | NN | Libellé affiché |
| `code` | VARCHAR(50) | NN | Code technique (ORG_ADMIN, MANAGER…) |
| `description` | TEXT | NULL | Description du rôle |
| `is_system` | TINYINT(1) | NN D=0 | Non modifiable si 1 |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

**Clés & Index :**
```
PRIMARY KEY (id)
UNIQUE KEY uk_roles_org_code (organisation_id, code)
INDEX idx_roles_is_system (is_system)
```

---

### `user_roles`
> Association utilisateurs ↔ rôles

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `user_id` | BIGINT UNSIGNED | FK NN | |
| `role_id` | BIGINT UNSIGNED | FK NN | |
| `granted_by` | BIGINT UNSIGNED | FK NULL | Admin ayant attribué |
| `granted_at` | DATETIME | NN D=NOW() | |
| `expires_at` | DATETIME | NULL | Expiration si temporaire |
| `revoked_at` | DATETIME | NULL | Date révocation |

**Clés & Index :**
```
PRIMARY KEY (id)
UNIQUE KEY uk_user_roles_user_role (user_id, role_id)
INDEX idx_user_roles_role_id (role_id)
INDEX idx_user_roles_expires_at (expires_at)
```
**FK :** `user_id → users.id (CASCADE)`, `role_id → roles.id (CASCADE)`

---

### `permissions`
> Permissions atomiques du système

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `module` | VARCHAR(50) | NN | Module (property, lease…) |
| `action` | VARCHAR(50) | NN | Action (create, read, update, delete) |
| `resource` | VARCHAR(100) | NN | Ressource cible |
| `code` | VARCHAR(150) | UK NN | Code unique : `module.action.resource` |
| `description` | VARCHAR(255) | NULL | |
| `created_at` | DATETIME | NN D=NOW() | |

**Clés & Index :**
```
PRIMARY KEY (id)
UNIQUE KEY uk_permissions_code (code)
INDEX idx_permissions_module (module)
```

---

### `role_permissions`
> Liaison rôles ↔ permissions

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `role_id` | BIGINT UNSIGNED | FK NN | |
| `permission_id` | BIGINT UNSIGNED | FK NN | |

```
PRIMARY KEY (role_id, permission_id)
INDEX idx_rp_permission_id (permission_id)
```

---

### `user_permissions`
> Overrides de permissions par utilisateur (exceptions)

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `user_id` | BIGINT UNSIGNED | FK NN | |
| `permission_id` | BIGINT UNSIGNED | FK NN | |
| `granted` | TINYINT(1) | NN | 1=accordé explicitement, 0=refusé |
| `granted_by` | BIGINT UNSIGNED | FK NULL | |
| `granted_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_user_permissions (user_id, permission_id)
```

---

### `sessions`
> Sessions actives — rotation rapide, archivage fréquent

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `user_id` | BIGINT UNSIGNED | FK NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | Dénormalisé pour isolation |
| `token_hash` | VARCHAR(255) | UK NN | Hash du JWT |
| `refresh_token_hash` | VARCHAR(255) | UK NN | Hash du refresh token |
| `ip_address` | VARCHAR(45) | NULL | IPv4/IPv6 |
| `user_agent` | VARCHAR(500) | NULL | |
| `device_type` | VARCHAR(50) | NULL | web, mobile, tablet |
| `expires_at` | DATETIME | NN | Expiration JWT |
| `refresh_expires_at` | DATETIME | NN | Expiration refresh |
| `created_at` | DATETIME | NN D=NOW() | |
| `revoked_at` | DATETIME | NULL | Révocation explicite |

```
PRIMARY KEY (id)
UNIQUE KEY uk_sessions_token (token_hash)
UNIQUE KEY uk_sessions_refresh (refresh_token_hash)
INDEX idx_sessions_user_id (user_id, expires_at)
INDEX idx_sessions_expires_at (expires_at)
```
*Partition RANGE sur `created_at` — conservation 90 jours*

---

### `invitations`
> Invitations en attente d'acceptation

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `role_id` | BIGINT UNSIGNED | FK NN | |
| `email` | VARCHAR(255) | NN | |
| `token_hash` | VARCHAR(255) | UK NN | Hashed invitation token |
| `invited_by` | BIGINT UNSIGNED | FK NN | |
| `expires_at` | DATETIME | NN | +72h par défaut |
| `accepted_at` | DATETIME | NULL | |
| `accepted_by_user_id` | BIGINT UNSIGNED | FK NULL | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_invitations_token (token_hash)
UNIQUE KEY uk_invitations_org_email (organisation_id, email)
INDEX idx_invitations_expires_at (expires_at)
```

---

### `password_reset_tokens`
> Tokens de réinitialisation de mot de passe

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `user_id` | BIGINT UNSIGNED | FK NN | |
| `token_hash` | VARCHAR(255) | UK NN | |
| `expires_at` | DATETIME | NN | +1h |
| `used_at` | DATETIME | NULL | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_prt_token (token_hash)
INDEX idx_prt_user_id (user_id)
INDEX idx_prt_expires_at (expires_at)
```

---

## DOMAINE 2 — SUBSCRIPTION

---

### `subscription_plans`
> Plans SaaS disponibles

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `name` | VARCHAR(100) | NN | Starter, Pro, Enterprise |
| `code` | VARCHAR(50) | UK NN | STARTER, PRO, ENTERPRISE |
| `price_monthly_xof` | BIGINT UNSIGNED | NN | Prix mensuel en XOF |
| `price_annual_xof` | BIGINT UNSIGNED | NN | Prix annuel en XOF |
| `max_users` | SMALLINT UNSIGNED | NN | Limite utilisateurs |
| `max_properties` | INT UNSIGNED | NN | Limite biens |
| `max_units` | INT UNSIGNED | NN | Limite unités |
| `max_storage_gb` | SMALLINT UNSIGNED | NN | Stockage documents |
| `features` | JSON | NN | Features activées par plan |
| `is_active` | TINYINT(1) | NN D=1 | |
| `sort_order` | TINYINT UNSIGNED | NN D=0 | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_plans_code (code)
INDEX idx_plans_is_active (is_active)
```

---

### `organisation_subscriptions`
> Abonnement actif d'une organisation

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK UK NN | |
| `plan_id` | BIGINT UNSIGNED | FK NN | |
| `status` | ENUM('TRIAL','ACTIVE','PAST_DUE','CANCELLED','EXPIRED') | NN | |
| `billing_cycle` | ENUM('MONTHLY','ANNUAL') | NN D='MONTHLY' | |
| `price_xof` | BIGINT UNSIGNED | NN | Prix effectif payé |
| `starts_at` | DATETIME | NN | |
| `ends_at` | DATETIME | NULL | NULL = ouvert |
| `cancelled_at` | DATETIME | NULL | |
| `cancellation_reason` | TEXT | NULL | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_org_subscriptions_org (organisation_id)
INDEX idx_org_sub_status (status)
INDEX idx_org_sub_ends_at (ends_at)
```

---

### `subscription_invoices`
> Factures SaaS émises aux organisations

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `subscription_id` | BIGINT UNSIGNED | FK NN | |
| `invoice_number` | VARCHAR(50) | UK NN | KI-2026-00001 |
| `period_start` | DATE | NN | |
| `period_end` | DATE | NN | |
| `amount_xof` | BIGINT UNSIGNED | NN | |
| `status` | ENUM('DRAFT','SENT','PAID','OVERDUE','VOID') | NN | |
| `due_date` | DATE | NN | |
| `paid_at` | DATETIME | NULL | |
| `payment_channel` | VARCHAR(50) | NULL | |
| `payment_reference` | VARCHAR(255) | NULL | |
| `document_id` | BIGINT UNSIGNED | FK NULL | PDF facture |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_sub_invoices_number (invoice_number)
INDEX idx_sub_invoices_org_status (organisation_id, status)
INDEX idx_sub_invoices_due_date (due_date)
```

---

## DOMAINE 3 — PROPERTY

---

### `properties`
> Catalogue des biens immobiliers — table centrale Property

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `uuid` | CHAR(36) | UK NN | UUID API |
| `reference_code` | VARCHAR(50) | NN | BN-2024-00001 |
| `name` | VARCHAR(255) | NN | Libellé bien |
| `type` | ENUM('VILLA','APPARTEMENT','STUDIO','CHAMBRE','BUREAU','LOCAL_COMMERCIAL','ENTREPOT','TERRAIN','IMMEUBLE','AUTRE') | NN | |
| `status` | ENUM('BROUILLON','DISPONIBLE','EN_LOCATION','EN_VENTE','VENDU','SUSPENDU','ARCHIVE') | NN D='BROUILLON' | |
| `primary_owner_id` | BIGINT UNSIGNED | FK NN | Propriétaire principal |
| `manager_id` | BIGINT UNSIGNED | FK NULL | Gestionnaire assigné |
| `commune` | VARCHAR(100) | NN | Commune CI |
| `quartier` | VARCHAR(150) | NN | Quartier |
| `ilot` | VARCHAR(100) | NULL | Ilot cadastral |
| `lot_number` | VARCHAR(50) | NULL | Numéro de lot |
| `street_description` | TEXT | NULL | Description accès |
| `gps_lat` | DECIMAL(10,8) | NULL | Latitude WGS84 |
| `gps_lng` | DECIMAL(11,8) | NULL | Longitude WGS84 |
| `total_surface` | DECIMAL(10,2) | NULL | Surface totale m² |
| `land_surface` | DECIMAL(10,2) | NULL | Surface terrain m² |
| `rooms_count` | TINYINT UNSIGNED | NULL | Nombre de pièces |
| `bedrooms_count` | TINYINT UNSIGNED | NULL | Chambres |
| `bathrooms_count` | TINYINT UNSIGNED | NULL | Salles de bain |
| `floor_number` | TINYINT | NULL | Étage (immeuble) |
| `floors_total` | TINYINT UNSIGNED | NULL | Nombre d'étages total |
| `parking_count` | TINYINT UNSIGNED | NULL D=0 | Places de parking |
| `year_built` | YEAR | NULL | Année construction |
| `condition` | ENUM('NEUF','BON','MOYEN','MAUVAIS','RENOVATION') | NULL | État du bien |
| `indicative_rent_amount` | BIGINT UNSIGNED | NULL | Loyer indicatif XOF |
| `indicative_sale_price` | BIGINT UNSIGNED | NULL | Prix vente indicatif XOF |
| `is_furnished` | TINYINT(1) | NN D=0 | Meublé |
| `is_vat_applicable` | TINYINT(1) | NN D=0 | TVA applicable |
| `title_deed_number` | VARCHAR(100) | NULL | Numéro titre foncier |
| `title_deed_type` | ENUM('TF','ACD','ACP','AV','PC','AUTRE') | NULL | Type document foncier |
| `description` | TEXT | NULL | Description publique |
| `internal_notes` | TEXT | NULL | Notes internes |
| `primary_photo_id` | BIGINT UNSIGNED | FK NULL | Photo principale |
| `created_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |
| `deleted_at` | DATETIME | NULL | Soft delete |

**Clés & Index :**
```
PRIMARY KEY (id)
UNIQUE KEY uk_properties_uuid (uuid)
UNIQUE KEY uk_properties_org_ref (organisation_id, reference_code)
INDEX idx_properties_org_status (organisation_id, status)
INDEX idx_properties_org_type (organisation_id, type)
INDEX idx_properties_org_commune (organisation_id, commune)
INDEX idx_properties_owner (organisation_id, primary_owner_id)
INDEX idx_properties_manager (organisation_id, manager_id)
INDEX idx_properties_deleted_at (deleted_at)
FULLTEXT INDEX ft_properties_name_desc (name, description)
```

---

### `property_units`
> Unités/lots au sein d'un bien multi-unités

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `property_id` | BIGINT UNSIGNED | FK NN | |
| `uuid` | CHAR(36) | UK NN | |
| `reference_code` | VARCHAR(50) | NN | Appt A, Bureau 3B… |
| `type` | ENUM('APPARTEMENT','STUDIO','CHAMBRE','BUREAU','LOCAL','ENTREPOT','PARKING','AUTRE') | NN | |
| `status` | ENUM('DISPONIBLE','OCCUPE','RESERVE','SUSPENDU') | NN D='DISPONIBLE' | |
| `floor_number` | TINYINT | NULL | |
| `surface` | DECIMAL(10,2) | NULL | m² |
| `rooms_count` | TINYINT UNSIGNED | NULL | |
| `bedrooms_count` | TINYINT UNSIGNED | NULL | |
| `bathrooms_count` | TINYINT UNSIGNED | NULL | |
| `is_furnished` | TINYINT(1) | NN D=0 | |
| `indicative_rent_amount` | BIGINT UNSIGNED | NULL | |
| `condominium_share` | DECIMAL(10,6) | NULL | Tantièmes copropriété |
| `notes` | TEXT | NULL | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |
| `deleted_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_units_uuid (uuid)
UNIQUE KEY uk_units_prop_ref (property_id, reference_code)
INDEX idx_units_org_status (organisation_id, status)
INDEX idx_units_property_id (property_id)
```

---

### `property_photos`
> Photos et médias des biens et unités

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `property_id` | BIGINT UNSIGNED | FK NN | |
| `unit_id` | BIGINT UNSIGNED | FK NULL | |
| `storage_path` | VARCHAR(500) | NN | Chemin S3 |
| `cdn_url` | VARCHAR(500) | NN | URL CDN publique |
| `thumbnail_url` | VARCHAR(500) | NULL | Miniature |
| `original_filename` | VARCHAR(255) | NN | |
| `file_size_bytes` | INT UNSIGNED | NN | |
| `mime_type` | VARCHAR(100) | NN | |
| `width_px` | SMALLINT UNSIGNED | NULL | |
| `height_px` | SMALLINT UNSIGNED | NULL | |
| `sort_order` | TINYINT UNSIGNED | NN D=0 | |
| `is_primary` | TINYINT(1) | NN D=0 | Photo principale |
| `alt_text` | VARCHAR(255) | NULL | |
| `uploaded_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `deleted_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
INDEX idx_photos_org_property (organisation_id, property_id, is_primary)
INDEX idx_photos_unit_id (unit_id)
```

---

### `property_legal_docs`
> Documents fonciers attachés à un bien

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `property_id` | BIGINT UNSIGNED | FK NN | |
| `doc_type` | ENUM('TF','ACD','ACP','AV','PERMIS_CONSTRUIRE','CERT_CONFORMITE','PV_BORNAGE','AUTRE') | NN | |
| `reference_number` | VARCHAR(100) | NULL | |
| `issuing_authority` | VARCHAR(255) | NULL | DGF, Mairie… |
| `issue_date` | DATE | NULL | |
| `expiry_date` | DATE | NULL | |
| `notes` | TEXT | NULL | |
| `document_id` | BIGINT UNSIGNED | FK NN | Lien vers GED |
| `uploaded_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
INDEX idx_pld_org_property (organisation_id, property_id)
INDEX idx_pld_doc_type (property_id, doc_type)
```

---

### `property_mandates`
> Mandats de gestion/location/vente

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `property_id` | BIGINT UNSIGNED | FK NN | |
| `owner_id` | BIGINT UNSIGNED | FK NN | Propriétaire mandant |
| `manager_id` | BIGINT UNSIGNED | FK NN | Gestionnaire mandataire |
| `mandate_type` | ENUM('GESTION','LOCATION','VENTE','GESTION_LOCATION') | NN | |
| `status` | ENUM('ACTIF','EXPIRE','RESILIE','SUSPENDU') | NN D='ACTIF' | |
| `start_date` | DATE | NN | |
| `end_date` | DATE | NULL | |
| `management_fee_rate` | DECIMAL(5,2) | NULL | % du loyer (gestion) |
| `letting_fee_months` | DECIMAL(4,2) | NULL | Mois de loyer (mise en loc) |
| `sale_commission_rate` | DECIMAL(5,2) | NULL | % prix de vente |
| `vat_on_fees` | TINYINT(1) | NN D=0 | TVA sur honoraires |
| `auto_renewal` | TINYINT(1) | NN D=0 | Renouvellement auto |
| `document_id` | BIGINT UNSIGNED | FK NULL | |
| `signed_at` | DATETIME | NULL | |
| `terminated_at` | DATETIME | NULL | |
| `termination_reason` | TEXT | NULL | |
| `created_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
INDEX idx_mandates_org_property (organisation_id, property_id, status)
INDEX idx_mandates_owner_id (organisation_id, owner_id)
INDEX idx_mandates_end_date (end_date, status)
```

---

### `property_status_history`
> Historique des changements de statut

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `property_id` | BIGINT UNSIGNED | FK NN | |
| `old_status` | VARCHAR(30) | NULL | |
| `new_status` | VARCHAR(30) | NN | |
| `reason` | VARCHAR(255) | NULL | |
| `changed_by` | BIGINT UNSIGNED | FK NULL | |
| `changed_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_psh_org_property (organisation_id, property_id, changed_at)
```

---

### `property_amenities`
> Équipements et caractéristiques d'un bien

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `property_id` | BIGINT UNSIGNED | FK NN | |
| `unit_id` | BIGINT UNSIGNED | FK NULL | |
| `amenity_code` | VARCHAR(100) | NN | CLIM, GARDIEN, PISCINE… |
| `amenity_label` | VARCHAR(255) | NN | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_amenities_prop_code (property_id, unit_id, amenity_code)
INDEX idx_amenities_org_property (organisation_id, property_id)
```

---

## DOMAINE 4 — PARTIES PRENANTES

---

### `parties`
> Table racine polymorphique de toutes les parties prenantes

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `uuid` | CHAR(36) | UK NN | |
| `type` | SET('OWNER','TENANT','GUARANTOR','BUYER','SELLER','VENDOR','CONTACT') | NN | Multi-rôle possible |
| `party_kind` | ENUM('INDIVIDUAL','COMPANY') | NN | |
| `display_name` | VARCHAR(255) | NN | Nom affiché calculé |
| `email` | VARCHAR(255) | NULL | |
| `phone` | VARCHAR(20) | NULL | |
| `phone_secondary` | VARCHAR(20) | NULL | |
| `commune` | VARCHAR(100) | NULL | |
| `quartier` | VARCHAR(150) | NULL | |
| `address_description` | TEXT | NULL | |
| `kyc_status` | ENUM('NOT_REQUIRED','PENDING','IN_REVIEW','APPROVED','REJECTED','EXPIRED') | NN D='PENDING' | |
| `kyc_reviewed_by` | BIGINT UNSIGNED | FK NULL | |
| `kyc_reviewed_at` | DATETIME | NULL | |
| `kyc_rejection_reason` | TEXT | NULL | |
| `is_active` | TINYINT(1) | NN D=1 | |
| `notes` | TEXT | NULL | |
| `created_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |
| `deleted_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_parties_uuid (uuid)
INDEX idx_parties_org_kyc (organisation_id, kyc_status)
INDEX idx_parties_org_type (organisation_id, type)
INDEX idx_parties_email (organisation_id, email)
INDEX idx_parties_phone (organisation_id, phone)
INDEX idx_parties_deleted_at (deleted_at)
FULLTEXT INDEX ft_parties_name (display_name)
```

---

### `party_individuals`
> Extension pour personnes physiques

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `party_id` | BIGINT UNSIGNED | FK UK NN | 1-1 avec parties |
| `last_name` | VARCHAR(100) | NN | |
| `first_names` | VARCHAR(200) | NN | |
| `birth_date` | DATE | NULL | |
| `birth_place` | VARCHAR(255) | NULL | |
| `nationality` | VARCHAR(100) | NULL | |
| `marital_status` | ENUM('CELIBATAIRE','MARIE','PACSE','DIVORCE','VEUF') | NULL | |
| `id_doc_type` | ENUM('CNI','PASSEPORT','TITRE_SEJOUR','AUTRE') | NULL | |
| `id_doc_number` | VARCHAR(100) | NULL | |
| `id_doc_expiry` | DATE | NULL | |
| `employment_type` | ENUM('CDI','CDD','INDEPENDANT','FONCTIONNAIRE','RETRAITE','ETUDIANT','SANS_EMPLOI','AUTRE') | NULL | |
| `employer` | VARCHAR(255) | NULL | |
| `job_title` | VARCHAR(200) | NULL | |
| `employer_phone` | VARCHAR(20) | NULL | |
| `monthly_income_xof` | BIGINT UNSIGNED | NULL | Revenus mensuels nets |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_party_individuals_party (party_id)
INDEX idx_pi_id_doc_number (id_doc_number)
```

---

### `party_companies`
> Extension pour personnes morales (SCI, entreprises)

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `party_id` | BIGINT UNSIGNED | FK UK NN | |
| `company_name` | VARCHAR(255) | NN | |
| `legal_form` | ENUM('SA','SARL','SCI','SNC','GIE','EURL','SASU','ASSOCIATION','AUTRE') | NN | |
| `rccm` | VARCHAR(100) | NULL | Registre du commerce CI |
| `cif` | VARCHAR(100) | NULL | Carte Identité Fiscale CI |
| `tax_id` | VARCHAR(100) | NULL | Identifiant fiscal |
| `representative_name` | VARCHAR(255) | NULL | Représentant légal |
| `representative_title` | VARCHAR(100) | NULL | Qualité du représentant |
| `representative_id_doc` | VARCHAR(100) | NULL | Pièce ID représentant |
| `is_vat_registered` | TINYINT(1) | NN D=0 | Assujetti TVA |
| `vat_number` | VARCHAR(50) | NULL | |
| `share_capital_xof` | BIGINT UNSIGNED | NULL | Capital social |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_party_companies_party (party_id)
INDEX idx_pc_rccm (rccm)
INDEX idx_pc_cif (cif)
```

---

### `party_kyc_docs`
> Documents KYC téléversés pour vérification

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `party_id` | BIGINT UNSIGNED | FK NN | |
| `doc_type` | ENUM('CNI','PASSEPORT','BULLETIN_SALAIRE','ATTESTATION_EMPLOI','RELEVE_COMPTE','BILAN','RCCM','STATUTS','AUTRE') | NN | |
| `period_label` | VARCHAR(50) | NULL | Mois/Année du bulletin |
| `is_valid` | TINYINT(1) | NULL | NULL=non vérifié |
| `rejection_reason` | VARCHAR(255) | NULL | |
| `document_id` | BIGINT UNSIGNED | FK NN | Lien GED |
| `uploaded_by` | BIGINT UNSIGNED | FK NN | |
| `verified_by` | BIGINT UNSIGNED | FK NULL | |
| `verified_at` | DATETIME | NULL | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_kyc_docs_org_party (organisation_id, party_id)
INDEX idx_kyc_docs_type (party_id, doc_type)
```

---

### `party_guarantors`
> Cautions solidaires associées à des locataires

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `tenant_party_id` | BIGINT UNSIGNED | FK NN | Locataire garanti |
| `guarantor_party_id` | BIGINT UNSIGNED | FK NN | Garant |
| `guarantee_type` | ENUM('CAUTION_SOLIDAIRE','CAUTION_BANCAIRE','GARANTIE_VISALE','AUTRE') | NN | |
| `guaranteed_amount_xof` | BIGINT UNSIGNED | NULL | Montant maximum garanti |
| `start_date` | DATE | NN | |
| `end_date` | DATE | NULL | |
| `document_id` | BIGINT UNSIGNED | FK NULL | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_guarantors_org_tenant (organisation_id, tenant_party_id)
INDEX idx_guarantors_guarantor (guarantor_party_id)
```

---

## DOMAINE 5 — BAUX & CONTRATS

---

### `leases`
> Table centrale des contrats de location — haute volumétrie

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `uuid` | CHAR(36) | UK NN | |
| `reference_number` | VARCHAR(50) | NN | BAL-2024-00001 |
| `unit_id` | BIGINT UNSIGNED | FK NN | Unité louée |
| `tenant_id` | BIGINT UNSIGNED | FK NN | Locataire principal |
| `owner_id` | BIGINT UNSIGNED | FK NN | Propriétaire bailleur |
| `manager_id` | BIGINT UNSIGNED | FK NULL | Gestionnaire |
| `type` | ENUM('HABITATION_NON_MEUBLE','HABITATION_MEUBLE','COMMERCIAL_BUREAU','COMMERCIAL_LOCAL','COMMERCIAL_ENTREPOT','AUTRE') | NN | |
| `status` | ENUM('BROUILLON','EN_ATTENTE_SIGNATURE','SIGNE','ACTIF','EN_REVISION','PREAVIS','EN_SORTIE','TERMINE','ANNULE','CONTENTIEUX') | NN D='BROUILLON' | |
| `start_date` | DATE | NN | |
| `end_date` | DATE | NULL | |
| `duration_months` | SMALLINT UNSIGNED | NN | |
| `rent_amount_xof` | BIGINT UNSIGNED | NN | Loyer HT en XOF |
| `charges_amount_xof` | BIGINT UNSIGNED | NN D=0 | Charges mensuelles |
| `is_vat_applicable` | TINYINT(1) | NN D=0 | |
| `vat_rate` | DECIMAL(5,2) | NN D=18.00 | |
| `total_amount_ttc_xof` | BIGINT UNSIGNED | NN | Loyer TTC |
| `deposit_amount_xof` | BIGINT UNSIGNED | NN D=0 | Dépôt de garantie |
| `payment_day` | TINYINT UNSIGNED | NN D=1 | Jour d'échéance (1-28) |
| `late_fee_rate` | DECIMAL(5,2) | NN D=10.00 | % pénalité/mois |
| `late_fee_tolerance_days` | TINYINT UNSIGNED | NN D=5 | Jours de grâce |
| `notice_period_days` | SMALLINT UNSIGNED | NN | Délai préavis légal |
| `revision_frequency_months` | TINYINT UNSIGNED | NN D=12 | Fréquence révision |
| `revision_cap_pct` | DECIMAL(5,2) | NULL | Plafond révision % |
| `document_id` | BIGINT UNSIGNED | FK NULL | PDF bail signé |
| `signed_at` | DATETIME | NULL | |
| `activated_at` | DATETIME | NULL | |
| `terminated_at` | DATETIME | NULL | |
| `termination_reason` | TEXT | NULL | |
| `internal_notes` | TEXT | NULL | |
| `created_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |
| `deleted_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_leases_uuid (uuid)
UNIQUE KEY uk_leases_org_ref (organisation_id, reference_number)
INDEX idx_leases_org_status (organisation_id, status)
INDEX idx_leases_unit_id (organisation_id, unit_id, status)
INDEX idx_leases_tenant_id (organisation_id, tenant_id)
INDEX idx_leases_owner_id (organisation_id, owner_id)
INDEX idx_leases_manager_id (organisation_id, manager_id)
INDEX idx_leases_end_date (organisation_id, end_date, status)
INDEX idx_leases_deleted_at (deleted_at)
```

---

### `lease_schedules`
> Échéancier de paiement — table très haute volumétrie (>30M lignes)

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `lease_id` | BIGINT UNSIGNED | FK NN | |
| `period_year` | SMALLINT UNSIGNED | NN | Année de l'échéance |
| `period_month` | TINYINT UNSIGNED | NN | Mois (1-12) |
| `due_date` | DATE | NN | Date d'exigibilité |
| `rent_amount_xof` | BIGINT UNSIGNED | NN | |
| `charges_amount_xof` | BIGINT UNSIGNED | NN D=0 | |
| `vat_amount_xof` | BIGINT UNSIGNED | NN D=0 | |
| `late_fee_amount_xof` | BIGINT UNSIGNED | NN D=0 | Pénalités accumulées |
| `total_amount_xof` | BIGINT UNSIGNED | NN | Total dû |
| `paid_amount_xof` | BIGINT UNSIGNED | NN D=0 | Montant payé |
| `balance_xof` | BIGINT UNSIGNED | VG | total - paid (VIRTUAL) |
| `status` | ENUM('PENDING','PARTIAL','PAID','OVERDUE','WAIVED','CONTENTIOUS') | NN D='PENDING' | |
| `first_paid_at` | DATE | NULL | Date premier paiement |
| `fully_paid_at` | DATE | NULL | Date solde complet |
| `reminders_sent` | TINYINT UNSIGNED | NN D=0 | Nombre relances envoyées |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_schedules_lease_period (lease_id, period_year, period_month)
INDEX idx_schedules_org_due_date (organisation_id, due_date, status)
INDEX idx_schedules_org_status (organisation_id, status)
INDEX idx_schedules_lease_id (lease_id)
```
*Partition RANGE sur `due_date` — partitions annuelles*

---

### `lease_clauses`
> Clauses spéciales des baux

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `lease_id` | BIGINT UNSIGNED | FK NN | |
| `clause_type` | VARCHAR(50) | NN | ENTRETIEN, ANIMAUX, TRAVAUX… |
| `title` | VARCHAR(255) | NN | |
| `content` | TEXT | NN | |
| `sort_order` | TINYINT UNSIGNED | NN D=0 | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_clauses_lease_id (lease_id)
```

---

### `lease_revisions`
> Historique des révisions de loyer

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `lease_id` | BIGINT UNSIGNED | FK NN | |
| `revision_date` | DATE | NN | Date d'effet |
| `old_rent_amount_xof` | BIGINT UNSIGNED | NN | |
| `new_rent_amount_xof` | BIGINT UNSIGNED | NN | |
| `revision_rate` | DECIMAL(5,2) | NN | % de hausse |
| `revision_basis` | VARCHAR(255) | NULL | Indice, contractuel… |
| `status` | ENUM('PROPOSEE','ACCEPTEE','REJETEE','APPLIQUEE') | NN | |
| `document_id` | BIGINT UNSIGNED | FK NULL | Avenant signé |
| `approved_by` | BIGINT UNSIGNED | FK NULL | |
| `approved_at` | DATETIME | NULL | |
| `applied_at` | DATETIME | NULL | |
| `created_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_revisions_org_lease (organisation_id, lease_id)
INDEX idx_revisions_date (lease_id, revision_date)
```

---

### `lease_notices`
> Préavis de résiliation

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `lease_id` | BIGINT UNSIGNED | FK NN | |
| `notice_type` | ENUM('RESILIATION','NON_RENOUVELLEMENT') | NN | |
| `origin` | ENUM('TENANT','OWNER','MUTUAL') | NN | Qui donne le préavis |
| `notice_date` | DATE | NN | Date réception préavis |
| `notice_period_days` | SMALLINT UNSIGNED | NN | |
| `effective_end_date` | DATE | NN | Date fin calculée |
| `status` | ENUM('EN_COURS','VALIDE','CONTESTE','ANNULE') | NN D='EN_COURS' | |
| `reason` | TEXT | NULL | |
| `document_id` | BIGINT UNSIGNED | FK NULL | |
| `recorded_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
INDEX idx_notices_org_lease (organisation_id, lease_id)
INDEX idx_notices_effective_date (effective_end_date, status)
```

---

### `lease_inventory_checks`
> États des lieux d'entrée et de sortie

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `lease_id` | BIGINT UNSIGNED | FK NN | |
| `check_type` | ENUM('ENTREE','SORTIE','INTERMEDIAIRE') | NN | |
| `check_date` | DATE | NN | |
| `agent_user_id` | BIGINT UNSIGNED | FK NULL | Agent présent |
| `status` | ENUM('EN_COURS','COMPLETE','SIGNE','CONTESTE') | NN D='EN_COURS' | |
| `general_observations` | TEXT | NULL | |
| `meter_readings` | JSON | NULL | Relevés compteurs |
| `document_id` | BIGINT UNSIGNED | FK NULL | PDF EDL |
| `signed_at` | DATETIME | NULL | |
| `signed_by_tenant` | TINYINT(1) | NN D=0 | |
| `signed_by_agent` | TINYINT(1) | NN D=0 | |
| `created_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_inventory_lease_type (lease_id, check_type)
INDEX idx_inventory_org_lease (organisation_id, lease_id)
```

---

### `lease_inventory_items`
> Lignes détail de l'état des lieux

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `check_id` | BIGINT UNSIGNED | FK NN | |
| `room_name` | VARCHAR(100) | NN | Pièce (Salon, Chambre 1…) |
| `item_name` | VARCHAR(150) | NN | Élément (Sol, Mur, Fenêtre…) |
| `condition_entry` | ENUM('BON','USAGE','MAUVAIS','ABSENT') | NULL | |
| `condition_exit` | ENUM('BON','USAGE','MAUVAIS','ABSENT') | NULL | |
| `observations` | TEXT | NULL | |
| `photo_urls` | JSON | NULL | Array d'URLs |
| `estimated_repair_cost` | BIGINT UNSIGNED | NULL | XOF |
| `sort_order` | SMALLINT UNSIGNED | NN D=0 | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_inv_items_check_id (check_id)
```

---

### `lease_signatories`
> Suivi des signatures électroniques par bail

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `lease_id` | BIGINT UNSIGNED | FK NN | |
| `party_id` | BIGINT UNSIGNED | FK NULL | |
| `user_id` | BIGINT UNSIGNED | FK NULL | |
| `signatory_role` | ENUM('TENANT','OWNER','MANAGER','GUARANTOR','WITNESS') | NN | |
| `signing_method` | ENUM('ELECTRONIC','PHYSICAL','OTP_SMS') | NN | |
| `status` | ENUM('PENDING','SIGNED','DECLINED','EXPIRED') | NN D='PENDING' | |
| `signed_at` | DATETIME | NULL | |
| `ip_address` | VARCHAR(45) | NULL | |
| `signature_ref` | VARCHAR(255) | NULL | Référence YouSign/DocuSign |
| `otp_verified` | TINYINT(1) | NN D=0 | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_signatories_lease_party (lease_id, party_id, signatory_role)
INDEX idx_signatories_org_lease (organisation_id, lease_id)
INDEX idx_signatories_status (organisation_id, status)
```

---

### `security_deposits`
> Dépôts de garantie — cycle de vie complet

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `lease_id` | BIGINT UNSIGNED | FK UK NN | 1-1 avec lease |
| `tenant_id` | BIGINT UNSIGNED | FK NN | |
| `amount_xof` | BIGINT UNSIGNED | NN | Montant initial |
| `status` | ENUM('EN_ATTENTE','ENCAISSE','RESTITUE_TOTAL','RESTITUE_PARTIEL','LITIGE') | NN D='EN_ATTENTE' | |
| `collected_at` | DATETIME | NULL | |
| `collection_payment_id` | BIGINT UNSIGNED | FK NULL | |
| `deductions_amount_xof` | BIGINT UNSIGNED | NN D=0 | Déductions dégâts |
| `deductions_detail` | JSON | NULL | Détail des déductions |
| `returned_amount_xof` | BIGINT UNSIGNED | NULL | Montant effectivement restitué |
| `return_channel` | VARCHAR(50) | NULL | |
| `return_reference` | VARCHAR(255) | NULL | |
| `returned_at` | DATETIME | NULL | |
| `processed_by` | BIGINT UNSIGNED | FK NULL | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_deposits_lease (lease_id)
INDEX idx_deposits_org_status (organisation_id, status)
INDEX idx_deposits_tenant (organisation_id, tenant_id)
```

---

## DOMAINE 6 — FINANCE & PAIEMENTS

---

### `payments`
> Enregistrement de tous les paiements reçus — table critique

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `uuid` | CHAR(36) | UK NN | Référence externe |
| `lease_id` | BIGINT UNSIGNED | FK NN | |
| `schedule_id` | BIGINT UNSIGNED | FK NULL | Échéance liée |
| `party_id` | BIGINT UNSIGNED | FK NN | Payeur |
| `amount_xof` | BIGINT UNSIGNED | NN | Montant reçu |
| `channel` | ENUM('MTN_MOMO','ORANGE_MONEY','WAVE','MOOV_MONEY','VIREMENT','ESPECES','CHEQUE','AUTRE') | NN | |
| `payment_date` | DATE | NN | Date du paiement |
| `internal_reference` | VARCHAR(100) | UK NN | Référence interne unique |
| `external_reference` | VARCHAR(255) | NULL | Réf opérateur Mobile Money |
| `mobile_money_phone` | VARCHAR(20) | NULL | Numéro MoMo |
| `status` | ENUM('PENDING','CONFIRMED','FAILED','CANCELLED','REFUNDED') | NN D='PENDING' | |
| `confirmed_at` | DATETIME | NULL | |
| `is_partial` | TINYINT(1) | NN D=0 | Paiement partiel |
| `receipt_id` | BIGINT UNSIGNED | FK NULL | Quittance générée |
| `notes` | TEXT | NULL | |
| `recorded_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_payments_uuid (uuid)
UNIQUE KEY uk_payments_internal_ref (organisation_id, internal_reference)
INDEX idx_payments_org_status (organisation_id, status)
INDEX idx_payments_org_lease (organisation_id, lease_id, payment_date)
INDEX idx_payments_schedule_id (schedule_id)
INDEX idx_payments_party_id (organisation_id, party_id)
INDEX idx_payments_date (organisation_id, payment_date)
INDEX idx_payments_channel (organisation_id, channel)
INDEX idx_payments_ext_ref (external_reference)
```
*Partition RANGE sur `payment_date` — partitions annuelles*

---

### `payment_transactions`
> Transactions Mobile Money via CinetPay — idempotence critique

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `payment_id` | BIGINT UNSIGNED | FK NULL | Lié après confirmation |
| `cinetpay_transaction_id` | VARCHAR(255) | UK NULL | ID CinetPay |
| `idempotency_key` | VARCHAR(255) | UK NN | Clé idempotence |
| `operator` | ENUM('MTN','ORANGE','WAVE','MOOV','CARTE','AUTRE') | NN | |
| `amount_xof` | BIGINT UNSIGNED | NN | |
| `customer_phone` | VARCHAR(20) | NN | Numéro du payeur |
| `customer_name` | VARCHAR(255) | NULL | |
| `status` | ENUM('INITIATED','PENDING','SUCCESS','FAILED','EXPIRED','REFUNDED') | NN D='INITIATED' | |
| `ussd_code` | VARCHAR(200) | NULL | Code USSD généré |
| `payment_url` | TEXT | NULL | URL paiement web |
| `qr_code_url` | VARCHAR(500) | NULL | QR Wave |
| `webhook_payload` | JSON | NULL | Payload brut CinetPay |
| `error_code` | VARCHAR(50) | NULL | |
| `error_message` | VARCHAR(500) | NULL | |
| `initiated_at` | DATETIME | NN | |
| `expires_at` | DATETIME | NN | +15 minutes |
| `webhook_received_at` | DATETIME | NULL | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_ptx_cinetpay_id (cinetpay_transaction_id)
UNIQUE KEY uk_ptx_idempotency (idempotency_key)
INDEX idx_ptx_org_payment (organisation_id, payment_id)
INDEX idx_ptx_status (organisation_id, status)
INDEX idx_ptx_phone (customer_phone)
INDEX idx_ptx_expires_at (expires_at, status)
```

---

### `receipts`
> Quittances de loyer — numérotation séquentielle immuable

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `uuid` | CHAR(36) | UK NN | |
| `receipt_number` | VARCHAR(50) | UK NN | QUI-2024-00001 |
| `payment_id` | BIGINT UNSIGNED | FK UK NN | 1 paiement = 1 quittance |
| `lease_id` | BIGINT UNSIGNED | FK NN | |
| `tenant_id` | BIGINT UNSIGNED | FK NN | |
| `unit_id` | BIGINT UNSIGNED | FK NN | |
| `period_year` | SMALLINT UNSIGNED | NN | |
| `period_month` | TINYINT UNSIGNED | NN | |
| `rent_amount_xof` | BIGINT UNSIGNED | NN | |
| `charges_amount_xof` | BIGINT UNSIGNED | NN D=0 | |
| `vat_amount_xof` | BIGINT UNSIGNED | NN D=0 | |
| `late_fee_amount_xof` | BIGINT UNSIGNED | NN D=0 | |
| `total_amount_xof` | BIGINT UNSIGNED | NN | |
| `document_id` | BIGINT UNSIGNED | FK NULL | PDF quittance |
| `issued_at` | DATETIME | NN | |
| `sent_at` | DATETIME | NULL | |
| `issued_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_receipts_uuid (uuid)
UNIQUE KEY uk_receipts_number (organisation_id, receipt_number)
UNIQUE KEY uk_receipts_payment (payment_id)
INDEX idx_receipts_org_tenant (organisation_id, tenant_id)
INDEX idx_receipts_org_lease_period (organisation_id, lease_id, period_year, period_month)
INDEX idx_receipts_issued_at (organisation_id, issued_at)
```

---

### `late_fees`
> Pénalités de retard calculées

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `lease_id` | BIGINT UNSIGNED | FK NN | |
| `schedule_id` | BIGINT UNSIGNED | FK NN | |
| `amount_xof` | BIGINT UNSIGNED | NN | |
| `fee_rate` | DECIMAL(5,2) | NN | |
| `days_overdue` | SMALLINT UNSIGNED | NN | |
| `calculated_date` | DATE | NN | |
| `status` | ENUM('ACTIVE','WAIVED','INCLUDED_IN_PAYMENT') | NN D='ACTIVE' | |
| `waived_by` | BIGINT UNSIGNED | FK NULL | |
| `waiver_reason` | TEXT | NULL | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_late_fees_org_lease (organisation_id, lease_id, status)
INDEX idx_late_fees_schedule (schedule_id)
```

---

### `reminders`
> Journal des relances envoyées

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `lease_id` | BIGINT UNSIGNED | FK NN | |
| `schedule_id` | BIGINT UNSIGNED | FK NN | |
| `reminder_level` | TINYINT UNSIGNED | NN | 1=G1, 2=G2, 3=G3 |
| `amount_due_xof` | BIGINT UNSIGNED | NN | Montant relancé |
| `days_overdue` | SMALLINT UNSIGNED | NN | |
| `channel` | VARCHAR(50) | NN | SMS, EMAIL, WHATSAPP |
| `status` | ENUM('PENDING','SENT','DELIVERED','FAILED') | NN | |
| `sent_at` | DATETIME | NULL | |
| `notification_log_id` | BIGINT UNSIGNED | FK NULL | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_reminders_org_lease (organisation_id, lease_id)
INDEX idx_reminders_schedule (schedule_id, reminder_level)
INDEX idx_reminders_sent_at (organisation_id, sent_at)
```

---

### `owner_statements`
> Relevés propriétaires mensuels/trimestriels

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `uuid` | CHAR(36) | UK NN | |
| `statement_number` | VARCHAR(50) | UK NN | RPP-2024-001-0001 |
| `owner_id` | BIGINT UNSIGNED | FK NN | |
| `period_start` | DATE | NN | |
| `period_end` | DATE | NN | |
| `gross_rent_xof` | BIGINT UNSIGNED | NN D=0 | |
| `charges_deducted_xof` | BIGINT UNSIGNED | NN D=0 | |
| `management_fees_xof` | BIGINT UNSIGNED | NN D=0 | |
| `vat_on_fees_xof` | BIGINT UNSIGNED | NN D=0 | |
| `tax_withholding_xof` | BIGINT UNSIGNED | NN D=0 | Retenue à la source |
| `net_amount_xof` | BIGINT UNSIGNED | NN D=0 | Net à reverser |
| `status` | ENUM('BROUILLON','GENERE','ENVOYE','PAYE') | NN D='BROUILLON' | |
| `document_id` | BIGINT UNSIGNED | FK NULL | |
| `generated_at` | DATETIME | NULL | |
| `sent_at` | DATETIME | NULL | |
| `paid_at` | DATETIME | NULL | |
| `created_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_statements_uuid (uuid)
UNIQUE KEY uk_statements_number (organisation_id, statement_number)
INDEX idx_statements_org_owner (organisation_id, owner_id, period_end)
INDEX idx_statements_status (organisation_id, status)
```

---

### `owner_statement_lines`
> Lignes de détail des relevés propriétaires

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `statement_id` | BIGINT UNSIGNED | FK NN | |
| `lease_id` | BIGINT UNSIGNED | FK NULL | |
| `unit_id` | BIGINT UNSIGNED | FK NULL | |
| `period_year` | SMALLINT UNSIGNED | NULL | |
| `period_month` | TINYINT UNSIGNED | NULL | |
| `line_type` | ENUM('LOYER','CHARGES','HONORAIRES','TRAVAUX','TAXE','RETENUE','AUTRE') | NN | |
| `description` | VARCHAR(500) | NN | |
| `gross_amount_xof` | BIGINT UNSIGNED | NN D=0 | |
| `fee_amount_xof` | BIGINT UNSIGNED | NN D=0 | |
| `tax_amount_xof` | BIGINT UNSIGNED | NN D=0 | |
| `net_amount_xof` | BIGINT UNSIGNED | NN D=0 | |
| `sort_order` | SMALLINT UNSIGNED | NN D=0 | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_osl_statement_id (statement_id)
INDEX idx_osl_lease_id (lease_id)
```

---

### `refunds`
> Remboursements initiés sur des paiements

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `original_payment_id` | BIGINT UNSIGNED | FK NN | |
| `amount_xof` | BIGINT UNSIGNED | NN | |
| `reason` | TEXT | NN | |
| `channel` | VARCHAR(50) | NN | |
| `status` | ENUM('PENDING','APPROVED','REJECTED','PROCESSED','FAILED') | NN | |
| `initiated_by` | BIGINT UNSIGNED | FK NN | |
| `approved_by` | BIGINT UNSIGNED | FK NULL | |
| `approved_at` | DATETIME | NULL | |
| `processed_at` | DATETIME | NULL | |
| `external_reference` | VARCHAR(255) | NULL | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
INDEX idx_refunds_org_payment (organisation_id, original_payment_id)
INDEX idx_refunds_status (organisation_id, status)
```

---

## DOMAINE 7 — COMPTABILITÉ

---

### `chart_of_accounts`
> Plan comptable par organisation

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | NULL = plan système |
| `account_code` | VARCHAR(20) | NN | 411000, 706100… |
| `account_name` | VARCHAR(255) | NN | |
| `account_type` | ENUM('ACTIF','PASSIF','CHARGE','PRODUIT','CAPITAUX') | NN | |
| `parent_code` | VARCHAR(20) | NULL | |
| `is_active` | TINYINT(1) | NN D=1 | |
| `is_system` | TINYINT(1) | NN D=0 | Non modifiable |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_coa_org_code (organisation_id, account_code)
INDEX idx_coa_type (organisation_id, account_type)
```

---

### `fiscal_periods`
> Exercices et périodes comptables

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `fiscal_year` | SMALLINT UNSIGNED | NN | |
| `period_month` | TINYINT UNSIGNED | NULL | NULL = année entière |
| `start_date` | DATE | NN | |
| `end_date` | DATE | NN | |
| `status` | ENUM('OUVERT','CLOTURE','ARCHIVE') | NN D='OUVERT' | |
| `closed_at` | DATETIME | NULL | |
| `closed_by` | BIGINT UNSIGNED | FK NULL | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_fp_org_year_month (organisation_id, fiscal_year, period_month)
INDEX idx_fp_status (organisation_id, status)
```

---

### `journal_entries`
> Écritures comptables — immuables après validation

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `entry_number` | VARCHAR(50) | NN | JNL-2024-000001 |
| `fiscal_period_id` | BIGINT UNSIGNED | FK NN | |
| `entry_date` | DATE | NN | |
| `entry_type` | ENUM('ENCAISSEMENT','DECAISSEMENT','OD','EXTOURNE') | NN | |
| `description` | VARCHAR(500) | NN | |
| `reference` | VARCHAR(100) | NULL | Réf document source |
| `source_module` | VARCHAR(50) | NULL | financial, maintenance… |
| `source_id` | BIGINT UNSIGNED | NULL | ID entité source |
| `status` | ENUM('BROUILLON','VALIDE','EXTOURNE') | NN D='BROUILLON' | |
| `validated_by` | BIGINT UNSIGNED | FK NULL | |
| `validated_at` | DATETIME | NULL | |
| `extourned_by_id` | BIGINT UNSIGNED | FK NULL | ID écriture d'extourne |
| `created_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_je_org_number (organisation_id, entry_number)
INDEX idx_je_org_period (organisation_id, fiscal_period_id, entry_date)
INDEX idx_je_status (organisation_id, status)
INDEX idx_je_source (organisation_id, source_module, source_id)
```
*Partition RANGE sur `entry_date` — partitions annuelles*

---

### `journal_entry_lines`
> Lignes d'imputation comptable — équilibre débit/crédit obligatoire

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `entry_id` | BIGINT UNSIGNED | FK NN | |
| `account_id` | BIGINT UNSIGNED | FK NN | |
| `description` | VARCHAR(500) | NULL | |
| `debit_amount_xof` | BIGINT UNSIGNED | NN D=0 | |
| `credit_amount_xof` | BIGINT UNSIGNED | NN D=0 | |
| `sort_order` | SMALLINT UNSIGNED | NN D=0 | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_jel_entry_id (entry_id)
INDEX idx_jel_account_id (account_id)
```
*CONSTRAINT : debit XOR credit — au moins un > 0*

---

### `tax_declarations`
> Déclarations fiscales (TVA, retenue à la source)

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `fiscal_period_id` | BIGINT UNSIGNED | FK NN | |
| `declaration_type` | ENUM('TVA','RETENUE_SOURCE','CONTRIBUTION_FONCIERE') | NN | |
| `period_year` | SMALLINT UNSIGNED | NN | |
| `period_month` | TINYINT UNSIGNED | NULL | |
| `taxable_base_xof` | BIGINT UNSIGNED | NN D=0 | |
| `tax_rate` | DECIMAL(5,2) | NN | |
| `tax_amount_xof` | BIGINT UNSIGNED | NN D=0 | |
| `tax_paid_xof` | BIGINT UNSIGNED | NN D=0 | |
| `status` | ENUM('BROUILLON','VALIDE','SOUMISE','PAYEE') | NN D='BROUILLON' | |
| `due_date` | DATE | NN | |
| `submitted_at` | DATETIME | NULL | |
| `document_id` | BIGINT UNSIGNED | FK NULL | |
| `created_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_tax_org_period_type (organisation_id, fiscal_period_id, declaration_type)
INDEX idx_tax_status (organisation_id, status, due_date)
```

---

## DOMAINE 8 — MAINTENANCE

---

### `service_providers`
> Annuaire des prestataires de maintenance

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `party_id` | BIGINT UNSIGNED | FK NULL | Lien vers parties si créé |
| `company_name` | VARCHAR(255) | NN | |
| `contact_name` | VARCHAR(200) | NULL | |
| `phone` | VARCHAR(20) | NN | |
| `email` | VARCHAR(255) | NULL | |
| `specialties` | JSON | NULL | Array de spécialités |
| `commune` | VARCHAR(100) | NULL | Zone d'intervention |
| `status` | ENUM('ACTIF','INACTIF','BLACKLISTE') | NN D='ACTIF' | |
| `avg_rating` | DECIMAL(3,2) | NULL | Moyenne notation (0-5) |
| `total_interventions` | INT UNSIGNED | NN D=0 | |
| `has_portal_access` | TINYINT(1) | NN D=0 | Accès portail prestataire |
| `portal_user_id` | BIGINT UNSIGNED | FK NULL | |
| `notes` | TEXT | NULL | |
| `created_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |
| `deleted_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
INDEX idx_sp_org_status (organisation_id, status)
INDEX idx_sp_phone (organisation_id, phone)
FULLTEXT INDEX ft_sp_name (company_name, contact_name)
```

---

### `work_orders`
> Ordres d'intervention — cycle de vie complet

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `uuid` | CHAR(36) | UK NN | |
| `reference_number` | VARCHAR(50) | NN | OI-2024-00001 |
| `property_id` | BIGINT UNSIGNED | FK NN | |
| `unit_id` | BIGINT UNSIGNED | FK NULL | |
| `lease_id` | BIGINT UNSIGNED | FK NULL | |
| `type` | ENUM('PLOMBERIE','ELECTRICITE','PEINTURE','MENUISERIE','TOITURE','CLIMATISATION','DIVERS','URGENCE') | NN | |
| `priority` | ENUM('URGENTE','HAUTE','NORMALE','BASSE') | NN D='NORMALE' | |
| `status` | ENUM('CREATED','QUALIFIED','ASSIGNED','IN_PROGRESS','COMPLETED','CLOSED','CANCELLED') | NN D='CREATED' | |
| `description` | TEXT | NN | |
| `photos` | JSON | NULL | URLs photos signalement |
| `reported_by_user_id` | BIGINT UNSIGNED | FK NULL | Signalement interne |
| `reported_by_tenant_id` | BIGINT UNSIGNED | FK NULL | Signalement locataire |
| `assigned_provider_id` | BIGINT UNSIGNED | FK NULL | |
| `assigned_by` | BIGINT UNSIGNED | FK NULL | |
| `assigned_at` | DATETIME | NULL | |
| `scheduled_at` | DATETIME | NULL | Date intervention prévue |
| `started_at` | DATETIME | NULL | |
| `completed_at` | DATETIME | NULL | |
| `completion_report` | TEXT | NULL | |
| `is_charge_recoverable` | TINYINT(1) | NULL | NULL = non déterminé |
| `imputed_to_party_id` | BIGINT UNSIGNED | FK NULL | |
| `sla_deadline` | DATETIME | NULL | Date limite SLA |
| `sla_breached` | TINYINT(1) | NN D=0 | |
| `created_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_wo_uuid (uuid)
UNIQUE KEY uk_wo_org_ref (organisation_id, reference_number)
INDEX idx_wo_org_status (organisation_id, status, priority)
INDEX idx_wo_property_id (organisation_id, property_id)
INDEX idx_wo_provider_id (organisation_id, assigned_provider_id, status)
INDEX idx_wo_sla (sla_deadline, status)
```

---

### `work_order_quotes`, `work_order_quote_lines`, `work_order_invoices`

**`work_order_quotes` :**
```
id PK | organisation_id FK | work_order_id FK | provider_id FK
status ENUM(SOUMIS,APPROUVE,REJETE,EXPIRE) | amount_ht_xof | vat_rate | vat_amount_xof | amount_ttc_xof
valid_until DATE | document_id FK | approved_by FK | approved_at | rejection_reason | created_at
INDEX idx_wq_org_wo (organisation_id, work_order_id, status)
```

**`work_order_quote_lines` :**
```
id PK | quote_id FK | description | quantity DECIMAL(10,3) | unit VARCHAR(30)
unit_price_ht_xof BIGINT | total_ht_xof BIGINT | sort_order TINYINT | created_at
INDEX idx_wql_quote_id (quote_id)
```

**`work_order_invoices` :**
```
id PK | organisation_id FK | work_order_id FK | provider_id FK | invoice_number VARCHAR
invoice_date DATE | amount_ht_xof BIGINT | vat_amount_xof BIGINT | amount_ttc_xof BIGINT
status ENUM(RECUE,VALIDEE,PAYEE,REJETEE) | document_id FK | validated_by FK | validated_at
paid_at | payment_reference | created_at | updated_at
UNIQUE KEY uk_woi_org_number (organisation_id, invoice_number)
INDEX idx_woi_org_wo (organisation_id, work_order_id)
INDEX idx_woi_status (organisation_id, status)
```

---

### `work_order_status_history`
> Historique complet des changements de statut des OI

```
id PK AI | organisation_id FK | work_order_id FK
old_status VARCHAR(30) | new_status VARCHAR(30) NN | reason VARCHAR(255)
changed_by FK users | changed_at DATETIME NN DEFAULT NOW()
INDEX idx_wosh_org_wo (organisation_id, work_order_id, changed_at)
```

---

## DOMAINE 9 — VENTES IMMOBILIÈRES

---

### `sale_mandates`, `sale_viewings`, `purchase_offers`, `sale_agreements`, `sale_deeds`, `sale_commissions`

**`sale_mandates` :**
```
id PK | organisation_id FK | property_id FK | seller_id FK | manager_id FK
status ENUM(ACTIF,EXPIRE,RESILIE) | asking_price_xof BIGINT | commission_rate DECIMAL(5,2)
start_date DATE | end_date DATE | exclusive TINYINT(1) | document_id FK
signed_at DATETIME | created_by FK | created_at | updated_at
INDEX idx_sm_org_property (organisation_id, property_id, status)
```

**`sale_viewings` :**
```
id PK | organisation_id FK | property_id FK | sale_mandate_id FK | lead_id FK
scheduled_at DATETIME NN | status ENUM(PLANIFIEE,REALISEE,ANNULEE,NO_SHOW)
agent_user_id FK | report TEXT | interest_level TINYINT(1..5)
created_by FK | created_at | updated_at
INDEX idx_sv_org_mandate (organisation_id, sale_mandate_id, scheduled_at)
```

**`purchase_offers` :**
```
id PK | organisation_id FK | property_id FK | sale_mandate_id FK | buyer_id FK
offered_price_xof BIGINT NN | offer_date DATE NN | offer_expiry DATE
status ENUM(SOUMISE,ACCEPTEE,REJETEE,EXPIREE,RETIREE,CONTRE_OFFERTE)
conditions TEXT | counter_offer_price_xof BIGINT | document_id FK
created_by FK | created_at | updated_at
INDEX idx_po_org_mandate (organisation_id, sale_mandate_id, status)
```

**`sale_agreements` :**
```
id PK | organisation_id FK | purchase_offer_id FK UK
agreed_price_xof BIGINT NN | signature_date DATE NN | completion_deadline DATE
sequestre_amount_xof BIGINT | sequestre_paid TINYINT(1) D=0
status ENUM(EN_COURS,COMPLETE,RESOLU_AMIABLE,RESOLU_JUDICIAIRE)
notary_party_id FK | document_id FK | created_by FK | created_at | updated_at
```

**`sale_deeds` :**
```
id PK | organisation_id FK | sale_agreement_id FK UK | sale_price_xof BIGINT NN
signing_date DATE NN | notary_party_id FK | registration_number VARCHAR(100)
title_transfer_date DATE | title_new_owner_id FK | document_id FK
created_by FK | created_at
```

**`sale_commissions` :**
```
id PK | organisation_id FK | sale_deed_id FK | mandate_id FK
commission_rate DECIMAL(5,2) NN | commission_amount_xof BIGINT NN
vat_amount_xof BIGINT NN D=0 | total_ttc_xof BIGINT NN
status ENUM(DUE,ENCAISSEE,REVERSEE) | collected_at DATETIME | payment_id FK
created_at
INDEX idx_sc_org_deed (organisation_id, sale_deed_id)
```

---

## DOMAINE 10 — CRM

---

### `leads`
> Prospects locataires et acheteurs

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `uuid` | CHAR(36) | UK NN | |
| `type` | ENUM('LOCATION','ACHAT') | NN | |
| `status` | ENUM('NOUVEAU','CONTACT','VISITE_PLANIFIEE','VISITE_REALISEE','DOSSIER_SOUMIS','CONVERTI','PERDU','ARCHIVE') | NN D='NOUVEAU' | |
| `heat_score` | ENUM('COLD','WARM','HOT') | NN D='COLD' | |
| `first_name` | VARCHAR(100) | NN | |
| `last_name` | VARCHAR(100) | NN | |
| `email` | VARCHAR(255) | NULL | |
| `phone` | VARCHAR(20) | NULL | |
| `budget_min_xof` | BIGINT UNSIGNED | NULL | |
| `budget_max_xof` | BIGINT UNSIGNED | NULL | |
| `desired_commune` | VARCHAR(100) | NULL | |
| `desired_type` | VARCHAR(100) | NULL | |
| `desired_rooms` | TINYINT UNSIGNED | NULL | |
| `search_notes` | TEXT | NULL | |
| `source_id` | BIGINT UNSIGNED | FK NULL | |
| `assigned_to` | BIGINT UNSIGNED | FK NULL | Agent assigné |
| `converted_party_id` | BIGINT UNSIGNED | FK NULL | |
| `converted_at` | DATETIME | NULL | |
| `last_activity_at` | DATETIME | NULL | |
| `lost_reason` | VARCHAR(255) | NULL | |
| `created_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |
| `deleted_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_leads_uuid (uuid)
INDEX idx_leads_org_status (organisation_id, status, heat_score)
INDEX idx_leads_org_agent (organisation_id, assigned_to)
INDEX idx_leads_last_activity (organisation_id, last_activity_at)
INDEX idx_leads_phone (organisation_id, phone)
FULLTEXT INDEX ft_leads_name (first_name, last_name)
```

---

### `lead_interests`, `lead_activities`, `lead_viewings`, `lead_sources`

**`lead_interests` :** Biens qui intéressent un lead
```
id PK | organisation_id FK | lead_id FK | property_id FK | unit_id FK
interest_level TINYINT(1..5) | notes TEXT | created_at
UNIQUE KEY uk_li_lead_unit (lead_id, unit_id)
INDEX idx_li_org_lead (organisation_id, lead_id)
```

**`lead_activities` :** Journal des interactions CRM
```
id PK | organisation_id FK | lead_id FK
activity_type ENUM(APPEL,EMAIL,SMS,VISITE,NOTE,RELANCE,AUTRE)
subject VARCHAR(255) | notes TEXT | activity_date DATETIME NN
performed_by FK users | created_at
INDEX idx_la_org_lead (organisation_id, lead_id, activity_date)
```

**`lead_viewings` :** Visites planifiées et réalisées
```
id PK | organisation_id FK | lead_id FK | property_id FK | unit_id FK
scheduled_at DATETIME NN | status ENUM(PLANIFIEE,REALISEE,ANNULEE,NO_SHOW)
agent_user_id FK | feedback TEXT | interest_after TINYINT(1..5)
created_by FK | created_at | updated_at
INDEX idx_lv_org_lead (organisation_id, lead_id, scheduled_at)
INDEX idx_lv_scheduled (organisation_id, scheduled_at, status)
```

**`lead_sources` :** Référentiel des sources de leads
```
id PK | organisation_id FK | name VARCHAR(100) NN | code VARCHAR(50) UK NN
is_active TINYINT(1) D=1 | created_at
```

---

## DOMAINE 11 — COPROPRIÉTÉ

---

### `condominiums`, `condominium_lots`, `condominium_budgets`, `condominium_charges`, `condominium_charge_lots`, `general_meetings`

**`condominiums` :**
```
id PK | organisation_id FK | property_id FK UK | name VARCHAR(255) NN
syndicat_name VARCHAR(255) | syndicat_manager_id FK parties | total_shares DECIMAL(12,6) NN
regulation_document_id FK | status ENUM(ACTIF,DISSOUS) D='ACTIF' | created_at | updated_at
```

**`condominium_lots` :**
```
id PK | organisation_id FK | condominium_id FK | unit_id FK UK
lot_number VARCHAR(50) NN | lot_type ENUM(PRINCIPAL,ANNEXE,PARKING)
shares DECIMAL(12,6) NN | owner_id FK parties
created_at | updated_at
INDEX idx_cl_condo (organisation_id, condominium_id)
```

**`condominium_budgets` :**
```
id PK | organisation_id FK | condominium_id FK | fiscal_year SMALLINT NN
total_amount_xof BIGINT NN D=0 | approved_at DATETIME | document_id FK
status ENUM(BROUILLON,APPROUVE,CLOTURE) | created_at
UNIQUE KEY uk_cb_condo_year (condominium_id, fiscal_year)
```

**`condominium_charges` :**
```
id PK | organisation_id FK | condominium_id FK | budget_id FK
charge_type ENUM(ENTRETIEN,GARDIENNAGE,ASSURANCE,TRAVAUX,AUTRE) NN
description VARCHAR(500) NN | period_start DATE | period_end DATE
total_amount_xof BIGINT NN | status ENUM(REPARTIE,APPELEE,SOLDEE)
invoice_id FK work_order_invoices | created_by FK | created_at
INDEX idx_cc_org_condo (organisation_id, condominium_id, status)
```

**`condominium_charge_lots` :** Distribution charges par lot
```
id PK | charge_id FK | lot_id FK | share_pct DECIMAL(10,6) NN
amount_xof BIGINT NN | paid_amount_xof BIGINT D=0
status ENUM(DUE,PARTIELLE,PAYEE) D='DUE' | schedule_id FK lease_schedules | created_at
UNIQUE KEY uk_ccl_charge_lot (charge_id, lot_id)
```

**`general_meetings` :**
```
id PK | organisation_id FK | condominium_id FK | meeting_type ENUM(AG_ORDINAIRE,AG_EXTRAORDINAIRE)
scheduled_at DATETIME NN | status ENUM(PLANIFIEE,TENUE,ANNULEE) | quorum_reached TINYINT(1)
minutes_document_id FK | created_by FK | created_at | updated_at
INDEX idx_gm_org_condo (organisation_id, condominium_id, scheduled_at)
```

---

## DOMAINE 12 — GED (GESTION DOCUMENTAIRE)

---

### `documents`
> Table centrale GED — référence polymorphique vers toutes entités

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `uuid` | CHAR(36) | UK NN | |
| `entity_type` | VARCHAR(50) | NULL | lease, property, party… |
| `entity_id` | BIGINT UNSIGNED | NULL | ID entité associée |
| `folder_id` | BIGINT UNSIGNED | FK NULL | |
| `document_type` | VARCHAR(50) | NN | BAIL, QUITTANCE, KYC, EDL… |
| `original_filename` | VARCHAR(500) | NN | |
| `storage_path` | VARCHAR(1000) | NN | Chemin S3 chiffré |
| `cdn_url` | VARCHAR(1000) | NULL | URL CDN si public |
| `file_size_bytes` | BIGINT UNSIGNED | NN | |
| `mime_type` | VARCHAR(100) | NN | |
| `checksum_sha256` | CHAR(64) | NN | Intégrité |
| `is_generated` | TINYINT(1) | NN D=0 | Généré par le système |
| `is_signed` | TINYINT(1) | NN D=0 | Signé électroniquement |
| `is_encrypted` | TINYINT(1) | NN D=1 | Chiffré at-rest |
| `is_archived` | TINYINT(1) | NN D=0 | |
| `archived_at` | DATETIME | NULL | |
| `retention_until` | DATE | NULL | Date fin rétention légale |
| `uploaded_by` | BIGINT UNSIGNED | FK NN | |
| `created_at` | DATETIME | NN D=NOW() | |
| `deleted_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_documents_uuid (uuid)
INDEX idx_documents_entity (organisation_id, entity_type, entity_id)
INDEX idx_documents_type (organisation_id, document_type)
INDEX idx_documents_folder (organisation_id, folder_id)
INDEX idx_documents_archived (is_archived, retention_until)
FULLTEXT INDEX ft_documents_name (original_filename)
```

---

### `document_folders`, `document_shares`, `document_signature_requests`, `document_signatories`

**`document_folders` :**
```
id PK | organisation_id FK | parent_id FK self | entity_type VARCHAR(50)
entity_id BIGINT | name VARCHAR(255) NN | path TEXT | sort_order TINYINT D=0
created_by FK | created_at
INDEX idx_df_org_parent (organisation_id, parent_id)
INDEX idx_df_entity (organisation_id, entity_type, entity_id)
```

**`document_shares` :**
```
id PK | organisation_id FK | document_id FK | share_token VARCHAR(255) UK NN
shared_with_email VARCHAR(255) | shared_with_party_id FK | max_downloads TINYINT D=1
download_count TINYINT NN D=0 | expires_at DATETIME NN
created_by FK | created_at
INDEX idx_dsh_token (share_token)
INDEX idx_dsh_expires (expires_at)
```

**`document_signature_requests` :**
```
id PK | organisation_id FK | document_id FK | external_ref VARCHAR(255)
provider ENUM(YOUSIGN,DOCUSIGN,OTP_SMS) NN | status ENUM(PENDING,COMPLETED,EXPIRED,CANCELLED)
initiated_at DATETIME NN | completed_at DATETIME | expires_at DATETIME
created_by FK | created_at | updated_at
INDEX idx_dsr_org_doc (organisation_id, document_id, status)
```

**`document_signatories` :**
```
id PK | signature_request_id FK | party_id FK | user_id FK
signatory_role VARCHAR(50) NN | email VARCHAR(255) | phone VARCHAR(20)
status ENUM(PENDING,SIGNED,DECLINED) D='PENDING' | signed_at DATETIME
ip_address VARCHAR(45) | signature_certificate TEXT | created_at
INDEX idx_ds_request (signature_request_id, status)
```

---

## DOMAINE 13 — NOTIFICATIONS

---

### `notification_templates`
> Templates de messages par type d'événement

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NULL | NULL = template système |
| `template_code` | VARCHAR(100) | NN | PAYMENT_RECEIVED, LEASE_SIGNED… |
| `channel` | ENUM('SMS','EMAIL','WHATSAPP','PUSH','IN_APP') | NN | |
| `language` | VARCHAR(10) | NN D='fr' | |
| `subject` | VARCHAR(500) | NULL | Sujet email |
| `body` | TEXT | NN | Template avec {{variables}} |
| `is_active` | TINYINT(1) | NN D=1 | |
| `is_system` | TINYINT(1) | NN D=0 | Non modifiable |
| `created_at` | DATETIME | NN D=NOW() | |
| `updated_at` | DATETIME | NULL | |

```
PRIMARY KEY (id)
UNIQUE KEY uk_nt_org_code_channel (organisation_id, template_code, channel, language)
```

---

### `notification_logs`
> Journal complet de toutes les notifications envoyées — très haute volumétrie

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `recipient_user_id` | BIGINT UNSIGNED | FK NULL | |
| `recipient_party_id` | BIGINT UNSIGNED | FK NULL | |
| `channel` | ENUM('SMS','EMAIL','WHATSAPP','PUSH','IN_APP') | NN | |
| `template_code` | VARCHAR(100) | NULL | |
| `recipient_address` | VARCHAR(500) | NN | Email ou téléphone |
| `subject` | VARCHAR(500) | NULL | |
| `body` | TEXT | NN | Contenu envoyé |
| `status` | ENUM('QUEUED','SENT','DELIVERED','READ','FAILED','BOUNCED') | NN D='QUEUED' | |
| `attempt_count` | TINYINT UNSIGNED | NN D=0 | |
| `provider_message_id` | VARCHAR(255) | NULL | ID Infobip/SES |
| `error_message` | VARCHAR(500) | NULL | |
| `sent_at` | DATETIME | NULL | |
| `delivered_at` | DATETIME | NULL | |
| `read_at` | DATETIME | NULL | |
| `metadata` | JSON | NULL | Contexte (lease_id, etc.) |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_nl_org_recipient_user (organisation_id, recipient_user_id, created_at)
INDEX idx_nl_org_recipient_party (organisation_id, recipient_party_id, created_at)
INDEX idx_nl_status (organisation_id, status, sent_at)
INDEX idx_nl_channel (organisation_id, channel, created_at)
```
*Partition RANGE sur `created_at` — partitions mensuelles, rétention 24 mois actif, archivage ensuite*

---

### `notification_preferences`, `notification_queue`

**`notification_preferences` :**
```
id PK | organisation_id FK | user_id FK | party_id FK
channel ENUM(SMS,EMAIL,WHATSAPP,PUSH) NN
notification_type VARCHAR(100) NN | is_enabled TINYINT(1) NN D=1
created_at | updated_at
UNIQUE KEY uk_np_recipient_channel_type (user_id, party_id, channel, notification_type)
```

**`notification_queue` :** File d'attente d'envoi
```
id PK | organisation_id FK | template_code VARCHAR(100) NN | channel VARCHAR(20) NN
recipient_address VARCHAR(500) NN | recipient_user_id FK | recipient_party_id FK
payload JSON NN | scheduled_at DATETIME NN | attempt_count TINYINT D=0
status ENUM(PENDING,PROCESSING,SENT,FAILED) D='PENDING' | last_error TEXT
created_at | updated_at
INDEX idx_nq_status_scheduled (status, scheduled_at)
INDEX idx_nq_org (organisation_id, status)
```

---

## DOMAINE 14 — INTELLIGENCE ARTIFICIELLE

---

### `solvency_scores`
> Scores de solvabilité calculés pour les locataires

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NN | |
| `party_id` | BIGINT UNSIGNED | FK NN | |
| `lease_id` | BIGINT UNSIGNED | FK NULL | Si score pour bail spécifique |
| `score_value` | TINYINT UNSIGNED | NN | 0–100 |
| `score_grade` | ENUM('A','B','C','D','E') | NN | |
| `rent_to_income_ratio` | DECIMAL(5,2) | NULL | % loyer / revenus |
| `monthly_income_xof` | BIGINT UNSIGNED | NULL | |
| `rent_amount_xof` | BIGINT UNSIGNED | NULL | Loyer évalué |
| `employment_points` | TINYINT UNSIGNED | NULL | |
| `docs_complete_points` | TINYINT UNSIGNED | NULL | |
| `history_points` | TINYINT UNSIGNED | NULL | |
| `score_details` | JSON | NULL | Détail calcul |
| `model_version` | VARCHAR(20) | NN D='rule_v1' | |
| `is_overridden` | TINYINT(1) | NN D=0 | |
| `override_grade` | ENUM('A','B','C','D','E') | NULL | |
| `override_reason` | TEXT | NULL | |
| `overridden_by` | BIGINT UNSIGNED | FK NULL | |
| `overridden_at` | DATETIME | NULL | |
| `calculated_at` | DATETIME | NN | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_ss_org_party (organisation_id, party_id, calculated_at)
INDEX idx_ss_grade (organisation_id, score_grade)
```

---

### `property_valuations`, `payment_risk_assessments`, `ocr_extractions`, `ai_decision_overrides`

**`property_valuations` :**
```
id PK | organisation_id FK | property_id FK | unit_id FK
valuation_type ENUM(LOYER,VENTE) NN | estimated_min_xof BIGINT NN | estimated_max_xof BIGINT NN
estimated_value_xof BIGINT NN | confidence_score DECIMAL(5,2) | comparables_count TINYINT
model_version VARCHAR(20) NN | input_data JSON | is_overridden TINYINT(1) D=0
final_value_xof BIGINT | override_reason TEXT | overridden_by FK | calculated_at DATETIME NN
created_at
INDEX idx_pv_org_property (organisation_id, property_id, valuation_type, calculated_at)
```

**`payment_risk_assessments` :**
```
id PK | organisation_id FK | lease_id FK | party_id FK
assessment_date DATE NN | risk_score TINYINT UNSIGNED NN
risk_level ENUM(FAIBLE,MOYEN,ELEVE,CRITIQUE) NN
days_late_avg DECIMAL(5,2) | partial_payments_count TINYINT
missed_payments_count TINYINT | model_version VARCHAR(20) NN
recommendations JSON | created_at
INDEX idx_pra_org_lease (organisation_id, lease_id, assessment_date)
```

**`ocr_extractions` :**
```
id PK | organisation_id FK | document_id FK | party_id FK
doc_type VARCHAR(50) NN | extracted_fields JSON NN | confidence_score DECIMAL(5,2)
validated_fields JSON | model_version VARCHAR(20) NN
status ENUM(EXTRACTED,VALIDATED,REJECTED) D='EXTRACTED' | validated_by FK | validated_at
created_at
INDEX idx_ocr_org_doc (organisation_id, document_id)
```

**`ai_decision_overrides` :**
```
id PK | organisation_id FK | module VARCHAR(50) NN | entity_type VARCHAR(50) NN
entity_id BIGINT NN | ai_recommendation VARCHAR(255) | ai_confidence DECIMAL(5,2)
human_decision VARCHAR(255) NN | override_reason TEXT NN | overridden_by FK NN
created_at
INDEX idx_ado_org_entity (organisation_id, entity_type, entity_id)
```

---

## DOMAINE 15 — AUDIT, HISTORISATION & ARCHIVAGE

---

### `audit_log`
> Journal d'audit global — immuable, haute volumétrie (~500M enregistrements)

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | FK NULL | NULL = action système |
| `user_id` | BIGINT UNSIGNED | FK NULL | |
| `action` | VARCHAR(100) | NN | CREATE, UPDATE, DELETE, LOGIN… |
| `module` | VARCHAR(50) | NN | property, lease, financial… |
| `entity_type` | VARCHAR(100) | NN | |
| `entity_id` | BIGINT UNSIGNED | NULL | |
| `entity_uuid` | CHAR(36) | NULL | |
| `ip_address` | VARCHAR(45) | NULL | |
| `user_agent` | VARCHAR(500) | NULL | |
| `request_id` | CHAR(36) | NULL | Corrélation requête HTTP |
| `old_values` | JSON | NULL | État avant (UPDATE/DELETE) |
| `new_values` | JSON | NULL | État après (CREATE/UPDATE) |
| `context` | JSON | NULL | Métadonnées additionnelles |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_al_org_module (organisation_id, module, created_at)
INDEX idx_al_entity (organisation_id, entity_type, entity_id, created_at)
INDEX idx_al_user_id (user_id, created_at)
INDEX idx_al_created_at (created_at)
```
*Partition RANGE sur `created_at` — partitions mensuelles*
*Rétention partition active : 12 mois — Archivage ensuite dans `archived_audit_log`*

---

### `data_change_log`
> Historisation row-level des modifications de données critiques

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `organisation_id` | BIGINT UNSIGNED | NN | |
| `table_name` | VARCHAR(100) | NN | |
| `record_id` | BIGINT UNSIGNED | NN | |
| `operation` | ENUM('INSERT','UPDATE','DELETE') | NN | |
| `old_data` | JSON | NULL | JSON avant |
| `new_data` | JSON | NULL | JSON après |
| `changed_columns` | JSON | NULL | Array des colonnes modifiées |
| `changed_by` | BIGINT UNSIGNED | NULL | |
| `changed_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_dcl_table_record (table_name, record_id, changed_at)
INDEX idx_dcl_org (organisation_id, changed_at)
```
*Partition RANGE sur `changed_at` — partitions mensuelles*

---

### `audit_auth_logs`
> Journal spécifique des événements d'authentification

| Colonne | Type | Attr | Description |
|---|---|---|---|
| `id` | BIGINT UNSIGNED | PK AI NN | |
| `user_id` | BIGINT UNSIGNED | FK NULL | |
| `organisation_id` | BIGINT UNSIGNED | NULL | |
| `event_type` | ENUM('LOGIN_SUCCESS','LOGIN_FAILED','LOGOUT','MFA_SUCCESS','MFA_FAILED','PASSWORD_RESET','ACCOUNT_LOCKED','SESSION_REVOKED') | NN | |
| `email_attempted` | VARCHAR(255) | NULL | Si login échoué |
| `ip_address` | VARCHAR(45) | NN | |
| `user_agent` | VARCHAR(500) | NULL | |
| `country_code` | CHAR(2) | NULL | |
| `is_suspicious` | TINYINT(1) | NN D=0 | |
| `created_at` | DATETIME | NN D=NOW() | |

```
PRIMARY KEY (id)
INDEX idx_aal_user_id (user_id, created_at)
INDEX idx_aal_ip (ip_address, created_at)
INDEX idx_aal_event_type (event_type, created_at)
INDEX idx_aal_suspicious (is_suspicious, created_at)
```
*Partition RANGE sur `created_at` — partitions mensuelles, rétention 24 mois*

---

### Tables d'archivage

**`archive_jobs` :**
```
id PK | job_type VARCHAR(100) NN | target_table VARCHAR(100) NN
criteria JSON NN | records_archived INT UNSIGNED D=0 | status ENUM(PENDING,RUNNING,COMPLETED,FAILED)
started_at DATETIME | completed_at DATETIME | error_message TEXT
created_by FK | created_at
```

**`archived_leases` :** Même structure que `leases` + colonnes :
```
archived_at DATETIME NN | archive_reason VARCHAR(255) | original_id BIGINT NN
INDEX idx_arch_leases_org (organisation_id, archived_at)
INDEX idx_arch_leases_original (original_id)
```

**`archived_payments` :** Même structure que `payments` + `archived_at DATETIME NN`
*Partition RANGE sur `payment_date` — partitions annuelles*

**`archived_journal_entries` :** Même structure que `journal_entries` + `archived_at DATETIME NN`
*Partition RANGE sur `entry_date` — partitions annuelles*

---

## 5. STRATÉGIE D'INDEXATION GLOBALE

### 5.1 Règles d'index par type de requête

| Pattern de requête | Stratégie |
|---|---|
| Filtrage multi-tenant | Toujours `(organisation_id, ...)` en premier |
| Recherche par statut | `(organisation_id, status)` ou `(organisation_id, status, created_at)` |
| Tri chronologique | Inclure `created_at` ou `date` en dernier dans l'index |
| Lookup par UUID externe | `UNIQUE KEY (uuid)` simple |
| Jointure FK | Index sur toute FK (MySQL ne les crée pas automatiquement) |
| Recherche full-text | `FULLTEXT INDEX (colonnes texte)` sur les colonnes de recherche |
| Soft delete | `INDEX (deleted_at)` + conditions `WHERE deleted_at IS NULL` |
| Expiration | `INDEX (expires_at, status)` pour les jobs de nettoyage |

### 5.2 Index critiques pour les requêtes chaudes

```sql
-- Tableau de bord agence : biens actifs
(organisation_id, status, deleted_at)           -- properties

-- Appels de fonds en retard
(organisation_id, due_date, status)             -- lease_schedules

-- Paiements du mois
(organisation_id, payment_date, status)         -- payments

-- Locataires avec impayés
(organisation_id, status)                       -- lease_schedules WHERE status IN ('OVERDUE','CONTENTIOUS')

-- Relances à envoyer
(status, scheduled_at)                         -- notification_queue

-- OI urgentes non assignées
(organisation_id, priority, status)            -- work_orders
```

---

## 6. PARTITIONNEMENT MYSQL

### Tables partitionnées par RANGE sur date

| Table | Clé de partition | Granularité | Rétention active |
|---|---|---|---|
| `payments` | `payment_date` | Annuelle | 5 ans |
| `journal_entries` | `entry_date` | Annuelle | 10 ans |
| `lease_schedules` | `due_date` | Annuelle | Durée bail + 5 ans |
| `notification_logs` | `created_at` | Mensuelle | 24 mois |
| `audit_log` | `created_at` | Mensuelle | 12 mois actif |
| `data_change_log` | `changed_at` | Mensuelle | 12 mois actif |
| `audit_auth_logs` | `created_at` | Mensuelle | 24 mois |
| `sessions` | `created_at` | Mensuelle | 3 mois |

### Exemple de définition de partition

```sql
-- payments : partitions annuelles
PARTITION BY RANGE (YEAR(payment_date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

---

## 7. POLITIQUE D'ARCHIVAGE

| Table source | Critère d'archivage | Table cible | Fréquence job |
|---|---|---|---|
| `leases` | `status IN ('TERMINE','ARCHIVE') AND terminated_at < NOW() - INTERVAL 3 YEAR` | `archived_leases` | Trimestrielle |
| `payments` | `created_at < NOW() - INTERVAL 5 YEAR` | `archived_payments` | Annuelle |
| `journal_entries` | `entry_date < NOW() - INTERVAL 10 YEAR` | `archived_journal_entries` | Annuelle |
| `audit_log` | `created_at < NOW() - INTERVAL 12 MONTH` | Partition froide S3 | Mensuelle |
| `notification_logs` | `created_at < NOW() - INTERVAL 24 MONTH` | Partition froide S3 | Mensuelle |
| `sessions` | `expires_at < NOW() - INTERVAL 1 MONTH` | DELETE direct | Quotidienne |
| `notification_queue` | `status IN ('SENT','FAILED') AND created_at < NOW() - INTERVAL 7 DAY` | DELETE direct | Quotidienne |

---

## 8. CONTRAINTES D'INTÉGRITÉ GLOBALES

### 8.1 Contraintes CHECK (MySQL 8.0+)

```
payments       : CHECK (debit_amount_xof = 0 OR credit_amount_xof = 0)
lease_schedules: CHECK (paid_amount_xof <= total_amount_xof)
leases         : CHECK (payment_day BETWEEN 1 AND 28)
leases         : CHECK (vat_rate IN (0.00, 18.00))
parties        : CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$')
properties     : CHECK (indicative_rent_amount > 0 OR indicative_sale_price > 0 OR (indicative_rent_amount IS NULL AND indicative_sale_price IS NULL))
security_deposits: CHECK (returned_amount_xof <= amount_xof)
```

### 8.2 Soft delete — règle universelle

Toutes les tables avec `deleted_at` respectent :
- Tous les index incluent `deleted_at` en filtre implicite applicatif
- Les FK qui pointent vers des entités soft-deleted restent valides (données historiques)
- Les rapports excluent toujours `WHERE deleted_at IS NULL`

### 8.3 XOF — intégrité des montants

- Toutes les colonnes `*_amount_xof`, `*_price_xof`, `*_xof` : `BIGINT UNSIGNED`
- Valeur maximum stockable : 18 446 744 073 709 551 615 — largement suffisant pour XOF
- Aucun stockage de montants en format décimal pour éviter les erreurs d'arrondi

---

*Suite → DATABASE_DESIGN_P3.md : Vues, Procédures, Triggers, Configuration MySQL*
