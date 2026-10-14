# Marketing theme v2 (design-only)

Visual refresh **without** changing SEO copy, URLs, or site behaviour.

## Status

- **Site-wide:** `marketing_theme.php` → `v2_all` = `TRUE` (all marketing URLs).
- **v2.1:** Radical hero site-wide (dark panel + optional full-bleed photo + metrics foot): home, services/hubs/brands/localities, FAQ/split content pages, and text-only minimal pages — same H1/intro/CTAs.
- **Rollback:** set `v2_all` => `FALSE`, or env `YMO_MARKETING_THEME=v1`, or per-page `'theme' => 'v1'`.
- **Views:** `home_v2`, `page_v2`, `contact_v2` (same text/links/forms as v1).

## Rules (do not break)

- Do **not** edit page `h1`, `title`, `meta_*`, `body`, or `faq` strings as part of theme work.
- URLs, redirects, booking links, nav, WhatsApp, CRM contact form, and schema inputs stay the same.
- Theme = CSS + view shells only.

## Enable / disable

| Method | Effect |
|--------|--------|
| `v2_all` in `application/config/marketing_theme.php` | All pages (current default) |
| `YMO_MARKETING_THEME=v2` or `v1` in `.env` / Docker | Overrides config when set |
| `v2_card_skin` in `marketing_theme.php` or `YMO_MARKETING_CARD_SKIN=default\|instrument` | Card chrome: light panels vs phosphor instrument panels |
| `'theme' => 'v2'` on a page row | Force v2 for one URL |
| `'theme' => 'v1'` on a page row | Force v1 when `v2_all` is true |

## Files

| File | Role |
|------|------|
| `public/assets/css/marketing-v2.css` | v2 surfaces, FAQ, footer, legacy body softening |
| `public/assets/css/marketing-v2-instrument.css` | Optional instrument / phosphor card skin |
| `application/views/marketing/*_v2.php` | Layout shells |
| `application/helpers/marketing_helper.php` | `marketing_is_theme_v2()`, `marketing_marketing_view()` |
| `application/core/MY_Controller.php` | View + body class resolution |

## QA after deploy

```bash
bash deploy/marketing/scripts/run-reports-in-docker.sh
```

Includes `theme-content-snapshot.php` (registry h1/meta fingerprint). Compare TSV before/after theme deploy — text columns should match.

Manual smoke: home, service page, FAQ, contact form POST, book/quick book links, nav dropdowns.

## VPS deploy

```bash
git pull
docker compose -f docker-compose.yml -f deploy/docker-compose.vps.yml -f deploy/docker-compose.prod.yml up -d app
```

No `marketing_pages_registry` cache clear needed for theme-only changes.

Optional rollback without git: `YMO_MARKETING_THEME=v1` in `.env` and recreate app container.
