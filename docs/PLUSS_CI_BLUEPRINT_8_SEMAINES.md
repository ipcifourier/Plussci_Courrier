# Blueprint complet SI Courrier + GED + Collaboration
## PLUSS.CI (Plateforme Une Seule Santé – Côte d’Ivoire)

Date: 2026-03-04
Portée: Application unifiée de gestion/traitement du courrier, GED et collaboration, avec IAM (rôles/permissions) et traçabilité.

---


## 1) Vision produit (mise à jour IA)

Construire une plateforme unique permettant:
1. La gestion de bout en bout des courriers entrants/sortants.
2. La GED (classement, versioning, recherche, archivage).
3. Le travail collaboratif inter-services (commentaires, tâches, mentions, notifications).
4. Le contrôle d’accès strict par rôles/permissions et audit des actions.
5. L’acquisition intelligente des documents grâce à l’IA (extraction, classification, suggestion, recherche sémantique).

Objectif métier PLUSS.CI:
- Réduire les délais de traitement.
- Sécuriser et tracer toutes les décisions/documentations.
- Fluidifier la collaboration entre directions/services.
- Moderniser l’acquisition documentaire par l’automatisation et l’intelligence.

---

## 2.1) Roadmap IA acquisition/numérisation

Priorités IA pour l’acquisition documentaire:
1. Extraction automatique de métadonnées (titre, type, expéditeur, date) via OCR + NLP.
2. Classification automatique des documents (catégorisation, routage).
3. Suggestion d’indexation ou de tags par IA.
4. Détection d’anomalies ou de doublons.
5. Résumé automatique du contenu.
6. Recherche intelligente (semantic search).
7. Reconnaissance de formulaires ou de tableaux.

Première brique IA à implémenter:
- Extraction automatique de métadonnées lors de l’acquisition (titre, type, expéditeur, date) :
	- Utilisation de modèles NLP open-source (spaCy, transformers) ou API cloud (Azure, Google, AWS).
	- Pipeline : OCR → NLP → suggestion → validation utilisateur.
	- Stockage des suggestions dans les champs du document.
	- Interface : affichage des suggestions lors de l’import, validation ou correction par l’utilisateur.

---

---

## 2) Architecture cible (applicative)

- Backend: Laravel 12
- Back-office: Filament v5
- AuthZ: Spatie Permission (RBAC + permissions fines)
- GED media: Spatie Media Library
- Notifications: base de données + email (optionnel)
- Export: DOMPDF (registre / états)
- CI: GitHub Actions (tests ciblés métier)

Modules:
1. Courriers
2. GED
3. Collaboration
4. Administration IAM
5. Reporting & Export
6. Audit & Observabilité

---

## 3) Schéma DB (blueprint logique)

## 3.1 Domaine Courrier (déjà en grande partie en place)

### `courriers`
- id
- type (Entrant/Sortant)
- reference (unique)
- date_reception_envoi
- objet
- resume
- priorite
- statut (Nouveau/En cours/Traité/Archivé)
- niveau_confidentialite
- correspondant_id (FK)
- user_id (FK initiateur)
- requires_approval (bool)
- approval_status (not_required/pending/approved/rejected)
- current_approval_level (nullable)
- signed_by (nullable FK users)
- signed_at (nullable datetime)
- signature_comment (nullable text)
- timestamps

### `correspondants`
- id
- nom_structure
- nom_contact
- email
- telephone
- adresse
- timestamps

### `imputations`
- id
- courrier_id (FK)
- expediteur_id (FK users)
- destinataire_id (FK users)
- instructions
- statut_traitement (En attente/En cours/Traité)
- date_imputation
- timestamps

### `courrier_approvals`
- id
- courrier_id (FK)
- level (tinyint)
- approver_id (FK users)
- status (pending/approved/rejected)
- comment (nullable)
- decided_at (nullable)
- timestamps

## 3.2 Domaine GED (à implémenter)

### `dossiers`
- id
- code (unique)
- libelle
- description (nullable)
- parent_id (nullable FK dossiers)
- confidentialite (Standard/Confidentiel/Personnel)
- owner_id (FK users)
- statut (Actif/Clos/Archivé)
- timestamps

### `documents`
- id
- dossier_id (nullable FK dossiers)
- courrier_id (nullable FK courriers)
- reference_doc (unique)
- titre
- type_document (PV/Note/Rapport/Annexe/etc.)
- version_courante_id (nullable FK document_versions)
- etat_cycle_vie (Brouillon/Validé/Archivé)
- auteur_id (FK users)
- confidentiality_level
- tags_json (nullable)
- metadata_json (nullable)
- timestamps

### `document_versions`
- id
- document_id (FK)
- numero_version (v1, v2...)
- media_id (FK media)
- checksum_sha256
- commentaire_version (nullable)
- created_by (FK users)
- created_at

