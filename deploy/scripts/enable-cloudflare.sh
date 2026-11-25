#!/usr/bin/env bash
# One-time (safe to re-run): make host nginx see the real visitor behind Cloudflare.
#   1. /etc/nginx/conf.d/cloudflare-realip.conf  (trust CF ranges, use CF-Connecting-IP)
#   2. Sites pass the app exactly one IP: X-Forwarded-For $remote_addr
#      (instead of appending to whatever the visitor sent, which could be faked)
#   3. nginx -t, then reload. Nothing changes if the test fails.
# Usage: sudo bash deploy/scripts/enable-cloudflare.sh
set -euo pipefail

REPO="$(cd "$(dirname "$0")/../.." && pwd)"
SRC="$REPO/deploy/nginx/cloudflare-realip.conf"
DST="/etc/nginx/conf.d/cloudflare-realip.conf"
SITES=(/etc/nginx/sites-available/*)

[ "$(id -u)" -eq 0 ] || { echo "Run with sudo." >&2; exit 1; }
grep -q "conf.d/\*.conf" /etc/nginx/nginx.conf || { echo "nginx.conf does not include conf.d/*.conf; add the file to the http block by hand." >&2; exit 1; }

BACKUP="/root/nginx-backup-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP"
cp -a /etc/nginx/sites-available "$BACKUP/"
[ -f "$DST" ] && cp -a "$DST" "$BACKUP/"
echo "Backup: $BACKUP"

# Fresh ranges from Cloudflare if reachable; otherwise the copy in the repo.
TMP="$(mktemp)"
V4="$(curl -fsS --max-time 10 https://www.cloudflare.com/ips-v4 || true)"
V6="$(curl -fsS --max-time 10 https://www.cloudflare.com/ips-v6 || true)"
if [ -n "$V4" ] && [ -n "$V6" ]; then
  {
    echo "# Real visitor IP behind Cloudflare. Generated $(date -u +%F) by enable-cloudflare.sh"
    echo "real_ip_header CF-Connecting-IP;"
    echo "real_ip_recursive off;"
    for r in $V4 $V6; do echo "set_real_ip_from $r;"; done
  } > "$TMP"
  echo "Using live Cloudflare ranges ($(echo $V4 $V6 | wc -w) ranges)."
else
  cp "$SRC" "$TMP"
  echo "Cloudflare list not reachable; using the repo copy."
fi
install -m 644 "$TMP" "$DST"
rm -f "$TMP"

for f in "${SITES[@]}"; do
  [ -f "$f" ] || continue
  if grep -q 'X-Forwarded-For \$proxy_add_x_forwarded_for' "$f"; then
    sed -i 's/X-Forwarded-For \$proxy_add_x_forwarded_for/X-Forwarded-For $remote_addr/' "$f"
    echo "Updated: $f"
  fi
done

if nginx -t; then
  systemctl reload nginx
  echo "Done. nginx reloaded."
else
  echo "nginx -t failed: restoring backup." >&2
  rm -f "$DST"
  [ -f "$BACKUP/cloudflare-realip.conf" ] && cp -a "$BACKUP/cloudflare-realip.conf" "$DST"
  cp -a "$BACKUP/sites-available/." /etc/nginx/sites-available/
  nginx -t && systemctl reload nginx
  exit 1
fi
