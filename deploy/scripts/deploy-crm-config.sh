#!/usr/bin/env bash
# One-shot CRM Meta/WhatsApp env setup on the VPS (run after git pull / sync-code).
# Usage: bash deploy/scripts/deploy-crm-config.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml"

echo "==> 1/5 Generate CRM secrets (.env)"
bash deploy/scripts/setup-crm-env.sh --write

echo ""
echo "==> 2/5 Recreate app container (load .env)"
$COMPOSE up -d --force-recreate app

echo ""
echo "==> 3/5 Webhook GET verify (local — needs CRM_META_VERIFY_TOKEN in .env)"
bash deploy/scripts/test-crm-webhook-verify.sh

echo ""
echo "==> 4/5 Webhook smoke POST tests"
bash deploy/scripts/smoke-webhooks.sh

echo ""
echo "==> 5/5 CRM v3 automated verify"
$COMPOSE exec -T app php index.php cli/verify_crm v3

echo ""
echo "==> Next: complete Meta Developer setup (Page token + WhatsApp phone ID)"
echo "    See deploy/scripts/META_CRM_CHECKLIST.md"
echo ""
echo "After setting CRM_META_ACCESS_TOKEN and CRM_WHATSAPP_PHONE_NUMBER_ID in .env:"
echo "  $COMPOSE up -d --force-recreate app"
