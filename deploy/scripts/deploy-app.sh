#!/usr/bin/env bash
# Deploy / update YMO on the VPS (run from repo root).
# Prerequisites: Docker, .env configured, optional vendor/ from composer install.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml"

if [[ ! -f .env ]]; then
  echo "Missing .env — copy from .env.example and set production values." >&2
  exit 1
fi

echo "==> Ensuring upload directories..."
mkdir -p storage/logs \
  public/uploads/vehicles \
  public/uploads/crm/resumes \
  public/uploads/invoices
chmod -R u+rwX,g+rwX storage public/uploads 2>/dev/null || true

if [[ ! -d vendor/dompdf ]] && command -v docker >/dev/null 2>&1; then
  echo "==> Installing Composer dependencies (dompdf) via Docker..."
  docker run --rm -v "$ROOT:/app" -w /app composer:2 install --no-dev --no-interaction
fi

echo "==> Building and starting containers..."
$COMPOSE up -d --build

echo "==> Waiting for MySQL (healthcheck)..."
for i in $(seq 1 45); do
  if $COMPOSE ps db 2>/dev/null | grep -q healthy; then
    break
  fi
  sleep 2
done

echo ""
echo "App is listening on 127.0.0.1:8080 (nginx should proxy to this)."
echo ""
echo "Fresh database (only if empty):"
echo "  $COMPOSE exec -T db mysql -u\${YMO_DB_USER} -p\${YMO_DB_PASS} \${YMO_DB_NAME} < database/schema.sql"
echo "  $COMPOSE exec -T db mysql -u\${YMO_DB_USER} -p\${YMO_DB_PASS} \${YMO_DB_NAME} < database/seed.sql"
echo ""
echo "Existing DB migrations (run once each if upgrading):"
echo "  $COMPOSE exec -T db mysql ... < database/crm_migration_v1.sql"
echo "  $COMPOSE exec -T db mysql ... < database/crm_migration_v2_team_rbac.sql"
echo "  $COMPOSE exec -T db mysql ... < database/booking_invoice_migration_v1.sql"
echo ""
echo "Create admin:"
echo "  $COMPOSE exec app php index.php cli/install create_admin admin@yourcompany.com \"Admin Name\""
