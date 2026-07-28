# GoDaddy DNS — WordPress + YMO subdomains

Keep your **marketing site** on GoDaddy WordPress until cutover, then point **`@` and `www`** at your VPS (see [marketing/README.md](marketing/README.md)). Point only **`booking`** and **`admin`** subdomains at your VPS during initial setup.

## Before you start

- Provision the VPS and note its **public IPv4** (e.g. `203.0.113.50`).
- Optional: lower TTL to **600 seconds** (10 min) one day before cutover for faster rollback.

## Step-by-step (GoDaddy)

1. Sign in at [https://dcc.godaddy.com](https://dcc.godaddy.com).
2. Open **My Products** → your domain → **DNS** (or **Manage DNS**).
3. **Do not change** records that serve WordPress:
   - `@` (apex) — usually points to GoDaddy Website Builder / WordPress IP
   - `www` — CNAME or A to the same WordPress host
4. **Add** two new records:

| Type | Name    | Value        | TTL  |
|------|---------|--------------|------|
| A    | booking | `YOUR_VPS_IP`| 600  |
| A    | admin   | `YOUR_VPS_IP`| 600  |

5. **Remove** any existing **Forwarding** rules for `booking` or `admin` if present — forwarding breaks `Host:` headers required by the app.
6. Save. Propagation usually takes **5–30 minutes** (up to 48h globally).

## Verify

From your laptop:

```bash
nslookup booking.yourmechaniconline.com
nslookup admin.yourmechaniconline.com
```

Both should return your VPS IP.

After nginx + TLS are configured:

```bash
curl -I https://booking.yourmechaniconline.com/
curl -I https://admin.yourmechaniconline.com/login
```

## WordPress stays on GoDaddy

| Hostname | Where it lives |
|----------|----------------|
| `yourmechaniconline.com` | **VPS** (301 → www) after marketing cutover |
| `www.yourmechaniconline.com` | **VPS** — marketing site (same app as booking) |
| `booking.yourmechaniconline.com` | Your VPS |
| `admin.yourmechaniconline.com` | Your VPS |

**Migration:** Point `@` and `www` A records to the VPS IP (same as booking). Enable [nginx/marketing.conf](nginx/marketing.conf). Set `YMO_MARKETING_APP_URL=https://www.yourmechaniconline.com` in `.env`. See [marketing/README.md](marketing/README.md).

Previously WordPress on GoDaddy — decommission after cutover is stable.

Update marketing CTAs to `https://booking.yourmechaniconline.com/` — see [wordpress/README.md](wordpress/README.md) (legacy).

## Rollback

If something goes wrong, delete the `booking` and `admin` A records (or point them elsewhere). WordPress on `@` / `www` is unaffected.
