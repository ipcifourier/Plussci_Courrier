#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

echo "[1/5] Create build virtual environment"
if [ ! -d ".venv-build" ]; then
  python3 -m venv .venv-build
fi

source .venv-build/bin/activate

echo "[2/5] Install build dependencies"
python -m pip install --upgrade pip
python -m pip install -r requirements-build.txt

echo "[3/5] Build macOS CLI binary"
pyinstaller --noconfirm --clean --onefile --name plussci-sync-client sync_client.py

echo "[4/5] Build macOS app bundle (.app)"
pyinstaller --noconfirm --clean --windowed --name PLUSSCISyncClient sync_client_app.py

echo "[5/5] Prepare distribution folder"
rm -rf dist/package-macos
mkdir -p dist/package-macos
cp -f dist/plussci-sync-client dist/package-macos/plussci-sync-client
cp -f config.example.json dist/package-macos/config.example.json
cp -f README.md dist/package-macos/README.md
cp -R dist/PLUSSCISyncClient.app dist/package-macos/PLUSSCISyncClient.app

echo "Done"
echo "Output: dist/package-macos/"
tar -czf dist/plussci-sync-client-macos.tar.gz -C dist/package-macos .
echo "Archive: dist/plussci-sync-client-macos.tar.gz"
