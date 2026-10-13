#!/usr/bin/env bash
# Run marketing SEO PHP reports inside the app container (VPS).
# Usage (from repo root): bash deploy/marketing/scripts/run-reports-in-docker.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml"
PHP="${COMPOSE} exec -T app php"

export SIMILARITY_MODE="${SIMILARITY_MODE:-strict}"

for script in meta-length-report.php similarity-report.php inlinks-report.php; do
  path="/var/www/deploy/marketing/scripts/${script}"
  echo "==> ${script}"
  if ! ${COMPOSE} exec -T app test -f "${path}"; then
    echo "    Missing ${path} — git pull and recreate app (deploy volume mount)." >&2
    exit 1
  fi
  ${PHP} "${path}" || true
  echo ""
done
