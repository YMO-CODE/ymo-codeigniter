# YMO CRM — Manual setup & go-live checklist

This document lists everything **you must do manually** after the CRM code is in place. The application is built; these steps configure the database, team access, integrations, production infrastructure, and day-to-day operations.

**Local dev (Docker):** http://localhost:8080/admin/login  
**Production (recommended):** https://admin.yourmechaniconline.com/login

---

## Table of contents

0. [Quick start — local Docker (first time)](#0-quick-start--local-docker-first-time)
1. [Database](#1-database)
2. [Admin & team access (RBAC)](#2-admin--team-access-rbac)
3. [Environment variables](#3-environment-variables)
4. [Email (SMTP)](#4-email-smtp)
5. [SMS (MSG91 + DLT)](#5-sms-msg91--dlt)
6. [Cron jobs](#6-cron-jobs)
7. [Lead capture — Meta / Instagram Ads](#7-lead-capture--meta--instagram-ads)
8. [Lead capture — website & landing pages](#8-lead-capture--website--landing-pages)
9. [Lead capture — WhatsApp (inbound)](#9-lead-capture--whatsapp-inbound)
10. [Production deployment](#10-production-deployment)
11. [WordPress / marketing site links](#11-wordpress--marketing-site-links)
12. [Operational testing checklist](#12-operational-testing-checklist)
13. [Training & handover](#13-training--handover)
14. [Not automated (known gaps)](#14-not-automated-known-gaps)
15. [Booking service invoices](#15-booking-service-invoices)
16. [Import legacy customer contacts (Excel → CSV)](#16-import-legacy-customer-contacts-excel--csv)

---

## 0. Quick start — local Docker (first time)

Follow these steps in order if you are setting up on your laptop for the first time.

### Step 0 — Prerequisites

1. Install [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows/Mac) or Docker Engine (Linux).
2. Clone or open the project folder, e.g. `c:\Users\ayush\Desktop\ymo-codeigniter`.
3. Open a terminal **in the project root** (where `docker-compose.yml` lives).

### Step 1 — Start containers

```powershell
# Windows (PowerShell)
cd c:\Users\ayush\Desktop\ymo-codeigniter
docker compose up -d
```

```bash
# Linux / macOS
cd /path/to/ymo-codeigniter
docker compose up -d
```

Wait until `docker compose ps` shows `app` and `db` as running.

### Step 2 — Load the database

**If this is a brand-new database** (never had YMO tables):

```powershell
# Windows — schema + seed
Get-Content database\schema.sql | docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking
Get-Content database\seed.sql   | docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking
```

```bash
# Linux / macOS
docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking < database/schema.sql
docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking < database/seed.sql
```

**If you already had the booking app** but CRM was added later, skip schema/seed and run migrations instead (see [§1](#1-database)).

### Step 3 — Create your admin account

```bash
docker compose exec app php public/index.php cli/install create_admin admin@test.com "Your Name"
```

1. Copy the **password** printed in the terminal — it is shown only once.
2. The account is created with **Administrator** role (`crm_role_id = 1`, full access).

### Step 4 — Sign in

1. Open http://localhost:8080/admin/login
2. Enter the email and password from Step 3.
3. You should land on the **Dashboard** with sidebar links for Bookings, CRM, Team, Roles, etc.

### Step 5 — Confirm CRM works

1. Go to **Admin → Leads** (http://localhost:8080/admin/leads).
2. Click **New lead**, fill in a test name and mobile, save.
3. If you see the lead list (not “CRM tables are not installed”), CRM is ready.

### Step 6 — Add a mechanic (optional smoke test)

See [§2 — Example: add a workshop mechanic](#example--add-a-workshop-mechanic) for the full walkthrough.

---

## 1. Database

### Which path do I take?

| Your situation | What to run |
|----------------|-------------|
| **New empty database** | `schema.sql` then `seed.sql` (includes CRM + Team RBAC v2) |
| **Old booking DB, no CRM yet** | `crm_migration_v1.sql` then `crm_migration_v2_team_rbac.sql` |
| **CRM v1 already applied, no Team panel** | `crm_migration_v2_team_rbac.sql` only |
| **Fresh schema.sql from this repo** | Nothing extra — v2 is already included |

### Fresh install (step by step)

1. Ensure Docker MySQL is running (`docker compose up -d`).
2. Run schema (creates all tables including `crm_*`, roles, permissions):

```powershell
# Windows
Get-Content database\schema.sql | docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking
```

```bash
# Linux / macOS
docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking < database/schema.sql
```

3. Run seed (sample packages, settings):

```powershell
Get-Content database\seed.sql | docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking
```

4. Verify (see below).

### Existing install — CRM migration (v1)

If you see **“CRM tables are not installed”** when opening Leads:

1. Stop and confirm you are on the correct database (`ymo_booking`).
2. Run v1 migration **once**:

```powershell
# Windows + Docker (from project root)
Get-Content database\crm_migration_v1.sql | docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking
```

```bash
# Linux / macOS + Docker
docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking < database/crm_migration_v1.sql

# Direct MySQL (no Docker)
mysql -u ymo_user -p ymo_booking < database/crm_migration_v1.sql
```

3. Refresh **Admin → Leads** — the error should be gone.

### Existing install — Team & RBAC migration (v2)

Required for **Team**, **Roles**, and booking-side permission checks (Mechanic role, etc.):

1. Run v1 first if CRM tables do not exist yet.
2. Run v2 **once**:

```powershell
Get-Content database\crm_migration_v2_team_rbac.sql | docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking
```

```bash
docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking < database/crm_migration_v2_team_rbac.sql
```

3. **Sign out and sign in again** so your session picks up new permissions (especially if you were already logged in as admin).

What v2 adds:

- Permissions: `dashboard.view`, `bookings.view`, `bookings.edit`, `customers.view`, `packages.*`, `settings.manage`, `team.*`, `roles.*`
- Grants all new permissions to **Administrator** (role id 1)
- Seeds **Mechanic** role (id 5): dashboard + bookings + customers only

### Verify database

Run these checks after any migration:

```bash
# List CRM tables (~17 rows)
docker compose exec db mysql -u ymo_user -pymo_pass ymo_booking -e "SHOW TABLES LIKE 'crm_%';"

# Role count (expect 5: Admin, Sales, Marketing, HR, Mechanic)
docker compose exec db mysql -u ymo_user -pymo_pass ymo_booking -e "SELECT id, slug, label FROM crm_roles ORDER BY id;"

# Permission count (expect 26)
docker compose exec db mysql -u ymo_user -pymo_pass ymo_booking -e "SELECT COUNT(*) AS total FROM crm_permissions;"
```

Expected:

- ~17 `crm_*` tables
- **5 rows** in `crm_roles`
- **26 rows** in `crm_permissions`

---

## 2. Admin & team access (RBAC)

Access is controlled by **roles** and **permissions**. Each staff member has one role; the role defines which admin URLs they can open. Direct URL access without permission returns **403 Forbidden**.

### Admin URL pattern

| Environment | Login URL | Example: Bookings |
|-------------|-----------|-------------------|
| Local Docker | http://localhost:8080/admin/login | http://localhost:8080/admin/bookings |
| Production (subdomain) | https://admin.yourmechaniconline.com/login | https://admin.yourmechaniconline.com/bookings |

### Built-in roles

| ID | Slug | Who it's for | Typical sidebar |
|----|------|--------------|-----------------|
| 1 | `admin` | Owner / super admin | Everything |
| 2 | `sales_executive` | Sales team | Dashboard, Leads, Pipeline, Follow-ups, Contacts, Reports |
| 3 | `marketing` | Marketing | Dashboard, Leads (view), Campaigns, Reports |
| 4 | `hr` | Recruitment | Dashboard, Recruitment, Reports |
| 5 | `mechanic` | Workshop staff | Dashboard, Bookings, Customers |

Custom roles can mix any permissions via **Admin → Roles**.

### Create the first administrator

No default password ships with the repo.

1. Run once per environment:

```bash
docker compose exec app php public/index.php cli/install create_admin admin@yourcompany.com "Your Name"
```

2. Save the printed password.
3. Sign in at `/admin/login`.
4. Optionally reset password via **Admin → Team → Edit** (after v2 migration) or CLI:

```bash
docker compose exec app php public/index.php cli/install set_admin_password admin@yourcompany.com
```

### Add staff via Admin → Team & Roles (step by step)

You need a user with **Administrator** role (or `team.manage` + `roles.manage`).

#### Create or review a role

1. Sign in as admin → open **Administration → Roles** (`/admin/roles`).
2. Review built-in roles, or click **New role**.
3. Enter **Role name** (e.g. `Sales Lite`), optional **Slug** (auto-generated from name if blank).
4. Check permissions in the matrix (grouped: Booking & operations, CRM, Administration, Integrations).
5. Click **Create role** or **Save role**.

#### Add a team member

1. Open **Administration → Team** (`/admin/team`).
2. Click **Add member**.
3. Fill in:
   - **Full name**
   - **Email** (used for login)
   - **Role** (dropdown)
   - **Password** — leave blank to auto-generate (shown once in a green flash message after save)
4. Click **Create member**.
5. Share email + password with the staff member securely.
6. Ask them to sign in at `/admin/login`.

#### Edit, reset password, or deactivate

1. **Team → Edit** on the user row.
2. **Reset password** — enter a new password or leave blank for a generated one.
3. **Deactivate account** — blocks sign-in (you cannot deactivate your own account).
4. **Reactivate** — available on inactive users.

**Important:** After changing someone's role or permissions, they must **sign out and sign in again** for changes to apply.

### Example — add a workshop mechanic

1. Confirm v2 migration ran ([§1](#existing-install--team--rbac-migration-v2)).
2. Sign in as admin.
3. Open **Roles** — confirm **Mechanic** exists (slug `mechanic`). If not, create a role with only:
   - `dashboard.view`
   - `bookings.view`
   - `bookings.edit`
   - `customers.view`
4. Open **Team → Add member**:
   - Name: `Workshop Mechanic`
   - Email: `mechanic@yourcompany.com`
   - Role: **Mechanic**
   - Password: leave blank (note the generated password from the flash message)
5. Sign out. Sign in as `mechanic@yourcompany.com`.
6. Confirm sidebar shows **Dashboard**, **Bookings**, **Customers** only.
7. Confirm these URLs return **403**:
   - `/admin/leads`
   - `/admin/settings`
   - `/admin/team`
8. Open a booking → update status (requires `bookings.edit` — should work).

### Permission → URL reference

| Permission | URLs |
|------------|------|
| `dashboard.view` | `/admin/dashboard` |
| `bookings.view` | `/admin/bookings`, `/admin/bookings/{id}` |
| `bookings.edit` | POST `/admin/bookings/{id}/status`, `/admin/bookings/{id}/send-review` |
| `customers.view` | `/admin/customers`, `/admin/customers/{id}` |
| `packages.view` | `/admin/packages` |
| `packages.edit` | `/admin/packages/new`, edit, delete |
| `settings.manage` | `/admin/settings` |
| `team.view` / `team.manage` | `/admin/team`, create/edit/reset/deactivate |
| `roles.view` / `roles.manage` | `/admin/roles`, create/edit/delete |
| `leads.view` | `/admin/leads`, `/admin/leads/pipeline`, `/admin/leads/{id}` |
| `contacts.view` | `/admin/contacts`, … |
| `tasks.view` | `/admin/tasks`, … |
| `campaigns.view` | `/admin/campaigns`, … |
| `recruitment.view` | `/admin/recruitment`, … |
| `reports.view` | `/admin/reports`, export |

### Manual SQL (optional)

If you cannot use the UI:

1. Generate a password hash:

```bash
docker compose exec app php public/index.php cli/install hash_password 'YourSecurePassword123'
```

2. Insert the user:

```sql
INSERT INTO admin_users (name, email, password_hash, role, crm_role_id, is_active, created_at, updated_at)
VALUES (
  'Sales Executive Name',
  'sales@yourcompany.com',
  '$2y$10$....',  -- paste hash from step 1
  'staff',
  2,              -- role id from crm_roles
  1,
  NOW(), NOW()
);
```

---

## 3. Environment variables

### Step-by-step (local)

1. Copy the example file:

```powershell
copy .env.example .env
```

```bash
cp .env.example .env
```

2. For **local Docker**, minimum useful values:

```
CI_ENV=development
YMO_DB_HOST=db
YMO_DB_NAME=ymo_booking
YMO_DB_USER=ymo_user
YMO_DB_PASS=ymo_pass
YMO_ENCRYPTION_KEY=any-32-character-random-string!!
```

3. Leave `YMO_ADMIN_APP_URL` **empty** locally — admin uses `/admin/*` paths on port 8080.
4. Restart containers after changing env: `docker compose up -d`.

### Required for production

| Variable | Purpose | Example |
|----------|---------|---------|
| `CI_ENV` | Environment mode | `production` |
| `YMO_DB_HOST` | Database host | `db` (Docker) or `127.0.0.1` |
| `YMO_DB_NAME` | Database name | `ymo_booking` |
| `YMO_DB_USER` / `YMO_DB_PASS` | DB credentials | — |
| `YMO_ENCRYPTION_KEY` | Encrypts session data — **keep stable** across restores | 32+ random chars |
| `YMO_PUBLIC_APP_URL` | Customer/booking site | `https://booking.yourmechaniconline.com` |
| `YMO_ADMIN_APP_URL` | Admin subdomain | `https://admin.yourmechaniconline.com` |
| `YMO_TRUST_PROXY_HEADERS` | Trust `X-Forwarded-*` from nginx/Caddy | `1` |

### CRM-specific

| Variable | Purpose | When to set |
|----------|---------|-------------|
| `CRM_WEBHOOK_SECRET` | HMAC for website/WhatsApp webhooks | Before §8 or §9 |
| `CRM_META_VERIFY_TOKEN` | Meta webhook verify handshake | Before §7 |
| `CRM_META_ACCESS_TOKEN` | Fetch full lead fields from Graph API | Before §7 (strongly recommended) |
| `YMO_TPL_CRM_CAMPAIGN` | MSG91 DLT template for SMS campaigns | Before SMS campaigns (§5) |

**Docker note:** `docker-compose.yml` sets DB vars in `environment:` but **not** CRM vars. Add CRM keys to Compose `environment:` or load `.env` on the host.

---

## 4. Email (SMTP)

### Step-by-step

1. Create SMTP credentials (Gmail App Password, SendGrid, Amazon SES, etc.).
2. Add to `.env`:

```
YMO_MAIL_HOST=smtp.gmail.com
YMO_MAIL_PORT=587
YMO_MAIL_USER=no-reply@yourmechaniconline.com
YMO_MAIL_PASS=app-password-or-smtp-secret
YMO_MAIL_ENC=tls
YMO_MAIL_FROM=no-reply@yourmechaniconline.com
YMO_MAIL_FROM_NAME=Your Mechanic Online
```

3. Restart the app container if env changed.
4. Test:
   - Sign in as admin → **Campaigns → New** → create a draft email campaign.
   - Add your own email as the only recipient → **Send now**.
   - Check inbox (and spam folder).

**Used by CRM for:**

- Campaign emails (bulk)
- Follow-up task reminders to assignees
- Existing booking notifications

**Development:** If SMTP is blank, the mailer logs stubs instead of sending (see `application/libraries/Mailer.php`).

---

## 5. SMS (MSG91 + DLT)

Required for **India (DLT)** campaign SMS.

### Step-by-step

1. Log in to [MSG91](https://msg91.com/) and note your **Auth Key**.
2. Register a **promotional/transactional** DLT template for campaign SMS copy with your operator.
3. After approval, copy the **Template ID**.
4. Set in `.env`:

```
YMO_SMS_DRIVER=msg91
YMO_MSG91_AUTHKEY=your-authkey
YMO_MSG91_SENDER=YMOCAR
YMO_MSG91_ROUTE=4
YMO_TPL_CRM_CAMPAIGN=your-dlt-template-id
```

5. Restart app.
6. Test: **Campaigns → New → SMS** → send to your mobile.

**Invoice SMS:** Register a separate DLT template for service invoices and set `YMO_TPL_INVOICE`. Used when staff issue a booking invoice (see [§15](#15-booking-service-invoices)).

Campaign SMS **will fail** without `YMO_TPL_CRM_CAMPAIGN`. Email campaigns work independently.

Existing booking templates (`YMO_TPL_OTP`, `YMO_TPL_BOOKING_OK`, etc.) remain separate.

---

## 6. Cron jobs

CRM task reminders and scheduled campaigns run via CLI cron — they do **not** run automatically on a dev laptop unless you schedule them.

### What cron runs

| Job | Command | Purpose |
|-----|---------|---------|
| Full daily | `cli/cron run` | Booking reminders + CRM task emails + scheduled campaigns + OTP purge |
| CRM only | `cli/cron crm` | Task reminders + campaigns only |

### Step-by-step — test locally

1. Create a follow-up task due today (**Leads → open lead → Add follow-up**).
2. Run:

```bash
docker compose exec app php public/index.php cli/cron crm
```

3. Check output for `crm_tasks=` and `crm_camps=` counts.
4. Inspect `storage/logs/cron.log` if configured.

### Step-by-step — production (VPS)

1. SSH into the server.
2. Edit crontab: `crontab -e`
3. Add (adjust path):

```cron
0 7 * * * cd /path/to/ymo-codeigniter && docker compose exec -T app php public/index.php cli/cron run >> storage/logs/cron.log 2>&1
```

4. Save. Verify next morning that `storage/logs/cron.log` has new entries.

---

## 7. Lead capture — Meta / Instagram Ads

### Webhook URL

```
https://booking.yourmechaniconline.com/api/webhooks/meta
```

Must be **HTTPS** in production. Use your public booking hostname (not the admin subdomain).

### Step-by-step in Meta Business Suite

1. Go to [developers.facebook.com](https://developers.facebook.com) → **Create App** → type **Business**.
2. Add product **Webhooks**.
3. Click **Configure** for **Page** subscriptions.
4. Set:
   - **Callback URL:** URL above
   - **Verify token:** same string as `CRM_META_VERIFY_TOKEN` in your `.env`
5. Click **Verify and Save** — Meta sends a GET challenge; the app responds automatically if the token matches.
6. Subscribe to field **`leadgen`**.
7. Under **Webhooks → Page**, subscribe your **Facebook Page** (and linked Instagram account).
8. Generate a **Page access token** with `leads_retrieval` permission:
   - Graph API Explorer or Business Settings → System Users
   - Set token as `CRM_META_ACCESS_TOKEN` in `.env`
9. Restart app. Submit a **test lead** from Meta Lead Ads form builder.

### Verify in CRM

1. **Admin → Reports** → check **Recent webhook activity** (admin role).
2. **Admin → Leads** → new lead with source **Meta Ads** or **Instagram Ads**.

Without `CRM_META_ACCESS_TOKEN`, webhooks may arrive with only `leadgen_id` and empty name/mobile fields.

---

## 8. Lead capture — website & landing pages

### Webhook URL

```
POST https://booking.yourmechaniconline.com/api/webhooks/website
Content-Type: application/json
X-CRM-Signature: sha256=<hmac-sha256-hex-of-raw-body>
```

### Step-by-step

1. Generate a long random string → set as `CRM_WEBHOOK_SECRET` in `.env`.
2. Restart app.
3. Wire your form (WordPress, Elementor, custom landing page) to **POST JSON** to the URL above.
4. Sign the **raw request body** with HMAC-SHA256 using the secret.
5. Send header: `X-CRM-Signature: sha256=<hex>`.

### Example JSON body

```json
{
  "name": "Rajesh Kumar",
  "mobile": "9876543210",
  "email": "rajesh@example.com",
  "message": "Need car service quote",
  "source": "website"
}
```

`source` may be: `website`, `landing`, `referral`, or `cold_call` (defaults to `website`).

### Example signature (PHP)

```php
$body = json_encode(['name' => 'Test', 'mobile' => '9876543210']);
$sig = hash_hmac('sha256', $body, getenv('CRM_WEBHOOK_SECRET'));
// Header: X-CRM-Signature: sha256=$sig
```

### Test with curl

```bash
BODY='{"name":"Test User","mobile":"9876543210","source":"website"}'
SIG=$(echo -n "$BODY" | openssl dgst -sha256 -hmac "YOUR_CRM_WEBHOOK_SECRET" | awk '{print $2}')
curl -X POST https://booking.yourmechaniconline.com/api/webhooks/website \
  -H "Content-Type: application/json" \
  -H "X-CRM-Signature: sha256=$SIG" \
  -d "$BODY"
```

Check **Admin → Leads** for the new lead.

WordPress: add a small plugin, mu-plugin, or use a form plugin’s webhook feature — the CRM does not install anything on WordPress automatically.

---

## 9. Lead capture — WhatsApp (inbound)

### Webhook URL

```
POST https://booking.yourmechaniconline.com/api/webhooks/whatsapp
```

Optional HMAC: same `CRM_WEBHOOK_SECRET` + `X-CRM-Signature` header as §8.

### Step-by-step

1. Choose a WhatsApp Business API provider (Meta Cloud API, Interakt, Wati, Gupshup, etc.).
2. In their dashboard, set **incoming message webhook** to the URL above.
3. Map their payload to JSON fields the CRM expects:

```json
{
  "from": "919876543210",
  "name": "Customer Name",
  "text": "Hi, I need a quote for Honda City service"
}
```

4. Send a test message from your phone.
5. Confirm lead appears under **Admin → Leads** with source **WhatsApp**.

**Note:** Outbound bulk WhatsApp campaigns are **not** implemented — only inbound lead creation.

---

## 10. Production deployment

Follow [DEPLOY.md](DEPLOY.md) and [deploy/README.md](deploy/README.md) for the full **GoDaddy WordPress + VPS** checklist (bootstrap scripts, DNS, TLS, cron, backups).

### DNS (step by step)

1. Log in to your domain registrar / Cloudflare.
2. Add **A record**: `booking.yourmechaniconline.com` → VPS IP.
3. Add **A record**: `admin.yourmechaniconline.com` → same VPS IP.
4. Wait for DNS propagation (minutes to hours).
5. Set `YMO_PUBLIC_APP_URL` and `YMO_ADMIN_APP_URL` in production `.env`.

### File permissions

```bash
mkdir -p public/uploads/crm/resumes
chown -R www-data:www-data storage public/uploads
chmod -R u+rwX,g+rwX storage public/uploads
```

### Database on production

| Scenario | Steps |
|----------|-------|
| **New server** | `schema.sql` → `seed.sql` → `create_admin` CLI |
| **Migrating old booking DB** | Restore backup → `crm_migration_v1.sql` → `crm_migration_v2_team_rbac.sql` → sign in again |

### Smoke test (CRM + Team)

- [ ] `/admin/login` works (or admin subdomain login).
- [ ] Sidebar shows CRM section for admin role.
- [ ] **Team** and **Roles** pages load (`/admin/team`, `/admin/roles`).
- [ ] Create a Mechanic user → confirm limited sidebar (§2 example).
- [ ] Create manual lead → assign → log activity → convert to contact.
- [ ] Schedule follow-up → appears under **Follow-ups**.
- [ ] Draft campaign → send test email.
- [ ] Add recruitment candidate → upload resume PDF.
- [ ] Reports page loads; CSV export downloads.
- [ ] Webhook test creates a lead.

---

## 11. WordPress / marketing site links

| Action | Detail |
|--------|--------|
| “Book now” buttons | Link to `https://booking.yourmechaniconline.com/` |
| Enquiry forms | POST to CRM website webhook (§8) **or** manual lead entry in admin |
| Admin links | Do **not** expose admin URL on public pages |

---

## 12. Operational testing checklist

Use this before handing over to the client team.

### Access control (RBAC)

- [ ] Mechanic role: sees Dashboard, Bookings, Customers only
- [ ] Mechanic: `/admin/leads` returns 403
- [ ] Mechanic: can issue service invoice on a booking
- [ ] Sales role: sees CRM, not Team/Roles (unless granted)
- [ ] Admin: full Team + Roles access
- [ ] Role change requires re-login to take effect
- [ ] Cannot deactivate own account

### Leads & pipeline

- [ ] Manual lead entry (all sources in dropdown)
- [ ] List filters (source, stage, assignee, priority)
- [ ] Pipeline kanban view
- [ ] Assign lead to team member
- [ ] Log call / note / WhatsApp activity
- [ ] Update stage & priority
- [ ] Convert lead → contact
- [ ] Archive lead

### Follow-ups

- [ ] Create task from lead detail page
- [ ] “Due today” / “Overdue” filters
- [ ] Mark task done
- [ ] Cron sends reminder email (`cli/cron crm`)

### Contacts

- [ ] Create / edit contact
- [ ] Tags (create new tag inline)
- [ ] Export contacts CSV
- [ ] Import contacts CSV (Admin → Contacts → Import CSV)
- [ ] View booking history when contact linked to booking user

### Campaigns

- [ ] Draft email campaign → send now
- [ ] Schedule campaign → cron completes send
- [ ] SMS campaign (requires DLT template)

### Recruitment

- [ ] Add candidate → upload resume → schedule interview

### Reports

- [ ] Date range filter
- [ ] Lead source breakdown
- [ ] Team performance table
- [ ] Export leads CSV

### Integrations

- [ ] Meta webhook verification (GET challenge)
- [ ] Meta test lead → appears in CRM
- [ ] Website form webhook with valid signature
- [ ] WhatsApp inbound test → lead created

### Service invoices

- [ ] Issue invoice with 2+ line items and 18% GST
- [ ] Edit invoice — change line items, PDF regenerates
- [ ] Edit with “resend notify” sends updated SMS/email
- [ ] PDF shows YMO logo, line items, GST breakdown, staff name
- [ ] Customer receives email with PDF attachment (or mail stub in dev)
- [ ] Customer downloads PDF from account booking page
- [ ] Cancelled booking: invoice form hidden

---

## 13. Training & handover

| Item | Manual action |
|------|----------------|
| User training | Walk through admin login, leads, follow-ups, campaigns **per role** |
| Role assignment | Create staff in **Admin → Team**; tune permissions in **Admin → Roles** |
| Mechanic onboarding | Assign Mechanic role; show Bookings list and status updates only |
| SOP documents | Define when to move leads between pipeline stages |
| DLT / SMS compliance | Campaign copy must match registered MSG91 templates |
| Meta lead form mapping | Confirm form fields map to name/mobile/email in Meta |
| Backup routine | Daily MySQL dump + `public/uploads/` sync |
| Support contact | Monitor `storage/logs/` and webhook activity in Reports |

---

## 14. Not automated (known gaps)

| Requirement | Current state | Workaround |
|-------------|---------------|------------|
| Bulk **WhatsApp** campaigns | Inbound webhook only | Use provider’s broadcast tool; log activity manually in CRM |
| **Push notifications** | Not implemented | Email/SMS task reminders only |
| **Google Sheets** sync | Not implemented | CSV export / Import CSV on Contacts |
| **Assign mechanic to specific booking** | Not in schema | Mechanics see all bookings with `bookings.view` |
| **Email invite flow for new staff** | Admin sets password in Team UI | Share generated password securely |
| **Auto follow-up rules** | Manual task creation | Sales creates tasks when closing calls |
| **Sales executive dashboard** | Shared admin dashboard + CRM widget | Use Reports + Pipeline views |
| **Link CRM contact ↔ booking user** | Manual / on conversion | Link `crm_contacts.user_id` via SQL if needed |
| **Two-way WhatsApp chat** | Not implemented | Use WhatsApp Business app alongside CRM |
| **Live permission refresh** | Requires re-login | Sign out/in after role changes |
| **Delete issued invoices** | Not implemented | Edit in place or keep for audit trail |
| **Online payment on invoice** | Not implemented | Collect payment offline; invoice is informational |

---

## 15. Booking service invoices

Staff with **`bookings.edit`** (Administrator, Mechanic, etc.) can issue a **service invoice** from a booking detail page. Each invoice lists work performed, GST breakdown, and generates a PDF with the YMO logo and the name of the staff member who created it.

### Database migration (existing installs)

```powershell
Get-Content database\booking_invoice_migration_v1.sql | docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking
```

Fresh installs from `database/schema.sql` already include invoice tables.

### Install dompdf (PDF generation)

From the project root:

```bash
composer install
```

In Docker, run inside the app container if PHP/Composer is available there, or run on the host and ensure `vendor/` is mounted.

### SMS template (production)

Register a DLT template with MSG91 for invoice notification, then set:

```
YMO_TPL_INVOICE=your-dlt-template-id
```

Suggested template variables: `name`, `ref` (booking reference), `invoice_no`, `total` (formatted amount).

### Step-by-step — issue an invoice

1. Sign in as admin or mechanic → **Bookings** → open a booking (not cancelled).
2. In the **Service invoice** panel (right sidebar):
   - Add one or more **line items** (description + amount in ₹).
   - Choose **GST type**: Intra-state (CGST+SGST) or Inter-state (IGST).
   - Choose **GST rate**: 0%, 5%, 12%, 18%, or 28%.
   - Optionally add notes.
3. Click **Issue & send invoice**.
4. The system will:
   - Save the invoice and line items
   - Generate a PDF under `public/uploads/invoices/{year}/`
   - Send SMS to the customer (if `YMO_TPL_INVOICE` configured)
   - Email the customer with the PDF attached (if SMTP configured)
5. Invoice history appears on the booking page; customer can download from **My account → Booking detail**.

### Step-by-step — edit an invoice

1. On the booking page, find the invoice in **Service invoices** → click **Edit**.
2. Update line items, GST type/rate, or notes.
3. Leave **Send updated invoice to customer** checked to re-send SMS + email with the new PDF, or uncheck to save quietly.
4. Click **Save changes** — the PDF is regenerated (same invoice number; original “Prepared by” name is preserved).

### Who can do what

| Action | Permission |
|--------|------------|
| Issue invoice | `bookings.edit` |
| Edit invoice | `bookings.edit` |
| View invoice list / download PDF | `bookings.view` |
| Customer download | Own booking only (account area) |

### Production file permissions

```bash
mkdir -p public/uploads/invoices
chown -R www-data:www-data public/uploads/invoices
chmod -R u+rwX,g+rwX public/uploads/invoices
```

### Verify locally

1. Open http://localhost:8080/admin/bookings → pick a booking.
2. Add two line items, 18% intra-state GST → submit.
3. Confirm PDF download works and shows logo + your staff name.
4. Check `storage/logs/` for `[sms-stub]` / `[mail-stub]` in development.

---

## Quick reference — local Docker

```powershell
cd c:\Users\ayush\Desktop\ymo-codeigniter
docker compose up -d

# First-time database (new)
Get-Content database\schema.sql | docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking
Get-Content database\seed.sql   | docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking

# Migrations (existing DB only)
Get-Content database\crm_migration_v1.sql | docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking
Get-Content database\crm_migration_v2_team_rbac.sql | docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking
Get-Content database\booking_invoice_migration_v1.sql | docker compose exec -T db mysql -u ymo_user -pymo_pass ymo_booking

# PDF invoices (dompdf)
composer install

# First admin user
docker compose exec app php public/index.php cli/install create_admin admin@test.com "Test Admin"

# Test cron
docker compose exec app php public/index.php cli/cron crm
```

### Local URLs

| Page | URL |
|------|-----|
| Booking site | http://localhost:8080 |
| Admin login | http://localhost:8080/admin/login |
| Dashboard | http://localhost:8080/admin/dashboard |
| Bookings | http://localhost:8080/admin/bookings |
| Leads | http://localhost:8080/admin/leads |
| Team | http://localhost:8080/admin/team |
| Roles | http://localhost:8080/admin/roles |

---

## 16. Import legacy customer contacts (Excel → CSV)

Use this to load historical customer data from the YMO customer workbook into **CRM → Contacts**.

### Step 1 — Merge Excel tabs into one CSV

1. Copy the workbook to `import/source/YMO Customer data sheet.xlsx`.
2. Install Python dependency once: `pip install openpyxl`
3. Run from the project root:

```powershell
python import/scripts/merge_ymo_customer_excel.py "import/source/YMO Customer data sheet.xlsx"
```

Output:

- `import/staging/contacts_master.csv` — one row per unique mobile (visit history merged into `notes`)
- `import/staging/contacts_merge_report.json` — row counts and dedup stats

Expected: ~450–500 contacts from the Pune + exhibition sheets.

### Step 2 — Import via Admin UI

1. Sign in as admin → **Contacts** → **Import CSV**.
2. Upload `import/staging/contacts_master.csv`.
3. Review the preview (new vs duplicate counts).
4. Choose duplicate policy:
   - **Merge notes** (recommended) — append visit lines, add tags, keep existing email/name when CSV is sparse
   - **Skip** — leave existing contacts unchanged
   - **Update** — overwrite contact fields and tags from CSV
5. Click **Import now**.

Contacts with a matching booking **user** (same mobile or email) are linked automatically (`user_id`).

### Step 3 — Verify before production commit

Spot-check these names in the preview table after upload:

- Anoop, Sachin Chitnis (repeat visits merged into notes)
- Rahul, Snehal (exhibition / Wakad leads)

Expected dry-run on a fresh CRM: **~505 creates**, 0 duplicates. Re-import with **Merge notes** if you need to refresh visit history.

The merge report (`import/staging/contacts_merge_report.json`) lists invalid phone samples and name-only contacts for manual review.

### CSV format (any future import)

| Column | Required | Notes |
|--------|----------|-------|
| name | Yes | |
| mobile | No | 10-digit Indian mobile preferred |
| email | No | |
| workshop | No | Service location (e.g. G1 Pune, Wakad). CSV column `company` also accepted. |
| notes | No | Multi-line visit history is OK |
| tags | No | Comma-separated tag names |

---

*Last updated: includes Team & Roles RBAC (v2), booking service invoices with PDF + GST, contacts CSV import.*
