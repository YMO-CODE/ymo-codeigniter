#!/usr/bin/env bash
# Smoke-test CRM webhook endpoints (run from repo root on VPS after .env is configured).
# Does not require Meta/WhatsApp — sends sample payloads to local app container.
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

COMPOSE="docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml"
BASE="${YMO_PUBLIC_APP_URL:-https://booking.yourmechaniconline.com}"

echo "==> Webhook smoke tests (base: $BASE)"
echo ""

if [ -f .env ] && grep -q '^CRM_META_VERIFY_TOKEN=' .env && [ -n "$(grep '^CRM_META_VERIFY_TOKEN=' .env | cut -d= -f2- | tr -d '\r')" ]; then
  echo "0. Webhook GET verify (Meta + WhatsApp)"
  bash deploy/scripts/test-crm-webhook-verify.sh || true
  echo ""
fi

echo "1. Meta Instagram DM sample (POST /api/webhooks/meta)"
curl -sS -X POST "$BASE/api/webhooks/meta" \
  -H "Content-Type: application/json" \
  -d '{
    "object": "page",
    "entry": [{
      "messaging": [{
        "sender": {"id": "test_ig_sender_001"},
        "message": {"mid": "test_mid_001", "text": "Smoke test from Instagram DM webhook"}
      }]
    }]
  }'
echo ""
echo ""

if [ -f .env ] && grep -q '^CRM_WEBHOOK_SECRET=' .env && [ -n "$(grep '^CRM_WEBHOOK_SECRET=' .env | cut -d= -f2-)" ]; then
  SECRET="$(grep '^CRM_WEBHOOK_SECRET=' .env | cut -d= -f2-)"
  BODY='{"from":"9199999999999","text":"Smoke test WhatsApp","message_id":"smoke_wa_001"}'
  SIG=$(printf '%s' "$BODY" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')
  echo "2. WhatsApp flat payload (signed)"
  curl -sS -X POST "$BASE/api/webhooks/whatsapp" \
    -H "Content-Type: application/json" \
    -H "X-CRM-Signature: sha256=$SIG" \
    -d "$BODY"
  echo ""
  echo "3. WhatsApp Meta Cloud payload (unsigned — must succeed when CRM_WEBHOOK_SECRET is set)"
  curl -sS -X POST "$BASE/api/webhooks/whatsapp" \
    -H "Content-Type: application/json" \
    -d '{
      "object": "whatsapp_business_account",
      "entry": [{
        "changes": [{
          "field": "messages",
          "value": {
            "contacts": [{"wa_id": "9198888777766", "profile": {"name": "Smoke Test"}}],
            "messages": [{
              "from": "9198888777766",
              "id": "smoke_wa_meta_001",
              "type": "text",
              "text": {"body": "Smoke test Meta Cloud WhatsApp"}
            }]
          }
        }]
      }]
    }'
  echo ""
else
  echo "2. WhatsApp — skipped signed test (set CRM_WEBHOOK_SECRET in .env)"
  curl -sS -X POST "$BASE/api/webhooks/whatsapp" \
    -H "Content-Type: application/json" \
    -d '{"from":"9199999999999","text":"Smoke test WhatsApp unsigned","message_id":"smoke_wa_002"}'
  echo ""
fi

echo ""
echo "==> Check Admin → Leads and Reports → Recent webhook activity"
echo "==> Two-way chat: open the lead → use WhatsApp/Instagram chat panel to reply (requires CRM_META_ACCESS_TOKEN + CRM_WHATSAPP_PHONE_NUMBER_ID in .env)"
echo "==> Automated verify: $COMPOSE exec app php index.php cli/verify_crm v3"
