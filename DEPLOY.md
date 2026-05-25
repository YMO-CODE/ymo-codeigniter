# Deployment guide — GoDaddy WordPress + VPS (booking + admin)

This checklist covers production hosting when your **domain and WordPress marketing site are on GoDaddy**, and the **YMO booking + admin + CRM app** runs on a separate **Linux VPS**.

- **Customer:** `https://booking.yourmechaniconline.com/`
- **Admin:** `https://admin.yourmechaniconline.com/` (short paths: `/login`, `/dashboard`, …)
- **WordPress:** `https://yourmechaniconline.com` and `https://www.…` — unchanged on GoDaddy

Both app hostnames use the **same** PHP app, MySQL database, and uploads on the VPS.

**Automation in this repo:** see [`deploy/README.md`](deploy/README.md) for scripts (`vps-bootstrap.sh`, `deploy-app.sh`, `setup-nginx.sh`, `backup.sh`).

---

## Architecture

| Hostname | DNS | Host |
|----------|-----|------|
| `@` / `www` | GoDaddy (unchanged) | GoDaddy managed WordPress |
| `booking` | GoDaddy A → VPS IP | Docker app on VPS |
| `admin` | GoDaddy A → VPS IP | Same VPS |

Detailed GoDaddy DNS steps: [`deploy/GODADDY_DNS.md`](deploy/GODADDY_DNS.md).

WordPress link updates: [`deploy/wordpress/README.md`](deploy/wordpress/README.md).

---

## 1. Provision the VPS (one time)

On a fresh **Ubuntu 22.04/24.04** VPS (2 GB RAM recommended):

```bash
sudo bash deploy/scripts/vps-bootstrap.sh
```

This installs Docker, nginx, certbot, and configures **ufw** (ports 22, 80, 443 only).

Manual alternative: see §1 in previous revisions — Docker Engine, Compose plugin, nginx, certbot.

---

## 2. GoDaddy DNS

In GoDaddy DNS for your domain:

1. **Leave** `@` and `www` pointing at WordPress.
2. **Add A records** (not URL forwarding):
   - `booking` → VPS IPv4
   - `admin` → same VPS IPv4
3. Wait for propagation before running certbot.

Full walkthrough: [`deploy/GODADDY_DNS.md`](deploy/GODADDY_DNS.md).

---

## 3. Deploy the app

```bash
git clone <repo-url> /opt/ymo-codeigniter
cd /opt/ymo-codeigniter
cp .env.example .env
# Or: cp deploy/env.production.example .env
nano .env
bash deploy/scripts/deploy-app.sh
```

Production Compose stack:

```bash
docker compose -f docker-compose.yml \
  -f deploy/docker-compose.vps.yml \
  -f deploy/docker-compose.prod.yml up -d --build
```

The **prod overlay** loads `.env`, binds the app to **127.0.0.1:8080**, and **does not publish MySQL** publicly.

### Required `.env` values (production)

| Variable | Example |
|----------|---------|
| `CI_ENV` | `production` |
| `YMO_PUBLIC_APP_URL` | `https://booking.yourmechaniconline.com` |
| `YMO_ADMIN_APP_URL` | `https://admin.yourmechaniconline.com` |
| `YMO_TRUST_PROXY_HEADERS` | `1` |
| `YMO_ENCRYPTION_KEY` | 32-byte random — **never rotate** after go-live with real data |
| `YMO_DB_PASS` | Strong password |
| `YMO_MAIL_*` | SMTP |
| `YMO_MSG91_*` / `YMO_TPL_*` | SMS (DLT) |
| `CRM_*` | Webhooks / Meta |

Run `composer install` on the server (or let `deploy-app.sh` run it via Docker) for **dompdf** (invoices).

### Database

**Fresh:**

```bash
docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml \
  exec -T db mysql -uymo_user -p ymo_booking < database/schema.sql
docker compose ... exec -T db mysql ... < database/seed.sql
```

**Upgrading an existing DB:** run `crm_migration_v1.sql`, `crm_migration_v2_team_rbac.sql`, `booking_invoice_migration_v1.sql` once each.

### First admin

```bash
docker compose ... exec app php public/index.php cli/install create_admin admin@yourmechaniconline.com "YMO Admin"
```

---

## 4. TLS + nginx

After DNS resolves to the VPS:

```bash
sudo bash deploy/scripts/setup-nginx.sh booking.yourmechaniconline.com admin.yourmechaniconline.com
```

Manual config template: [`deploy/nginx/booking-admin.conf`](deploy/nginx/booking-admin.conf).

Restart app after `.env` URL changes:

```bash
docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml restart app
```

---

## 5. WordPress (GoDaddy)

- Point all **Book now** buttons to `https://booking.yourmechaniconline.com/`
- Optional enquiry → CRM webhook: [`deploy/wordpress/ymo-lead-webhook.php`](deploy/wordpress/ymo-lead-webhook.php)
- Do **not** link admin URLs publicly

---

## 6. Cron + backups

Install host crontab from [`deploy/cron/ymo.crontab.example`](deploy/cron/ymo.crontab.example) (adjust `/opt/ymo-codeigniter` path).

Daily backup script:

```bash
bash deploy/scripts/backup.sh
```

Backups default to `/var/backups/ymo/` (MySQL gzip + uploads tarball).

---

## 7. Data migration (from another host)

1. `mysqldump` from old server → restore into Docker MySQL.
2. Rsync `public/uploads/` (vehicles, CRM resumes, invoices).
3. Keep **`YMO_ENCRYPTION_KEY`** identical if restoring encrypted data.

---

## 8. Smoke test checklist

- [ ] `https://booking…/` — home, packages, booking flow
- [ ] `https://admin…/login` — staff sign-in
- [ ] `https://booking…/admin/login` → **301** to admin subdomain
- [ ] WordPress “Book now” opens booking site
- [ ] Cron log shows reminder/CRM runs
- [ ] Invoice PDF generates (dompdf / `vendor/` present)
- [ ] `/application/` not web-accessible

---

## 9. Day-2 ops

- Tail `storage/logs/log-*.php`, `storage/logs/cron.log`
- Monitor `deploy/scripts/backup.sh` output in `storage/logs/backup.log`
- Keep a secure offline copy of `.env`

For CRM go-live (MSG91, Meta webhooks, team RBAC): [CRM_SETUP.md](CRM_SETUP.md).
