#!/usr/bin/env bash
# Daily backup: MySQL dump + uploads tarball. Run from repo root via cron.
# The DB dump is also copied off the server to the private R2 bucket when R2 is
# configured in .env (php index.php cli/media backup), keeping R2_RETAIN_DAYS there.
# Usage: bash deploy/scripts/backup.sh
# Env: BACKUP_DIR (default /var/backups/ymo), RETAIN_DAYS (default 14), R2_RETAIN_DAYS (default 30)
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

BACKUP_DIR="${BACKUP_DIR:-/var/backups/ymo}"
RETAIN_DAYS="${RETAIN_DAYS:-14}"
STAMP="$(date +%Y%m%d-%H%M%S)"
DEST="${BACKUP_DIR}/${STAMP}"

COMPOSE="docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml"

if [[ ! -f .env ]]; then
  echo "Missing .env in $ROOT" >&2
  exit 1
fi

mkdir -p "$DEST"

echo "==> MySQL dump..."
$COMPOSE exec -T db sh -c 'mysqldump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --single-transaction --routines --triggers "$MYSQL_DATABASE"' \
  | gzip > "${DEST}/ymo_booking.sql.gz"

echo "==> Uploads archive..."
tar -czf "${DEST}/uploads.tar.gz" -C "$ROOT/public" uploads 2>/dev/null || true

echo "==> Off-site copy to Cloudflare R2..."
mkdir -p "$ROOT/storage/backups"
OFFSITE="ymo_booking-${STAMP}.sql.gz"
cp "${DEST}/ymo_booking.sql.gz" "$ROOT/storage/backups/${OFFSITE}"
$COMPOSE exec -T app php index.php cli/media backup "${OFFSITE}" "${R2_RETAIN_DAYS:-30}" || echo "WARN: R2 upload failed; local backup is still in ${DEST}" >&2
rm -f "$ROOT/storage/backups/${OFFSITE}"

echo "==> Pruning backups older than ${RETAIN_DAYS} days..."
find "$BACKUP_DIR" -mindepth 1 -maxdepth 1 -type d -mtime +"$RETAIN_DAYS" -exec rm -rf {} + 2>/dev/null || true

echo "Backup saved to ${DEST}"
