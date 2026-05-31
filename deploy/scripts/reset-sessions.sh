#!/usr/bin/env bash
# Bump deploy session epoch and clear ci_sessions so all users re-login after deploy.
# Run from repo root: bash deploy/scripts/reset-sessions.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml"
EPOCH="$(git rev-parse HEAD 2>/dev/null || echo "manual-$(date +%s)")"
EPOCH_FILE="$ROOT/storage/.session_epoch"

mkdir -p "$ROOT/storage"
printf '%s' "$EPOCH" > "$EPOCH_FILE"
chmod 644 "$EPOCH_FILE" 2>/dev/null || true

echo "==> Session epoch: ${EPOCH:0:12}..."
echo "    Written to storage/.session_epoch"

if $COMPOSE ps db 2>/dev/null | grep -q -E 'healthy|running'; then
  if $COMPOSE exec -T db sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "TRUNCATE TABLE ci_sessions;"' 2>/dev/null; then
    echo "==> Cleared ci_sessions (all users signed out)"
  else
    echo "==> Could not truncate ci_sessions (epoch file still invalidates stale sessions on next request)"
  fi
else
  echo "==> Database not running — epoch file updated; stale sessions cleared on next request"
fi