### `document_access_rules`
- id
- document_id (FK)
- role_id (nullable FK roles)
- user_id (nullable FK users)
- can_view (bool)
- can_download (bool)
- can_edit (bool)
- can_share (bool)

## 3.3 Domaine Collaboration (à implémenter)

### `comments`
- id
- commentable_type
- commentable_id
- user_id
- body
- is_internal (bool)
- timestamps

### `mentions`
- id
- comment_id (FK)
- mentioned_user_id (FK users)
- notified_at (nullable)

### `tasks`
- id
- taskable_type
- taskable_id
- titre
- description (nullable)
- assignee_id (FK users)
- assigner_id (FK users)
- priority
- due_date (nullable)
- status (todo/doing/done/cancelled)
- timestamps

### `task_histories`
- id
- task_id (FK)
- changed_by (FK users)
- from_status
- to_status
- note (nullable)
- created_at

## 3.4 Sécurité / Audit / Notifications

### `audit_logs`
- id
- actor_id (nullable FK users)
- action (create/update/delete/approve/reject/sign/export/login)
- entity_type
- entity_id
- before_json (nullable)
- after_json (nullable)
- ip_address
- user_agent
- created_at

### `notifications`
- (déjà en place)

---

## 4) Rôles & permissions (RBAC)

## 4.1 Rôles cibles
1. Super Admin
2. Admin Métier
3. Gestionnaire Courrier
4. Approbateur N1
5. Approbateur N2
6. Lecteur Courrier
7. Archiviste GED
8. Collaborateur
9. Auditeur (lecture + export audit)

## 4.2 Catalogue permissions (nomenclature)

### Courriers
- courriers.viewAny
- courriers.view
- courriers.create
- courriers.update
- courriers.delete
- courriers.export
- courriers.sign
- courriers.approval.submit
- courriers.approval.approve
- courriers.approval.reject

### GED
- ged.dossiers.view
- ged.dossiers.create
- ged.dossiers.update
- ged.dossiers.archive
- ged.documents.view
- ged.documents.create
- ged.documents.update
- ged.documents.version
- ged.documents.download
- ged.documents.share
- ged.documents.delete

### Collaboration
- collaboration.comments.create
- collaboration.comments.delete
- collaboration.tasks.create
- collaboration.tasks.assign
- collaboration.tasks.update
- collaboration.tasks.close

### Administration
- admin.users.view
- admin.users.create
- admin.users.update
- admin.roles.manage
- admin.permissions.manage

### Audit
- audit.view
- audit.export

## 4.3 Matrice d’attribution (résumé)

- Super Admin: toutes permissions.
- Admin Métier: admin.users.*, admin.roles.manage (optionnel), courriers.*, ged.*, audit.view.
- Gestionnaire Courrier: courriers.viewAny/view/create/update/export/sign/approval.submit.
- Approbateur N1/N2: courriers.viewAny/view/approval.approve/approval.reject.
- Lecteur Courrier: courriers.viewAny/view.
- Archiviste GED: ged.* (sauf suppression définitive si non autorisée).
- Collaborateur: collaboration.comments.create, collaboration.tasks.update (sur ses tâches), ged.documents.view.
- Auditeur: audit.view/audit.export + lecture seule courriers/ged.

---

## 5) Backlog priorisé sur 8 semaines (3 sprints)

Cadence: 3 sprints
- Sprint 1: Semaines 1-3
- Sprint 2: Semaines 4-6
- Sprint 3: Semaines 7-8

## Sprint 1 (Semaines 1-3) — Fondations opérationnelles

Objectif: Stabiliser Courrier + IAM + Audit minimal + base GED.

Livrables:
1. Durcissement RBAC complet (permissions d’approbation/export/signature).
2. Journal d’audit minimal sur actions clés (approve/reject/sign/export/update).
3. GED base: dossiers + documents + versions (CRUD initial).
4. UI Filament: navigation module GED, vues liste/détail document.
5. Tests ciblés supplémentaires (permissions + audit).

Priorité (P1):
- Schéma DB GED + migrations
- Policies GED
- Audit logs sur événements métier critiques
- Écrans CRUD GED base

Critères d’acceptation:
- Toutes actions sensibles refusées sans permission.
- Chaque action critique crée une trace audit exploitable.
- Upload versionné fonctionnel et lisible en UI.

## Sprint 2 (Semaines 4-6) — Collaboration & recherche

Objectif: Ajouter productivité collective et pilotage du traitement.

Livrables:
1. Commentaires sur courrier/documents + @mentions.
2. Tâches collaboratives liées aux courriers (assignation, échéance, statut).
3. Widget “Mes tâches” + “Retards”.
4. Recherche avancée (métadonnées + filtres combinés).
5. Notifications DB + email (optionnelles) pour mentions et tâches.

Priorité (P1):
- tables comments/mentions/tasks
- policies collaboration
- notifications & widgets

Critères d’acceptation:
- Mention notifie l’utilisateur ciblé.
- Tâches assignées visibles dans dashboard personnel.
- Recherche retourne des résultats pertinents sur jeu de données test.

