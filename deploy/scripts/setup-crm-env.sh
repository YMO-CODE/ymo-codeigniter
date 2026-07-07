#!/usr/bin/env bash
# Generate CRM webhook secrets and optionally merge into .env on the VPS.
# Run from repo root: bash deploy/scripts/setup-crm-env.sh [--write]
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

ENV_FILE="${ENV_FILE:-$ROOT/.env}"
WRITE=0
if [ "${1:-}" = "--write" ]; then
  WRITE=1
fi

WEBHOOK_SECRET="$(openssl rand -hex 32)"
VERIFY_TOKEN="$(openssl rand -hex 16)"
PAGE_ID="${CRM_META_PAGE_ID:-548089742374214}"

echo "==> CRM integration secrets (generated $(date -u +%Y-%m-%dT%H:%M:%SZ))"
echo ""
echo "# Add or update these in $ENV_FILE:"
echo "CRM_WEBHOOK_SECRET=$WEBHOOK_SECRET"
echo "CRM_META_VERIFY_TOKEN=$VERIFY_TOKEN"
echo "CRM_META_ACCESS_TOKEN="
echo "CRM_WHATSAPP_PHONE_NUMBER_ID="
echo "CRM_META_PAGE_ID=$PAGE_ID"
echo ""
echo "Use CRM_META_VERIFY_TOKEN in Meta Developer → Webhooks (Page + WhatsApp)."
echo "After setting CRM_META_ACCESS_TOKEN and CRM_WHATSAPP_PHONE_NUMBER_ID, restart app:"
echo "  docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml up -d --force-recreate app"
echo ""

if [ "$WRITE" -eq 0 ]; then
  echo "Dry run only. To merge into .env: bash deploy/scripts/setup-crm-env.sh --write"
  exit 0
fi

touch "$ENV_FILE"

set_kv() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" "$ENV_FILE" 2>/dev/null; then
    if [ "$(uname)" = "Darwin" ]; then
      sed -i '' "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
    else
      sed -i "s|^${key}=.*|${key}=${val}|" "$ENV_FILE"
    fi
  else
    echo "${key}=${val}" >> "$ENV_FILE"
  fi
}

set_kv CRM_WEBHOOK_SECRET "$WEBHOOK_SECRET"
set_kv CRM_META_VERIFY_TOKEN "$VERIFY_TOKEN"
set_kv CRM_META_PAGE_ID "$PAGE_ID"

if ! grep -q '^CRM_META_ACCESS_TOKEN=' "$ENV_FILE" 2>/dev/null; then
  echo "CRM_META_ACCESS_TOKEN=" >> "$ENV_FILE"
fi
if ! grep -q '^CRM_WHATSAPP_PHONE_NUMBER_ID=' "$ENV_FILE" 2>/dev/null; then
  echo "CRM_WHATSAPP_PHONE_NUMBER_ID=" >> "$ENV_FILE"
fi

echo "==> Updated $ENV_FILE (secrets only — fill Meta tokens manually)."
echo "==> Verify token for Meta console: $VERIFY_TOKEN"
