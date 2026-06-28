# KILIE IMMO — Conception Base de Données MySQL
## Document de Référence DBA — v1.0 — Juin 2026
### Partie 1 : Modèle Conceptuel, Logique & ERD

---

## 1. PRINCIPES DE CONCEPTION

### 1.1 Moteur et Encodage

| Paramètre | Valeur | Justification |
|---|---|---|
| Moteur | InnoDB | ACID, FK, transactions, row-level locking |
| Charset | utf8mb4 | Unicode complet, emojis, noms africains |
| Collation | utf8mb4_unicode_ci | Tri insensible à la casse, caractères français |
| Row format | DYNAMIC | VARCHAR/TEXT hors page, performance |
| Version cible | MySQL 8.0+ | JSON natif, window functions, partitioning avancé |

### 1.2 Multi-tenancy — Stratégie Row-Level Isolation

Toutes les tables opérationnelles portent `organisation_id BIGINT UNSIGNED NOT NULL` en deuxième colonne après `id`. Tous les index composites commencent par `organisation_id`. L'isolation est garantie à deux niveaux :

1. **Application** : Chaque requête injecte `WHERE organisation_id = :current_org`
2. **Base** : Index composites `(organisation_id, ...)` — table scan multi-tenant impossible

### 1.3 Conventions de Nommage

| Objet | Convention | Exemple |
|---|---|---|
| Table | snake_case, pluriel | `lease_schedules` |
| Colonne | snake_case | `due_date`, `created_at` |
| PK | `id` BIGINT UNSIGNED AUTO_INCREMENT | `id` |
| UUID externe | `uuid` CHAR(36) | `uuid` (exposé aux APIs) |
| FK | `{table_sing}_id` BIGINT UNSIGNED | `lease_id`, `organisation_id` |
| PK nommée | `PRIMARY KEY (id)` | — |
| UK | `uk_{table}_{champs}` | `uk_users_org_email` |
| Index | `idx_{table}_{champs}` | `idx_leases_org_status` |
| FK constraint | `fk_{table}_{ref}` | `fk_leases_organisation` |
| Statut | `ENUM(...)` ou `VARCHAR(30)` | `status` |
| Booléen | `TINYINT(1)` | `is_active`, `is_vat_applicable` |
| Montant XOF | `BIGINT UNSIGNED` | `amount`, `rent_amount` |
| Taux | `DECIMAL(5,2)` | `vat_rate`, `late_fee_rate` |
| Timestamps | `DATETIME` (UTC) | `created_at`, `updated_at`, `deleted_at` |
| Suppression | Soft delete `deleted_at` | — |

### 1.4 Types de données clés

| Donnée | Type MySQL | Raison |
|---|---|---|
| Montant XOF | `BIGINT UNSIGNED` | FCFA = entier, pas de décimal, évite les erreurs float |
| Taux (%) | `DECIMAL(5,2)` | Ex : 18.00, 10.50 |
| Surface (m²) | `DECIMAL(10,2)` | Précision décimale nécessaire |
| Latitude GPS | `DECIMAL(10,8)` | Précision ~1mm |
| Longitude GPS | `DECIMAL(11,8)` | Précision ~1mm |
| Téléphone | `VARCHAR(20)` | Format E.164 : +22507XXXXXXXX |
| UUID API | `CHAR(36)` | Format standard UUID v4 |
| JSON libre | `JSON` | MySQL 8.0 natif, validé |
| Durée (jours) | `SMALLINT UNSIGNED` | Max 65535 jours |
| Durée (mois) | `TINYINT UNSIGNED` | Max 255 mois |
| Année | `YEAR` | 1901–2155 |

---

## 2. MODÈLE CONCEPTUEL — ENTITÉS ET ASSOCIATIONS

### 2.1 Domaines et entités principales

