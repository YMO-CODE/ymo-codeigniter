#!/usr/bin/env bash
# Download the free DB-IP "IP to City Lite" database (MaxMind DB format) used by
# application/libraries/City_geo.php to pick the visitor's city (Pune / Indore / Nashik).
#
# Licence: CC BY 4.0 — attribution "IP geolocation by DB-IP" is shown in the site footer.
# DB-IP publishes a new file at the start of each month; this tries this month, then last month.
#
# Usage (on the VM):   bash deploy/scripts/update-geoip.sh
# Cron (monthly):      see deploy/cron/ymo.crontab.example
set -euo pipefail

ROOT="${ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)}"
DIR="$ROOT/storage/geo"
DEST="$DIR/dbip-city-lite.mmdb"
mkdir -p "$DIR"

tmp="$(mktemp "$DIR/.dbip.XXXXXX")"
trap 'rm -f "$tmp" "$tmp.gz"' EXIT

for month in "$(date -u +%Y-%m)" "$(date -u -d '-1 month' +%Y-%m)"; do
    url="https://download.db-ip.com/free/dbip-city-lite-${month}.mmdb.gz"
    if curl -fsSL --retry 2 --max-time 300 "$url" -o "$tmp.gz"; then
        gunzip -c "$tmp.gz" > "$tmp"
        size=$(stat -c %s "$tmp")
        if [ "$size" -lt 10000000 ]; then
            echo "$(date -u +%FT%TZ) downloaded file looks too small ($size bytes), keeping the old one" >&2
            exit 1
        fi
        chmod 644 "$tmp"
        mv -f "$tmp" "$DEST"
        echo "$(date -u +%FT%TZ) installed DB-IP City Lite ${month} ($size bytes) -> $DEST"
        exit 0
    fi
done

echo "$(date -u +%FT%TZ) could not download DB-IP City Lite; keeping existing file" >&2
exit 1
