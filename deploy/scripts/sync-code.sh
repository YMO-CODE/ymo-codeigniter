#!/usr/bin/env bash
# Pull latest code on the VPS and verify CRM deploy.
# Run from repo root: bash deploy/scripts/sync-code.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml"

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
grep -q "Customers" application/views/admin/contacts/index.php
grep -q "hot_lead" application/models/Crm_lead_model.php
grep -q "customers/bulk-edit" application/config/routes.php
grep -q "crm_migration_v3_flow" database/crm_migration_v3_flow.sql
echo "    Host: OK"

echo "==> Checking container mount..."
$COMPOSE exec -T app grep -q "Customers" /var/www/application/views/admin/contacts/index.php
$COMPOSE exec -T app grep -q "hot_lead" /var/www/application/models/Crm_lead_model.php
echo "    Container: OK"

echo "==> Restarting app (clear opcache)..."
$COMPOSE restart app

echo ""
echo "==> Resetting sessions (post-deploy sign-out)..."
bash "$ROOT/deploy/scripts/reset-sessions.sh"

echo ""
echo "Deploy sync complete (HEAD=$HEAD)."
echo ""
echo "If upgrading from pre-v3 CRM, run once:"
echo "  docker compose exec -T db mysql -u root -p\"\$MYSQL_ROOT_PASSWORD\" ymo < database/crm_migration_v3_flow.sql"
echo ""
echo "Browser: https://admin.yourmechaniconline.com/customers (hard refresh: Ctrl+Shift+R)"
echo "Pipeline: https://admin.yourmechaniconline.com/leads/pipeline"
echo "Reports:  https://admin.yourmechaniconline.com/reports"
echo ""
echo "CLI import (after copying CSV to storage/import/ on the server):"
echo "  $COMPOSE exec app php index.php cli/crm import_contacts storage/import/contacts_master.csv merge_notes"
