#!/bin/bash

# ============================================================
# AOS Incremental Deploy
# Pushes only files changed since last deploy.
# Run from Git Bash at project root:  bash deploy.sh
# ============================================================

set -e

# ── Config ───────────────────────────────────────────────────
SERVER_IP="192.168.41.204"
SERVER_USER="support"
SERVER_WEB_ROOT="/var/www/html"
PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
LAST_DEPLOY_FILE="$PROJECT_ROOT/.last_deploy_commit"

# ── Colors ───────────────────────────────────────────────────
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; RED='\033[0;31m'; NC='\033[0m'
log()  { echo -e "${CYAN}[deploy]${NC} $1"; }
ok()   { echo -e "${GREEN}[  OK  ]${NC} $1"; }
warn() { echo -e "${YELLOW}[ WARN ]${NC} $1"; }
err()  { echo -e "${RED}[ FAIL ]${NC} $1"; exit 1; }

# ── SSH password (entered once, reused everywhere) ───────────
read -rsp "SSH password for ${SERVER_USER}@${SERVER_IP}: " SSH_PASS; echo ""

if ! command -v sshpass &>/dev/null; then
    warn "sshpass not found — you'll be prompted for the password each time."
    warn "To install: open Git Bash as admin and run: pacman -S sshpass"
    ssh_run()  { ssh  -o StrictHostKeyChecking=no "${SERVER_USER}@${SERVER_IP}" "$1"; }
    scp_file() { scp  -o StrictHostKeyChecking=no "$1" "${SERVER_USER}@${SERVER_IP}:$2"; }
else
    ssh_run()  { sshpass -p "$SSH_PASS" ssh  -o StrictHostKeyChecking=no "${SERVER_USER}@${SERVER_IP}" "$1"; }
    scp_file() { sshpass -p "$SSH_PASS" scp  -o StrictHostKeyChecking=no "$1" "${SERVER_USER}@${SERVER_IP}:$2"; }
fi

# ── Detect changed files since last deploy ───────────────────
CURRENT_COMMIT=$(git -C "$PROJECT_ROOT" rev-parse HEAD)

if [ -f "$LAST_DEPLOY_FILE" ]; then
    LAST_COMMIT=$(cat "$LAST_DEPLOY_FILE")
    if [ "$LAST_COMMIT" = "$CURRENT_COMMIT" ]; then
        ok "Already up to date (commit: ${CURRENT_COMMIT:0:8}). Nothing to deploy."
        exit 0
    fi
    CHANGED=$(git -C "$PROJECT_ROOT" diff --name-only "$LAST_COMMIT" "$CURRENT_COMMIT")
else
    warn "No previous deploy recorded — diffing against last commit."
    CHANGED=$(git -C "$PROJECT_ROOT" diff --name-only HEAD~1 HEAD 2>/dev/null || git -C "$PROJECT_ROOT" ls-files)
fi

if [ -z "$CHANGED" ]; then
    ok "No changed files found. Exiting."
    exit 0
fi

echo ""
echo "Changed files since last deploy:"
echo "$CHANGED" | sed 's/^/  /'
echo ""

# ── Split by area ─────────────────────────────────────────────
BACKEND_FILES=$(echo "$CHANGED" | grep '^backend/' | grep -v '^backend/vendor/' || true)
FRONTEND_FILES=$(echo "$CHANGED" | grep '^frontend/src/' || true)

# ============================================================
# BACKEND — copy only the changed files
# ============================================================
if [ -n "$BACKEND_FILES" ]; then
    log "Deploying changed backend files..."

    while IFS= read -r file; do
        local_path="$PROJECT_ROOT/$file"

        # File was deleted in git — remove it on server too
        if [ ! -f "$local_path" ]; then
            remote_rel="${file#backend/}"
            warn "  Removing deleted file on server: $file"
            ssh_run "rm -f '$SERVER_WEB_ROOT/backend/$remote_rel'" || true
            continue
        fi

        remote_rel="${file#backend/}"
        remote_path="$SERVER_WEB_ROOT/backend/$remote_rel"
        remote_dir=$(dirname "$remote_path")

        log "  Uploading: $file"
        ssh_run "mkdir -p '$remote_dir'"
        scp_file "$local_path" "$remote_path"

    done <<< "$BACKEND_FILES"

    # Clear caches after any backend update
    log "Clearing Laravel caches..."
    ssh_run "cd $SERVER_WEB_ROOT/backend && php artisan config:cache && php artisan route:cache && php artisan view:cache" || \
        warn "Cache clear failed — check artisan on the server."

    # Always run migrate — only applies pending migrations, never removes existing ones
    log "Running pending migrations..."
    ssh_run "cd $SERVER_WEB_ROOT/backend && php artisan migrate --force"

    ok "Backend updated."
else
    log "No backend changes."
fi

# ============================================================
# FRONTEND — rebuild and replace dist if src changed
# ============================================================
if [ -n "$FRONTEND_FILES" ]; then
    log "Frontend source changed — rebuilding..."
    cd "$PROJECT_ROOT/frontend"
    npm run build || err "Frontend build failed."
    ok "Build complete → dist/"

    log "Zipping dist..."
    TMP_ZIP="/tmp/aos_frontend_$$.zip"
    (cd "$PROJECT_ROOT/frontend/dist" && zip -r "$TMP_ZIP" . -x "*.map" > /dev/null)
    ok "Created zip ($(du -sh "$TMP_ZIP" | cut -f1))"

    log "Uploading to server..."
    REMOTE_ZIP="/tmp/aos_frontend_$$.zip"
    scp_file "$TMP_ZIP" "$REMOTE_ZIP"

    log "Replacing frontend on server..."
    ssh_run "
        set -e

        # Remove old frontend files, leave backend/ untouched
        find $SERVER_WEB_ROOT -maxdepth 1 \
            ! -name 'backend' \
            ! -path '$SERVER_WEB_ROOT' \
            -exec rm -rf {} + 2>/dev/null || true

        # Extract new build
        unzip -o '$REMOTE_ZIP' -d '$SERVER_WEB_ROOT' > /dev/null

        # Write .htaccess
        cat > '$SERVER_WEB_ROOT/.htaccess' << 'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule ^ - [L]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]
    RewriteCond %{REQUEST_URI} ^/api/
    RewriteRule ^api/(.*)$ /backend/index.php [QSA,L]
    RewriteRule ^ index.html [L]
</IfModule>
<IfModule mod_mime.c>
    AddType application/javascript .js
    AddType text/css .css
    AddType application/wasm .wasm
</IfModule>
HTACCESS

        rm -f '$REMOTE_ZIP'
    "

    rm -f "$TMP_ZIP"
    ok "Frontend updated."
else
    log "No frontend source changes."
fi

# ── Save this commit as the new baseline ─────────────────────
echo "$CURRENT_COMMIT" > "$LAST_DEPLOY_FILE"
echo ""
ok "Done. Deployed commit ${CURRENT_COMMIT:0:8}."
echo ""
