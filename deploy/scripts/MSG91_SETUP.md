# MSG91 setup — Your Mechanic Online (YMOCAR)

Jio DLT templates are **approved** (see [DLT_TEMPLATES.md](../../DLT_TEMPLATES.md)). Finish MSG91, then wire `.env` on the VPS.

**App API:** MSG91 Flow v5 (`Sms_gateway.php` → `POST https://control.msg91.com/api/v5/flow/`).  
**`.env` values must be MSG91 Flow IDs** — not Jio DLT IDs.

---

## Step 1 — Auth Key

1. Open [MSG91 Control Panel](https://control.msg91.com/app/).
2. Top bar → **AuthKey** → copy the key.
3. On VPS `.env`:

```env
YMO_MSG91_AUTHKEY=paste-authkey-here
YMO_SMS_DRIVER=msg91
YMO_MSG91_SENDER=YMOCAR
YMO_MSG91_ROUTE=4
```

---

## Step 2 — DLT registration in MSG91

1. **Channels → SMS** (or **OTP → DLT** depending on UI version).
2. **DLT / Headers** → add sender **`YMOCAR`** if not listed.
3. **PE ID:** `1201178526321075131`
4. For **each** Jio template, **Add content template** and paste the **Jio DLT Template ID**:

| Flow name (label) | Jio DLT ID (paste in MSG91) | MSG91 variable names (order) |
|-------------------|----------------------------|------------------------------|
| YMO_TPL_OTP | `1277178591884069081` | `otp` |
| YMO_TPL_BOOKING_OK | `1277178591453817908` | `name`, `ref` |
| YMO_TPL_BOOKING_STATUS | `1277178591568547322` | `ref`, `status` |
| YMO_TPL_SERVICE_REMIND | `1277178591934852577` | `name`, `vehicle` |
| YMO_TPL_REVIEW | `1277178591508786260` | `name`, `ref` |
| YMO_TPL_INVOICE | `1277178591247861897` | `name`, `ref`, `total` |
| YMO_TPL_REFERRAL | `1277178591255421707` | `name`, `amount`, `ref` |
| YMO_TPL_CRM_CAMPAIGN | `1277178591687073165` | `msg` |

Map each `{#number#}` / `{#alphanumeric#}` slot in MSG91 to the **exact API variable name** below (MSG91 → + Add Variable). These names must match what the app sends in the Flow API `recipients` object.

| Template | MSG91 variable names (left → right) | App JSON keys |
|----------|-------------------------------------|---------------|
| OTP | `otp` | `otp` |
| BOOKING_OK | `name`, `ref` | `name`, `ref` |
| BOOKING_STATUS | `ref`, `status` | `ref`, `status` |
| SERVICE_REMIND | `name`, `vehicle` | `name`, `vehicle` |
| REVIEW | `name`, `ref` | `name`, `ref` |
| INVOICE | `ref`, `total` | `ref`, `total` |
| REFERRAL | `amount`, `ref` | `amount`, `ref` |
| CRM_CAMPAIGN | `msg` | `msg` |

> Do **not** leave generic names like `var1` / `alphanumeric` unless you rename them in MSG91 to match the App JSON keys column.

---

## Step 3 — Create Flows (get Flow IDs)

1. **One API** (dashboard) or **Flow** section → **Create flow** for each template above.
2. Link sender **YMOCAR** + the DLT template from step 2.
3. After save, copy each **Flow ID** (sometimes shown as Template ID in Flow API).

---

## Step 4 — `.env` on VPS

```env
YMO_TPL_OTP=flow-id-from-msg91
YMO_TPL_BOOKING_OK=...
YMO_TPL_BOOKING_STATUS=...
YMO_TPL_SERVICE_REMIND=...
YMO_TPL_REVIEW=...
YMO_TPL_INVOICE=...
YMO_TPL_REFERRAL=...
YMO_TPL_CRM_CAMPAIGN=...
```

Helper (interactive):

```bash
bash deploy/scripts/setup-msg91-env.sh          # print template
bash deploy/scripts/setup-msg91-env.sh --write  # merge into .env
```

Restart app:

```bash
docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml restart app
```

---

## Step 5 — Smoke test

From repo root on VPS:

```bash
bash deploy/scripts/smoke-sms.sh 9876543210 otp
# or
docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml exec app php index.php cli/sms test otp 9876543210
```

Then test in product:

1. **OTP** — sign up on booking site with your mobile.
2. **Booking confirmed** — place a test booking.
3. **Booking status** — Admin → Bookings → change status.
4. **Service reminder** — cron after booking completed (+4 months default).
5. **Review request** — cron (+3 days) or Admin → Send review request.
6. **Invoice** — Admin → issue & send invoice.
7. **Referral** — complete a referred booking.
8. **CRM campaign** — Admin → Campaigns → SMS campaign.

---

## Feature → template wiring (app)

| Feature | Template key | Trigger |
|---------|--------------|---------|
| Signup / login OTP | `otp` | `Otp_service` on verify flow |
| Booking placed | `booking_confirmed` | `Bookings::place()` |
| Admin status change | `booking_status` | `admin/Bookings::update_status()` |
| Service due reminder | `service_reminder` | Daily cron (`cli/cron run`) |
| Google review prompt | `review_request` | Cron (+N days) or admin manual send |
| Invoice notification | `invoice_sent` | Admin create/update invoice with notify |
| Referral credit | `referral_credit` | Booking marked completed (referral programme) |
| CRM bulk SMS | `crm_campaign` | Admin campaign send + cron batches |

---

## Wallet / credits

Dashboard shows wallet balance (e.g. ₹50). Each test SMS deducts credits. **Add Funds** before bulk tests.

---

## Troubleshooting

| Symptom | Fix |
|---------|-----|
| `MSG91 authkey not set` | Set `YMO_MSG91_AUTHKEY`, restart app |
| `template 'otp' is not configured` | Set `YMO_TPL_OTP` to Flow ID (not Jio ID) |
| DLT mismatch / blocked | Confirm header `YMOCAR` + Jio template ID linked in MSG91 |
| HTTP 401 | Wrong AuthKey |
| SMS not received | Check MSG91 logs; verify route 4 (transactional) and wallet balance |

Logs: `storage/logs/` → search `[sms]`.
