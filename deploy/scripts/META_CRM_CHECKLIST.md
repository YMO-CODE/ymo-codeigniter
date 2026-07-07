# Meta CRM setup checklist (Lead Forms + WhatsApp)

Run on the VPS after `bash deploy/scripts/setup-crm-env.sh --write` and app restart.

## 1. Env on VPS

```bash
cd /opt/ymo-codeigniter
bash deploy/scripts/setup-crm-env.sh --write
docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml up -d --force-recreate app
bash deploy/scripts/test-crm-webhook-verify.sh
```

Copy `CRM_META_VERIFY_TOKEN` from `.env` — use the **same value** in Meta for Page and WhatsApp webhooks.

## 2. Meta Developer App — Lead Forms

1. [developers.facebook.com](https://developers.facebook.com) → **Create App** → **Business**
2. Add product **Webhooks**
3. **Webhooks → Page → Configure**:
   - Callback URL: `https://booking.yourmechaniconline.com/api/webhooks/meta`
   - Verify token: value of `CRM_META_VERIFY_TOKEN` from `.env`
   - Click **Verify and Save**
   - Subscribe field: **`leadgen`**
4. Subscribe Page **Your Mechanic Online** (ID `548089742374214`)
5. Generate **Page access token** with `leads_retrieval`, `whatsapp_business_messaging`, `pages_messaging`:
   - Business Settings → System Users → Generate token → assign Page + WhatsApp assets
6. Set in `.env`: `CRM_META_ACCESS_TOKEN=<token>` → restart app

Test:

```bash
curl "https://booking.yourmechaniconline.com/api/webhooks/meta?hub.mode=subscribe&hub.verify_token=YOUR_TOKEN&hub.challenge=test123"
# Expect: test123
```

Submit test lead from Meta Lead Ads form → **Admin → Leads** (Meta Ads source).

## 3. Meta Developer App — WhatsApp

1. Same app → add product **WhatsApp**
2. **WhatsApp → API Setup** → copy **Phone number ID** → `CRM_WHATSAPP_PHONE_NUMBER_ID` in `.env`
3. **WhatsApp → Configuration → Webhook**:
   - Callback: `https://booking.yourmechaniconline.com/api/webhooks/whatsapp`
   - Verify token: same `CRM_META_VERIFY_TOKEN`
   - Subscribe: **`messages`**
4. Restart app
5. Send WhatsApp message to business number → check **Admin → Leads**
6. Open lead → **WhatsApp chat** → reply (within 24h of customer message)

## 4. Smoke tests

```bash
bash deploy/scripts/smoke-webhooks.sh
docker compose exec app php public/index.php cli/verify_crm v3
```

## YMO reference IDs

| Asset | ID |
|-------|-----|
| Facebook Page | `548089742374214` |
| Business portfolio | `267349181376488` |

Instagram DMs: defer until Page ↔ IG link is complete; then subscribe **`messages`** on Page webhook.
