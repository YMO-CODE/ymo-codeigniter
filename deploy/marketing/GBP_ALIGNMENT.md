# GBP ↔ Website alignment checklist

Use this when updating city hub pages or after GBP profile changes.

## NAP consistency (must match exactly on GBP and website)

| Field | Website source | GBP |
|-------|----------------|-----|
| Business name | Your Mechanic Online | Same |
| Phone | +91-7744-065904 | Same |
| Email | contactus@yourmechaniconline.com | Same |
| Cities | Pune, Indore, Nashik | One profile per city |

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

```powershell
# 1. Export fresh GSC data to deploy/marketing/gsc/
python deploy/marketing/scripts/audit_gsc_pages.py
python deploy/marketing/scripts/check_marketing_pages.py
python deploy/marketing/scripts/check_marketing_links.py
```

Submit updated sitemap in GSC: `https://www.yourmechaniconline.com/sitemap.xml`