```
┌─────────────────────────────────────────────────────────────────────┐
│  DOMAINE IAM          DOMAINE PROPERTY       DOMAINE PARTY          │
│  ─────────────        ─────────────────       ─────────────          │
│  Organisation         Property               Party                  │
│  User                 PropertyUnit           Individual             │
│  Role                 PropertyPhoto          Company                │
│  Session              LegalDocument          KYCDocument            │
│  Invitation           Mandate                IdentityDocument       │
│                                                                      │
│  DOMAINE LEASING      DOMAINE FINANCIAL      DOMAINE ACCOUNTING     │
│  ───────────────       ─────────────────      ──────────────────     │
│  Lease                LeaseSchedule          ChartOfAccount         │
│  Clause               Payment                JournalEntry           │
│  Revision             Transaction            FiscalPeriod           │
│  Notice               Receipt                TaxDeclaration         │
│  InventoryCheck       LateFee                OwnerStatement         │
│  SecurityDeposit      Reminder                                       │
│                                                                      │
│  DOMAINE MAINTENANCE  DOMAINE SALES          DOMAINE CRM            │
│  ─────────────────     ─────────────          ───────────            │
│  WorkOrder            SaleMandate            Lead                   │
│  Quote                PurchaseOffer          LeadActivity           │
│  WorkInvoice          SaleAgreement          Viewing                │
│  ServiceProvider      Deed                                           │
│                                                                      │
│  DOMAINE DOCUMENT     DOMAINE NOTIF          DOMAINE AI             │
│  ──────────────        ─────────────          ──────────             │
│  Document             NotifTemplate          SolvencyScore          │
│  Folder               NotifLog               PropertyValuation      │
│  ShareLink            NotifQueue             PaymentRisk            │
│  SignatureRequest      Preference             OcrExtraction          │
│                                                                      │
│  DOMAINE AUDIT                                                       │
│  ──────────────                                                      │
│  AuditLog                                                            │
│  DataChangeLog                                                       │
└─────────────────────────────────────────────────────────────────────┘
```

### 2.2 Catalogue complet des tables (~100 tables)

