# DLT SMS Templates — Your Mechanic Online (YMO)

Use this document to register SMS templates on **Jio TrueConnect DLT** and map them in **MSG91**. The sender header **YMOCAR** is already registered and active.

| Field | Value |
|-------|-------|
| Sender ID (Header) | `YMOCAR` |
| PE ID | `1201178526321075131` |
| Brand | Your Mechanic Online |
| Website | https://www.yourmechaniconline.com |
| Support phone | +91-7744-065904 |

---

## Registration workflow

1. **Jio DLT portal** → Content Template → Add template (copy text from sections below).
2. On Jio, use **only official tags** (`{#number#}`, `{#alphanumeric#}`, etc.) — not custom names like `{#otp#}`.
3. After Jio approval, add the template in **MSG91** → DLT → map to header `YMOCAR`.
4. In MSG91, name each variable to match the **App variable** column (e.g. first `{#number#}` → `otp`).
5. Copy each **Template ID** from MSG91 into production `.env` (see [Env mapping](#env-mapping)).
6. Send a test SMS from MSG91, then from the app.

### Jio portal fields (per template)

| Field | OTP only | All other templates (booking, invoice, etc.) |
|-------|----------|-----------------------------------------------|
| Type of Communication | **Transactional** | **Service Implicit** (NOT Transactional) |
| Choose Category | Consumer Goods and Automobiles | Same |
| Choose Header | `YMOCAR` | `YMOCAR` |
| Content Template name | `YMO_TPL_OTP` | Use env var name (e.g. `YMO_TPL_BOOKING_OK`) |

> **Critical:** If Jio shows rejection reason **"Service Implicit Content"**, you picked the wrong **Type of Communication**. Edit the template and change **Transactional → Service Implicit**. Do not change to Promotional unless Jio rejects Service Implicit for CRM offers.

> **TRAI / Jio rules:** PE trade name must appear in content (“Your Mechanic Online” or “YMO”). Templates with **3 or more variables** may be rejected — prefer the **≤2 variable** versions marked below.

### Jio tag types (use these in template text)

| Tag | Use for | App examples |
|-----|---------|--------------|
| `{#number#}` | Digits only (1–40) | OTP code, amount, phone |
| `{#alphanumeric#}` | Letters + numbers (1–40) | Name, booking ref, vehicle no., status |
| `{#url#}` | Whitelisted website URL | Static links only |
| `{#email#}` | Email address | Rarely needed |
| `{#cbn#}` | Registered callback number | Support line |

### MSG91 variable mapping

Jio tags are **types**, not names. When creating the flow in MSG91, assign **variable names** that match what the app sends:

```
Jio template:     Your Mechanic Online OTP is {#number#}. ...
MSG91 variable:   otp  →  maps to {#number#}
App JSON payload: { "otp": "482916" }
```

For templates with multiple `{#alphanumeric#}` tags, MSG91 maps them **in left-to-right order** — configure names in the same order as the table in each section.

---

## Template register

**All 8 templates approved — Registered with All TSP (07-08-2026).** Next: add each in MSG91 and copy Flow IDs to `.env`.

| Env variable | Jio DLT Template ID | MSG91 Flow ID | Status |
|--------------|---------------------|---------------|--------|
| `YMO_TPL_OTP` | `1277178591884069081` | | Jio ✅ Active · MSG91 pending |
| `YMO_TPL_BOOKING_OK` | `1277178591453817908` | | Jio ✅ Active · MSG91 pending |
| `YMO_TPL_BOOKING_STATUS` | `1277178591568547322` | | Jio ✅ Active · MSG91 pending |
| `YMO_TPL_SERVICE_REMIND` | `1277178591934852577` | | Jio ✅ Active · MSG91 pending |
| `YMO_TPL_REVIEW` | `1277178591508786260` | | Jio ✅ Active · MSG91 pending |
| `YMO_TPL_INVOICE` | `1277178591247861897` | | Jio ✅ Active · MSG91 pending |
| `YMO_TPL_REFERRAL` | `1277178591255421707` | | Jio ✅ Active · MSG91 pending |
| `YMO_TPL_CRM_CAMPAIGN` | `1277178591687073165` | | Jio ✅ Active · MSG91 pending |

> **Important:** The app `.env` values (`YMO_TPL_*`) must be the **MSG91 Flow template IDs** after Jio approval — not the Jio DLT IDs.

---

## Resubmission guide

### 06-08-2026 rejections (your screenshot)

| What Jio showed | What it means | What to do |
|-----------------|---------------|------------|
| Reason: **Service Implicit Content** | Template text is fine for **Service Implicit**; you filed it as **Transactional** | Click **Edit** → change **Type of Communication** to **Service Implicit** → keep the resubmit content below → save |
| Communication Type: **Transactional** | Wrong for booking, invoice, reminder, review, referral, CRM | Only **OTP** uses Transactional |

**Do not resubmit as Transactional again.** Jio auto-rejects car-service updates under Transactional.

### All rejection reasons (04-08 and 06-08)

| Rejection reason | Fix |
|------------------|-----|
| **Service Implicit Content** | Set **Type of Communication = Service Implicit** (this is the 06-08 issue) |
| **Complete entity name required** | Use **`Your Mechanic Online`** at the **start** of every template (not only `- YMO` at the end). Must match your PE / brand name on Jio exactly. |
| **Sample value incorrect** | Preview samples must match tag types: `{#number#}` = digits only (`482916`, `4850`), `{#alphanumeric#}` = letters/numbers (`Rajesh`, `YMO20260042`, `Confirmed`, `MH12AB1234`). No underscores in status sample - use `Confirmed` not `in_progress`. |
| **Content not clear** (CRM) | Do not start with a bare variable. Use a fixed prefix: `Your Mechanic Online car service update:` |

### Portal settings (all templates)

| Field | Value |
|-------|-------|
| Header | `YMOCAR` |
| Category | Consumer Goods and Automobiles |
| Entity / brand in text | **Your Mechanic Online** (exact spelling) |

### Communication type per template

| Template | Jio type to select |
|----------|-------------------|
| OTP | **Transactional** |
| Booking confirmed / status / reminder / review / invoice / referral | **Service Implicit** |
| CRM campaign | **Service Implicit** (or Promotional only if SI not available and you have consent) |

### Resubmit checklist

1. Click **Edit** on each rejected row.
2. Set **Type of Communication** to **Service Implicit** (except OTP = Transactional).
3. Paste the **Resubmit content** from the table below (includes `{#number#}` / `{#alphanumeric#}` tags).
4. Paste the **Sample preview** exactly - Jio validates types from this.
5. Category: **Consumer Goods and Automobiles** · Header: **YMOCAR**.
6. Save, wait for approval, add in MSG91, copy Flow IDs to `.env`.

| Name | Resubmit content | Sample preview |
|------|------------------|----------------|
| `YMO_TPL_OTP` | `Your Mechanic Online: OTP {#number#} for car service account verification. Valid 10 minutes. Do not share. - Your Mechanic Online` | `Your Mechanic Online: OTP 482916 for car service account verification. Valid 10 minutes. Do not share. - Your Mechanic Online` |
| `YMO_TPL_BOOKING_OK` | `Your Mechanic Online: Hi {#alphanumeric#}, your car service booking {#alphanumeric#} is confirmed. We will call for pick-up. - Your Mechanic Online` | `Your Mechanic Online: Hi Rajesh, your car service booking YMO20260042 is confirmed. We will call for pick-up. - Your Mechanic Online` |
| `YMO_TPL_BOOKING_STATUS` | `Your Mechanic Online: Car service booking {#alphanumeric#} status is {#alphanumeric#}. Details at yourmechaniconline.com - Your Mechanic Online` | `Your Mechanic Online: Car service booking YMO20260042 status is Confirmed. Details at yourmechaniconline.com - Your Mechanic Online` |
| `YMO_TPL_SERVICE_REMIND` | `Your Mechanic Online: Hi {#alphanumeric#}, car {#alphanumeric#} is due for periodic service. Book at yourmechaniconline.com - Your Mechanic Online` | `Your Mechanic Online: Hi Rajesh, car MH12AB1234 is due for periodic service. Book at yourmechaniconline.com - Your Mechanic Online` |
| `YMO_TPL_REVIEW` | `Your Mechanic Online: Hi {#alphanumeric#}, thank you for choosing our car service for booking {#alphanumeric#}. Please rate us on Google. - Your Mechanic Online` | `Your Mechanic Online: Hi Rajesh, thank you for choosing our car service for booking YMO20260042. Please rate us on Google. - Your Mechanic Online` |
| `YMO_TPL_INVOICE` | `Your Mechanic Online: Car service invoice for booking {#alphanumeric#} total Rs {#number#}. Copy sent to your email. - Your Mechanic Online` | `Your Mechanic Online: Car service invoice for booking YMO20260042 total Rs 4850. Copy sent to your email. - Your Mechanic Online` |
| `YMO_TPL_REFERRAL` | `Your Mechanic Online: Rs {#number#} car service referral credit for booking {#alphanumeric#}. - Your Mechanic Online` | `Your Mechanic Online: Rs 500 car service referral credit for booking YMO20260042. - Your Mechanic Online` |
| `YMO_TPL_CRM_CAMPAIGN` | `Your Mechanic Online car service update: {#alphanumeric#}. Book at yourmechaniconline.com or call 7744065904. - Your Mechanic Online` | `Your Mechanic Online car service update: Monsoon offer 10 percent off periodic service this week. Book at yourmechaniconline.com or call 7744065904. - Your Mechanic Online` |

**MSG91 variable mapping (unchanged order):**

| Template | Variables (left → right) |
|----------|-------------------------|
| OTP | `otp` |
| BOOKING_OK | `name`, `ref` |
| BOOKING_STATUS | `ref`, `status` |
| SERVICE_REMIND | `name`, `vehicle` |
| REVIEW | `name`, `ref` |
| INVOICE | `ref`, `total` *(name still sent by app — extra keys ignored)* |
| REFERRAL | `amount`, `ref` *(name still sent by app — extra keys ignored)* |
| CRM_CAMPAIGN | `msg` |

| # | App key | Env variable | Jio type | # vars | App variables (MSG91 names) |
|---|---------|--------------|----------|--------|---------------------------|
| 1 | `otp` | `YMO_TPL_OTP` | **Transactional** | 1 | `otp` |
| 2 | `booking_confirmed` | `YMO_TPL_BOOKING_OK` | **Service Implicit** | 2 | `name`, `ref` |
| 3 | `booking_status` | `YMO_TPL_BOOKING_STATUS` | **Service Implicit** | 2 | `ref`, `status` |
| 4 | `service_reminder` | `YMO_TPL_SERVICE_REMIND` | **Service Implicit** | 2 | `name`, `vehicle` |
| 5 | `review_request` | `YMO_TPL_REVIEW` | **Service Implicit** | 2 | `name`, `ref` |
| 6 | `invoice_sent` | `YMO_TPL_INVOICE` | **Service Implicit** | 2 | `ref`, `total` |
| 7 | `referral_credit` | `YMO_TPL_REFERRAL` | **Service Implicit** | 2 | `amount`, `ref` |
| 8 | `crm_campaign` | `YMO_TPL_CRM_CAMPAIGN` | **Service Implicit** | 1 | `msg` |

---

## 1. OTP (signup / login verification) ✅ Registered on Jio

**Env:** `YMO_TPL_OTP`  
**Jio DLT ID:** `1277178591884069081`  
**Jio settings:** Transactional · Consumer Goods · Header `YMOCAR` · Name `YMO_TPL_OTP`  
**App variable:** `otp` → maps to `{#number#}`

### Jio DLT content (submit this)

```
Your Mechanic Online OTP is {#number#}. Valid for 10 minutes. Do not share with anyone. - YMO
```

### Sample for Jio approval preview

```
Your Mechanic Online OTP is 482916. Valid for 10 minutes. Do not share with anyone. - YMO
```

### MSG91 setup (after Jio approval)

1. Add DLT template → paste same content → link header `YMOCAR`.
2. Set variable `{#number#}` name to **`otp`**.
3. Copy Template ID → `YMO_TPL_OTP=...` in `.env`.

| Jio DLT Template ID | MSG91 Flow ID | Status |
|---------------------|---------------|--------|
| `1277178591884069081` | | Jio ✅ · MSG91 pending |

---

## 2. Booking confirmed ✅ Registered on Jio

**Env:** `YMO_TPL_BOOKING_OK`  
**Jio DLT ID:** `1277178591453817908`  
**App variables:** `name`, `ref`, `package` (app sends all three; SMS uses first two only)

### Jio DLT content — **recommended (2 variables)**

```
Hi {#alphanumeric#}, booking {#alphanumeric#} is confirmed. We will call you soon. - Your Mechanic Online
```

| Order | Jio tag | MSG91 / app variable | Example |
|-------|---------|----------------------|---------|
| 1 | `{#alphanumeric#}` | `name` | `Ashish` |
| 2 | `{#alphanumeric#}` | `ref` | `YMO-2026-0042` |

> App still sends `package` in the API payload; MSG91 ignores extra keys. Package details go via email.

### Avoid (3 variables — may be rejected)

```
Hi {#alphanumeric#}, your YMO booking {#alphanumeric#} for {#alphanumeric#} is confirmed. ...
```

| Jio DLT Template ID | MSG91 Flow ID | Status |
|---------------------|---------------|--------|
| `1277178591453817908` | | Jio ✅ · MSG91 pending |

---

## 3. Booking status update ✅ Registered on Jio

**Env:** `YMO_TPL_BOOKING_STATUS`  
**Jio DLT ID:** `1277178591568547322`  
**App variables:** `ref`, `status`

### Jio DLT content

```
YMO booking {#alphanumeric#} is now {#alphanumeric#}. Visit yourmechaniconline.com - YMO
```

| Order | Jio tag | MSG91 / app variable | Example |
|-------|---------|----------------------|---------|
| 1 | `{#alphanumeric#}` | `ref` | `YMO-2026-0042` |
| 2 | `{#alphanumeric#}` | `status` | `confirmed`, `in_progress`, `completed`, `cancelled` |

| Jio DLT Template ID | MSG91 Flow ID | Status |
|---------------------|---------------|--------|
| `1277178591568547322` | | Jio ✅ · MSG91 pending |

---

## 4. Service reminder ✅ Registered on Jio

**Env:** `YMO_TPL_SERVICE_REMIND`  
**Jio DLT ID:** `1277178591934852577`  
**App variables:** `name`, `vehicle`

### Jio DLT content

```
Hi {#alphanumeric#}, time for your next car service ({#alphanumeric#}). Book at yourmechaniconline.com - YMO
```

| Order | Jio tag | MSG91 / app variable | Example |
|-------|---------|----------------------|---------|
| 1 | `{#alphanumeric#}` | `name` | `Ashish` |
| 2 | `{#alphanumeric#}` | `vehicle` | `MH12AB1234` |

| Jio DLT Template ID | MSG91 Flow ID | Status |
|---------------------|---------------|--------|
| `1277178591934852577` | | Jio ✅ · MSG91 pending |

---

## 5. Review request ✅ Registered on Jio

**Env:** `YMO_TPL_REVIEW`  
**Jio DLT ID:** `1277178591508786260`  
**App variables:** `name`, `ref`

### Jio DLT content

```
Hi {#alphanumeric#}, thanks for choosing YMO for booking {#alphanumeric#}. Please rate us on Google. - YMO
```

| Order | Jio tag | MSG91 / app variable | Example |
|-------|---------|----------------------|---------|
| 1 | `{#alphanumeric#}` | `name` | `Ashish` |
| 2 | `{#alphanumeric#}` | `ref` | `YMO-2026-0042` |

| Jio DLT Template ID | MSG91 Flow ID | Status |
|---------------------|---------------|--------|
| `1277178591508786260` | | Jio ✅ · MSG91 pending |

---

## 6. Service invoice sent ✅ Registered on Jio

**Env:** `YMO_TPL_INVOICE`  
**Jio DLT ID:** `1277178591247861897`  
**App variables:** `name`, `ref`, `invoice_no`, `total`

### Jio DLT content — **recommended (3 variables)**

```
Hi {#alphanumeric#}, invoice for booking {#alphanumeric#} is ready. Total Rs {#number#}. Check your email. - YMO
```

| Order | Jio tag | MSG91 / app variable | Example |
|-------|---------|----------------------|---------|
| 1 | `{#alphanumeric#}` | `name` | `Ashish` |
| 2 | `{#alphanumeric#}` | `ref` | `YMO-2026-0042` |
| 3 | `{#number#}` | `total` | `4850.00` |

> Invoice number is in the email/PDF attachment; kept out of SMS to stay under the 3-variable limit.

| Jio DLT Template ID | MSG91 Flow ID | Status |
|---------------------|---------------|--------|
| `1277178591247861897` | | Jio ✅ · MSG91 pending |

---

## 7. Referral credit ✅ Registered on Jio

**Env:** `YMO_TPL_REFERRAL`  
**Jio DLT ID:** `1277178591255421707`  
**App variables:** `name`, `amount`, `ref`

### Jio DLT content (3 variables — register if accepted)

```
Hi {#alphanumeric#}, Rs {#number#} referral credit confirmed for booking {#alphanumeric#}. - Your Mechanic Online
```

| Order | Jio tag | MSG91 / app variable | Example |
|-------|---------|----------------------|---------|
| 1 | `{#alphanumeric#}` | `name` | `Ashish` |
| 2 | `{#number#}` | `amount` | `500` |
| 3 | `{#alphanumeric#}` | `ref` | `YMO-2026-0042` |

| Jio DLT Template ID | MSG91 Flow ID | Status |
|---------------------|---------------|--------|
| `1277178591255421707` | | Jio ✅ · MSG91 pending |

---

## 8. CRM campaign SMS ✅ Registered on Jio

**Env:** `YMO_TPL_CRM_CAMPAIGN`  
**Jio DLT ID:** `1277178591687073165`  
**App variable:** `msg`  
**Jio type:** Promotional / Service Explicit (requires subscriber consent)

### Jio DLT content

```
{#alphanumeric#} - Your Mechanic Online. Call 7744065904
```

| Order | Jio tag | MSG91 / app variable |
|-------|---------|----------------------|
| 1 | `{#alphanumeric#}` | `msg` |

### Example final SMS

| Campaign body (`msg`) | Delivered SMS |
|-----------------------|---------------|
| `Monsoon offer: 10% off complete car service this week` | `Monsoon offer: 10% off complete car service this week - Your Mechanic Online. Call 7744065904` |

| Jio DLT Template ID | MSG91 Flow ID | Status |
|---------------------|---------------|--------|
| `1277178591687073165` | | Jio ✅ · MSG91 pending |

---

## Env mapping

After adding templates in **MSG91** and creating Flows, set in production `.env`:

```env
YMO_SMS_DRIVER=msg91
YMO_MSG91_AUTHKEY=your-authkey
YMO_MSG91_SENDER=YMOCAR
YMO_MSG91_ROUTE=4

# MSG91 Flow template IDs (NOT the Jio DLT IDs below)
YMO_TPL_OTP=msg91-flow-id-here
YMO_TPL_BOOKING_OK=msg91-flow-id-here
YMO_TPL_BOOKING_STATUS=msg91-flow-id-here
YMO_TPL_SERVICE_REMIND=msg91-flow-id-here
YMO_TPL_REVIEW=msg91-flow-id-here
YMO_TPL_INVOICE=msg91-flow-id-here
YMO_TPL_REFERRAL=msg91-flow-id-here
YMO_TPL_CRM_CAMPAIGN=msg91-flow-id-here
```

**Jio DLT IDs (for MSG91 registration reference only):**

```env
# Do not use these in the app — keep for MSG91 DLT linking
# YMO_TPL_OTP           → 1277178591884069081
# YMO_TPL_BOOKING_OK    → 1277178591453817908
# YMO_TPL_BOOKING_STATUS→ 1277178591568547322
# YMO_TPL_SERVICE_REMIND→ 1277178591934852577
# YMO_TPL_REVIEW        → 1277178591508786260
# YMO_TPL_INVOICE       → 1277178591247861897
# YMO_TPL_REFERRAL      → 1277178591255421707
# YMO_TPL_CRM_CAMPAIGN  → 1277178591687073165
```

Restart the app after updating env vars.

---

## Testing checklist

| Template | How to test |
|----------|-------------|
| OTP | Sign up with your mobile → enter OTP on verify screen |
| Booking confirmed | Place a test booking on the website |
| Booking status | Admin → Bookings → change status |
| Service reminder | Requires cron + reminder row (or manual `cli/cron run`) |
| Review request | Admin → completed booking → send review request |
| Invoice | Admin → booking → Issue & send invoice |
| Referral | Complete a referred booking (referral programme enabled) |
| CRM campaign | Admin → Campaigns → New → SMS → send to your number |

---

## Next steps

1. ~~Register all templates on Jio DLT~~ ✅ Done — all Active, Registered with All TSP (07-08-2026)
2. **MSG91** — add each Jio template ID above, map header `YMOCAR`, set variable names (see sections above)
3. **MSG91** — create Flow for each template → copy Flow IDs to `.env`
4. Set `YMO_MSG91_AUTHKEY` in `.env`
5. Test OTP signup, then booking confirmed

---

## Related docs

- [CRM_SETUP.md §5 SMS (MSG91 + DLT)](CRM_SETUP.md#5-sms-msg91--dlt)
- [DEPLOY.md](DEPLOY.md) — production env vars
- App SMS library: `application/libraries/Sms_gateway.php`
- Template config: `application/config/ymo.php` → `sms_templates`
