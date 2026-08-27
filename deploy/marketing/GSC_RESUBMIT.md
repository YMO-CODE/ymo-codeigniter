# GSC resubmit checklist (manual — after www consolidation deploy)

Run after deploying apex→www fixes and verifying redirects.

## 1. Verify live (VPS)

```bash
bash deploy/marketing/scripts/verify_www_redirects.sh
python deploy/marketing/scripts/check_sitemap_live.py
```

All checks must pass before resubmitting.

## 2. Search Console → Sitemaps

1. Open [Google Search Console](https://search.google.com/search-console)
2. Property: `https://www.yourmechaniconline.com/`
3. **Sitemaps** → Submit: `https://www.yourmechaniconline.com/sitemap.xml`
4. Remove stale sitemap entries for apex (`https://yourmechaniconline.com/...`) if listed

## 3. URL Inspection → Request indexing

Inspect each URL (must be **www + HTTPS**), then **Request indexing**:

| Priority | URL |
|----------|-----|
| P0 | `https://www.yourmechaniconline.com/` |
| P0 | `https://www.yourmechaniconline.com/services/car-denting-and-painting-3000` |
| P0 | `https://www.yourmechaniconline.com/blog/best-oil-change-service-in-pune` |
| P1 | `https://www.yourmechaniconline.com/best-car-services-hinjewadi-pune` |
| P1 | `https://www.yourmechaniconline.com/affordable-car-servicing-in-wakad-pune` |
| P1 | `https://www.yourmechaniconline.com/the-best-car-servicing-in-baner` |
| P1 | `https://www.yourmechaniconline.com/car-servicing-in-warje-pune` |

## 4. Removals (optional)

If apex URLs still appear as duplicates after 2–4 weeks:

- GSC → **Removals** → Temporary removal for specific apex URLs still returning 200
- Or wait for recrawl after 301s propagate

## 5. Booking subdomain

Confirm `booking.yourmechaniconline.com/login` shows **noindex** (View Page Source or URL Inspection). Should drop from organic within a few weeks.

## 6. Track KPIs (weekly)

```bash
python deploy/marketing/scripts/fetch_gsc.py   # after API setup
python deploy/marketing/scripts/gsc_report.py
```

See plan targets: www homepage click share >80%, denting painting CTR >2%, booking clicks → 0.