| # | Table | Domaine | Enregistrements estimés (5 ans) |
|---|---|---|---|
| 1 | `organisations` | IAM | 5 000 |
| 2 | `users` | IAM | 50 000 |
| 3 | `roles` | IAM | 200 |
| 4 | `user_roles` | IAM | 100 000 |
| 5 | `permissions` | IAM | 500 |
| 6 | `role_permissions` | IAM | 5 000 |
| 7 | `user_permissions` | IAM | 20 000 |
| 8 | `sessions` | IAM | 2 000 000 |
| 9 | `invitations` | IAM | 50 000 |
| 10 | `password_reset_tokens` | IAM | 100 000 |
| 11 | `subscription_plans` | Subscription | 20 |
| 12 | `subscription_plan_features` | Subscription | 200 |
| 13 | `organisation_subscriptions` | Subscription | 5 000 |
| 14 | `subscription_invoices` | Subscription | 60 000 |
| 15 | `properties` | Property | 500 000 |
| 16 | `property_units` | Property | 2 000 000 |
| 17 | `property_photos` | Property | 10 000 000 |
| 18 | `property_legal_docs` | Property | 1 000 000 |
| 19 | `property_mandates` | Property | 500 000 |
| 20 | `property_status_history` | Property | 2 000 000 |
| 21 | `property_amenities` | Property | 5 000 000 |
| 22 | `parties` | Party | 2 000 000 |
| 23 | `party_individuals` | Party | 1 500 000 |
| 24 | `party_companies` | Party | 500 000 |
| 25 | `party_identity_docs` | Party | 3 000 000 |
| 26 | `party_kyc_docs` | Party | 5 000 000 |
| 27 | `party_guarantors` | Party | 500 000 |
| 28 | `leases` | Leasing | 1 000 000 |
| 29 | `lease_schedules` | Leasing | 30 000 000 |
| 30 | `lease_clauses` | Leasing | 5 000 000 |
| 31 | `lease_revisions` | Leasing | 2 000 000 |
| 32 | `lease_renewals` | Leasing | 500 000 |
| 33 | `lease_notices` | Leasing | 1 000 000 |
| 34 | `lease_inventory_checks` | Leasing | 2 000 000 |
| 35 | `lease_inventory_items` | Leasing | 40 000 000 |
| 36 | `lease_signatories` | Leasing | 3 000 000 |
| 37 | `security_deposits` | Leasing | 1 000 000 |
| 38 | `payments` | Financial | 30 000 000 |
| 39 | `payment_transactions` | Financial | 25 000 000 |
| 40 | `receipts` | Financial | 25 000 000 |
| 41 | `late_fees` | Financial | 5 000 000 |
| 42 | `reminders` | Financial | 20 000 000 |
| 43 | `owner_statements` | Financial | 5 000 000 |
| 44 | `owner_statement_lines` | Financial | 30 000 000 |
| 45 | `refunds` | Financial | 500 000 |
| 46 | `chart_of_accounts` | Accounting | 10 000 |
| 47 | `journal_entries` | Accounting | 60 000 000 |
| 48 | `journal_entry_lines` | Accounting | 120 000 000 |
| 49 | `fiscal_periods` | Accounting | 60 000 |
| 50 | `tax_declarations` | Accounting | 300 000 |
| 51 | `tax_declaration_lines` | Accounting | 1 500 000 |
| 52 | `service_providers` | Maintenance | 200 000 |
| 53 | `service_provider_specialties` | Maintenance | 500 000 |
| 54 | `work_orders` | Maintenance | 5 000 000 |
| 55 | `work_order_quotes` | Maintenance | 4 000 000 |
| 56 | `work_order_quote_lines` | Maintenance | 20 000 000 |
| 57 | `work_order_invoices` | Maintenance | 4 000 000 |
| 58 | `work_order_status_history` | Maintenance | 20 000 000 |
| 59 | `sale_mandates` | Sales | 200 000 |
| 60 | `sale_viewings` | Sales | 1 000 000 |
| 61 | `purchase_offers` | Sales | 500 000 |
| 62 | `sale_agreements` | Sales | 200 000 |
| 63 | `sale_deeds` | Sales | 100 000 |
| 64 | `sale_commissions` | Sales | 200 000 |
| 65 | `leads` | CRM | 2 000 000 |
| 66 | `lead_interests` | CRM | 5 000 000 |
| 67 | `lead_activities` | CRM | 10 000 000 |
| 68 | `lead_viewings` | CRM | 2 000 000 |
| 69 | `lead_sources` | CRM | 100 |
| 70 | `condominiums` | Condominium | 50 000 |
| 71 | `condominium_lots` | Condominium | 500 000 |
| 72 | `condominium_budgets` | Condominium | 200 000 |
| 73 | `condominium_charges` | Condominium | 1 000 000 |
| 74 | `condominium_charge_lots` | Condominium | 10 000 000 |
| 75 | `general_meetings` | Condominium | 100 000 |
| 76 | `general_meeting_resolutions` | Condominium | 500 000 |
| 77 | `documents` | Document | 50 000 000 |
| 78 | `document_folders` | Document | 5 000 000 |
| 79 | `document_shares` | Document | 2 000 000 |
| 80 | `document_signature_requests` | Document | 3 000 000 |
| 81 | `document_signatories` | Document | 6 000 000 |
| 82 | `document_templates` | Document | 1 000 |
| 83 | `notification_templates` | Notification | 500 |
| 84 | `notification_logs` | Notification | 200 000 000 |
| 85 | `notification_preferences` | Notification | 1 000 000 |
| 86 | `notification_queue` | Notification | 1 000 000 (actifs) |
| 87 | `solvency_scores` | AI | 5 000 000 |
| 88 | `property_valuations` | AI | 2 000 000 |
| 89 | `payment_risk_assessments` | AI | 20 000 000 |
| 90 | `ocr_extractions` | AI | 10 000 000 |
| 91 | `ai_decision_overrides` | AI | 500 000 |
| 92 | `audit_auth_logs` | Audit | 100 000 000 |
| 93 | `audit_log` | Audit | 500 000 000 |
| 94 | `data_change_log` | Audit | 200 000 000 |
| 95 | `system_events` | Audit | 50 000 000 |
| 96 | `archive_jobs` | Archive | 10 000 |
| 97 | `archived_leases` | Archive | 5 000 000 |
| 98 | `archived_payments` | Archive | 100 000 000 |
| 99 | `archived_journal_entries` | Archive | 300 000 000 |
| 100 | `schema_migrations` | System | 1 000 |

---

## 3. DIAGRAMMES ERD — PAR DOMAINE

### ERD 1 — IAM & Subscriptions

