# Cible Menu Courriers (4 semaines)

Ce document propose une evolution concrete du menu `Courriers` a partir de l'existant (`CourrierResource`, `MesImputationsCourrier`, validation multi-niveaux, export PDF, smart search).

## 1. Objectif

- Donner des files de travail claires par role.
- Reduire le temps de traitement et les retards.
- Ajouter de la flexibilite sans casser les routes/permissions actuelles.

## 2. Existant pris en compte

Fonctionnalites deja presentes:

- Liste courriers avec filtres riches et actions de cycle de vie.
- Page `Mes imputations` (`/admin/courriers/mes-imputations`).
- Workflow d'approbation (`soumettre`, `approuver`, `rejeter`).
- Signature courrier sortant.
- Recherche intelligente et export registre PDF.
- Permissions Spatie deja en place:
  - `courriers.viewAny`, `courriers.view`, `courriers.create`, `courriers.update`, `courriers.delete`
  - `courriers.sign`, `courriers.export`
  - `courriers.approval.submit`, `courriers.approval.approve`, `courriers.approval.reject`

## 3. Cible Menu Courriers

Menu principal `Courriers`:

- `Registre global`
- `Mes files`
- `Validation`
- `Signatures`
- `Supervision`
- `Parametres metier`

### 3.1 Registre global

But: vue complete avec flexibilité d'analyse.

Sous-pages:

- `Tous les courriers`
- `Favoris / vues enregistrees`
- `Recherche avancee`

Ajouts UX:

- Vues enregistrees par utilisateur (filtres + tri + colonnes).
- 3 vues par defaut:
  - `Entrants en retard`
  - `Sortants non signes`
  - `Confidentiels en cours`

### 3.2 Mes files

But: file personnelle orientee action.

Sous-pages:

- `Mes imputations en attente`
- `Mes courriers a traiter`
- `Mes echeances proches (7 jours)`
- `Mes courriers en retard`

Regles minimales:

- Tri par delai le plus proche.
- Badge SLA (vert/orange/rouge).

### 3.3 Validation

But: isoler et accelerer les decisions d'approbation.

Sous-pages:

- `A approuver (Niveau courant)`
- `Mes decisions recentes`
- `Rejetes a corriger`

Ajouts:

- Action rapide en table: `Approuver` / `Rejeter` sans ouvrir la fiche.
- Motif obligatoire en cas de rejet.

### 3.4 Signatures

But: piloter les sortants signables et signes.

Sous-pages:

- `A signer`
- `Signes aujourd'hui`
- `Historique signatures`

Ajouts:

- Filtre `Pret pour signature` (statut traite + workflow approuve).
- Export du journal de signature (CSV/PDF).

### 3.5 Supervision

But: suivi manager/chef de service.

Sous-pages:

- `Retards par departement`
- `Blocages workflow`
- `Volumes entrants/sortants`

KPIs:

- Delai moyen de traitement.
- Taux de retard.
- Taux de rejet workflow.

### 3.6 Parametres metier

But: rendre le module configurable sans dev.

Sous-pages:

- `Regles d'auto-imputation`
- `Modeles de reponse`
- `Regles SLA par type de courrier`

## 4. Permissions: reusage + extensions

Permissions existantes a reutiliser:

- Consultation/edition: `courriers.viewAny`, `courriers.view`, `courriers.create`, `courriers.update`, `courriers.delete`
- Process: `courriers.approval.submit`, `courriers.approval.approve`, `courriers.approval.reject`, `courriers.sign`
- Sortie: `courriers.export`

Nouvelles permissions recommandees:

- `courriers.views.manage` (creer/modifier vues enregistrees)
- `courriers.sla.manage` (regles SLA)
- `courriers.routing.manage` (auto-imputation)
- `courriers.templates.manage` (modeles de reponse)
- `courriers.supervision.view` (tableaux supervision)

## 5. Plan d'implementation (4 semaines)

### Semaine 1 - Files de travail et vues metier

Livrables:

- Page `Mes courriers a traiter`.
- Page `Mes echeances proches`.
- Vue `A signer`.

Impacts techniques:

- Ajouter pages Filament sous `app/Filament/Resources/Courriers/Pages/`.
- Reutiliser `CourriersTable` avec `getTableQuery()` specialise.
- Ajouter widgets KPI rapides sur `ListCourriers`.

### Semaine 2 - Validation et signatures

Livrables:

- Vue `A approuver` (niveau courant uniquement).
- Vue `Rejetes a corriger`.
- Vue `Signes aujourd'hui`.

Impacts techniques:

- Nouvelles pages + filtres predefinis.
- Ajout de 1-2 actions bulk non destructives (ex: marquer en cours).
- Logs audit renforces pour actions de decision.

### Semaine 3 - Supervision

Livrables:

- Dashboard supervision Courriers.
- KPI retard/volume/workflow.

Impacts techniques:

- Nouveaux widgets sous `app/Filament/Widgets/`.
- Eventuel endpoint export supervision (CSV/PDF).

### Semaine 4 - Parametrage metier

Livrables:

- CRUD regles d'auto-imputation.
- CRUD SLA par type.
- CRUD modeles de reponse.

Impacts techniques:

- Nouvelles tables de config metier.
- Nouvelles resources Filament `Administration` ou `Courriers`.
- Seeder permissions/roles mis a jour.

## 6. Ordre de priorite recommande

1. `Mes files` (impact utilisateur immediat).
2. `Validation` (debloque les goulets de decision).
3. `Signatures` (visibilite des sortants).
4. `Supervision` (pilotage management).
5. `Parametres metier` (autonomie long terme).

## 7. Premiere action concrete (demarrage)

Commencer par 3 pages a forte valeur sans migration lourde:

- `Mes courriers a traiter`
- `A approuver`
- `A signer`

Ces pages reutilisent vos champs et permissions existants, donc le delai de mise en production est court.

## 8. Exploitation SLA configurable

- Page admin: `/admin/courriers-sla`
- Commande planifiee: `php artisan deadline:send-alerts` (scheduler quotidien)
- Validation sans envoi: `php artisan deadline:send-alerts --dry-run`
- Cle de persistance: `app_settings.key = courriers.sla`
- Variables d'environnement de base:
  - `COURRIERS_TASK_REMINDER_DAYS_BEFORE=3,1,0`
  - `COURRIERS_IMPUTATION_REMINDER_DAYS_BEFORE=3,1,0`
  - `COURRIERS_TASK_ESCALATION_AFTER_OVERDUE_DAYS=2`
  - `COURRIERS_IMPUTATION_ESCALATION_AFTER_OVERDUE_DAYS=1`
  - `COURRIERS_ENABLE_TASK_ESCALATION=true`
  - `COURRIERS_ENABLE_IMPUTATION_ESCALATION=true`
  - `COURRIERS_SEND_OVERDUE_DAILY=true`
