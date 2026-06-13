@echo off
setlocal
cd /d %~dp0

echo [1/5] Create build virtual environment
if not exist .venv-build (
  py -m venv .venv-build
)

call .venv-build\Scripts\activate.bat
if errorlevel 1 exit /b 1

echo [2/5] Install build dependencies
python -m pip install --upgrade pip
python -m pip install -r requirements-build.txt
if errorlevel 1 exit /b 1

echo [3/5] Build Windows executable
pyinstaller --noconfirm --clean --onefile --name plussci-sync-client sync_client.py
if errorlevel 1 exit /b 1

echo [4/5] Prepare distribution folder
if exist dist\package-windows rmdir /s /q dist\package-windows
mkdir dist\package-windows
copy /Y dist\plussci-sync-client.exe dist\package-windows\plussci-sync-client.exe >nul
copy /Y config.example.json dist\package-windows\config.example.json >nul
copy /Y README.md dist\package-windows\README.md >nul

echo [5/5] Done
echo Output: dist\package-windows\
powershell -NoProfile -Command "if (Test-Path 'dist\\plussci-sync-client-windows.zip') { Remove-Item 'dist\\plussci-sync-client-windows.zip' -Force }; Compress-Archive -Path 'dist\\package-windows\\*' -DestinationPath 'dist\\plussci-sync-client-windows.zip'"
echo Archive: dist\plussci-sync-client-windows.zip