```mermaid
erDiagram
    organisations {
        bigint id PK
        char uuid
        varchar name
        varchar slug
        varchar type
        varchar status
        bigint subscription_plan_id FK
        datetime trial_ends_at
        json settings
        varchar logo_url
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    subscription_plans {
        bigint id PK
        varchar name
        varchar code
        bigint price_monthly
        bigint price_annual
        int max_users
        int max_properties
        int max_units
        json features
        tinyint is_active
    }

    organisation_subscriptions {
        bigint id PK
        bigint organisation_id FK
        bigint plan_id FK
        varchar status
        datetime starts_at
        datetime ends_at
        varchar billing_cycle
        bigint price_paid
    }

    users {
        bigint id PK
        bigint organisation_id FK
        char uuid
        varchar email
        varchar phone
        varchar password_hash
        varchar first_name
        varchar last_name
        varchar status
        tinyint is_mfa_enabled
        int login_attempts
        datetime locked_until
        datetime last_login_at
        datetime created_at
        datetime deleted_at
    }

    roles {
        bigint id PK
        bigint organisation_id FK
        varchar name
        varchar code
        varchar description
        tinyint is_system
    }

    user_roles {
        bigint id PK
        bigint user_id FK
        bigint role_id FK
        bigint granted_by FK
        datetime granted_at
        datetime expires_at
    }

    permissions {
        bigint id PK
        varchar module
        varchar action
        varchar resource
        varchar code
        varchar description
    }

    role_permissions {
        bigint role_id FK
        bigint permission_id FK
    }

    sessions {
        bigint id PK
        bigint user_id FK
        varchar token_hash
        varchar refresh_token_hash
        varchar ip_address
        varchar user_agent
        datetime expires_at
        datetime created_at
        datetime revoked_at
    }

    invitations {
        bigint id PK
        bigint organisation_id FK
        bigint role_id FK
        varchar email
        varchar token_hash
        bigint invited_by FK
        datetime expires_at
        datetime accepted_at
        datetime created_at
    }

    organisations ||--o{ users : "has"
    organisations ||--|| organisation_subscriptions : "has"
    subscription_plans ||--o{ organisation_subscriptions : "governs"
    users ||--o{ user_roles : "has"
    roles ||--o{ user_roles : "assigned via"
    roles ||--o{ role_permissions : "has"
    permissions ||--o{ role_permissions : "included in"
    users ||--o{ sessions : "opens"
    organisations ||--o{ invitations : "sends"
    organisations ||--o{ roles : "defines"
```

### ERD 2 — Properties & Parties

```mermaid
erDiagram
    properties {
        bigint id PK
        bigint organisation_id FK
        char uuid
        varchar reference_code
        varchar name
        varchar type
        varchar status
        bigint primary_owner_id FK
        bigint manager_id FK
        varchar commune
        varchar quartier
        varchar ilot
        text street_description
        decimal gps_lat
        decimal gps_lng
        decimal total_surface
        tinyint rooms_count
        tinyint bedrooms_count
        varchar condition
        bigint indicative_rent_amount
        bigint indicative_sale_price
        tinyint is_vat_applicable
        varchar title_deed_number
        varchar title_deed_type
        bigint created_by FK
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    property_units {
        bigint id PK
        bigint organisation_id FK
        bigint property_id FK
        varchar reference_code
        varchar type
        varchar status
        decimal surface
        tinyint floor_number
        tinyint rooms_count
        bigint indicative_rent_amount
        tinyint is_furnished
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    property_photos {
        bigint id PK
        bigint organisation_id FK
        bigint property_id FK
        bigint unit_id FK
        varchar storage_path
        varchar cdn_url
        varchar alt_text
        tinyint sort_order
        tinyint is_primary
        bigint uploaded_by FK
        datetime created_at
    }

    property_legal_docs {
        bigint id PK
        bigint organisation_id FK
        bigint property_id FK
        varchar doc_type
        varchar reference_number
        date issue_date
        date expiry_date
        bigint document_id FK
        bigint uploaded_by FK
        datetime created_at
    }

    property_mandates {
        bigint id PK
        bigint organisation_id FK
        bigint property_id FK
        bigint owner_id FK
        bigint manager_id FK
        varchar mandate_type
        varchar status
        date start_date
        date end_date
        decimal management_fee_rate
        decimal letting_fee_months
        decimal sale_commission_rate
        bigint document_id FK
        datetime created_at
        datetime updated_at
    }

    parties {
        bigint id PK
        bigint organisation_id FK
        char uuid
        varchar type
        varchar party_kind
        varchar display_name
        varchar email
        varchar phone
        varchar phone_secondary
        varchar commune
        varchar address_description
        varchar kyc_status
        bigint kyc_reviewed_by FK
        datetime kyc_reviewed_at
        text notes
        bigint created_by FK
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    party_individuals {
        bigint id PK
        bigint party_id FK
        varchar last_name
        varchar first_names
        date birth_date
        varchar birth_place
        varchar nationality
        varchar marital_status
        varchar employment_type
        varchar employer
        bigint monthly_income
        datetime created_at
        datetime updated_at
    }

    party_companies {
        bigint id PK
        bigint party_id FK
        varchar company_name
        varchar legal_form
        varchar rccm
        varchar cif
        varchar tax_id
        varchar representative_name
        tinyint is_vat_registered
        varchar vat_number
        datetime created_at
        datetime updated_at
    }

    party_guarantors {
        bigint id PK
        bigint organisation_id FK
        bigint tenant_party_id FK
        bigint guarantor_party_id FK
        varchar guarantee_type
        bigint guaranteed_amount
        date start_date
        date end_date
        bigint document_id FK
        datetime created_at
    }

    properties ||--o{ property_units : "contains"
    properties ||--o{ property_photos : "has"
    properties ||--o{ property_legal_docs : "documented by"
    properties ||--o{ property_mandates : "governed by"
    parties ||--o| party_individuals : "extended by"
    parties ||--o| party_companies : "extended by"
    parties ||--o{ party_guarantors : "guarantees for"
    properties }o--|| parties : "owned by"
```

