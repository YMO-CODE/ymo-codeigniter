# Marketing cutover — Phase 3 & 4

Replace GoDaddy WordPress at `@` / `www` with the CodeIgniter marketing site on your **existing VPS** (same app as booking/admin).

**Do not change** `booking` or `admin` DNS records.

---

## Before you start

- [ ] Phase 1 done: latest code on VPS (`deploy-app.sh` or `sync-code.sh`)
- [ ] `.env` on VPS (no `:8080`, all HTTPS):
  ```bash
  YMO_MARKETING_APP_URL=https://www.yourmechaniconline.com
  YMO_PUBLIC_APP_URL=https://booking.yourmechaniconline.com
  YMO_ADMIN_APP_URL=https://admin.yourmechaniconline.com
  YMO_TRUST_PROXY_HEADERS=1
  YMO_COOKIE_DOMAIN=.yourmechaniconline.com
  CI_ENV=production
  ```
- [ ] Booking + admin still work over HTTPS
- [ ] Note your VPS IP (same as booking): `nslookup booking.yourmechaniconline.com`

Optional: lower GoDaddy TTL to **600** one day before cutover.

---

## Phase 3 — DNS (GoDaddy)

1. GoDaddy → **DNS** for `yourmechaniconline.com`
2. **Update** (do not delete booking/admin records):

| Type | Name | Value | Notes |
|------|------|-------|-------|
| A | `@` | `YOUR_VPS_IP` | Same IP as `booking` |
| A | `www` | `YOUR_VPS_IP` | Or CNAME `www` → `@` |

3. **Remove** any **Forwarding** rules on `@` or `www` (forwarding breaks Host headers).
4. **Leave unchanged:** `booking`, `admin`, MX, TXT (email verification).

### Verify (wait 5–30 min)

From your laptop:

```bash
nslookup www.yourmechaniconline.com
nslookup yourmechaniconline.com
```

Both should return your VPS IP (e.g. same as booking).

---

## Phase 4 — nginx + TLS (VPS)

SSH to the VPS:

```bash
cd /opt/ymo-codeigniter
git pull origin master   # includes setup-nginx-marketing.sh + base_url fix

sudo bash deploy/scripts/setup-nginx-marketing.sh \
  yourmechaniconline.com \
  www.yourmechaniconline.com
```

This script:

- Adds `/etc/nginx/sites-enabled/ymo-marketing` (separate from booking/admin)
- Issues Let's Encrypt cert for apex + www
- Proxies `www` → Docker app on `127.0.0.1:8080`
- 301 apex → `https://www.…`

Restart app after DNS + nginx:

```bash
docker compose -f docker-compose.yml \
  -f deploy/docker-compose.vps.yml \
  -f deploy/docker-compose.prod.yml restart app
```

---

## Smoke test

- [ ] `https://www.yourmechaniconline.com/` — styled home, no mixed-content errors in console
- [ ] `https://yourmechaniconline.com/` — redirects to www
- [ ] Service page e.g. `/services/car-brake-repair` — cards + FAQ layout
- [ ] `/contact-us` — form submits → CRM Leads (if enabled)
- [ ] **Book now** → `https://booking.yourmechaniconline.com/packages`
- [ ] `https://booking.yourmechaniconline.com/packages` — unchanged
- [ ] `https://admin.yourmechaniconline.com/login` — unchanged
- [ ] Legacy WP URL (from GSC) → 301 to nearest hub

---

## Rollback

1. GoDaddy: point `@` and `www` back to WordPress IP
2. Optional: `sudo rm /etc/nginx/sites-enabled/ymo-marketing && sudo nginx -t && sudo systemctl reload nginx`

Booking and admin are unaffected.

---

## After cutover is stable (1–2 weeks)

- Decommission GoDaddy WordPress
- Submit updated sitemap in Google Search Console
- Monitor GSC for 404s; add redirects in `marketing_redirects.php` if needed
