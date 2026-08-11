#!/usr/bin/env bash
# Send a test SMS via MSG91 Flow API (OTP template by default).
# Usage: bash deploy/scripts/smoke-sms.sh 9876543210 [otp|booking_confirmed|...]
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

MOBILE="${1:-}"
TEMPLATE="${2:-otp}"

if [ -z "$MOBILE" ]; then
  echo "Usage: bash deploy/scripts/smoke-sms.sh MOBILE [template_key]"
  echo "  template_key: otp (default), booking_confirmed, booking_status, crm_campaign, ..."
  exit 1
fi

COMPOSE="docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml"
if [ "$TEMPLATE" = "all" ]; then
  if [ -f "deploy/docker-compose.vps.yml" ]; then
    $COMPOSE exec -T app php index.php cli/sms test_all "$MOBILE"
  else
    php public/index.php cli/sms test_all "$MOBILE"
  fi
elif [ -f "deploy/docker-compose.vps.yml" ]; then
  $COMPOSE exec -T app php index.php cli/sms test "$TEMPLATE" "$MOBILE"
else
  php public/index.php cli/sms test "$TEMPLATE" "$MOBILE"
fi
