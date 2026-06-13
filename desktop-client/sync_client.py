#!/usr/bin/env python3
import argparse
import hashlib
import json
import os
import platform
import re
import signal
import sys
import time
from dataclasses import dataclass
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, Optional

import requests

APP_NAME = "plussci-sync-client"
DEFAULT_SYNC_DIR = "~/PLUSS_SYNC"
DEFAULT_TIMEOUT = 30
DEFAULT_POLL_MINUTES = 10
DEFAULT_MAX_FILES = 150
DEFAULT_SCAN_INBOX_DIR = ""
DEFAULT_SCAN_POLL_SECONDS = 30
SCAN_EXTENSIONS = {".pdf", ".jpg", ".jpeg", ".png", ".tiff", ".tif", ".bmp", ".doc", ".docx", ".txt"}


@dataclass
class RuntimePaths:
    app_dir: Path
    config_path: Path
    state_path: Path


def now_iso() -> str:
    return datetime.now(timezone.utc).isoformat()


def log(msg: str) -> None:
    stamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    print(f"[{stamp}] {msg}")


def get_app_dir() -> Path:
    system = platform.system().lower()

    if "windows" in system:
        base = os.environ.get("APPDATA") or str(Path.home() / "AppData" / "Roaming")
        return Path(base) / APP_NAME

    if "darwin" in system:
        return Path.home() / "Library" / "Application Support" / APP_NAME

    return Path.home() / ".config" / APP_NAME


def get_paths() -> RuntimePaths:
    app_dir = get_app_dir()
    app_dir.mkdir(parents=True, exist_ok=True)

    return RuntimePaths(
        app_dir=app_dir,
        config_path=app_dir / "config.json",
        state_path=app_dir / "state.json",
    )


def load_json(path: Path, default: Dict[str, Any]) -> Dict[str, Any]:
    if not path.exists():
        return default

    with path.open("r", encoding="utf-8") as f:
        return json.load(f)


