#!/usr/bin/env bash
# Test Meta/WhatsApp webhook GET verification against production or local base URL.
# Run from repo root: bash deploy/scripts/test-crm-webhook-verify.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

BASE="${YMO_PUBLIC_APP_URL:-https://booking.yourmechaniconline.com}"
TOKEN=""
if [ -f .env ] && grep -q '^CRM_META_VERIFY_TOKEN=' .env; then
  TOKEN="$(grep '^CRM_META_VERIFY_TOKEN=' .env | cut -d= -f2- | tr -d '\r')"
fi

if [ -z "$TOKEN" ]; then
  echo "ERROR: CRM_META_VERIFY_TOKEN not set in .env"
  echo "Run: bash deploy/scripts/setup-crm-env.sh --write"
  exit 1
fi

CHALLENGE="crm_verify_$(date +%s)"
QS="hub.mode=subscribe&hub.verify_token=${TOKEN}&hub.challenge=${CHALLENGE}"

echo "==> Meta webhook verify ($BASE/api/webhooks/meta)"
META_OUT="$(curl -sS "$BASE/api/webhooks/meta?$QS")"
if [ "$META_OUT" = "$CHALLENGE" ]; then
  echo "    OK — challenge echoed"
else
  echo "    FAIL — expected: $CHALLENGE"
  echo "    Got: $META_OUT"
  exit 1
fi

echo "==> WhatsApp webhook verify ($BASE/api/webhooks/whatsapp)"
WA_OUT="$(curl -sS "$BASE/api/webhooks/whatsapp?$QS")"
if [ "$WA_OUT" = "$CHALLENGE" ]; then
  echo "    OK — challenge echoed"
else
  echo "    FAIL — expected: $CHALLENGE"
  echo "    Got: $WA_OUT"
  exit 1
fi

echo ""
echo "Both endpoints ready for Meta Developer console verification."
