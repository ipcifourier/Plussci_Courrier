# Matrice Backoffice Roles / Permissions

## Regles globales

- `Super Admin` a acces a tout le backoffice via `Gate::before`.
- La navigation reelle est pilotee par Filament (`discoverResources`, `discoverPages`, `discoverWidgets`) et non par `DynamicMenu`.
- Le tableau de bord admin est accessible a tout utilisateur authentifie, mais les widgets sont filtres individuellement par `canView()`.
- `Mon profil` est accessible a tout utilisateur authentifie.

## Ecrans principaux

| Type | Ecran | Groupe | Acces |
| --- | --- | --- | --- |
| Page | Tableau de bord admin | Accueil | Tout utilisateur authentifie |
| Page | Mon profil | Menu utilisateur | Tout utilisateur authentifie |
| Page | Acquisition & OCR | GED | `Super Admin` ou `ged.documents.create` |
| Page | Gestionnaire fichiers | GED | `Super Admin` ou `ged.documents.view` |
| Page | Recherche avancee | GED | `Super Admin` ou `ged.documents.view` ou `courriers.viewAny` |
| Page | Registre d'archive | GED | `Super Admin` ou `ged.documents.view` ou `ged.documents.download` ou `ged.dossiers.view` |
| Page | SYSGED Share | GED | `Super Admin` ou `ged.documents.view` |
| Page | Utilisateurs en ligne | Taches et Collaboration | `Super Admin` ou une permission de taches (`collaboration.tasks.view/create/assign/update`) |
| Page | Parametres GED | Administration | `Super Admin` ou `admin.roles.manage` |
| Page | SLA Courriers | Administration | `Super Admin` ou `admin.roles.manage` |

## Resources Filament

| Resource | Groupe | Acces |
| --- | --- | --- |
| Utilisateurs | Administration | `Super Admin` ou `admin.users.view` |
| Roles & Permissions | Administration | `Super Admin` ou `admin.roles.manage` |
| Departements | Administration | `Super Admin` ou `admin.roles.manage` |
| Structures | Administration | `Super Admin` ou `admin.roles.manage` |
| GTT | Administration | `Super Admin` ou `admin.roles.manage` ou role `GTT Responsable` ou `gtt.documents.view` ou `gtt.members.view` |
| Circuits de validation | Administration | `Super Admin` ou `admin.roles.manage` |
| Types document | Administration | `Super Admin` ou `admin.roles.manage` |
| Domaines intervention | Administration | `Super Admin` ou `admin.roles.manage` |
| Sous-domaines | Administration | `Super Admin` ou `admin.roles.manage` |
| Journal d'audit | Audit et tracabilite | `Super Admin` ou `audit.view` ou `audit.export` |
| Courriers | Courriers | `Super Admin` ou role `GTT Responsable` ou `courriers.viewAny` ou `courriers.view` |
| Documents GED | GED | `Super Admin` ou role `GTT Responsable` ou `ged.documents.view` ou `ged.documents.create` |
| Dossiers GED | GED | `Super Admin` ou role `GTT Responsable` ou `ged.dossiers.view` |
| Rapports | GED | `Super Admin` ou `reports.viewAny/view/create/update/delete/export/approval.*` |
| Categories rapports | GED | `Super Admin` ou `reports.templates.manage` |
| Modeles rapports PLUSS | GED | `Super Admin` ou `reports.templates.manage` |
| Suivi recommandations | GED | `Super Admin` ou `reports.viewAny/view` ou `reports.recommendations.viewAny/create/update/delete` |
| Taches | Taches et Collaboration | `Super Admin` ou `collaboration.tasks.view/create/assign/update/close` |
| Rendez-vous | Agenda | `Super Admin` ou `agenda.viewAny/view/create/update/delete` ou `agenda.appointments.manage` |
| Reunions | Agenda | `Super Admin` ou `agenda.viewAny/view/create/update/delete` ou `agenda.meetings.manage` |
| Diligences de reunion | Agenda | `Super Admin` ou `agenda.viewAny/view/create/update/delete` ou `agenda.diligences.manage` |
| Visites | Agenda | `Super Admin` ou `agenda.viewAny/view/create/update/delete` ou `agenda.visits.manage` |

## Widgets du tableau de bord

| Widget | Acces |
| --- | --- |
| AdminWelcomeWidget | Tout utilisateur authentifie |
| MesValidationsWidget | Tout utilisateur authentifie |
| CourriersStatsOverview | `Super Admin` ou `courriers.viewAny` ou `courriers.view` |
| GedStatsOverview | `Super Admin` ou `ged.documents.view` ou `ged.dossiers.view` |
| CollaborationStatsOverview | `Super Admin` ou `collaboration.tasks.view/create/assign/update/close` |
| AgendaStatsOverview | `Super Admin` ou une permission agenda (`agenda.*`, `agenda.meetings.manage`, `agenda.appointments.manage`, `agenda.visits.manage`, `agenda.diligences.manage`) |
| EcheancesWidget | `Super Admin` ou permissions taches ou courriers (`collaboration.tasks.*`, `courriers.viewAny`, `courriers.view`) |
| MesImputationsEnAttenteWidget | `Super Admin` ou role `GTT Responsable` ou `courriers.viewAny` ou `courriers.view` |
| MesTachesWidget | `Super Admin` ou `collaboration.tasks.view/create/assign/update/close` |
| TachesEnRetardWidget | `Super Admin` ou `collaboration.tasks.view/create/assign/update/close` |
| AuditStatsOverview | `Super Admin` ou `audit.view` |
| AuditActivityChartWidget | `Super Admin` ou `audit.view` |
| AuditTopActorsWidget | `Super Admin` ou `audit.view` |
| WorkflowSlaOverviewWidget | role `Manager` ou `Super Admin` ou `admin.roles.manage` |

## Points de vigilance

- `WorkflowSlaOverviewWidget` accepte maintenant aussi `admin.roles.manage`, meme si le role `Manager` reste supporte.
- La page d'accueil reste visible a tout utilisateur connecte, mais sans widget non autorise apres filtrage.

## Correctifs appliques pendant l'audit

- Alignement de `DepartementResource` sur `admin.roles.manage`.
- Alignement de `DocumentResource` sur `ged.documents.view` / `ged.documents.create` sans fallback legacy.
- Suppression des fallbacks legacy `documents.create` / `dossiers.view` dans les resources GED et leurs points d'entree.
- Formalisation dans le seeder des roles `Admin`, `Assistante` et `Manager`.
- Filtrage des widgets autorises dans `AdminDashboard`.
- Ajout des guards manquants sur les resources/pages/widgets Filament restant sans `canAccess()` ou `canView()`.