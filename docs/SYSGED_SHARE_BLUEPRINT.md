# SYSGED Share - Blueprint d'integration collaborative

## Vision
`SYSGED Share` est un espace de partage documentaire collabore qui unifie:
- partage interne/externe securise,
- co-edition temps reel (OnlyOffice en priorite),
- connecteurs Microsoft 365 / Google Docs,
- traçabilite (audit + sessions + versions).

Objectif: offrir une experience unique de partage/edition, sans casser les regles GED existantes.

---

## Existant deja present dans le projet
- Edition OnlyOffice deja fonctionnelle:
  - `GET /admin/documents/{document}/office-editor`
  - callback signe `POST /onlyoffice/callback/{document}/{media}`
- Partage public securise par token:
  - `GET /share/{token}`
  - `GET /share/{token}/download/{mediaId}`
- Sessions document collaboratif:
  - modele `DocumentSession`
- Versioning et audit de documents deja en place.

Conclusion: le socle pour `SYSGED Share` existe deja, il faut l'orchestrer dans un module unique.

---

## Cible fonctionnelle (MVP)
## 1) Espace partage unifie par document
Creer un onglet/panneau `SYSGED Share` dans la fiche document:
- liste des liens de partage actifs,
- createur, date expiration, droits (vue, telechargement, commentaire, edition),
- statut (actif, expire, revoque),
- acces rapides: `Copier lien`, `Revoquer`, `Renouveler`, `Ouvrir en editeur`.

## 2) Regles d'acces et scopes
Droits par partage:
- `view`
- `download`
- `comment`
- `edit`

Regles globales:
- edition externe seulement si format compatible + policy valide,
- possibilite de forcer lecture seule sur document finalise.

## 3) Presence collaborative
Afficher dans `SYSGED Share`:
- utilisateurs en cours de consultation/edition,
- mode (view/edit),
- derniere activite.

## 4) Journal de partage
Historique dedie:
- creation lien,
- ouverture lien,
- telechargement,
- revocation,
- edition distante terminee.

---

## Integration editeurs
## A) OnlyOffice (prioritaire, natif)
Mode recommande pour MVP et production immediate:
- conserver le flux actuel,
- router toutes les editions `SYSGED Share` vers OnlyOffice quand le fichier est Office-compatible,
- continuer a versionner a chaque sauvegarde callback.

## B) Microsoft 365 (Word/Excel/PowerPoint Online)
Approche recommande:
- integration Graph API + OneDrive/SharePoint comme stockage temporaire de co-edition,
- mapping `Document <-> cloud_file_id`,
- webhook/notification de fin d'edition pour re-importer version dans GED.

Points cle:
- OAuth2 Azure AD (delegated ou application permissions selon scenario),
- chiffrement des tokens,
- politique de residence des donnees.

## C) Google Docs
Approche recommande:
- Google Drive API + conversion optionnelle (DOCX <-> Google Docs),
- mapping `Document <-> google_file_id`,
- recuperation de version finale dans GED.

Points cle:
- OAuth2 Google,
- maitrise des conversions de format,
- gestion claire du sens de synchronisation.

---

## Architecture technique proposee
## 1) Nouvelles tables (MVP+)
- `sysged_share_spaces`
  - `id`, `document_id`, `owner_id`, `status`, `default_editor` (`onlyoffice|m365|gdocs`), `created_at`...
- `sysged_share_members`
  - `space_id`, `user_id` nullable, `email` nullable, `role` (`owner|editor|viewer|commenter`), `invited_at`, `accepted_at`
- `sysged_share_links`
  - proche de `document_shares` mais orientee espace, ou reuse de `document_shares` avec colonnes additionnelles
- `sysged_share_provider_links`
  - `document_id`, `provider` (`m365|gdocs`), `provider_file_id`, `provider_url`, `synced_at`, `sync_status`
- `sysged_share_events`
  - journal d'evenements technique et metier.

Note: pour aller vite, on peut reutiliser `document_shares` et ajouter les colonnes necessaires en migration incrementale.

## 2) Services applicatifs
- `SysgedShareService`
  - orchestration des droits, liens, revocation, expiration.
- `ShareEditorResolverService`
  - choisit l'editeur selon format + politique + disponibilite provider.
- `M365BridgeService` (phase 2)
- `GoogleDocsBridgeService` (phase 2)

## 3) API/routes cibles
- `POST /admin/sysged-share/{document}/links`
- `PATCH /admin/sysged-share/links/{link}`
- `DELETE /admin/sysged-share/links/{link}`
- `GET /admin/sysged-share/{document}/presence`
- `POST /admin/sysged-share/{document}/open-editor` (retourne URL editor resolue)
- `POST /admin/sysged-share/providers/{provider}/callback`

---

## Securite et conformite
- Tokens de partage longs + revocables + expirables.
- Signature obligatoire des callbacks editeurs.
- Validation stricte MIME/extensions autorises par provider.
- Rate limit sur endpoints publics.
- Audit obligatoire des operations critiques.
- Chiffrement des credentials OAuth provider en base.

---

## Plan de livraison recommande
## Phase 1 (rapide, 1-2 sprints)
- creer UI `SYSGED Share` dans Filament,
- centraliser les partages existants (`document_shares`) dans ce panneau,
- ajouter presence + journal,
- relier edition a OnlyOffice via un bouton unique `Editer`.

## Phase 2 (Microsoft 365)
- OAuth Azure + liaison fichier cloud + import retour GED,
- pilotage d'une strategie `default_editor` par type de document.

## Phase 3 (Google Docs)
- OAuth Google + liaison Drive + retour GED,
- garde-fous de conversion format.

---

## Decisions produit a valider
- edition externe autorisee pour liens publics ou seulement internes?
- priorite editeur par defaut: OnlyOffice globalement, ou par type MIME?
- gestion des conflits si edition simultanee multi-provider.
- duree de vie standard des liens partage.

---

## Recommandation immediate
Demarrer avec `SYSGED Share` sur base OnlyOffice (deja stable), puis brancher M365/Google comme providers optionnels. Cela limite le risque et donne une valeur utilisateur immediate.