def save_json(path: Path, data: Dict[str, Any]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)

    with path.open("w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=True)


def normalize_base_url(url: str) -> str:
    clean = url.strip().rstrip("/")
    if clean.endswith("/api"):
        return clean
    return clean + "/api"


def sanitize_filename(name: str) -> str:
    name = re.sub(r"[\\/:*?\"<>|]", "_", name)
    name = re.sub(r"\s+", " ", name).strip()
    return name or "unnamed"


def sanitize_folder(name: str) -> str:
    name = re.sub(r"[^a-zA-Z0-9._ -]", "_", name)
    name = re.sub(r"\s+", " ", name).strip()
    return name or "document"


class SyncClient:
    def __init__(self, config: Dict[str, Any], state: Dict[str, Any], paths: RuntimePaths):
        self.config = config
        self.state = state
        self.paths = paths
        self.running = True

        self.base_api = normalize_base_url(config["base_url"])
        self.token = config["token"].strip()

        self.sync_dir = Path(os.path.expanduser(config.get("sync_dir", DEFAULT_SYNC_DIR))).resolve()
        self.sync_dir.mkdir(parents=True, exist_ok=True)

        self.timeout = int(config.get("request_timeout_seconds", DEFAULT_TIMEOUT))
        self.verify_ssl = bool(config.get("verify_ssl", True))

        raw_scan = str(config.get("scan_inbox_dir", "")).strip()
        if raw_scan:
            self.scan_inbox_dir: Optional[Path] = Path(os.path.expanduser(raw_scan)).resolve()
            self.scan_inbox_dir.mkdir(parents=True, exist_ok=True)
            self.scan_done_dir: Optional[Path] = self.scan_inbox_dir / "done"
            self.scan_done_dir.mkdir(parents=True, exist_ok=True)
        else:
            self.scan_inbox_dir = None
            self.scan_done_dir = None

    def headers(self) -> Dict[str, str]:
        return {
            "Authorization": f"Bearer {self.token}",
            "Accept": "application/json",
        }

    def request_json(self, method: str, path: str, params: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        url = f"{self.base_api}/sync-client/{path.lstrip('/')}"
        response = requests.request(
            method=method,
            url=url,
            headers=self.headers(),
            params=params,
            timeout=self.timeout,
            verify=self.verify_ssl,
        )

        response.raise_for_status()
        return response.json()

    def upload_scan_file(self, file_path: Path) -> Dict[str, Any]:
        url = f"{self.base_api}/sync-client/scan-upload"
        with file_path.open("rb") as fh:
            response = requests.post(
                url,
                headers=self.headers(),
                files={"file": (file_path.name, fh, "application/octet-stream")},
                timeout=self.timeout,
                verify=self.verify_ssl,
            )
        response.raise_for_status()
        return response.json()

    @staticmethod
    def _file_key(path: Path) -> str:
        stat = path.stat()
        raw = f"{path.name}:{stat.st_size}:{stat.st_mtime}"
        return hashlib.sha1(raw.encode()).hexdigest()

    def run_scan_inbox_once(self) -> None:
        if not self.scan_inbox_dir or not self.scan_inbox_dir.is_dir():
            return

        uploaded: Dict[str, Any] = self.state.setdefault("scan_uploaded", {})
        count = 0

        for file_path in sorted(self.scan_inbox_dir.iterdir()):
            if not file_path.is_file():
                continue
            if file_path.suffix.lower() not in SCAN_EXTENSIONS:
                continue

            try:
                key = self._file_key(file_path)
            except FileNotFoundError:
                continue

            if key in uploaded:
                continue

            # Wait 2 s and re-check size to ensure the scanner finished writing
            try:
                size_before = file_path.stat().st_size
                time.sleep(2)
                if not file_path.exists() or file_path.stat().st_size != size_before:
                    continue
            except FileNotFoundError:
                continue

            try:
                result = self.upload_scan_file(file_path)
                uploaded[key] = {
                    "file": file_path.name,
                    "uploaded_at": now_iso(),
                    "document_id": result.get("document_id"),
                    "reference": result.get("reference"),
                }
                count += 1
                log(f"[scan-upload] \u2714 {file_path.name} \u2192 {result.get('reference', '?')}")

                # Move to done/
                if self.scan_done_dir is not None:
                    dest = self.scan_done_dir / file_path.name
                    if dest.exists():
                        ts = datetime.now().strftime("%Y%m%d_%H%M%S_")
                        dest = self.scan_done_dir / (ts + file_path.name)
                    file_path.rename(dest)

            except Exception as exc:  # noqa: BLE001
                log(f"[scan-upload] \u2718 {file_path.name}: {exc}")

        if count > 0:
            save_json(self.paths.state_path, self.state)
            log(f"[scan-upload] {count} fichier(s) envoy\u00e9(s) vers la GED.")

    def download_file(self, download_url: str, target_path: Path) -> None:
        with requests.get(
            download_url,
            headers=self.headers(),
            timeout=self.timeout,
            verify=self.verify_ssl,
            stream=True,
        ) as response:
            response.raise_for_status()

            tmp_path = target_path.with_suffix(target_path.suffix + ".part")
            tmp_path.parent.mkdir(parents=True, exist_ok=True)

            with tmp_path.open("wb") as f:
                for chunk in response.iter_content(chunk_size=1024 * 256):
                    if chunk:
                        f.write(chunk)

            tmp_path.replace(target_path)

    def ping(self) -> Dict[str, Any]:
        return self.request_json("GET", "ping")

    def fetch_config(self) -> Dict[str, Any]:
        return self.request_json("GET", "config")

    def fetch_changes(self, since: str, limit: int) -> Dict[str, Any]:
        return self.request_json("GET", "changes", params={"since": since, "limit": limit})

    def sync_once(self) -> int:
        since = self.state.get("since") or ""

        remote_config = self.fetch_config()
        global_cfg = remote_config.get("global", {})
        user_cfg = remote_config.get("user", {})

        if not global_cfg.get("enabled", True) or not user_cfg.get("enabled", True):
            log("Sync disabled by platform or user settings.")
            return self.get_effective_interval_minutes(remote_config)

        limit = self.get_effective_limit(remote_config)
        changes = self.fetch_changes(since=since, limit=limit)

        if changes.get("disabled"):
            log("Sync temporarily disabled by server response.")
            return self.get_effective_interval_minutes(remote_config)

        files = changes.get("files", [])
        if not isinstance(files, list):
            files = []

        media_versions = self.state.setdefault("media_versions", {})
        downloaded = 0
        skipped = 0

        for item in files:
            media_id = str(item.get("media_id", "")).strip()
            document_id = str(item.get("document_id", "")).strip()
            title = str(item.get("document_title", "Document")).strip()
            file_name = sanitize_filename(str(item.get("file_name", "file.bin")))
            updated_at = str(item.get("updated_at", ""))
            download_url = str(item.get("download_url", "")).strip()

            if not media_id or not download_url:
                skipped += 1
                continue

            if media_versions.get(media_id) == updated_at:
                skipped += 1
                continue

            folder_name = sanitize_folder(f"{document_id}_{title}")
            target_dir = self.sync_dir / folder_name
            target_path = target_dir / file_name

            try:
                self.download_file(download_url=download_url, target_path=target_path)
                media_versions[media_id] = updated_at
                downloaded += 1
            except Exception as exc:  # noqa: BLE001
                log(f"Download failed for media {media_id}: {exc}")

        server_time = changes.get("server_time")
        if isinstance(server_time, str) and server_time.strip():
            self.state["since"] = server_time

        self.state["last_sync_at"] = now_iso()
        save_json(self.paths.state_path, self.state)

        log(f"Sync cycle done. downloaded={downloaded}, skipped={skipped}, since={self.state.get('since', '')}")

        return self.get_effective_interval_minutes(remote_config)

    def get_effective_interval_minutes(self, remote_config: Dict[str, Any]) -> int:
        user_cfg = remote_config.get("user", {}) if isinstance(remote_config, dict) else {}
        fallback = int(self.config.get("poll_interval_minutes", DEFAULT_POLL_MINUTES))

        value = user_cfg.get("interval_minutes", fallback)
        try:
            n = int(value)
        except Exception:  # noqa: BLE001
            n = fallback

        return max(1, min(120, n))

    def get_effective_limit(self, remote_config: Dict[str, Any]) -> int:
        global_cfg = remote_config.get("global", {}) if isinstance(remote_config, dict) else {}

        fallback = int(self.config.get("max_files_per_pull", DEFAULT_MAX_FILES))
        try:
            gmax = int(global_cfg.get("max_files_per_pull", fallback))
        except Exception:  # noqa: BLE001
            gmax = fallback

        return max(1, min(gmax, fallback))

    def run_forever(self) -> int:
        try:
            ping = self.ping()
            device = ping.get("device", {})
            log(
                "Connected to sync API. device_id={0}, name={1}, platform={2}".format(
                    device.get("id", "?"), device.get("name", "?"), device.get("platform", "?"),
                )
            )
        except Exception as exc:  # noqa: BLE001
            log(f"Ping failed: {exc}")
            return 2

        def _stop(_sig: int, _frame: Any) -> None:
            self.running = False
            log("Stop signal received. Exiting...")

        signal.signal(signal.SIGINT, _stop)
        signal.signal(signal.SIGTERM, _stop)

        while self.running:
            try:
                interval_minutes = self.sync_once()
                sleep_seconds = interval_minutes * 60
            except Exception as exc:  # noqa: BLE001
                log(f"Sync cycle error: {exc}")
                sleep_seconds = 30

            scan_tick = 0
            for _ in range(sleep_seconds):
                if not self.running:
                    break
                time.sleep(1)
                scan_tick += 1
                if self.scan_inbox_dir and scan_tick % DEFAULT_SCAN_POLL_SECONDS == 0:
                    try:
                        self.run_scan_inbox_once()
                    except Exception as exc:  # noqa: BLE001
                        log(f"[scan-upload] Erreur polling: {exc}")

        return 0


def cmd_init(args: argparse.Namespace, paths: RuntimePaths) -> int:
    config = {
        "base_url": args.base_url,
        "token": args.token,
        "sync_dir": args.sync_dir or DEFAULT_SYNC_DIR,
        "scan_inbox_dir": args.scan_inbox_dir or DEFAULT_SCAN_INBOX_DIR,
        "poll_interval_minutes": args.poll_interval_minutes,
        "max_files_per_pull": args.max_files_per_pull,
        "request_timeout_seconds": args.request_timeout_seconds,
        "verify_ssl": not args.insecure,
    }

    save_json(paths.config_path, config)

    if not paths.state_path.exists():
        save_json(paths.state_path, {"since": "", "media_versions": {}, "last_sync_at": None})

    log(f"Config written: {paths.config_path}")
    log(f"State file: {paths.state_path}")

    client = SyncClient(config=config, state=load_json(paths.state_path, {}), paths=paths)
    try:
        ping = client.ping()
        log(f"Init OK. Server time: {ping.get('server_time', '?')}")
    except Exception as exc:  # noqa: BLE001
        log(f"Init done but ping failed: {exc}")
        return 1

    return 0


def cmd_status(paths: RuntimePaths) -> int:
    config = load_json(paths.config_path, {})
    state = load_json(paths.state_path, {"since": "", "media_versions": {}, "last_sync_at": None})

    if not config:
        log("No config found. Run 'init' first.")
        return 1

    print("Config path:", paths.config_path)
    print("State path:", paths.state_path)
    print("Base URL:", config.get("base_url"))
    print("Sync dir:", os.path.expanduser(config.get("sync_dir", DEFAULT_SYNC_DIR)))
    print("Scan inbox:", config.get("scan_inbox_dir") or "— d\u00e9sactiv\u00e9 (pas de dossier configur\u00e9) —")
    print("Last sync:", state.get("last_sync_at"))
    print("Since:", state.get("since"))
    print("Tracked medias:", len(state.get("media_versions", {})))

    try:
        client = SyncClient(config=config, state=state, paths=paths)
        ping = client.ping()
        print("Ping:", "OK", "server_time=", ping.get("server_time"))
    except Exception as exc:  # noqa: BLE001
        print("Ping:", f"FAILED ({exc})")

    return 0


def cmd_watch_scan(paths: RuntimePaths) -> int:
    """Upload-only mode: watch scan_inbox_dir and push files to GED."""
    config = load_json(paths.config_path, {})
    if not config:
        log("No config found. Run: python sync_client.py init --base-url ... --token ...")
        return 1

    scan_dir = str(config.get("scan_inbox_dir", "")).strip()
    if not scan_dir:
        log("scan_inbox_dir non configur\u00e9. Utilisez 'init --scan-inbox-dir /chemin/vers/dossier'.")
        return 1

    state = load_json(paths.state_path, {"since": "", "media_versions": {}, "last_sync_at": None})
    client = SyncClient(config=config, state=state, paths=paths)

    running = True

    def _stop(_sig: int, _frame: Any) -> None:
        nonlocal running
        running = False
        log("Stop signal re\u00e7u. Arr\u00eat en cours...")

    signal.signal(signal.SIGINT, _stop)
    signal.signal(signal.SIGTERM, _stop)

    log(f"[watch-scan] Surveillance de : {client.scan_inbox_dir}")
    log(f"[watch-scan] Polling toutes les {DEFAULT_SCAN_POLL_SECONDS} secondes. Ctrl+C pour arr\u00eater.")

    tick = 0
    while running:
        time.sleep(1)
        tick += 1
        if tick % DEFAULT_SCAN_POLL_SECONDS == 0:
            try:
                client.run_scan_inbox_once()
            except Exception as exc:  # noqa: BLE001
                log(f"[watch-scan] Erreur: {exc}")

    return 0


def cmd_run(paths: RuntimePaths) -> int:
    config = load_json(paths.config_path, {})
    if not config:
        log("No config found. Run: python sync_client.py init --base-url ... --token ...")
        return 1

    state = load_json(paths.state_path, {"since": "", "media_versions": {}, "last_sync_at": None})

    client = SyncClient(config=config, state=state, paths=paths)
    return client.run_forever()


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="PLUSS.CI Desktop Sync Client")
    sub = parser.add_subparsers(dest="command", required=True)

    p_init = sub.add_parser("init", help="Create local config and test connection")
    p_init.add_argument("--base-url", required=True, help="Platform base URL (example: https://votre-domaine.tld)")
    p_init.add_argument("--token", required=True, help="Sync token generated from profile page")
    p_init.add_argument("--sync-dir", default=DEFAULT_SYNC_DIR, help="Local sync folder")
    p_init.add_argument("--poll-interval-minutes", type=int, default=DEFAULT_POLL_MINUTES)
    p_init.add_argument("--max-files-per-pull", type=int, default=DEFAULT_MAX_FILES)
    p_init.add_argument("--request-timeout-seconds", type=int, default=DEFAULT_TIMEOUT)
    p_init.add_argument("--insecure", action="store_true", help="Disable SSL certificate verification")
    p_init.add_argument(
        "--scan-inbox-dir",
        default="",
        help="Dossier local surveill\u00e9 pour l'envoi automatique des scans vers la GED (optionnel)",
    )

    sub.add_parser("status", help="Show local status and test ping")
    sub.add_parser("run", help="Run sync loop (GED download + scan upload if configured)")
    sub.add_parser("watch-scan", help="Upload-only mode: watch scan_inbox_dir and push files to GED")

    return parser


def main() -> int:
    parser = build_parser()
    args = parser.parse_args()
    paths = get_paths()

    if args.command == "init":
        return cmd_init(args, paths)

    if args.command == "status":
        return cmd_status(paths)

    if args.command == "run":
        return cmd_run(paths)

    if args.command == "watch-scan":
        return cmd_watch_scan(paths)

    parser.print_help()
    return 1


if __name__ == "__main__":
    sys.exit(main())