### ERD 3 — Leasing & Contrats

```mermaid
erDiagram
    leases {
        bigint id PK
        bigint organisation_id FK
        char uuid
        varchar reference_number
        bigint unit_id FK
        bigint tenant_id FK
        bigint owner_id FK
        bigint manager_id FK
        varchar type
        varchar status
        date start_date
        date end_date
        smallint duration_months
        bigint rent_amount
        bigint charges_amount
        tinyint is_vat_applicable
        decimal vat_rate
        bigint total_amount_ttc
        bigint deposit_amount
        tinyint payment_day
        decimal late_fee_rate
        tinyint late_fee_tolerance_days
        smallint notice_period_days
        smallint revision_frequency_months
        decimal revision_cap_percent
        bigint document_id FK
        datetime signed_at
        datetime activated_at
        datetime terminated_at
        bigint created_by FK
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    lease_schedules {
        bigint id PK
        bigint organisation_id FK
        bigint lease_id FK
        smallint period_year
        tinyint period_month
        date due_date
        bigint rent_amount
        bigint charges_amount
        bigint vat_amount
        bigint total_amount
        bigint paid_amount
        bigint balance_amount
        bigint late_fee_amount
        varchar status
        date paid_at
        datetime created_at
        datetime updated_at
    }

    lease_clauses {
        bigint id PK
        bigint organisation_id FK
        bigint lease_id FK
        varchar clause_type
        varchar title
        text content
        tinyint sort_order
        datetime created_at
    }

    lease_revisions {
        bigint id PK
        bigint organisation_id FK
        bigint lease_id FK
        date revision_date
        bigint old_rent_amount
        bigint new_rent_amount
        decimal revision_rate
        varchar revision_basis
        varchar status
        bigint document_id FK
        bigint approved_by FK
        datetime approved_at
        datetime created_at
    }

    lease_notices {
        bigint id PK
        bigint organisation_id FK
        bigint lease_id FK
        varchar notice_type
        varchar origin
        date notice_date
        date effective_end_date
        smallint notice_days
        varchar status
        bigint document_id FK
        bigint recorded_by FK
        datetime created_at
    }

    lease_inventory_checks {
        bigint id PK
        bigint organisation_id FK
        bigint lease_id FK
        varchar check_type
        date check_date
        bigint agent_user_id FK
        varchar status
        text general_observations
        bigint document_id FK
        datetime signed_at
        datetime created_at
        datetime updated_at
    }

    lease_inventory_items {
        bigint id PK
        bigint check_id FK
        varchar room_name
        varchar item_name
        varchar condition_entry
        varchar condition_exit
        text observations
        varchar photo_url
        datetime created_at
    }

    lease_signatories {
        bigint id PK
        bigint organisation_id FK
        bigint lease_id FK
        bigint party_id FK
        varchar signatory_role
        varchar status
        varchar signing_method
        varchar ip_address
        datetime signed_at
        varchar signature_ref
        datetime created_at
    }

    security_deposits {
        bigint id PK
        bigint organisation_id FK
        bigint lease_id FK
        bigint tenant_id FK
        bigint amount
        varchar status
        bigint payment_id FK
        datetime collected_at
        bigint deductions_amount
        text deductions_detail
        bigint returned_amount
        bigint return_payment_id FK
        datetime returned_at
        bigint processed_by FK
        datetime created_at
        datetime updated_at
    }

    leases ||--o{ lease_schedules : "generates"
    leases ||--o{ lease_clauses : "has"
    leases ||--o{ lease_revisions : "undergoes"
    leases ||--o{ lease_notices : "has"
    leases ||--o{ lease_inventory_checks : "subject to"
    lease_inventory_checks ||--o{ lease_inventory_items : "contains"
    leases ||--o{ lease_signatories : "signed by"
    leases ||--|| security_deposits : "has"
```

### ERD 4 — Finance & Paiements

