# Guide Distribution Client Desktop (Windows + macOS)

Ce guide est destine a l'equipe IT / support pour distribuer et mettre en service le client de synchronisation PLUSSCI.

## 1. Objectif

- Installer le client desktop sur les postes utilisateurs.
- Appairer chaque poste avec un jeton unique.
- Activer le demarrage automatique au login.
- Fournir une procedure de depannage rapide.

## 2. Prerequis

- Le serveur est accessible (`https://votre-domaine.tld`).
- La sync est active cote plateforme (`SYNC_ENABLED=true`).
- L'utilisateur peut generer un jeton depuis `Mon profil`.
- Les packages de distribution sont disponibles:
  - Windows: `desktop-client/dist/plussci-sync-client-windows.zip`
  - macOS: `desktop-client/dist/plussci-sync-client-macos.tar.gz`

## 3. Distribution Windows

### 3.1 Installation

1. Copier `plussci-sync-client-windows.zip` sur le poste.
2. Decompresser dans: `C:\PLUSS\SyncClient\`
3. Ouvrir PowerShell dans ce dossier.

### 3.2 Appairage initial

Commande:

```powershell
.\plussci-sync-client.exe init --base-url https://votre-domaine.tld --token sync_xxxxxxxxx --sync-dir "C:\PLUSS_SYNC"
```

Verification:

```powershell
.\plussci-sync-client.exe status
```

Resultat attendu: `Ping: OK`.

### 3.3 Demarrage manuel

```powershell
.\plussci-sync-client.exe run
```

### 3.4 Demarrage automatique (Task Scheduler)

Creer une tache planifiee (au login utilisateur):

```powershell
$action = New-ScheduledTaskAction -Execute "C:\PLUSS\SyncClient\plussci-sync-client.exe" -Argument "run"
$trigger = New-ScheduledTaskTrigger -AtLogOn
Register-ScheduledTask -TaskName "PLUSS Sync Client" -Action $action -Trigger $trigger -Description "PLUSS desktop sync" -User $env:USERNAME
```

Verifier:

```powershell
Get-ScheduledTask -TaskName "PLUSS Sync Client"
```

## 4. Distribution macOS

### 4.1 Installation

1. Copier `plussci-sync-client-macos.tar.gz` sur le Mac.
2. Decompresser dans: `~/Applications/PLUSS-SyncClient/`
3. Ouvrir Terminal dans ce dossier.

### 4.2 Appairage initial (CLI)

```bash
./plussci-sync-client init --base-url https://votre-domaine.tld --token sync_xxxxxxxxx --sync-dir "~/PLUSS_SYNC"
```

Verification:

```bash
./plussci-sync-client status
```

Resultat attendu: `Ping: OK`.

### 4.3 Demarrage manuel

Option CLI:

```bash
./plussci-sync-client run
```

Option app bundle:

- Ouvrir `PLUSSCISyncClient.app`

### 4.4 Demarrage automatique (LaunchAgent)

1. Creer le fichier:

`~/Library/LaunchAgents/ci.pluss.sync.client.plist`

Contenu:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" "http://www.apple.com/DTDs/PropertyList-1.0.dtd">
<plist version="1.0">
<dict>
  <key>Label</key>
  <string>ci.pluss.sync.client</string>
  <key>ProgramArguments</key>
  <array>
    <string>/Users/USERNAME/Applications/PLUSS-SyncClient/plussci-sync-client</string>
    <string>run</string>
  </array>
  <key>RunAtLoad</key>
  <true/>
  <key>KeepAlive</key>
  <true/>
  <key>StandardOutPath</key>
  <string>/tmp/pluss-sync-client.out.log</string>
  <key>StandardErrorPath</key>
  <string>/tmp/pluss-sync-client.err.log</string>
</dict>
</plist>
```

2. Charger l'agent:

```bash
launchctl load ~/Library/LaunchAgents/ci.pluss.sync.client.plist
```

3. Verifier:

```bash
launchctl list | grep ci.pluss.sync.client
```

## 5. Checklist support (installation)

- Jeton genere depuis `Mon profil`.
- `init` execute sans erreur.
- `status` retourne `Ping: OK`.
- Dossier de sync existe localement.
- Le client demarre automatiquement apres reconnexion session.

## 6. Procedure de rotation / revocation jeton

1. Utilisateur: `Mon profil` -> `Revoquer tous les jetons sync`.
2. Regenerer un nouveau jeton.
3. Reexecuter `init` sur le poste avec le nouveau jeton.

## 7. Depannage rapide

### 401 / token invalide

- Cause probable: token revoque/incorrect.
- Action: regenerer token et relancer `init`.

### 403 / acces refuse document

- Cause probable: droits GED insuffisants.
- Action: verifier roles/permissions utilisateur sur la plateforme.

### 429 / trop de requetes

- Cause probable: client en boucle ou trop de postes.
- Action: augmenter intervalle (`poll_interval_minutes`) et verifier doublons de process.

### Erreur SSL

- Cause probable: certificat non valide / interception reseau.
- Action: corriger certificat serveur. Eviter `--insecure` en production.

### Le client ne demarre pas au login

- Windows: verifier la tache `PLUSS Sync Client`.
- macOS: verifier `launchctl list` + chemins dans le `.plist`.

## 8. Emplacements config/etat (par utilisateur)

- Windows: `%APPDATA%/plussci-sync-client/`
- macOS: `~/Library/Application Support/plussci-sync-client/`

Fichiers:

- `config.json`
- `state.json`

## 9. Bonnes pratiques de securite

- Un jeton par appareil.
- Revocation immediate en cas de perte/vol poste.
- Ne pas partager les jetons par messagerie non securisee.
- Tracer les installations (poste, utilisateur, date).

---

Version: 1.0
Date: 2026-03-06
Owner: Equipe IT PLUSSCI