## Sprint 3 (Semaines 7-8) — Industrialisation & conformité

Objectif: Finaliser niveau production institutionnelle.

Livrables:
1. Export avancé (registre périodique, signatures, audits).
2. Rétention/archivage documentaire (règles lifecycle).
3. Hardening sécurité (quotas upload, validation mime stricte, rate limiting actions sensibles).
4. Observabilité (logs métier structurés + dashboards KPI).
5. Documentation exploitation + runbooks support.

Priorité (P1):
- lifecycle GED
- audit export
- hardening sécurité

Critères d’acceptation:
- Cycle de vie documentaire appliqué automatiquement selon règles.
- Rapports exportables validés métier.
- Documentation support utilisable par équipe SI.

---

## 6) KPI de pilotage (hebdo)

- Délai moyen de traitement courrier.
- % courriers traités dans les SLA.
- % courriers signés sur les sortants traités.
- Nombre d’imputations en retard.
- Nombre d’actions auditées / actions totales sensibles.
- Taux d’usage collaboration (commentaires, tâches clôturées).

---

## 7) Définition of Done (DoD)

Une user story est "Done" si:
1. Fonctionnelle en UI (cas nominal + cas d’erreur).
2. Sécurisée (policy/permission testée).
3. Auditée si action sensible.
4. Couverte par tests (feature/unit pertinents).
5. Documentée (usage + impact exploitation).

---

## 8) Lancement immédiat — Sprint 1

Ordre d’exécution recommandé:
1. Implémenter `audit_logs` + service d’audit central.
2. Mettre en place tables GED (`dossiers`, `documents`, `document_versions`) + CRUD Filament.
3. Ajouter policies GED + permissions `ged.*`.
4. Étendre tests CI sur RBAC/audit/GED base.

Livraison Sprint 1 attendue: fin semaine 3.

---

## 9) Roadmap modernisation (Impact x Effort)

Objectif: augmenter rapidement la puissance métier sans destabiliser l'existant.

### 9.1 Priorisation

1. Workflow intelligent de validation (Impact: Tres eleve, Effort: Moyen)
2. Recherche unifiee plein texte + filtres avances (Impact: Tres eleve, Effort: Moyen)
3. Centre de notifications proactives (Impact: Eleve, Effort: Faible a moyen)
4. Dashboard decisionnel SLA/charge (Impact: Eleve, Effort: Moyen)
5. API + webhooks d'integration (Impact: Eleve, Effort: Moyen)
6. Signature electronique avancee (Impact: Tres eleve, Effort: Eleve)

### 9.2 Lot #1 recommande (demarrage immediat)

Titre: Workflow intelligent de validation

Livrables:
1. Regles de validation parametriques (type document, priorite, montant, direction).
2. Escalade automatique sur depassement de delai (SLA).
3. Delegation temporaire de validation (absence/conges).
4. Historique de transitions complet dans l'audit.
5. Widgets de suivi: en attente, en retard, bloque.

Tables a ajouter:
1. `document_workflows` (definition du workflow).
2. `document_workflow_steps` (etapes, approbateur cible, ordre).
3. `document_sessions` ou `workflow_instances` (etat runtime par document).

Regles minimales V1:
1. Si priorite = Urgente -> SLA plus court.
2. Si confidentialite = Confidentiel -> approbation N2 obligatoire.
3. Si type = Contrat -> visa juridique obligatoire.

Critere d'acceptation V1:
1. Un document suit automatiquement son circuit selon les regles actives.
2. Un retard de validation declenche une alerte et une escalation.
3. Toute decision est tracee (acteur, date, commentaire, etape).

### 9.3 Sequencement suggere (4 semaines)

Semaine 1:
1. Schema DB workflow + migrations.
2. Entites/policies de base.
3. Ecrans admin de configuration des workflows.

Semaine 2:
1. Moteur d'execution (resolveur d'etape courante).
2. Transitions approve/reject/rework.
3. Audit des transitions.

Semaine 3:
1. SLA + job d'escalade.
2. Notifications ciblant approbateurs et managers.
3. Ecrans "A valider" et "En retard".

Semaine 4:
1. Delegation temporaire.
2. Tests end-to-end (cas normaux + erreurs + droits).
3. Durcissement UX + documentation exploitation.

### 9.4 Risques et mitigation

1. Complexite regles metier:
Mitigation: demarrer avec un set de regles V1 limite et testable.
2. Retards de notification:
Mitigation: queue dediee + retries + supervision jobs.
3. Regressions permissions:
Mitigation: tests automatises RBAC sur chaque transition.

### 9.5 Ready pour implementation

Le lot #1 est considere "Ready" si:
1. Les roles validateurs sont confirms par la maitrise d'ouvrage.
2. Les SLA cibles sont fixes par type/priorite.
3. Les regles V1 sont validees par ecrit.
