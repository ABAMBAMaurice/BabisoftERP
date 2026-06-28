# KILIE IMMO — Architecture SaaS de Gestion Immobilière
## Côte d'Ivoire & Afrique de l'Ouest
### Document d'Architecture Technique — v1.0 — Juin 2026

---

## RÉSUMÉ EXÉCUTIF

KILIE IMMO est une plateforme SaaS B2B multi-tenant de gestion immobilière conçue pour le marché ivoirien et ouest-africain. L'architecture retenue est un **Monolithe Modulaire** organisé autour de **12 Bounded Contexts** distincts, appliquant les principes **DDD (Domain-Driven Design)**, **Architecture Hexagonale (Ports & Adapters)**, et **Event-Driven Architecture** pour assurer la séparation des préoccupations et permettre une extraction progressive vers les microservices.

La spécificité africaine impose : Mobile Money comme canal de paiement principal (MTN MoMo, Orange Money, Wave via CinetPay), architecture PWA mobile-first, gestion de la complexité foncière ivoirienne (Titre Foncier, ACD, AV), et devise XOF.

---

## 1. ANALYSE DU BESOIN

### 1.1 Contexte Marché — Côte d'Ivoire

| Indicateur | Valeur |
|---|---|
| Population Abidjan | ~6 millions d'habitants |
| Croissance urbaine annuelle | ~4% |
| Pénétration Mobile Money | 85% des adultes |
| Bancarisation | 30–40% de la population |
| Internet (mobile-first) | 90%+ via smartphone |
| Devise | XOF / FCFA (valeur entière) |
| TVA loyers commerciaux | 18% |

**Tensions du marché :**
- Gestion immobilière encore très manuelle (cahiers, espèces, SMS informels)
- Forte diaspora investissant à distance (France, USA, Gabon) sans visibilité
- Croissance du parc locatif formel non accompagnée d'outils professionnels
- Complexité foncière : coexistence Titre Foncier, ACD, ACP, Attestation Villageoise
- Absence d'outils de scoring locataire et de scoring solvabilité

### 1.2 Acteurs du Système (Personas)

| Acteur | Type | Rôle Métier |
|---|---|---|
| Agence Immobilière | Tenant SaaS principal | Gestion mandats, portefeuille clients, commissions |
| Propriétaire Bailleur | Utilisateur portail | Suivi patrimoine, revenus, décisions |
| Gestionnaire de Patrimoine | Utilisateur interne | Multi-propriétaires, procurations, consolidation |
| Locataire | Utilisateur portail | Paiements, documents, demandes, communication |
| Acheteur | Utilisateur portail vente | Recherche, visites, suivi offres |
| Syndic de Copropriété | Tenant SaaS | Charges, travaux, assemblées générales |
| Comptable | Utilisateur interne | Rapprochement, TVA, déclarations fiscales |
| Prestataire Maintenance | Utilisateur externe limité | Réception ordres, devis, facturation |
| Administrateur SaaS | Opérateur plateforme | Onboarding tenants, facturation, support |

### 1.3 Contraintes Spécifiques Côte d'Ivoire

1. **Mobile Money obligatoire** : MTN MoMo, Orange Money, Wave, Moov Money via agrégateur CinetPay
2. **Devise XOF** : entiers, règles d'arrondi UEMOA
3. **Fiscalité** : TVA 18% (baux commerciaux), Contribution Foncière, retenue à la source (20%)
4. **Documents fonciers** : Titre Foncier (TF), ACD, ACP, Attestation Villageoise, Permis de Construire
5. **Baux** : bail d'habitation vs bail commercial — régimes légaux différents
6. **Adressage** : commune / quartier / ilot / GPS (pas toujours de numéro de rue)
7. **Connectivité instable** : PWA offline-capable, chargement progressif
8. **Conformité** : Loi n° 2013-450 sur la protection des données personnelles (CI)
9. **Langue** : Français obligatoire, interface RTL non requise

### 1.4 Besoins Fonctionnels par Phase

**Phase 1 — MVP :**
- Catalogue biens (photos, GPS, caractéristiques, statut)
- Gestion parties prenantes (locataires, propriétaires, KYC)
- Cycle de vie des baux (création → signature → activation → résiliation)
- Collecte loyers Mobile Money + quittancement automatique
- Gestion documentaire (contrats, quittances, états des lieux)
- Tableaux de bord agence & propriétaire
- Notifications multi-canaux (SMS, email, WhatsApp)
- Multi-tenant avec contrôle des accès par rôle

**Phase 2 :**
- Module vente immobilière (pipeline, compromis, acte)
- Gestion de copropriété (tantièmes, charges, AG)
- Portail locataire (self-service)
- Gestion maintenance et ordres d'intervention
- Comptabilité avancée et reporting fiscal ivoirien
- Application mobile native

**Phase 3 — Strategic :**
- Marketplace d'annonces immobilières
- Score de solvabilité locataire (IA)
- Tarification dynamique (IA)
- API publique partenaires (notaires, banques, cadastre DGF)

### 1.5 Exigences Non-Fonctionnelles (NFR)

