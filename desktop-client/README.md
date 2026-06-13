# PLUSSCI Desktop Sync Client

Cross-platform desktop sync client for Windows and macOS (also Linux).

This client uses the platform API endpoints:

- `GET /api/sync-client/ping`
- `GET /api/sync-client/config`
- `GET /api/sync-client/changes`
- `GET /api/sync-client/download/{mediaId}`

Current flow: server to desktop (download sync).

## 1) Requirements

- Python 3.10+
- Network access to the platform URL
- A sync token generated from `Mon profil`

## 2) Quick start - Windows

From `desktop-client/`:

```bat
run-windows.bat
```

First-time setup command (PowerShell):

```powershell
python sync_client.py init --base-url https://votre-domaine.tld --token sync_xxxxxxxxx --sync-dir "C:\\PLUSS_SYNC"
```

Then run:

```powershell
python sync_client.py run
```

## 3) Quick start - macOS

From `desktop-client/`:

```bash
chmod +x run-macos.sh
./run-macos.sh
```

First-time setup command:

```bash
python3 sync_client.py init --base-url https://votre-domaine.tld --token sync_xxxxxxxxx --sync-dir "~/PLUSS_SYNC"
```

Then run:

```bash
python3 sync_client.py run
```

## 4) Commands

Initialize config and test API:

```bash
python sync_client.py init --base-url https://votre-domaine.tld --token sync_xxxxxxxxx
```

Show status:

```bash
python sync_client.py status
```

Run sync loop:

```bash
python sync_client.py run
```

## 5) Local files

Config and state are saved per OS user:

- Windows: `%APPDATA%/plussci-sync-client/`
- macOS: `~/Library/Application Support/plussci-sync-client/`

Files:

- `config.json`
- `state.json`

## 6) Security notes

- Keep token private.
- Revoke token from profile if a device is lost.
- Avoid `--insecure` in production.

## 7) Packaging (distributable artifacts)

Build dependencies are listed in:

- `requirements-build.txt`

### Windows (`.exe`)

Run:

```bat
build-windows.bat
```

Artifacts:

- `dist/package-windows/plussci-sync-client.exe`
- `dist/package-windows/config.example.json`
- `dist/package-windows/README.md`
- `dist/plussci-sync-client-windows.zip`

### macOS (CLI + `.app` bundle)

Run:

```bash
chmod +x build-macos.sh
./build-macos.sh
```

Artifacts:

- `dist/package-macos/plussci-sync-client` (CLI)
- `dist/package-macos/PLUSSCISyncClient.app` (bundle)
- `dist/package-macos/config.example.json`
- `dist/package-macos/README.md`
- `dist/plussci-sync-client-macos.tar.gz`

Notes:

- Build Windows artifacts on Windows.
- Build macOS artifacts on macOS.
- The `.app` launcher runs sync directly using existing local config.
