#!/usr/bin/env bash
# Pull latest code on the VPS and verify CRM import feature files.
# Run from repo root: bash deploy/scripts/sync-code.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml"
EXPECTED_COMMIT="0d807f1"

echo "==> Git status (before pull)"
git log -1 --oneline
git status --short

if git status --porcelain | grep -qE '^.. deploy/docker-compose\.(prod|vps)\.yml$'; then
  echo "==> Discarding local-only compose edits (common VPS merge blocker)..."
  git checkout -- deploy/docker-compose.prod.yml deploy/docker-compose.vps.yml
fi

echo "==> Pulling latest from origin/master..."
git fetch origin
git pull origin master

HEAD="$(git rev-parse --short HEAD)"
echo "==> Now at: $(git log -1 --oneline)"

echo "==> Checking host files..."
grep -q "Import CSV" application/views/admin/contacts/index.php
grep -q "function import" application/controllers/admin/Contacts.php
grep -q "contacts/import" application/config/routes.php
echo "    Host: OK"

echo "==> Checking container mount..."
$COMPOSE exec -T app grep -q "Import CSV" /var/www/application/views/admin/contacts/index.php
$COMPOSE exec -T app grep -q "function import" /var/www/application/controllers/admin/Contacts.php
echo "    Container: OK"

echo "==> Restarting app (clear opcache)..."
$COMPOSE restart app

echo ""
echo "Deploy sync complete (HEAD=$HEAD)."
if [[ "$HEAD" != "$EXPECTED_COMMIT"* ]]; then
  echo "WARNING: expected commit starting with $EXPECTED_COMMIT — verify you pulled the import feature."
fi
echo ""
echo "Browser: https://admin.yourmechaniconline.com/contacts (hard refresh: Ctrl+Shift+R)"
echo "Direct:  https://admin.yourmechaniconline.com/contacts/import"
echo ""
echo "CLI import (after copying CSV to storage/import/ on the server):"
echo "  $COMPOSE exec app php index.php cli/crm import_contacts storage/import/contacts_master.csv merge_notes"