```mermaid
erDiagram
    payments {
        bigint id PK
        bigint organisation_id FK
        char uuid
        bigint lease_id FK
        bigint schedule_id FK
        bigint party_id FK
        bigint amount
        varchar channel
        date payment_date
        varchar internal_reference
        varchar external_reference
        varchar mobile_money_phone
        varchar status
        datetime confirmed_at
        bigint recorded_by FK
        text notes
        datetime created_at
        datetime updated_at
    }

    payment_transactions {
        bigint id PK
        bigint organisation_id FK
        bigint payment_id FK
        varchar cinetpay_transaction_id
        varchar operator
        bigint amount
        char currency
        varchar customer_phone
        varchar customer_name
        varchar status
        text payment_url
        varchar ussd_code
        json webhook_payload
        json metadata
        datetime initiated_at
        datetime expires_at
        datetime webhook_received_at
        datetime created_at
        datetime updated_at
    }

    receipts {
        bigint id PK
        bigint organisation_id FK
        char uuid
        varchar receipt_number
        bigint payment_id FK
        bigint lease_id FK
        bigint tenant_id FK
        bigint unit_id FK
        smallint period_year
        tinyint period_month
        bigint rent_amount
        bigint charges_amount
        bigint vat_amount
        bigint total_amount
        bigint document_id FK
        datetime issued_at
        datetime sent_at
        bigint issued_by FK
        datetime created_at
    }

    late_fees {
        bigint id PK
        bigint organisation_id FK
        bigint lease_id FK
        bigint schedule_id FK
        bigint amount
        decimal fee_rate
        date calculated_date
        tinyint days_overdue
        varchar status
        bigint waived_by FK
        text waiver_reason
        datetime created_at
    }

    reminders {
        bigint id PK
        bigint organisation_id FK
        bigint lease_id FK
        bigint schedule_id FK
        tinyint reminder_level
        varchar channel
        varchar status
        bigint amount_due
        date due_date
        datetime sent_at
        bigint notification_log_id FK
        datetime created_at
    }

    owner_statements {
        bigint id PK
        bigint organisation_id FK
        char uuid
        varchar statement_number
        bigint owner_id FK
        date period_start
        date period_end
        bigint gross_rent_amount
        bigint charges_amount
        bigint management_fees_amount
        bigint tax_withholding_amount
        bigint net_amount
        varchar status
        bigint document_id FK
        datetime generated_at
        datetime sent_at
        datetime paid_at
        bigint payment_reference_id FK
        datetime created_at
    }

    owner_statement_lines {
        bigint id PK
        bigint statement_id FK
        bigint lease_id FK
        bigint unit_id FK
        smallint period_year
        tinyint period_month
        varchar line_type
        varchar description
        bigint gross_amount
        bigint fee_amount
        bigint tax_amount
        bigint net_amount
        datetime created_at
    }

    refunds {
        bigint id PK
        bigint organisation_id FK
        bigint original_payment_id FK
        bigint amount
        varchar reason
        varchar channel
        varchar status
        datetime initiated_at
        bigint initiated_by FK
        bigint approved_by FK
        datetime approved_at
        varchar external_reference
        datetime processed_at
        datetime created_at
    }

    payments ||--o{ payment_transactions : "processed via"
    payments ||--o| receipts : "generates"
    lease_schedules ||--o{ payments : "settled by"
    lease_schedules ||--o{ late_fees : "generates"
    lease_schedules ||--o{ reminders : "triggers"
    owner_statements ||--o{ owner_statement_lines : "contains"
    payments ||--o{ refunds : "refunded by"
```

### ERD 5 — Comptabilité

```mermaid
erDiagram
    chart_of_accounts {
        bigint id PK
        bigint organisation_id FK
        varchar account_code
        varchar account_name
        varchar account_type
        varchar parent_code
        tinyint is_active
        tinyint is_system
        datetime created_at
    }

    fiscal_periods {
        bigint id PK
        bigint organisation_id FK
        smallint year
        tinyint month
        varchar status
        datetime opened_at
        datetime closed_at
        bigint closed_by FK
        datetime created_at
    }

    journal_entries {
        bigint id PK
        bigint organisation_id FK
        varchar entry_number
        bigint fiscal_period_id FK
        date entry_date
        varchar entry_type
        varchar description
        varchar reference
        varchar source_module
        bigint source_id
        varchar status
        bigint created_by FK
        bigint validated_by FK
        datetime validated_at
        datetime created_at
    }

    journal_entry_lines {
        bigint id PK
        bigint entry_id FK
        bigint account_id FK
        varchar description
        bigint debit_amount
        bigint credit_amount
        datetime created_at
    }

    tax_declarations {
        bigint id PK
        bigint organisation_id FK
        bigint fiscal_period_id FK
        varchar declaration_type
        smallint period_year
        tinyint period_month
        bigint taxable_base
        decimal tax_rate
        bigint tax_amount
        bigint tax_paid
        varchar status
        date due_date
        datetime submitted_at
        bigint document_id FK
        datetime created_at
    }

    fiscal_periods ||--o{ journal_entries : "contains"
    journal_entries ||--o{ journal_entry_lines : "has"
    chart_of_accounts ||--o{ journal_entry_lines : "used in"
    fiscal_periods ||--o{ tax_declarations : "generates"
```