| NFR | Cible |
|---|---|
| Disponibilité | 99.9% — SLA (< 8.7h d'indisponibilité/an) |
| Latence API | < 500ms P95 depuis Abidjan |
| Throughput | 500 req/s en charge nominale |
| Scalabilité horizontale | 10 000 biens, 100 000 utilisateurs / tenant |
| Sécurité | OWASP Top 10, TLS 1.3, chiffrement AES-256 at-rest |
| Mobile Performance | Lighthouse PWA > 90, First Contentful Paint < 3s (3G) |
| Multi-tenancy | Isolation data stricte par organisation |
| Audit Trail | Immuable — toutes opérations métier |
| RTO | < 4 heures |
| RPO | < 1 heure |

---

## 2. MODULES IDENTIFIÉS (15 modules)

| # | Module | Responsabilité | Domaine | Phase |
|---|---|---|---|---|
| 1 | **Property** | Catalogue, caractéristiques, statut, localisation | Core | MVP |
| 2 | **Party** | Personnes physiques/morales, KYC, dossiers | Core | MVP |
| 3 | **Leasing** | Baux, échéanciers, révisions, états des lieux | Core | MVP |
| 4 | **Financial** | Appels de fonds, paiements, quittances, comptabilité | Core | MVP |
| 5 | **Document** | GED, génération PDF, signature électronique | Supporting | MVP |
| 6 | **Notification** | SMS, email, WhatsApp, push, planification | Supporting | MVP |
| 7 | **IAM** | Utilisateurs, rôles, organisations, MFA | Generic | MVP |
| 8 | **Subscription** | Plans SaaS, facturation, limites d'usage | Generic | MVP |
| 9 | **Integration** | Adaptateurs CinetPay, SMS, email, cadastre | Generic | MVP |
| 10 | **Maintenance** | Ordres d'intervention, devis, prestataires | Supporting | V2 |
| 11 | **Sales** | Pipeline vente, offres, compromis, actes | Core | V2 |
| 12 | **Condominium** | Copropriété, tantièmes, charges, AG | Supporting | V2 |
| 13 | **TenantPortal** | Self-service locataire | Supporting | V2 |
| 14 | **OwnerPortal** | Self-service propriétaire | Supporting | V2 |
| 15 | **Analytics** | Reporting BI, tableaux de bord, export | Generic | V2 |

---

## 3. BOUNDED CONTEXTS (DDD)

### 3.1 Context Map

```mermaid
graph LR
    subgraph CoreDomain["DOMAINE PRINCIPAL (Core Domain)"]
        PROP["Property\nContext"]
        LEASE["Leasing\nContext"]
        FIN["Financial\nContext"]
        SALES["Sales\nContext"]
    end

    subgraph SupportingDomain["DOMAINES SUPPORT (Supporting Domain)"]
        PARTY["Party\nContext"]
        MAINT["Maintenance\nContext"]
        CONDO["Condominium\nContext"]
        DOC["Document\nContext"]
        NOTIF["Notification\nContext"]
    end

    subgraph GenericDomain["DOMAINES GÉNÉRIQUES (Generic Domain)"]
        IAM["IAM\nContext"]
        SUB["Subscription\nContext"]
        ANA["Analytics\nContext"]
        INTEG["Integration\nContext"]
    end

    PROP -->|"Upstream (Published Language)"| LEASE
    PROP -->|"Upstream (Published Language)"| SALES
    PROP -->|"Upstream (Published Language)"| MAINT
    PROP -->|"Upstream (Published Language)"| CONDO
    PARTY -->|"Upstream (Customer/Supplier)"| LEASE
    PARTY -->|"Upstream (Customer/Supplier)"| SALES
    LEASE -->|"Upstream (Domain Events)"| FIN
    LEASE -->|"Triggers"| DOC
    LEASE -->|"Triggers"| NOTIF
    FIN -->|"Triggers"| NOTIF
    FIN -->|"Triggers"| DOC
    FIN -->|"ACL"| INTEG
    MAINT -->|"Triggers"| NOTIF
    MAINT -->|"Triggers"| DOC
    SALES -->|"Triggers"| DOC
    SALES -->|"Triggers"| NOTIF
    IAM -->|"Shared Kernel"| PROP
    IAM -->|"Shared Kernel"| LEASE
    IAM -->|"Shared Kernel"| FIN
    IAM -->|"Shared Kernel"| PARTY
    SUB -->|"Conformist"| IAM
    ANA -->|"Downstream - ACL"| PROP
    ANA -->|"Downstream - ACL"| LEASE
    ANA -->|"Downstream - ACL"| FIN
    INTEG -->|"ACL"| NOTIF
```

### 3.2 Relations entre Contextes

| Relation | Contexte Upstream | Contexte Downstream | Type |
|---|---|---|---|
| Property → Leasing | Property | Leasing | Published Language |
| Property → Sales | Property | Sales | Published Language |
| Party → Leasing | Party | Leasing | Customer/Supplier |
| Leasing → Financial | Leasing | Financial | Domain Events |
| Leasing → Document | Leasing | Document | Domain Events |
| Financial → Integration | Financial | Integration (CinetPay) | ACL |
| IAM → All | IAM | Tous | Shared Kernel |
| Analytics → Core | Core Domains | Analytics | ACL (Read Model) |

**Légende des relations :**
- **Published Language** : Contrat API explicite, stable
- **Customer/Supplier** : Négociation entre équipes
- **ACL (Anti-Corruption Layer)** : Traduction de modèles entre contextes
- **Shared Kernel** : Code partagé, évolue consensuellement
- **Domain Events** : Communication asynchrone par événements

---

## 4. DOMAINES MÉTIER

### 4.1 Classification Strategic (Wardley / DDD)

```
┌─────────────────────────────────────────────────────────────────┐
│                    STRATEGIC DESIGN                              │
├──────────────────┬──────────────────────┬───────────────────────┤
│   CORE DOMAIN    │  SUPPORTING DOMAIN   │   GENERIC DOMAIN      │
│  (Avantage       │  (Différenciateur    │  (Commodité —         │
│   Concurrentiel) │   secondaire)        │   Build or Buy)       │
├──────────────────┼──────────────────────┼───────────────────────┤
│ • Property       │ • Party (KYC CI)     │ • IAM                 │
│ • Leasing        │ • Maintenance        │ • Subscription        │
│ • Financial      │ • Condominium        │ • Analytics           │
│ • Sales          │ • Document           │ • Notification        │
│                  │ • TenantPortal       │ • Integration         │
│                  │ • OwnerPortal        │                       │
├──────────────────┼──────────────────────┼───────────────────────┤
│  → Développer    │  → Développer avec   │  → Acheter ou Open    │
│    en interne    │    soin              │    Source (Keycloak,  │
│    (DDD complet) │    (DDD léger)       │    Stripe, etc.)      │
└──────────────────┴──────────────────────┴───────────────────────┘
```

### 4.2 Ubiquitous Language par Domaine

| Terme Métier | Contexte | Définition CI |
|---|---|---|
| **Bien** | Property | Actif immobilier (villa, appart, local commercial, terrain) |
| **Lot** | Property / Condo | Unité dans un immeuble en copropriété |
| **Titre Foncier (TF)** | Property | Document officiel de propriété, délivré par la DGF |
| **Attestation Villageoise** | Property | Titre informel reconnu dans les zones villageoises |
| **Bail** | Leasing | Contrat de location (habitation ou commercial) |
| **Loyer** | Leasing / Financial | Montant mensuel dû par le locataire |
| **Dépôt de garantie** | Leasing | Caution versée à l'entrée (1–2 mois) |
| **Quittance** | Financial | Reçu officiel de paiement de loyer |
| **Appel de fonds** | Financial | Facturation périodique émise vers le locataire |
| **Régularisation** | Financial | Ajustement annuel des charges |
| **État des lieux** | Leasing | Inventaire contradictoire entrant/sortant |
| **Mandat** | Property / Sales | Autorisation donnée par propriétaire à agence |
| **Compromis** | Sales | Avant-contrat de vente |
| **Acte authentique** | Sales | Acte notarié définitif de vente |
| **Tantième** | Condominium | Quote-part de charges en copropriété |
| **OI** | Maintenance | Ordre d'Intervention |

---

## 5. AGRÉGATS DDD

### 5.1 Property Context — Agrégats

```mermaid
classDiagram
    class Bien {
        +BienId id
        +OrganisationId tenantId
        +TypeBien type
        +StatutBien statut
        +AdresseCi adresse
        +Surface surface
        +TitreFoncier titreFoncier
        +Mandat mandat
        +publier() void
        +occuper() void
        +liberer() void
        +suspendre(raison) void
        +ajouterUnite(unite) void
    }

    class Unite {
        +UniteId id
        +BienId bienParentId
        +TypeUnite type
        +Surface surface
        +Etage etage
        +StatutOccupation statut
    }

    class AdresseCi {
        +String commune
        +String quartier
        +String ilot
        +String descriptionAcces
        +GpsCoord coordonnees
    }

    class TitreFoncier {
        +String numero
        +TypeTitre typeTitre
        +String proprietaireInscrit
        +Date dateDelivrance
    }

    class Mandat {
        +MandatId id
        +TypeMandat type
        +PartyId mandant
        +Date dateDebut
        +Date dateFin
        +Decimal tauxCommission
    }

    class Photo {
        +PhotoId id
        +String url
        +String altText
        +Integer ordre
        +Boolean principale
    }

    Bien "1" *-- "0..*" Unite : contient
    Bien "1" *-- "1" AdresseCi : est situé à
    Bien "1" *-- "0..1" TitreFoncier : possède
    Bien "1" *-- "0..1" Mandat : est couvert par
    Bien "1" *-- "0..*" Photo : a
```

### 5.2 Leasing Context — Agrégats

```mermaid
classDiagram
    class Bail {
        +BailId id
        +OrganisationId tenantId
        +UniteId uniteId
        +PartyId locataireId
        +PartyId proprietaireId
        +TypeBail type
        +StatutBail statut
        +PeriodeBail periode
        +MontantLoyer loyer
        +DepotGarantie depot
        +signer() void
        +activer() void
        +genererEcheancier() void
        +proposerRevision(params) void
        +resilier(motif, dateEffet) void
        +expirer() void
    }

    class EcheancierItem {
        +EcheancierItemId id
        +BailId bailId
        +Date dateEcheance
        +Decimal montantLoyer
        +Decimal montantCharges
        +Decimal montantTotal
        +StatutEcheance statut
    }

    class RevisionLoyer {
        +RevisionId id
        +BailId bailId
        +Date dateRevision
        +Decimal ancienLoyer
        +Decimal nouveauLoyer
        +Decimal tauxRevision
        +String baseRevision
    }

    class EtatDesLieux {
        +EtatDesLieuxId id
        +BailId bailId
        +TypeEtat type
        +Date dateRealisation
        +String observations
        +StatutEtat statut
        +signer() void
    }

    class Clause {
        +ClauseId id
        +String libelle
        +String contenu
        +TypeClause type
    }

    class PeriodeBail {
        +Date dateDebut
        +Date dateFin
        +Integer dureeMois
        +Integer preavisJours
    }

    class MontantLoyer {
        +Decimal montantHT
        +Boolean tvaApplicable
        +Decimal taux
        +Decimal montantTTC
        +String devise
    }

    Bail "1" *-- "0..*" EcheancierItem : génère
    Bail "1" *-- "0..*" RevisionLoyer : subit
    Bail "1" *-- "0..2" EtatDesLieux : fait l'objet de
    Bail "1" *-- "0..*" Clause : contient
    Bail "1" *-- "1" PeriodeBail : a une durée
    Bail "1" *-- "1" MontantLoyer : définit
```

### 5.3 Financial Context — Agrégats

```mermaid
classDiagram
    class AppelDeFonds {
        +AppelId id
        +OrganisationId tenantId
        +BailId bailId
        +EcheancierItemId echeanceId
        +Date dateEmission
        +Date dateEcheance
        +Decimal montantLoyer
        +Decimal montantCharges
        +Decimal penalites
        +Decimal montantTotal
        +StatutAppel statut
        +emettre() void
        +envoyer() void
        +marquerPayee() void
        +appliquerPenalite(taux) void
    }

    class Paiement {
        +PaiementId id
        +AppelId appelId
        +BailId bailId
        +Date datePaiement
        +Decimal montant
        +CanalPaiement canal
        +ReferencePaiement reference
        +StatutPaiement statut
        +confirmer() void
        +rejeter(motif) void
        +rembourser(montant) void
    }

    class Quittance {
        +QuittanceId id
        +PaiementId paiementId
        +String numero
        +Date dateEmission
        +Decimal montant
        +String locataireNom
        +String bienAdresse
        +emettre() void
        +envoyer() void
    }

    class RelanceLoyer {
        +RelanceId id
        +AppelId appelId
        +Integer numeroRelance
        +Date dateRelance
        +CanalRelance canal
        +StatutRelance statut
    }

    class RelevePropriétaire {
        +ReleveId id
        +PartyId proprietaireId
        +Date periodeDebut
        +Date periodeFin
        +Decimal totalLoyers
        +Decimal totalCharges
        +Decimal totalHonoraires
        +Decimal netVerse
        +generer() void
    }

    class CanalPaiement {
        <<enumeration>>
        MTN_MOMO
        ORANGE_MONEY
        WAVE
        MOOV_MONEY
        VIREMENT_BANCAIRE
        ESPECES
        CHEQUE
    }

    AppelDeFonds "1" -- "0..*" Paiement : est réglé par
    AppelDeFonds "1" -- "0..*" RelanceLoyer : génère
    Paiement "1" -- "0..1" Quittance : donne lieu à
```

### 5.4 Party Context — Agrégats

```mermaid
classDiagram
    class Partie {
        +PartyId id
        +OrganisationId tenantId
        +TypePartie type
        +StatutKYC statutKyc
        +validerKYC() void
        +archiverDossier() void
    }

    class PersonnePhysique {
        +IdentiteCI identite
        +String telephone
        +String email
        +String profession
        +Decimal revenusMensuels
        +String employeur
        +StatutMarital statut
    }

    class PersonneMorale {
        +String raisonSociale
        +String formeJuridique
        +String rccm
        +String cif
        +String telephone
        +String email
        +String representantLegal
    }

    class DocumentIdentite {
        +String type
        +String numero
        +Date dateExpiration
        +String urlDocument
        +Boolean verifie
    }

    class IdentiteCI {
        +String nom
        +String prenoms
        +Date dateNaissance
        +String lieuNaissance
        +String nationalite
        +String numeroCni
    }

    class DossierLocataire {
        +DossierLocataireId id
        +PartyId locataireId
        +Decimal revenusMensuels
        +Boolean salarie
        +ScoreSolvabilite score
        +StatutDossier statut
        +completer() void
        +approuver() void
        +rejeter(motif) void
    }

    Partie <|-- PersonnePhysique : est
    Partie <|-- PersonneMorale : est
    PersonnePhysique "1" *-- "1" IdentiteCI : a
    Partie "1" *-- "0..*" DocumentIdentite : produit
    PersonnePhysique "1" *-- "0..1" DossierLocataire : a un
```

### 5.5 Maintenance Context — Agrégats

```mermaid
classDiagram
    class OrdreIntervention {
        +OIId id
        +BienId bienId
        +BailId bailId
        +TypeIntervention type
        +PrioriteOI priorite
        +StatutOI statut
        +String description
        +PartyId prestataire
        +Date dateDemande
        +Date dateIntervention
        +accepter() void
        +assigner(prestataire) void
        +demarrer() void
        +terminer() void
        +clore() void
    }

    class Devis {
        +DevisId id
        +OIId ordreId
        +Date dateDevis
        +Decimal montantHT
        +Decimal tva
        +Decimal montantTTC
        +StatutDevis statut
        +approuver() void
        +rejeter(motif) void
    }

    class InterventionItem {
        +String description
        +Decimal quantite
        +Decimal prixUnitaire
        +String unite
    }

    class FacturePrestataire {
        +FactureId id
        +OIId ordreId
        +String numero
        +Date dateFacture
        +Decimal montantTTC
        +StatutFacture statut
        +valider() void
        +payer() void
    }

    OrdreIntervention "1" *-- "0..*" Devis : fait l'objet de
    OrdreIntervention "1" *-- "0..1" FacturePrestataire : génère
    Devis "1" *-- "1..*" InterventionItem : contient
```

---

## 6. ENTITÉS MÉTIER

### 6.1 Vue Relationnelle des Entités Principales

```mermaid
erDiagram
    ORGANISATIONS {
        uuid id PK
        string nom
        string type
        string plan_saas
        timestamp created_at
    }

    BIENS {
        uuid id PK
        uuid organisation_id FK
        uuid proprietaire_id FK
        string type
        string statut
        string commune
        string quartier
        string titre_foncier
        decimal surface
        point gps_coordinates
    }

    UNITES {
        uuid id PK
        uuid bien_id FK
        string type
        decimal surface
        int etage
        string statut_occupation
    }

    PARTIES {
        uuid id PK
        uuid organisation_id FK
        string type
        string nom
        string telephone
        string email
        string statut_kyc
    }

    BAUX {
        uuid id PK
        uuid organisation_id FK
        uuid unite_id FK
        uuid locataire_id FK
        uuid proprietaire_id FK
        string type_bail
        string statut
        date date_debut
        date date_fin
        decimal loyer_ht
        boolean tva_applicable
        decimal depot_garantie
    }

    ECHEANCIER {
        uuid id PK
        uuid bail_id FK
        date date_echeance
        decimal montant_loyer
        decimal montant_charges
        decimal montant_total
        string statut
    }

    PAIEMENTS {
        uuid id PK
        uuid organisation_id FK
        uuid bail_id FK
        uuid echeance_id FK
        date date_paiement
        decimal montant
        string canal
        string reference_mobile_money
        string statut
    }

    QUITTANCES {
        uuid id PK
        uuid paiement_id FK
        string numero
        date date_emission
        decimal montant
        string url_pdf
    }

    DOCUMENTS {
        uuid id PK
        uuid organisation_id FK
        string entite_type
        uuid entite_id
        string type_document
        string nom_fichier
        string url_stockage
        timestamp created_at
    }

    ORDRES_INTERVENTION {
        uuid id PK
        uuid organisation_id FK
        uuid bien_id FK
        uuid bail_id FK
        string type
        string priorite
        string statut
        string description
        uuid prestataire_id FK
    }

    ORGANISATIONS ||--o{ BIENS : "possède"
    BIENS ||--o{ UNITES : "contient"
    UNITES ||--o{ BAUX : "fait l'objet de"
    PARTIES ||--o{ BAUX : "est locataire dans"
    BAUX ||--o{ ECHEANCIER : "génère"
    ECHEANCIER ||--o{ PAIEMENTS : "est réglé par"
    PAIEMENTS ||--o| QUITTANCES : "donne lieu à"
    BIENS ||--o{ ORDRES_INTERVENTION : "subit"
```

---

## 7. ÉVÉNEMENTS MÉTIER (Domain Events)

### 7.1 Property Context

| Événement | Déclencheur | Consommateurs |
|---|---|---|
| `BienCree` | Création bien | Analytics, Document |
| `BienPublie` | Mise en ligne | Notification, Sales |
| `BienOccupe` | Bail activé | Analytics |
| `BienLibere` | Bail résilié/expiré | Notification, Analytics |
| `BienSuspendu` | Décision gestionnaire | Notification |
| `PhotoBienTeleversee` | Upload photo | Document |
| `MandatCree` | Signature mandat | Document, Notification |

### 7.2 Party Context

| Événement | Déclencheur | Consommateurs |
|---|---|---|
| `PartieCreee` | Enregistrement | IAM, Notification |
| `DossierLocataireSoumis` | Candidature | Notification |
| `DossierLocataireApprouve` | Validation KYC | Leasing, Notification |
| `DossierLocataireRejete` | Refus KYC | Notification |
| `KYCVerifie` | Validation documents | Leasing, Financial |

### 7.3 Leasing Context

| Événement | Déclencheur | Consommateurs |
|---|---|---|
| `BailCree` | Création bail | Document, Notification |
| `BailSigne` | Signature parties | Document, Notification |
| `BailActive` | Date début atteinte | Property (occupe bien), Financial (génère échéancier) |
| `EcheancierGenere` | Activation bail | Financial, Notification |
| `BailRevisionAppliquee` | Avenant | Financial, Document, Notification |
| `BailPreavisGiven` | Préavis locataire/proprio | Notification, Analytics |
| `BailResilié` | Résiliation | Property (libère), Financial, Notification |
| `BailExpire` | Date fin | Property (libère), Financial, Notification |
| `DepotGarantieEncaisse` | Paiement dépôt | Financial |
| `DepotGarantieRestitue` | Restitution | Financial, Notification |
| `EtatDesLieuxSigne` | Signature contradictoire | Document, Notification |

### 7.4 Financial Context

| Événement | Déclencheur | Consommateurs |
|---|---|---|
| `AppelDeFondsCree` | Génération mensuelle | Notification |
| `AppelDeFondsEnvoye` | Distribution | Notification |
| `PaiementRecu` | Confirmation Mobile Money | Leasing, Notification, Document |
| `PaiementEnRetard` | Dépassement échéance | Notification |
| `RelanceSentG1` | J+3 retard | Notification |
| `RelanceSentG2` | J+10 retard | Notification |
| `RelanceSentG3` | J+15 retard | Notification, Legal |
| `QuittanceEmise` | Paiement confirmé | Document, Notification |
| `DepenseEnregistree` | Saisie charge | Analytics |
| `RelevePropriétaireGenere` | Mensuel/Trimestriel | Document, Notification |

### 7.5 Maintenance Context

| Événement | Déclencheur | Consommateurs |
|---|---|---|
| `OICreee` | Signalement | Notification |
| `OIAssignee` | Affectation prestataire | Notification |
| `OIDemarree` | Début intervention | Notification |
| `OITerminee` | Fin intervention | Notification, Financial |
| `DevisRecu` | Soumission devis | Notification |
| `DevisApprouve` | Validation | Notification, Financial |
| `FacturePrestaireValidee` | Validation facture | Financial |

### 7.6 Sales Context

| Événement | Déclencheur | Consommateurs |
|---|---|---|
| `BienMisEnVente` | Décision propriétaire | Property, Notification |
| `VisteePlanifiee` | Prise de RDV | Notification |
| `OffreDachatRecue` | Dépôt offre | Notification |
| `OffreDachatAcceptee` | Validation | Notification, Document |
| `CompromisSigné` | Signature | Document, Notification, Financial |
| `ActeAuthentiqueSigne` | Notarisation | Property (transfert), Financial, Document |
| `CommissionEarnee` | Acte signé | Financial |

---

## 8. WORKFLOWS

### 8.1 Workflow — Mise en Location d'un Bien

```mermaid
flowchart TD
    A([Début]) --> B[Créer le bien\ndans le catalogue]
    B --> C[Téléverser photos\net documents]
    C --> D[Rédiger annonce\net fixer le loyer]
    D --> E[Publier le bien]
    E --> F{Candidature\nreçue ?}
    F -->|Non| G[Relancer la\npublication]
    G --> F
    F -->|Oui| H[Créer dossier\nlocataire]
    H --> I[Vérifier KYC et\nsolvabilité]
    I --> J{Dossier\napprouvé ?}
    J -->|Non| K[Notifier refus\nlocataire]
    K --> F
    J -->|Oui| L[Créer le bail\nbrouillon]
    L --> M[Signature\nélectronique\ndes deux parties]
    M --> N[État des lieux\nd'entrée]
    N --> O[Encaisser dépôt\nde garantie]
    O --> P[Activer le bail]
    P --> Q[Générer\nechéancier]
    Q --> R[Remettre les clés\nau locataire]
    R --> S([Bail Actif])

    style A fill:#22c55e,color:#fff
    style S fill:#22c55e,color:#fff
    style K fill:#ef4444,color:#fff
```

### 8.2 Workflow — Cycle de Vie d'un Bail (State Machine)

```mermaid
stateDiagram-v2
    [*] --> BROUILLON : Création bail

    BROUILLON --> EN_ATTENTE_SIGNATURE : Dossier complet
    BROUILLON --> ANNULE : Annulation avant signature

    EN_ATTENTE_SIGNATURE --> SIGNE : Signature des deux parties
    EN_ATTENTE_SIGNATURE --> ANNULE : Refus ou délai dépassé

    SIGNE --> ACTIF : Date de début atteinte
    SIGNE --> ANNULE : Rétractation avant entrée

    ACTIF --> EN_REVISION : Demande de révision du loyer
    EN_REVISION --> ACTIF : Révision appliquée

    ACTIF --> PREAVIS : Notification de préavis
    PREAVIS --> EN_SORTIE : Préavis validé, EDL de sortie
    EN_SORTIE --> TERMINE : EDL signé + solde apuré + dépôt restitué

    ACTIF --> CONTENTIEUX : Impayés prolongés (> 2 mois)
    CONTENTIEUX --> TERMINE : Résolution judiciaire

    TERMINE --> [*]
    ANNULE --> [*]
```

### 8.3 Workflow — Collecte de Loyer via Mobile Money

```mermaid
flowchart TD
    A([1er du mois]) --> B[Générer\nAppel de Fonds]
    B --> C[Envoyer notification\nSMS + Email + Push]
    C --> D{Paiement reçu\navant J+3 ?}

    D -->|Oui| E[Confirmer paiement\nvia webhook CinetPay]
    E --> F[Enregistrer\npaiement]
    F --> G[Générer\nquittance PDF]
    G --> H[Envoyer quittance\nau locataire]
    H --> I([Loyer Soldé])

    D -->|Non| J[Relance G1\nSMS + Email — J+3]
    J --> K{Paiement reçu\navant J+10 ?}

    K -->|Oui| E
    K -->|Non| L[Relance G2\nSMS + Email + WhatsApp — J+10]
    L --> M[Appliquer pénalité\nde retard]
    M --> N{Paiement reçu\navant J+15 ?}

    N -->|Oui| E
    N -->|Non| O[Relance G3\nCourrier + SMS — J+15]
    O --> P[Notifier propriétaire\nde l'impayé]
    P --> Q{Résolution\namiable ?}

    Q -->|Oui| E
    Q -->|Non| R[Passage en\ncontentieux]
    R --> S([Dossier Contentieux])

    style I fill:#22c55e,color:#fff
    style S fill:#ef4444,color:#fff
```

### 8.4 Workflow — Gestion d'un Ordre d'Intervention

```mermaid
flowchart TD
    A([Signalement\nlocataire/gestionnaire]) --> B[Créer OI avec\ndescription et photos]
    B --> C[Qualifier l'urgence\nPrioritaire / Normal]
    C --> D{Prioritaire ?}

    D -->|Oui| E[Notifier gestionnaire\nimmédiatement]
    D -->|Non| F[File d'attente\nstandard]
    E --> G
    F --> G[Sélectionner\nprestataire]

    G --> H[Envoyer OI\nau prestataire]
    H --> I{Devis requis ?}

    I -->|Oui| J[Prestataire soumet\ndevis]
    J --> K{Montant >\nseuil approbation ?}
    K -->|Oui| L[Approbation\npropriétaire]
    L --> M{Approuvé ?}
    M -->|Non| N[Chercher autre\nprestataire]
    N --> G
    M -->|Oui| O[Planifier intervention]
    K -->|Non| O
    I -->|Non| O

    O --> P[Réaliser\nl'intervention]
    P --> Q[Clôturer OI\navec rapport]
    Q --> R[Valider facture\nprestataire]
    R --> S[Imputer charges\nsur bail/bien]
    S --> T([OI Clôturé])

    style T fill:#22c55e,color:#fff
```

### 8.5 Workflow — Résiliation de Bail

```mermaid
flowchart TD
    A([Notification\npréavis]) --> B{Origine\npréavis ?}
    B -->|Locataire| C[Préavis locataire\n1 mois habitation\n3 mois commercial]
    B -->|Propriétaire| D[Préavis propriétaire\n3 mois habitation\n6 mois commercial]

    C --> E[Valider délai\npréavis légal]
    D --> E

    E --> F[Planifier état\ndes lieux de sortie]
    F --> G[Réaliser EDL\nde sortie contradictoire]
    G --> H{Dégâts\nconstatés ?}

    H -->|Oui| I[Chiffrer les\ndégâts locatifs]
    I --> J[Déduire du\ndépôt de garantie]
    H -->|Non| K[Restituer dépôt\nde garantie intégral]

    J --> L{Dégâts >\ndépôt ?}
    L -->|Oui| M[Facturer différence\nau locataire]
    L -->|Non| N[Restituer solde\ndépôt de garantie]

    M --> O[Clôturer le bail]
    N --> O
    K --> O

    O --> P[Libérer le bien\ndans le catalogue]
    P --> Q[Archiver\nle dossier]
    Q --> R([Bail Terminé\nBien Disponible])

    style R fill:#22c55e,color:#fff
```

### 8.6 Workflow — Vente Immobilière

```mermaid
flowchart TD
    A([Décision\nde vente]) --> B[Créer mandat\nde vente]
    B --> C[Estimer la valeur\n+ fixer prix]
    C --> D[Publier l'annonce\navec photos + docs]
    D --> E[Organiser\nles visites]
    E --> F{Offre\nreçue ?}

    F -->|Non| G[Relancer\nla prospection]
    G --> E
    F -->|Oui| H{Offre >=\nprix demandé ?}

    H -->|Non| I[Négociation\nvendeur/acheteur]
    I --> J{Accord\nde prix ?}
    J -->|Non| F
    J -->|Oui| K[Signer le\ncompromis de vente]

    H -->|Oui| K

    K --> L[Versement séquestre\nchez notaire]
    L --> M[Période de\ndue diligence\n45-90 jours]
    M --> N{Conditions\nlevées ?}

    N -->|Non| O[Résolution compromis\nremboursement séquestre]
    O --> Z([Vente Annulée])
    N -->|Oui| P[Planifier signature\nacte notarié]
    P --> Q[Signature acte\nauthentique chez notaire]
    Q --> R[Transfert titre\nfoncier à la DGF]
    R --> S[Paiement prix\net commission agence]
    S --> T([Vente Conclue])

    style T fill:#22c55e,color:#fff
    style Z fill:#ef4444,color:#fff
```

---

## 9. DÉPENDANCES ENTRE MODULES

### 9.1 Graphe de Dépendances

```mermaid
graph TD
    IAM["IAM"] -->|"auth"| PROP["Property"]
    IAM -->|"auth"| PARTY["Party"]
    IAM -->|"auth"| LEASE["Leasing"]
    IAM -->|"auth"| FIN["Financial"]
    IAM -->|"auth"| MAINT["Maintenance"]
    IAM -->|"auth"| SALES["Sales"]
    IAM -->|"auth"| CONDO["Condominium"]
    IAM -->|"auth"| DOC["Document"]

    PROP -->|"bien ref"| LEASE
    PROP -->|"bien ref"| SALES
    PROP -->|"bien ref"| MAINT
    PROP -->|"bien ref"| CONDO

    PARTY -->|"partie ref"| LEASE
    PARTY -->|"partie ref"| SALES
    PARTY -->|"partie ref"| FIN

    LEASE -->|"bail activé"| FIN
    LEASE -->|"bail créé"| DOC
    LEASE -->|"événements"| NOTIF["Notification"]

    FIN -->|"paiement"| INTEG["Integration\n(CinetPay)"]
    FIN -->|"événements"| NOTIF
    FIN -->|"documents"| DOC
    FIN -->|"données"| ANA["Analytics"]

    MAINT -->|"travaux terminés"| FIN
    MAINT -->|"événements"| NOTIF
    MAINT -->|"docs"| DOC

    SALES -->|"acte signé"| FIN
    SALES -->|"docs"| DOC
    SALES -->|"événements"| NOTIF

    CONDO -->|"charges"| FIN
    CONDO -->|"événements"| NOTIF
    CONDO -->|"docs"| DOC

    DOC -->|"envoi"| NOTIF
    NOTIF -->|"envoi"| INTEG

    SUB["Subscription"] -->|"limites"| IAM
    SUB -->|"facturation"| FIN

    ANA -.->|"lecture"| PROP
    ANA -.->|"lecture"| LEASE
    ANA -.->|"lecture"| FIN

    style IAM fill:#3b82f6,color:#fff
    style INTEG fill:#f59e0b,color:#fff
    style ANA fill:#8b5cf6,color:#fff
```

### 9.2 Matrice de Dépendances

| Module | Dépend de | Produit des événements vers |
|---|---|---|
| IAM | — | Tous les modules (auth) |
| Property | IAM | Leasing, Sales, Maintenance, Condominium |
| Party | IAM | Leasing, Sales, Financial |
| Leasing | Property, Party, IAM | Financial, Document, Notification |
| Financial | Leasing, Party, IAM | Notification, Document, Analytics, Integration |
| Maintenance | Property, Leasing, IAM | Financial, Notification, Document |
| Sales | Property, Party, IAM | Financial, Document, Notification |
| Condominium | Property, Financial, IAM | Financial, Notification, Document |
| Document | Tous les modules | Notification |
| Notification | Tous les modules | Integration (SMS/Email) |
| Integration | Financial, Notification | — (adaptateur externe) |
| Analytics | Property, Leasing, Financial | — (read-only) |
| Subscription | IAM | Financial |

---

## 10. ARCHITECTURE HEXAGONALE COMPLÈTE

### 10.1 Principe de l'Architecture Hexagonale (Ports & Adapters)

```mermaid
graph TB
    subgraph "ADAPTATEURS PRIMAIRES (Driving Side)"
        REST["REST API Controller"]
        GQL["GraphQL Resolver"]
        CLI["CLI / Scheduler"]
        WEB["Web App PWA"]
        MOB["Mobile App"]
    end

    subgraph "HEXAGONE — DOMAINE"
        subgraph "APPLICATION LAYER"
            UC["Use Cases\n(Application Services)"]
            CMD["Commands"]
            QUERY["Queries CQRS"]
            EVT_H["Event Handlers"]
        end

        subgraph "DOMAIN LAYER"
            AGG["Aggregates"]
            ENT["Entities"]
            VO["Value Objects"]
            DS["Domain Services"]
            DE["Domain Events"]
            PORT_IN["Ports IN\n(Interfaces)"]
            PORT_OUT["Ports OUT\n(Interfaces)"]
        end
    end

    subgraph "ADAPTATEURS SECONDAIRES (Driven Side)"
        PG["PostgreSQL\nRepository"]
        REDIS["Redis\nCache"]
        ES["Elasticsearch\nSearch"]
        S3["S3/MinIO\nStorage"]
        MQ["RabbitMQ\nEvent Bus"]
        SMTP["SendGrid\nEmail Adapter"]
        SMS_A["Infobip\nSMS Adapter"]
        PAY["CinetPay\nPayment Adapter"]
        PDF["PDF Generator\nAdapter"]
    end

    REST --> UC
    GQL --> UC
    CLI --> UC
    WEB --> REST
    MOB --> REST

    UC --> AGG
    UC --> DS
    UC --> PORT_OUT
    CMD --> UC
    QUERY --> UC
    EVT_H --> UC

    AGG --> DE
    AGG --> VO
    AGG --> ENT

    PORT_OUT --> PG
    PORT_OUT --> REDIS
    PORT_OUT --> ES
    PORT_OUT --> S3
    PORT_OUT --> MQ
    PORT_OUT --> SMTP
    PORT_OUT --> SMS_A
    PORT_OUT --> PAY
    PORT_OUT --> PDF

    style AGG fill:#16a34a,color:#fff
    style ENT fill:#15803d,color:#fff
    style VO fill:#166534,color:#fff
    style UC fill:#1d4ed8,color:#fff
    style PORT_IN fill:#7c3aed,color:#fff
    style PORT_OUT fill:#7c3aed,color:#fff
```

### 10.2 Architecture Hexagonale — Module Financial (Exemple Détaillé)

```mermaid
graph LR
    subgraph "DRIVING ADAPTERS"
        A1["POST /api/payments\nREST Controller"]
        A2["CinetPay Webhook\nHTTP Receiver"]
        A3["Cron Job\nAppel Mensuel"]
        A4["Event: BailActive\nHandler"]
    end

    subgraph "APPLICATION — FINANCIAL"
        subgraph "Commands"
            C1["CreateAppelDeFondsCommand"]
            C2["RecordPaymentCommand"]
            C3["IssueReceiptCommand"]
            C4["ApplyPenaltyCommand"]
        end
        subgraph "Queries"
            Q1["GetPaymentHistoryQuery"]
            Q2["GetOwnerStatementQuery"]
            Q3["GetUnpaidCallsQuery"]
        end
        subgraph "Domain"
            D1["AppelDeFonds\nAggregate"]
            D2["Paiement\nAggregate"]
            D3["Quittance\nValue Object"]
            D4["FinancialDomainService"]
        end
        subgraph "Ports OUT"
            P1["IAppelRepository"]
            P2["IPaiementRepository"]
            P3["IPaymentGateway"]
            P4["INotificationService"]
            P5["IDocumentService"]
            P6["IEventBus"]
        end
    end

    subgraph "DRIVEN ADAPTERS"
        B1["PostgreSQL\nAppelRepository"]
        B2["PostgreSQL\nPaiementRepository"]
        B3["CinetPay\nPaymentGatewayAdapter"]
        B4["NotificationModule\nAdapter"]
        B5["DocumentModule\nAdapter"]
        B6["RabbitMQ\nEventBusAdapter"]
    end

    A1 --> C2
    A2 --> C2
    A3 --> C1
    A4 --> C1

    C1 --> D1
    C2 --> D2
    C3 --> D3
    C4 --> D4

    D1 --> P1
    D2 --> P2
    D2 --> P3
    D3 --> P5
    D4 --> P4
    D1 --> P6
    D2 --> P6

    P1 --> B1
    P2 --> B2
    P3 --> B3
    P4 --> B4
    P5 --> B5
    P6 --> B6
```

### 10.3 Structure du Monolithe Modulaire

```mermaid
graph TB
    subgraph "KILIE IMMO — MODULAR MONOLITH"
        subgraph "API LAYER"
            GW["API Gateway\n(NestJS AppModule)"]
            AUTH["Auth Middleware\n(JWT + RBAC)"]
            TENANT["Tenant Resolver\n(Multi-tenancy)"]
        end

        subgraph "MODULES CORE"
            M_PROP["PropertyModule\n[Domain + App + Infra]"]
            M_PARTY["PartyModule\n[Domain + App + Infra]"]
            M_LEASE["LeasingModule\n[Domain + App + Infra]"]
            M_FIN["FinancialModule\n[Domain + App + Infra]"]
        end

        subgraph "MODULES SUPPORT"
            M_MAINT["MaintenanceModule"]
            M_SALES["SalesModule"]
            M_CONDO["CondominiumModule"]
            M_DOC["DocumentModule"]
            M_NOTIF["NotificationModule"]
        end

        subgraph "MODULES GENERIC"
            M_IAM["IAMModule"]
            M_SUB["SubscriptionModule"]
            M_ANA["AnalyticsModule"]
            M_INTEG["IntegrationModule"]
        end

        subgraph "SHARED KERNEL"
            SK_EVT["EventBus\n(In-Process → RabbitMQ)"]
            SK_TYPES["SharedTypes\n(Value Objects communs)"]
            SK_GUARD["Guards & Decorators"]
            SK_EXCEP["Exception Filters"]
        end

        subgraph "INFRASTRUCTURE LAYER"
            INF_ORM["TypeORM\n+ PostgreSQL"]
            INF_CACHE["Redis Cache"]
            INF_STORE["MinIO / S3"]
            INF_SEARCH["Elasticsearch"]
        end
    end

    GW --> AUTH
    AUTH --> TENANT
    TENANT --> M_PROP
    TENANT --> M_PARTY
    TENANT --> M_LEASE
    TENANT --> M_FIN
    TENANT --> M_MAINT
    TENANT --> M_SALES
    TENANT --> M_CONDO
    TENANT --> M_DOC
    TENANT --> M_NOTIF
    TENANT --> M_IAM
    TENANT --> M_SUB
    TENANT --> M_ANA

    M_PROP --> SK_EVT
    M_LEASE --> SK_EVT
    M_FIN --> SK_EVT
    M_MAINT --> SK_EVT
    M_SALES --> SK_EVT

    SK_EVT --> M_NOTIF
    SK_EVT --> M_DOC
    SK_EVT --> M_ANA

    M_PROP --> INF_ORM
    M_LEASE --> INF_ORM
    M_FIN --> INF_ORM
    M_FIN --> INF_CACHE
    M_DOC --> INF_STORE
    M_ANA --> INF_SEARCH
    M_NOTIF --> M_INTEG
```

---

## DIAGRAMMES DE SÉQUENCE

### Séquence 1 — Création et Activation d'un Bail

```mermaid
sequenceDiagram
    participant GE as Gestionnaire
    participant API as API Gateway
    participant LEASE as LeasingModule
    participant PROP as PropertyModule
    participant PARTY as PartyModule
    participant FIN as FinancialModule
    participant DOC as DocumentModule
    participant NOTIF as NotificationModule

    GE->>API: POST /leases (uniteId, locataireId, params)
    API->>LEASE: CreateLeaseCommand
    LEASE->>PROP: Vérifier statut unité (disponible ?)
    PROP-->>LEASE: Unité disponible
    LEASE->>PARTY: Vérifier KYC locataire (approuvé ?)
    PARTY-->>LEASE: KYC approuvé
    LEASE->>LEASE: Créer Bail (statut: BROUILLON)
    LEASE-->>API: BailId + bail brouillon
    API-->>GE: 201 Created — bail créé

    Note over GE,NOTIF: Processus de signature

    GE->>API: POST /leases/{id}/send-for-signature
    API->>LEASE: SendForSignatureCommand
    LEASE->>DOC: Générer PDF bail
    DOC-->>LEASE: URL document bail
    LEASE->>NOTIF: Envoyer lien signature (locataire + propriétaire)
    NOTIF-->>GE: SMS + Email envoyés

    Note over GE,NOTIF: Signatures des deux parties

    GE->>API: PUT /leases/{id}/sign (signatureToken)
    API->>LEASE: RecordSignatureCommand
    LEASE->>LEASE: Mise à jour statut → SIGNE
    LEASE->>NOTIF: Notifier signature complète

    Note over GE,NOTIF: Activation à la date de début

    LEASE->>LEASE: BailActive (scheduler ou date trigger)
    LEASE->>PROP: OccuperUnite(uniteId)
    PROP->>PROP: Statut unité → OCCUPEE
    LEASE->>FIN: GenererEcheancier(bailId)
    FIN->>FIN: Créer 12+ items d'échéancier
    FIN->>NOTIF: Notifier bail actif + échéancier
    NOTIF-->>GE: Email confirmation activation
    NOTIF-->>GE: SMS locataire
```

### Séquence 2 — Paiement de Loyer via Mobile Money

```mermaid
sequenceDiagram
    participant LOC as Locataire
    participant APP as App Mobile/Web
    participant API as API Gateway
    participant FIN as FinancialModule
    participant INTEG as IntegrationModule
    participant CP as CinetPay API
    participant MTN as MTN Mobile Money
    participant NOTIF as NotificationModule
    participant DOC as DocumentModule

    Note over LOC,DOC: Initiation du paiement

    LOC->>APP: Consulter appel de loyer
    APP->>API: GET /payment-calls/{id}
    API->>FIN: GetPaymentCallQuery
    FIN-->>APP: Détail appel + montant
    APP-->>LOC: Afficher montant à payer

    LOC->>APP: Choisir MTN Mobile Money
    APP->>API: POST /payments/initiate {callId, canal: MTN_MOMO}
    API->>FIN: InitiatePaymentCommand
    FIN->>INTEG: CreatePaymentRequest(montant, canal, référence)
    INTEG->>CP: POST /payment (amount, currency: XOF, method: MOMO)
    CP-->>INTEG: paymentToken + ussd_code
    INTEG-->>FIN: paymentToken
    FIN-->>APP: 200 {ussd_code: *133*1*...#, token}

    APP-->>LOC: Afficher USSD code et instructions
    LOC->>MTN: Composer USSD *133*1*{montant}*{référence}#
    MTN->>MTN: Débiter wallet locataire
    MTN->>CP: Notification paiement effectué
    CP->>API: POST /webhooks/payment-success (token, status: SUCCESS)

    Note over API,DOC: Traitement du webhook

    API->>FIN: ConfirmPaymentCommand (token, référence)
    FIN->>FIN: Enregistrer paiement (idempotent)
    FIN->>FIN: Marquer appel de fonds SOLDER
    FIN->>DOC: GenererQuittance(paiementId)
    DOC->>DOC: Créer PDF quittance numérotée
    DOC-->>FIN: URL quittance PDF
    FIN->>NOTIF: EnvoyerQuittance(locataireId, urlPDF)
    NOTIF-->>LOC: SMS + Email + WhatsApp (quittance)
    NOTIF-->>GE: Notification paiement reçu

    APP-->>LOC: Confirmation paiement + accès quittance
```

### Séquence 3 — Workflow de Maintenance

```mermaid
sequenceDiagram
    participant LOC as Locataire
    participant PORT as Portail Locataire
    participant API as API Gateway
    participant MAINT as MaintenanceModule
    participant GE as Gestionnaire
    participant PREST as Prestataire
    participant FIN as FinancialModule
    participant NOTIF as NotificationModule

    LOC->>PORT: Signaler une panne
    PORT->>API: POST /maintenance-requests {bienId, description, photos}
    API->>MAINT: CreateOrderCommand
    MAINT->>MAINT: Créer OI (statut: CREATED, priorité: auto-détectée)
    MAINT->>NOTIF: Notifier gestionnaire
    NOTIF-->>GE: SMS + Email (nouveau signalement)

    GE->>API: GET /maintenance-orders/{id}
    API->>MAINT: GetOrderQuery
    MAINT-->>GE: Détail OI + photos

    GE->>API: PUT /maintenance-orders/{id}/assign {prestataireId}
    API->>MAINT: AssignOrderCommand
    MAINT->>MAINT: Statut → ASSIGNED
    MAINT->>NOTIF: Notifier prestataire
    NOTIF-->>PREST: SMS + Email (nouveau chantier)
    NOTIF-->>LOC: SMS (technicien affecté)

    alt Devis requis (montant estimé > seuil)
        PREST->>API: POST /maintenance-orders/{id}/quotes {lignes, montantTTC}
        API->>MAINT: SubmitQuoteCommand
        MAINT->>NOTIF: Notifier gestionnaire pour approbation
        NOTIF-->>GE: SMS + Email (devis à approuver)

        GE->>API: PUT /maintenance-orders/{id}/quotes/{qId}/approve
        API->>MAINT: ApproveQuoteCommand
        MAINT->>MAINT: Devis APPROUVE
        MAINT->>NOTIF: Notifier prestataire
        NOTIF-->>PREST: Accord pour intervention
    end

    PREST->>API: PUT /maintenance-orders/{id}/start
    API->>MAINT: StartInterventionCommand
    MAINT->>MAINT: Statut → IN_PROGRESS
    MAINT->>NOTIF: Notifier locataire (intervention en cours)

    PREST->>API: PUT /maintenance-orders/{id}/complete {rapport, photos}
    API->>MAINT: CompleteInterventionCommand
    MAINT->>MAINT: Statut → COMPLETED

    GE->>API: PUT /maintenance-orders/{id}/close
    API->>MAINT: CloseOrderCommand
    MAINT->>MAINT: Statut → CLOSED

    PREST->>API: POST /maintenance-orders/{id}/invoices
    API->>MAINT: SubmitInvoiceCommand
    MAINT->>GE: Notifier facture reçue
    GE->>API: PUT /invoices/{id}/validate
    API->>MAINT: ValidateInvoiceCommand
    MAINT->>FIN: RecordMaintenanceExpense(montant, bienId, bailId)
    FIN->>FIN: Enregistrer charge + imputer
    MAINT->>NOTIF: Notifier locataire (panne résolue)
    NOTIF-->>LOC: SMS + Email (intervention terminée)
```

---

## DIAGRAMME DE DÉPLOIEMENT

### Architecture Infrastructure — Production

```mermaid
graph TB
    subgraph Internet["INTERNET / CDN"]
        CF["Cloudflare CDN\n(Cache + WAF + DDoS Protection)"]
        DNS["DNS — kilie-immo.ci"]
    end

    subgraph Users["UTILISATEURS"]
        WEB_USER["Web Browser\n(PWA)"]
        MOB_USER["Mobile App\n(iOS / Android)"]
        ADMIN_USER["Admin SaaS\n(Internal)"]
    end

    subgraph AWS_PARIS["AWS eu-west-3 (Paris) — Région Principale"]
        subgraph VPC["VPC Privé"]
            subgraph PUBLIC_SN["Subnets Publics (2 AZ)"]
                ALB["Application Load Balancer\n(HTTPS termination)"]
                NAT["NAT Gateway"]
            end

            subgraph PRIVATE_SN["Subnets Privés (2 AZ)"]
                subgraph EKS["EKS Cluster (Kubernetes)"]
                    API_PODS["API Pods\n(NestJS — x3 min)\nHPA auto-scaling"]
                    WORKER_PODS["Worker Pods\n(Jobs/Cron)\n(x2)"]
                    NOTIF_PODS["Notification Pods\n(x2)"]
                end

                subgraph RDS["Amazon RDS"]
                    PG_PRIMARY["PostgreSQL 15\nPrimary (r6g.large)"]
                    PG_REPLICA["PostgreSQL 15\nRead Replica"]
                end

                subgraph CACHE["ElastiCache"]
                    REDIS["Redis 7\n(Cluster Mode)"]
                end

                subgraph MQ["Amazon MQ"]
                    RABBIT["RabbitMQ\n(3-node cluster)"]
                end

                subgraph SEARCH["OpenSearch Service"]
                    ES["OpenSearch\n(Elasticsearch)"]
                end
            end

            S3["Amazon S3\n(Documents, Photos)"]
            SES["Amazon SES\n(Email)"]
        end
    end

    subgraph EXTERNAL["SERVICES EXTERNES"]
        CINETPAY["CinetPay API\n(Mobile Money CI)"]
        INFOBIP["Infobip\n(SMS — Afrique)"]
        DOCUSIGN["DocuSign / YouSign\n(Signature électronique)"]
        CF_R2["Cloudflare R2\n(CDN Assets)"]
    end

    subgraph DR["DR Region — AWS eu-west-1 (Irlande)"]
        DR_RDS["RDS Snapshot\n(Cross-region)"]
        DR_S3["S3 Replication"]
    end

    WEB_USER --> CF
    MOB_USER --> CF
    ADMIN_USER --> CF
    DNS --> CF
    CF --> ALB
    ALB --> API_PODS
    API_PODS --> PG_PRIMARY
    API_PODS --> REDIS
    API_PODS --> RABBIT
    WORKER_PODS --> PG_PRIMARY
    WORKER_PODS --> RABBIT
    NOTIF_PODS --> RABBIT
    API_PODS --> S3
    API_PODS --> ES
    NOTIF_PODS --> INFOBIP
    NOTIF_PODS --> SES
    API_PODS --> CINETPAY
    API_PODS --> DOCUSIGN
    PG_PRIMARY --> PG_REPLICA
    PG_PRIMARY -.->|"Cross-region backup"| DR_RDS
    S3 -.->|"Replication"| DR_S3
    CF --> CF_R2

    style AWS_PARIS fill:#f0f9ff,stroke:#0284c7
    style EXTERNAL fill:#fefce8,stroke:#ca8a04
    style DR fill:#fef2f2,stroke:#dc2626
```

---

## ORGANISATION DU MONOLITHE MODULAIRE

### Structure des Dossiers (Organisation par Feature/Module)

```
src/
├── shared/                        # Shared Kernel
│   ├── domain/
│   │   ├── value-objects/         # ValueObject base, Money(XOF), etc.
│   │   ├── events/                # DomainEvent base
│   │   └── aggregate-root.ts
│   ├── infrastructure/
│   │   ├── database/              # TypeORM config, migrations
│   │   ├── event-bus/             # In-process EventBus
│   │   └── multi-tenancy/         # TenantContext, RLS
│   └── guards/                    # Auth guards, RBAC decorators
│
├── modules/
│   ├── iam/                       # Generic — IAM Module
│   │   ├── domain/
│   │   ├── application/
│   │   ├── infrastructure/
│   │   └── iam.module.ts
│   │
│   ├── property/                  # Core — Property Module
│   │   ├── domain/
│   │   │   ├── aggregates/        # Bien, Unite
│   │   │   ├── value-objects/     # AdresseCi, TitreFoncier, Surface
│   │   │   ├── events/            # BienCree, BienPublie, etc.
│   │   │   └── repositories/      # IBienRepository (port OUT)
│   │   ├── application/
│   │   │   ├── commands/          # CreateBienCommand, PublierBienCommand
│   │   │   ├── queries/           # GetBienQuery, SearchBiensQuery
│   │   │   └── event-handlers/    # Listeners d'autres modules
│   │   ├── infrastructure/
│   │   │   ├── persistence/       # BienRepositoryImpl (TypeORM)
│   │   │   └── search/            # BienSearchAdapter (Elasticsearch)
│   │   └── property.module.ts
│   │
│   ├── party/                     # Core — Party Module
│   ├── leasing/                   # Core — Leasing Module
│   ├── financial/                 # Core — Financial Module
│   ├── maintenance/               # Supporting — Maintenance Module
│   ├── sales/                     # Core — Sales Module
│   ├── condominium/               # Supporting — Condominium Module
│   ├── document/                  # Supporting — Document Module
│   ├── notification/              # Supporting — Notification Module
│   ├── analytics/                 # Generic — Analytics Module
│   ├── subscription/              # Generic — Subscription Module
│   └── integration/               # Generic — Integration Module
│       ├── cinetpay/              # CinetPay adapter
│       ├── sms/                   # SMS adapter (Infobip)
│       ├── email/                 # Email adapter (SES/SendGrid)
│       └── signature/             # E-signature adapter
│
└── main.ts                        # Bootstrap
```

---

## JUSTIFICATION TECHNIQUE

### Décision 1 — Monolithe Modulaire vs Microservices

| Critère | Monolithe Modulaire ✓ | Microservices |
|---|---|---|
| **Complexité opérationnelle** | Faible (1 déploiement) | Élevée (N services) |
| **Cohérence des transactions** | ACID native (PostgreSQL) | Sagas / 2PC requis |
| **Vitesse de développement** | Rapide (équipe unique) | Lente au démarrage |
| **Scalabilité** | Horizontale (pods K8s) | Par service individuel |
| **Débogage** | Stack trace unifiée | Tracing distribué requis |
| **Pertinence Africa** | Opérations simplifiées | Infrastructure coûteuse |
| **Évolution** | Extraction future possible | Immédiate mais coûteuse |

**Verdict : Monolithe Modulaire Phase 1.** Les bounded contexts clairement définis permettent l'extraction sélective en microservices pour les modules à forte charge (Notification, Analytics, Integration) dès que la croissance le justifie.

### Décision 2 — PostgreSQL + Row-Level Security pour Multi-tenancy

**Niveau 1 (MVP)** : `organization_id` sur toutes les tables + RLS PostgreSQL → simple et sûr.
**Niveau 2 (Premium)** : Schema-per-tenant → isolation totale pour grands comptes.
**Niveau 3 (Enterprise)** : Instance dédiée → pour agences gérant > 10 000 biens.

### Décision 3 — Event-Driven Architecture (Interne)

L'EventBus in-process du monolithe reproduit exactement le contrat des Domain Events. Lors de l'extraction d'un module en microservice, seule la couche infrastructure change (`InProcessEventBus` → `RabbitMQEventBus`). Le domaine reste inchangé.

### Décision 4 — CQRS Sélectif

CQRS est appliqué uniquement dans les modules à haute criticité de lecture :
- **Analytics** : Read Models dédiés (vues matérialisées PostgreSQL + Elasticsearch)
- **Owner Portal** : Projections optimisées (dashboard propriétaire)
- **Financial** : Séparation commandes critiques / requêtes de reporting

Pas de CQRS + Event Sourcing complet en Phase 1 — complexité injustifiée.

### Décision 5 — Mobile Money en Architecture

CinetPay est sélectionné comme agrégateur de paiement car :
- Supporte MTN MoMo, Orange Money, Wave, Moov Money en CI via une seule API
- Certifié PCI-DSS — pas d'obligation de stocker des données de paiement
- Support technique basé à Abidjan
- Webhook fiable pour confirmation de paiement asynchrone
- Paiements en XOF natifs

Le module `Integration` encapsule CinetPay derrière le port `IPaymentGateway`. Substitution possible sans impact sur le domaine.

### Décision 6 — Région Hébergement

**AWS eu-west-3 (Paris)** est retenu pour :
- Latence < 80ms depuis Abidjan (via Cloudflare PoP à Abidjan)
- Conformité RGPD (applicable par analogie avec loi CI 2013-450)
- Forte connectivité France–Côte d'Ivoire (infrastructure historique)
- Expertise locale des ingénieurs ivoiriens sur AWS

**Cloudflare** est utilisé pour : WAF, cache, CDN avec PoP en Afrique (Abidjan, Lagos, Nairobi).

### Stack Technique Recommandée (Sans Implémentation)

| Couche | Technologie | Justification |
|---|---|---|
| Backend | NestJS (Node.js) | DDD natif, modules, decorateurs CQRS |
| Base de données | PostgreSQL 15 | ACID, RLS multi-tenancy, JSONB |
| Cache | Redis 7 | Sessions, rate limiting, queue Bull |
| Recherche | OpenSearch | Full-text biens, locataires |
| Message Bus | RabbitMQ | Domain events, fiabilité |
| Stockage | S3 / Cloudflare R2 | Documents, photos |
| Frontend | Next.js 14 (React) | SSR, PWA, App Router |
| Mobile | React Native (Expo) | Code partagé iOS/Android |
| Containers | Docker + Kubernetes | EKS, scaling, rolling deploys |
| CI/CD | GitHub Actions + ArgoCD | GitOps |
| Monitoring | Datadog / Prometheus + Grafana | APM, alertes |
| Logs | CloudWatch + OpenSearch | Centralisation |
| E-signature | YouSign (EU) ou DocuSign | API simple, conformité |
| PDF | Puppeteer / Gotenberg | Quittances, contrats |
| SMS | Infobip | Couverture Afrique de l'Ouest |
| Email | Amazon SES | Coût + délivrabilité |
| Paiement CI | CinetPay | Mobile Money CI |

---

## ROADMAP ARCHITECTURE

```mermaid
gantt
    title KILIE IMMO — Roadmap Architecture
    dateFormat  YYYY-MM
    section Phase 1 - MVP
    IAM + Subscription        :2026-07, 1M
    Property + Party           :2026-07, 2M
    Leasing Core               :2026-08, 2M
    Financial + CinetPay       :2026-09, 2M
    Document + Notification    :2026-09, 1M
    Beta Privée CI             :2026-11, 1M

    section Phase 2 - Extension
    Sales Module               :2026-12, 2M
    Maintenance Module         :2027-01, 2M
    Condominium Module         :2027-02, 2M
    Portail Locataire          :2027-03, 1M
    Portail Propriétaire       :2027-03, 1M
    Analytics BI               :2027-04, 2M
    App Mobile Native          :2027-04, 3M

    section Phase 3 - Strategic
    Extraction Microservices   :2027-07, 3M
    Marketplace Annonces       :2027-07, 3M
    Score Solvabilité IA       :2027-10, 3M
    API Partenaires            :2028-01, 2M
    Expansion Sénégal/Mali     :2028-03, 3M
```

---

*Document rédigé par : Architecture Team — KILIE IMMO*
*Version : 1.0 — Juin 2026*
*Prochaine révision : avant démarrage Phase 2*
