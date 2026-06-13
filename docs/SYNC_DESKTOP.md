# Synchronisation Desktop (Windows / macOS / Linux)

Cette implementation fournit une base de synchronisation automatique configurable entre la plateforme et un client desktop.

## 1. Activation et configuration globale

Variables d'environnement:

- `SYNC_ENABLED=true`
- `SYNC_DEFAULT_INTERVAL_MINUTES=15`
- `SYNC_MAX_FILES_PER_PULL=200`
- `SYNC_ALLOW_UPLOAD=false`

La configuration globale peut aussi etre surchargee via `app_settings` (cle: `sync.global`).

## 2. Configuration utilisateur

Chaque utilisateur configure la synchronisation dans `Mon profil`:

- Activer/desactiver la sync
- Frequence (minutes)
- Politique de conflit (`keep_both`, `server_wins`, `local_wins`)
- Sync sur connexion limitee
- Demarrage automatique du client

Ces options sont stockees dans `users.preferences.sync`.

## 3. Appairage d'un appareil

Depuis `Mon profil`:

1. Cliquer `Generer un jeton de synchronisation`
2. Saisir le nom de l'appareil et le systeme
3. Copier le jeton renvoye (affiche une seule fois)

Le jeton est stocke cote serveur sous forme hachee (`sync_devices.token_hash`).

## 4. Endpoints client desktop

Base URL: `https://votre-domaine/api/sync-client`

Auth requise via header:

- `Authorization: Bearer <token>`
ou
- `X-Sync-Token: <token>`

Routes:

- `GET /ping`
- `GET /config`
- `GET /changes?since=2026-03-06T00:00:00Z&limit=200`
- `GET /download/{mediaId}`

## 5. Reponse `/changes`

Retourne la liste des fichiers modifies pour les documents que l'utilisateur est autorise a telecharger.

Exemple de boucle cliente:

1. Lire `/config`
2. Attendre `interval_minutes`
3. Appeler `/changes` avec le dernier `since`
4. Telecharger chaque fichier via `download_url`
5. Mettre a jour `since` avec l'heure serveur

## 6. Securite

- Les tokens sont haches en base
- Un jeton peut etre revoque (ou tous via l'action profil)
- Le controle d'acces document reutilise `DocumentAccessService`
- La sync est limitee par throttling

## 7. Limites actuelles

- Synchronisation descendante (serveur vers poste) seulement
- Upload client vers plateforme non active par defaut

## 8. Client desktop fourni

Un client desktop cross-platform est disponible dans:

- `desktop-client/`

Documentation de demarrage:

- `desktop-client/README.md`

Scripts de packaging:

- `desktop-client/build-windows.bat` (Windows `.exe`)
- `desktop-client/build-macos.sh` (macOS CLI + `.app`)

Guide de distribution utilisateurs:

- `docs/GUIDE_DISTRIBUTION_CLIENT_DESKTOP.md`

## 9. Telechargement depuis Mon profil

La page `Mon profil` expose une section `Telechargements client desktop` permettant de recuperer:

- le guide PDF (`GUIDE_DISTRIBUTION_CLIENT_DESKTOP.pdf`)
- le package Windows (`plussci-sync-client-windows.zip`)
- le package macOS (`plussci-sync-client-macos.tar.gz`)

Route backend:

- `GET /profile/downloads/{asset}` (nom: `profile.downloads.asset`)

Securite:

- route protegee par authentification + throttling
- acces reserve aux profils ayant au moins un des droits suivants:
	- role `Super Admin`
	- permission `ged.documents.download`
	- permission `admin.users.view`

## 10. Build macOS reel (.app)

Important: un vrai bundle `.app` ne peut pas etre compile depuis Windows.

Options recommandees:

- lancer `desktop-client/build-macos.sh` sur un Mac
- ou lancer le workflow GitHub `.github/workflows/build-macos-sync-client.yml`

Artefact attendu (publie ensuite dans l'environnement):

- `desktop-client/dist/plussci-sync-client-macos.tar.gz`

Le package macOS n'est expose dans `Mon profil` que s'il contient effectivement `PLUSSCISyncClient.app`.

Deploiement automatique de l'artefact sur serveur Windows (apres build GitHub Actions):

```powershell
Set-Location c:\xampp\htdocs\courrier-plussci
./scripts/deploy-macos-artifact.ps1 `
	-RepoOwner "VOTRE_ORG_OU_USER" `
	-RepoName "courrier-plussci" `
	-GithubToken "ghp_xxx"
```

Resultat attendu:

- le fichier `desktop-client/dist/plussci-sync-client-macos.tar.gz` est publie
- le lien macOS redevient disponible dans `Mon profil` pour les profils autorises