### ERD 6 — Maintenance & Sales

```mermaid
erDiagram
    service_providers {
        bigint id PK
        bigint organisation_id FK
        bigint party_id FK
        varchar company_name
        varchar contact_name
        varchar phone
        varchar email
        varchar status
        decimal avg_rating
        int total_interventions
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    work_orders {
        bigint id PK
        bigint organisation_id FK
        char uuid
        varchar reference_number
        bigint property_id FK
        bigint unit_id FK
        bigint lease_id FK
        bigint reported_by_user FK
        bigint reported_by_tenant FK
        varchar type
        varchar priority
        varchar status
        text description
        json photos
        bigint assigned_provider_id FK
        bigint assigned_by FK
        datetime assigned_at
        datetime scheduled_at
        datetime started_at
        datetime completed_at
        tinyint is_charge_recoverable
        bigint imputed_to_party FK
        bigint created_by FK
        datetime created_at
        datetime updated_at
    }

    work_order_quotes {
        bigint id PK
        bigint organisation_id FK
        bigint work_order_id FK
        bigint provider_id FK
        varchar status
        bigint amount_ht
        decimal vat_rate
        bigint vat_amount
        bigint amount_ttc
        date valid_until
        bigint document_id FK
        bigint approved_by FK
        datetime approved_at
        text rejection_reason
        datetime created_at
    }

    work_order_quote_lines {
        bigint id PK
        bigint quote_id FK
        varchar description
        decimal quantity
        varchar unit
        bigint unit_price_ht
        bigint total_ht
        datetime created_at
    }

    work_order_invoices {
        bigint id PK
        bigint organisation_id FK
        bigint work_order_id FK
        bigint provider_id FK
        varchar invoice_number
        date invoice_date
        bigint amount_ht
        bigint vat_amount
        bigint amount_ttc
        varchar status
        bigint document_id FK
        bigint validated_by FK
        datetime validated_at
        bigint paid_via_payment_id FK
        datetime created_at
    }

    sale_mandates {
        bigint id PK
        bigint organisation_id FK
        bigint property_id FK
        bigint seller_id FK
        bigint manager_id FK
        varchar status
        bigint asking_price
        decimal commission_rate
        date start_date
        date end_date
        bigint document_id FK
        datetime created_at
        datetime updated_at
    }

    purchase_offers {
        bigint id PK
        bigint organisation_id FK
        bigint property_id FK
        bigint buyer_id FK
        bigint sale_mandate_id FK
        bigint offered_price
        date offer_date
        date offer_expiry
        varchar status
        text conditions
        bigint document_id FK
        datetime created_at
    }

    sale_agreements {
        bigint id PK
        bigint organisation_id FK
        bigint purchase_offer_id FK
        bigint agreed_price
        date signature_date
        date completion_deadline
        bigint deposit_amount
        varchar status
        bigint notary_party_id FK
        bigint document_id FK
        datetime created_at
    }

    service_providers ||--o{ work_orders : "assigned to"
    work_orders ||--o{ work_order_quotes : "has"
    work_order_quotes ||--o{ work_order_quote_lines : "contains"
    work_orders ||--o| work_order_invoices : "billed by"
    sale_mandates ||--o{ purchase_offers : "receives"
    purchase_offers ||--o| sale_agreements : "leads to"
```

### ERD 7 — CRM, Documents, Notifications, IA & Audit

