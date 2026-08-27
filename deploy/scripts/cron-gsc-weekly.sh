#!/usr/bin/env bash
# Weekly GSC fetch + SEO audit. Install on VPS cron:
#   30 0 * * 1 /opt/ymo-codeigniter/deploy/scripts/cron-gsc-weekly.sh >> /var/log/ymo-gsc.log 2>&1
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

PYTHON="${PYTHON:-python3}"
LOG_PREFIX="[ymo-gsc $(date -u +%Y-%m-%dT%H:%M:%SZ)]"

echo "$LOG_PREFIX starting weekly SEO audit"

if [[ -f "$ROOT/.env" ]]; then
  set -a
  # shellcheck disable=SC1091
  source <(grep -E '^YMO_GSC_|^GOOGLE_APPLICATION_CREDENTIALS=' "$ROOT/.env" | sed 's/\r$//')
  set +a
fi

"$PYTHON" -m pip install -q -r deploy/marketing/requirements-gsc.txt 2>/dev/null || true
"$PYTHON" deploy/marketing/scripts/weekly_seo_audit.py
echo "$LOG_PREFIX done exit=$?"
