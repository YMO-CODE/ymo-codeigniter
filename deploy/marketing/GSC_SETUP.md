# Google Search Console API (automated fetch → deploy/marketing/gsc/)

One-time setup so `fetch_gsc.py` can pull fresh performance data without manual CSV exports.

## 1. Google Cloud project

1. Open [Google Cloud Console](https://console.cloud.google.com/)
2. Create or select a project (e.g. `ymo-marketing`)
3. **APIs & Services → Enable APIs** → enable **Google Search Console API**

## 2. Service account

1. **IAM & Admin → Service Accounts → Create**
2. Name: `ymo-gsc-fetch` (any name is fine)
3. Skip optional role grants (Search Console uses property-level sharing, not GCP IAM roles)
4. **Keys → Add key → JSON** → download the file

Store the JSON **outside git** (recommended paths):

| Location | Notes |
|----------|--------|
| `deploy/marketing/gsc-credentials.json` | Gitignored; easy for local + VPS |
| `C:\Users\you\secrets\ymo-gsc.json` | Absolute path in `.env` |

## 3. Grant Search Console access

1. Open [Google Search Console](https://search.google.com/search-console)
2. Select property **https://www.yourmechaniconline.com/** (or Domain property if you use that)
3. **Settings → Users and permissions → Add user**
4. Paste the service account email (`ymo-gsc-fetch@….iam.gserviceaccount.com`)
5. Permission: **Full** (readonly API scope still applies; Full is required for API access on many setups)

## 4. Configure `.env`

Add to repo root `.env` (and VPS `/opt/ymo-codeigniter/.env`):

```bash
# Google Search Console API
YMO_GSC_PROPERTY=https://www.yourmechaniconline.com/
YMO_GSC_CREDENTIALS=deploy/marketing/gsc-credentials.json
YMO_GSC_DAYS=487
```

If the JSON lives elsewhere:

```bash
YMO_GSC_CREDENTIALS=C:/Users/you/secrets/ymo-gsc.json
```

## 5. Install Python dependencies

```bash
pip install -r deploy/marketing/requirements-gsc.txt
```

On VPS (once):

```bash
pip3 install -r deploy/marketing/requirements-gsc.txt
```

## 6. Verify and fetch

```bash
# List properties this service account can see
python deploy/marketing/scripts/fetch_gsc.py --list-sites

# Pull latest data (writes deploy/marketing/gsc/*.csv)
python deploy/marketing/scripts/fetch_gsc.py

# Human-readable summary
python deploy/marketing/scripts/gsc_report.py

# Full weekly SEO audit (fetch + report + page checks)
python deploy/marketing/scripts/weekly_seo_audit.py
```

PowerShell:

```powershell
pip install -r deploy/marketing/requirements-gsc.txt
python deploy/marketing/scripts/fetch_gsc.py --list-sites
python deploy/marketing/scripts/fetch_gsc.py
python deploy/marketing/scripts/gsc_report.py
```

## What gets fetched automatically

| File | Source |
|------|--------|
| `Chart.csv` | Clicks/impressions by day |
| `Pages.csv` | By page URL |
| `Queries.csv` | By search query |
| `Devices.csv` | Mobile / desktop / tablet |
| `Countries.csv` | By country |
| `Metadata.csv` | Fetch timestamp + date range |
| `sitemaps_snapshot.json` | Sitemap submit status via API |

## Manual exports (still needed)

Google does **not** expose bulk indexing-issue history via API. Once a month (or after migration), export from Search Console → **Indexing → Pages** and save:

- `Critical issues.csv`
- `Chart-2.csv` (indexed vs not indexed over time)

Drop them into `deploy/marketing/gsc/`. See `gsc/INDEXING_EXPORT.md` after first fetch.

## VPS cron (optional)

Weekly refresh every Monday 06:00 IST (~00:30 UTC):

```cron
30 0 * * 1 cd /opt/ymo-codeigniter && /usr/bin/python3 deploy/marketing/scripts/fetch_gsc.py && /usr/bin/python3 deploy/marketing/scripts/gsc_report.py >> /var/log/ymo-gsc.log 2>&1
```

## Troubleshooting

| Error | Fix |
|-------|-----|
| `No credentials` | Set `YMO_GSC_CREDENTIALS` in `.env` |
| `403 / insufficientPermissions` | Add service account as Full user on the GSC property; enable Search Console API |
| `404 property not found` | Run `--list-sites`; copy exact property URL into `YMO_GSC_PROPERTY` |
| `No Search Console properties` | Service account not added to GSC, or wrong Google account owns the property |
| Empty last few days | Normal — GSC lags ~2–3 days; script ends date at today−3 |

## For Cursor / agents

After setup, ask: *“Run GSC fetch and report where we’re at.”*

The agent should run:

```bash
pip install -r deploy/marketing/requirements-gsc.txt
python deploy/marketing/scripts/fetch_gsc.py
python deploy/marketing/scripts/gsc_report.py
python deploy/marketing/scripts/audit_gsc_pages.py
```

Credentials are read from `.env`; never commit the JSON key file.