```mermaid
erDiagram
    leads {
        bigint id PK
        bigint organisation_id FK
        char uuid
        varchar first_name
        varchar last_name
        varchar email
        varchar phone
        varchar type
        varchar status
        varchar heat_score
        bigint assigned_to FK
        bigint source_id FK
        text search_criteria
        bigint converted_party_id FK
        datetime converted_at
        datetime last_activity_at
        bigint created_by FK
        datetime created_at
        datetime updated_at
        datetime deleted_at
    }

    lead_activities {
        bigint id PK
        bigint organisation_id FK
        bigint lead_id FK
        varchar activity_type
        text notes
        datetime activity_date
        bigint performed_by FK
        datetime created_at
    }

    documents {
        bigint id PK
        bigint organisation_id FK
        char uuid
        varchar entity_type
        bigint entity_id
        bigint folder_id FK
        varchar document_type
        varchar original_name
        varchar storage_path
        varchar cdn_url
        bigint file_size
        varchar mime_type
        varchar checksum
        tinyint is_generated
        tinyint is_signed
        tinyint is_encrypted
        bigint uploaded_by FK
        datetime created_at
        datetime deleted_at
    }

    notification_logs {
        bigint id PK
        bigint organisation_id FK
        bigint recipient_user_id FK
        bigint recipient_party_id FK
        varchar channel
        varchar template_code
        varchar recipient_address
        text subject
        text body
        varchar status
        tinyint attempt_count
        datetime sent_at
        datetime delivered_at
        datetime failed_at
        text error_message
        json metadata
        datetime created_at
    }

    solvency_scores {
        bigint id PK
        bigint organisation_id FK
        bigint party_id FK
        bigint lease_id FK
        varchar score_grade
        tinyint score_value
        decimal rent_to_income_ratio
        varchar employment_stability
        tinyint docs_complete
        text score_details
        tinyint is_overridden
        varchar override_grade
        text override_reason
        bigint overridden_by FK
        datetime overridden_at
        datetime calculated_at
        datetime created_at
    }

    audit_log {
        bigint id PK
        bigint organisation_id FK
        bigint user_id FK
        varchar action
        varchar entity_type
        bigint entity_id
        varchar ip_address
        varchar user_agent
        json old_values
        json new_values
        json context
        datetime created_at
    }

    data_change_log {
        bigint id PK
        bigint organisation_id FK
        varchar table_name
        bigint record_id
        varchar operation
        json old_data
        json new_data
        bigint changed_by FK
        datetime changed_at
    }

    leads ||--o{ lead_activities : "has"
    documents ||--o{ document_signature_requests : "signed via"
    solvency_scores }o--|| parties : "scored for"
    audit_log }o--|| organisations : "scoped to"
```

---

## 4. MODÈLE LOGIQUE — RELATIONS INTER-DOMAINES

### 4.1 Table de jointure des relations principales

| Entité Source | Cardinalité | Entité Cible | Clé de jointure | Contrainte |
|---|---|---|---|---|
| `organisations` | 1 → N | `users` | `users.organisation_id` | CASCADE DELETE impossible — désactiver uniquement |
| `organisations` | 1 → 1 | `organisation_subscriptions` | `organisation_subscriptions.organisation_id` | |
| `properties` | 1 → N | `property_units` | `property_units.property_id` | CASCADE DELETE si brouillon seulement |
| `property_units` | 1 → N | `leases` | `leases.unit_id` | RESTRICT — unité avec bail actif |
| `parties` | 1 → N | `leases` (tenant) | `leases.tenant_id` | RESTRICT |
| `parties` | 1 → N | `leases` (owner) | `leases.owner_id` | RESTRICT |
| `leases` | 1 → N | `lease_schedules` | `lease_schedules.lease_id` | CASCADE DELETE si BROUILLON |
| `lease_schedules` | 1 → N | `payments` | `payments.schedule_id` | RESTRICT |
| `payments` | 1 → N | `payment_transactions` | `payment_transactions.payment_id` | CASCADE |
| `payments` | 1 → 1 | `receipts` | `receipts.payment_id` | RESTRICT |
| `leases` | 1 → 1 | `security_deposits` | `security_deposits.lease_id` | |
| `work_orders` | 1 → N | `work_order_quotes` | `work_order_quotes.work_order_id` | |
| `documents` | N → 1 | toutes entités | `entity_type + entity_id` | Polymorphique |

### 4.2 Associations polymorphiques

Trois tables utilisent une relation polymorphique via `(entity_type, entity_id)` :

| Table | Entités référencées |
|---|---|
| `documents` | `lease`, `property`, `party`, `work_order`, `sale_agreement`, `receipt`, `owner_statement` |
| `audit_log` | Toutes les entités |
| `data_change_log` | Toutes les tables |

---

*Partie 2 : Modèle Physique complet → voir DATABASE_DESIGN_P2.md*
