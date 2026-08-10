# Marketing site migration (WordPress → ymo-codeigniter)

Replaces GoDaddy WordPress at `www.yourmechaniconline.com`. Booking and admin subdomains are unchanged.

**Production cutover:** [CUTOVER.md](CUTOVER.md) (Phase 3 DNS + Phase 4 nginx/TLS).

## Env

```bash
YMO_MARKETING_APP_URL=https://www.yourmechaniconline.com
```

## Local testing

Add to `C:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1 www.yourmechaniconline.com
127.0.0.1 yourmechaniconline.com
127.0.0.1 booking.yourmechaniconline.com
```

Set in `.env` (or docker-compose environment):

```
YMO_MARKETING_APP_URL=http://www.yourmechaniconline.com:8080
YMO_PUBLIC_APP_URL=http://booking.yourmechaniconline.com:8080
YMO_TRUST_PROXY_HEADERS=
```

| Host | What you see |
|------|----------------|
| http://www.yourmechaniconline.com:8080/ | Marketing pages (same header when logged out) |
| http://booking.yourmechaniconline.com:8080/ | Booking app (same header when logged out) |

Both layouts load the same `public_header.php`:

- **Logged out** (marketing + booking + login/signup): Home, Services, Pune, Indore, About, Contact, Sign in, Book now
- **Logged in on booking**: Packages, My Bookings, My Vehicles, account menu

Do **not** use `http://localhost:8080/` — use the hostnames above so marketing vs booking routing works.

Visit http://www.yourmechaniconline.com:8080/ — “Book now” goes to http://booking.yourmechaniconline.com:8080/packages with the **same** logged-out header.

## Key files

| Path | Purpose |
|------|---------|
| `application/config/marketing_pages_data.php` | Landing page SEO + content |
| `application/config/marketing_redirects.php` | Legacy WP 301 rules |
| `application/controllers/marketing/*` | Marketing controllers |
| `application/helpers/marketing_helper.php` | `ymo_booking_url()`, redirects |
| `deploy/nginx/marketing.conf` | Apex→www + www proxy |
| `deploy/marketing/gsc/` | GSC export reference |

## Production DNS

Point `@` and `www` A records to VPS. See [GODADDY_DNS.md](../GODADDY_DNS.md).

## Content migration (Option A — SEO-safe)

**Strategy:** Keep exact URLs for every GSC page with clicks or strong internal links (~50 pages). All other legacy WordPress URLs (`tag/`, `category/`, deep nested paths) 301 to the nearest hub.

| File | Purpose |
|------|---------|
| `application/config/marketing_pages_data.php` | Tier-1 pages (hand-tuned SEO) |
| `application/config/marketing_pages_option_a.php` | Auto-generated stubs for remaining GSC pages |
| `application/config/marketing_redirects_option_a.php` | Auto-generated 301 rules (~400) |
| `deploy/marketing/scripts/audit_gsc_pages.py` | Compare GSC vs configured pages |
| `deploy/marketing/scripts/generate_option_a.py` | Regenerate stubs + redirects from GSC CSVs |

Regenerate after updating GSC exports in `deploy/marketing/gsc/`:

```bash
python deploy/marketing/scripts/generate_option_a.py
copy deploy\marketing\generated\pages_option_a.php application\config\marketing_pages_option_a.php
copy deploy\marketing\generated\redirects_option_a.php application\config\marketing_redirects_option_a.php
```

**Phase 2 (content):** Pull WordPress copy via REST API, then apply to page configs.

### One-time content pull (from your PC — needs access to yourmechaniconline.com)

```powershell
# 1. Download all WP pages into deploy/marketing/wp-cache/
powershell -File deploy/marketing/scripts/fetch_wp_cache.ps1

# 2. Merge SEO + body HTML into marketing page configs
python deploy/marketing/scripts/migrate_wp_from_cache.py
```

Or live (PHP in Docker, if container can reach WP):

```bash
docker exec ymo_app php /var/www/application/cli/migrate_wp_content.php
```

URL aliases for WP vs GSC path differences: `application/config/marketing_wp_aliases.php`.

Migration report: `deploy/marketing/generated/wp_migration_report.json`.

### Images (local assets)

After content migration, pull WordPress uploads into the repo and rewrite HTML:

```bash
python deploy/marketing/scripts/migrate_marketing_images.py
```

- Saves files under `public/assets/img/marketing/` (mirrors `wp-content/uploads/` paths)
- Rewrites `marketing_pages_data.php` and `marketing_pages_option_a.php` to use `/assets/img/marketing/...`
- Strips lazy-load placeholders from `<img>` tags
- Report: `deploy/marketing/generated/image_migration_report.json`

Re-run safely — existing files are skipped (cached).

### Trimmed Bootstrap CSS (marketing only)

Marketing pages load `public/assets/css/bootstrap-marketing.min.css` (~130 KiB) instead of the full vendor bundle (~227 KiB). Rebuild after changing Bootstrap usage in views, `marketing.css`, or page body HTML:

```bash
cd deploy/marketing
npm install
npm run build:bootstrap
npm run build:bootstrap-js   # Offcanvas + Carousel only (~29 KiB vs ~79 KiB full bundle)
npm run build:critical-css   # Above-fold CSS → marketing-critical.min.css (inlined in <head>)
npm run build:ymo-css        # ymo.min.css for production
```

If the trimmed file is missing, `marketing_head_assets.php` falls back to `assets/vendor/bootstrap/css/bootstrap.min.css`. JS falls back to `bootstrap.bundle.min.js`. CSS loads `ymo.min.css` when present, else `ymo.css`.
