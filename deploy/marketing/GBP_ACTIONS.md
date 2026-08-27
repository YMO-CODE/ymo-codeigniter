# GBP off-site action checklist

Code changes align NAP in [`marketing_cities.php`](../../application/config/marketing_cities.php). Complete these manually in Google Business Profile.

## Phone numbers (aligned in site config)

| City | Voice (GBP + calls) | WhatsApp (description/posts only) |
|------|---------------------|-----------------------------------|
| Pune / Hinjewadi / Warje | +91-7558783868 | +91-7744065904 |
| Nashik | +91-7558303868 | +91-7744065904 |
| Indore | +91-7744065904 | +91-7744065904 |

**Do not** use 7744065904 as the GBP primary phone — it is WhatsApp API only.

## Pune (existing — verify)

- [ ] Profile complete: Your Mechanic Online (Hinjewadi) — 4.8★, 306 reviews
- [ ] Website: `https://www.yourmechaniconline.com`
- [ ] `gbp_url` set in `marketing_cities.php` (Pune) — feeds JSON-LD `sameAs`
- [ ] Set `YMO_GBP_PLACE_ID` in `.env` for direct review links (optional; falls back to share URL)

## Warje (incomplete)

- [ ] Rename or merge into Hinjewadi profile if duplicate (recommended — one strong listing beats two weak ones)
- [ ] If kept separate: complete hours, services, photos, description; link website to `/car-servicing-in-warje-pune`
- [ ] Voice phone: 7558783868
- [ ] Site page (after deploy): `https://www.yourmechaniconline.com/car-servicing-in-warje-pune`

## Nashik (incomplete)

- [ ] Complete profile: hours, services, service areas (College Road, Nashik Road, etc.)
- [ ] Voice phone: 7558303868
- [ ] Add `gbp_url` to `marketing_cities.php` → `nashik.gbp_url` when live
- [ ] Push reviews — high interactions, few reviews vs Pune

## Indore (missing listing)

- [ ] Create new GBP for Indore service area
- [ ] Match neighbourhoods in `marketing_cities.php` → `indore.localities`
- [ ] Link to `https://www.yourmechaniconline.com/locations/indore`
- [ ] Add `gbp_url` to `marketing_cities.php` → `indore.gbp_url` when live

## Review requests

Review email/SMS now uses `marketing_gbp_review_url()` — set `YMO_GBP_PLACE_ID` for writereview links, or uses Pune share URL as fallback.
