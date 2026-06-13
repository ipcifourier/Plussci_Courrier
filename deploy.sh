#!/usr/bin/env bash
# =============================================================================
# deploy.sh — Script de déploiement PLUSSCI-Courrier (Plesk / Linux)
# Usage : bash deploy.sh [--skip-assets] [--skip-migrate]
# =============================================================================
set -euo pipefail

# ─── Configuration ────────────────────────────────────────────────────────────
APP_DIR="/var/www/vhosts/pro.pluss.ci/httpdocs"
PHP_BIN="php8.2"                     # Adapter selon la version Plesk disponible
COMPOSER_BIN="composer"
GIT_BRANCH="main"

# ─── Flags optionnels ─────────────────────────────────────────────────────────
SKIP_ASSETS=false
SKIP_MIGRATE=false

for arg in "$@"; do
  case $arg in
    --skip-assets)   SKIP_ASSETS=true ;;
    --skip-migrate)  SKIP_MIGRATE=true ;;
  esac
done

# ─── Helpers ──────────────────────────────────────────────────────────────────
step() { echo ""; echo "▶  $1"; echo "───────────────────────────────────────────"; }
ok()   { echo "✔  $1"; }
warn() { echo "⚠  $1"; }

# =============================================================================
step "0. Pré-vérifications"
# =============================================================================
if [ ! -f "$APP_DIR/.env" ]; then
  echo "❌  Fichier .env introuvable dans $APP_DIR"
  echo "    Copier .env.production vers .env et compléter les valeurs obligatoires."
  exit 1
fi

APP_ENV_VALUE=$(grep -E "^APP_ENV=" "$APP_DIR/.env" | cut -d= -f2 | tr -d '[:space:]')
if [ "$APP_ENV_VALUE" != "production" ]; then
  warn "APP_ENV=$APP_ENV_VALUE — assurez-vous que .env est configuré pour la production."
fi

ok "Pré-vérifications passées"

# =============================================================================
step "1. Mode maintenance ON"
# =============================================================================
cd "$APP_DIR"
$PHP_BIN artisan down --retry=60 --refresh=15 --render="errors::503"
ok "Application mise en maintenance"

# =============================================================================
step "2. Pull Git ($GIT_BRANCH)"
# =============================================================================
git fetch --all
git checkout "$GIT_BRANCH"
git pull origin "$GIT_BRANCH"
ok "Code mis à jour depuis origin/$GIT_BRANCH"

# =============================================================================
step "3. Dépendances PHP (production)"
# =============================================================================
$COMPOSER_BIN install \
  --no-interaction \
  --no-dev \
  --prefer-dist \
  --optimize-autoloader
ok "Dépendances Composer installées"

# =============================================================================
step "4. Assets frontend"
# =============================================================================
if [ "$SKIP_ASSETS" = false ]; then
  if [ -d "node_modules" ]; then
    npm ci --silent
  else
    npm install --silent
  fi
  npm run build
  ok "Assets Vite compilés"
else
  warn "Compilation des assets ignorée (--skip-assets)"
fi

# =============================================================================
step "5. Migrations base de données"
# =============================================================================
if [ "$SKIP_MIGRATE" = false ]; then
  $PHP_BIN artisan migrate --force
  ok "Migrations exécutées"
else
  warn "Migrations ignorées (--skip-migrate)"
fi

# =============================================================================
step "6. Optimisation Laravel (cache config/routes/views)"
# =============================================================================
$PHP_BIN artisan config:cache
$PHP_BIN artisan route:cache
$PHP_BIN artisan view:cache
$PHP_BIN artisan icons:cache    2>/dev/null || true   # Filament icons
$PHP_BIN artisan filament:cache-components 2>/dev/null || true
ok "Caches Laravel régénérés"

# =============================================================================
step "7. Lien storage public"
# =============================================================================
if [ ! -L "$APP_DIR/public/storage" ]; then
  $PHP_BIN artisan storage:link
  ok "Lien symbolique storage créé"
else
  ok "Lien symbolique storage déjà présent"
fi

# =============================================================================
step "8. Redémarrage workers Queue"
# =============================================================================
$PHP_BIN artisan queue:restart
ok "Signal queue:restart envoyé"

# =============================================================================
step "9. Permissions fichiers"
# =============================================================================
find "$APP_DIR/storage" -type d -exec chmod 775 {} \;
find "$APP_DIR/storage" -type f -exec chmod 664 {} \;
find "$APP_DIR/bootstrap/cache" -type d -exec chmod 775 {} \;
find "$APP_DIR/bootstrap/cache" -type f -exec chmod 664 {} \;
ok "Permissions storage/bootstrap/cache corrigées"

# =============================================================================
step "10. Mode maintenance OFF"
# =============================================================================
$PHP_BIN artisan up
ok "Application remise en ligne"

# =============================================================================
echo ""
echo "════════════════════════════════════════════════════════"
echo "  ✔  Déploiement terminé — https://pro.pluss.ci"
echo "════════════════════════════════════════════════════════"
echo ""
echo "  Vérifications post-déploiement recommandées :"
echo "  → php artisan migrate:status"
echo "  → php artisan route:list --path=sync-client"
echo "  → php artisan queue:monitor database:default --max=100"
echo ""
