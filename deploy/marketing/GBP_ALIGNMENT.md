# GBP ↔ Website alignment checklist

Use this when updating city hub pages or after GBP profile changes.

## NAP consistency (must match exactly on GBP and website)

| Field | Website source | GBP |
|-------|----------------|-----|
| Business name | Your Mechanic Online | Same |
| Voice phone (Pune) | +91-7558783868 | Same |
| Voice phone (Nashik) | +91-7558303868 | Same |
| WhatsApp | +91-7744065904 | Description/posts only — not primary phone |
| Email | contactus@yourmechaniconline.com | Same |
| Cities | Pune, Indore, Nashik | One profile per city |

See [GBP_ACTIONS.md](GBP_ACTIONS.md) for profile completion checklist.

## City hub pages to verify

- `/locations/pune` — neighbourhoods + service links
- `/locations/indore` — Deoguradia, Vijay Nagar, Palasia, etc.
- `/locations/nashik` — College Road, Nashik Road, Uday Nagar, etc.

## GBP profile fields to align

1. **Service categories** — match service pages (AC repair, denting, periodic service, etc.)
2. **Service area** — match `neighborhoods` in `application/config/marketing_cities.php`
3. **Hours** — reflect on contact page and city NAP blocks
4. **Website URL** — `https://www.yourmechaniconline.com`
5. **Booking link** — `https://booking.yourmechaniconline.com/packages`

## After GBP URL is available

Set `gbp_url` in `application/config/marketing_cities.php` for each city — this feeds `sameAs` in JSON-LD.

## Weekly SEO audit

```bash
# Automated (recommended) — one-time setup: deploy/marketing/GSC_SETUP.md
python -m pip install -r deploy/marketing/requirements-gsc.txt
python deploy/marketing/scripts/fetch_gsc.py
python deploy/marketing/scripts/gsc_report.py
python deploy/marketing/scripts/weekly_seo_audit.py

# VPS cron (Mondays): deploy/scripts/cron-gsc-weekly.sh

# After www deploy — verify redirects before GSC resubmit:
bash deploy/marketing/scripts/verify_www_redirects.sh

# Manual GSC resubmit steps: deploy/marketing/GSC_RESUBMIT.md
# GBP off-site checklist: deploy/marketing/GBP_ACTIONS.md
```

Submit updated sitemap in GSC: `https://www.yourmechaniconline.com/sitemap.xml`
