#!/usr/bin/env python3
"""
Fetch Google Search Console performance data into deploy/marketing/gsc/*.csv.

Requires a service account with access to the Search Console property.
See deploy/marketing/GSC_SETUP.md for one-time setup.

Examples:
  python deploy/marketing/scripts/fetch_gsc.py
  python deploy/marketing/scripts/fetch_gsc.py --days 90
  python deploy/marketing/scripts/fetch_gsc.py --list-sites
"""

from __future__ import annotations

import argparse
import json
import sys
from datetime import datetime, timezone
from pathlib import Path

_SCRIPTS = Path(__file__).resolve().parent
if str(_SCRIPTS) not in sys.path:
    sys.path.insert(0, str(_SCRIPTS))

from gsc_lib import (
    GSC_DIR,
    build_service,
    configure_stdout,
    credentials_path,
    date_range,
    fetch_rows,
    list_accessible_sites,
    row_to_metrics,
    site_url,
    write_csv,
)

INDEXING_NOTE = GSC_DIR / "INDEXING_EXPORT.md"


def write_metadata(start_date: str, end_date: str, property_url: str) -> None:
    fetched = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M UTC")
    write_csv(
        GSC_DIR / "Metadata.csv",
        ["Property", "Value"],
        [
            ["Sitemap", "All known pages"],
            ["Search Console property", property_url],
            ["Date range", f"{start_date} to {end_date}"],
            ["Fetched at", fetched],
            ["Source", "Search Console API (searchanalytics.query)"],
        ],
    )
    write_csv(
        GSC_DIR / "Filters.csv",
        ["Filter", "Value"],
        [
            ["Search type", "Web"],
            ["Date", f"{(datetime.fromisoformat(end_date) - datetime.fromisoformat(start_date)).days + 1} days"],
        ],
    )


def write_indexing_note() -> None:
    INDEXING_NOTE.write_text(
        "# Indexing coverage (manual export)\n\n"
        "Performance CSVs in this folder are refreshed automatically by "
        "`fetch_gsc.py`.\n\n"
        "Google does not expose bulk indexing-issue history via the Search "
        "Console API. To refresh `Critical issues.csv` and `Chart-2.csv`, "
        "export them from Search Console → Indexing → Pages (Export), then "
        "drop the files here.\n\n"
        "Recommended: once per month, or after a major site migration.\n",
        encoding="utf-8",
    )


def fetch_performance(service, property_url: str, start_date: str, end_date: str) -> None:
    print(f"Property: {property_url}")
    print(f"Range:    {start_date} → {end_date}")

    # By date → Chart.csv
    date_rows = fetch_rows(service, property_url, start_date, end_date, ["date"])
    date_rows.sort(key=lambda r: r["keys"][0])
    chart_data = []
    for row in date_rows:
        d = row["keys"][0]
        clicks, impressions, ctr, position = row_to_metrics(row)
        chart_data.append([d, clicks, impressions, ctr, position])
    write_csv(GSC_DIR / "Chart.csv", ["Date", "Clicks", "Impressions", "CTR", "Position"], chart_data)
    print(f"  Chart.csv      {len(chart_data)} days")

    dimension_exports = [
        ("page", "Pages.csv", "Top pages"),
        ("query", "Queries.csv", "Top queries"),
        ("device", "Devices.csv", "Device"),
        ("country", "Countries.csv", "Country"),
    ]
    for dim, filename, header_label in dimension_exports:
        rows = fetch_rows(service, property_url, start_date, end_date, [dim])
        rows.sort(key=lambda r: (-int(r.get("clicks") or 0), -int(r.get("impressions") or 0)))
        data = []
        for row in rows:
            key = row["keys"][0]
            clicks, impressions, ctr, position = row_to_metrics(row)
            data.append([key, clicks, impressions, ctr, position])
        write_csv(GSC_DIR / filename, [header_label, "Clicks", "Impressions", "CTR", "Position"], data)
        print(f"  {filename:<14} {len(data)} rows")

    # Sitemap status snapshot (API-supported; not the same as index coverage chart)
    try:
        sitemaps = service.sitemaps().list(siteUrl=property_url).execute()
        snapshot = {
            "fetched_at": datetime.now(timezone.utc).isoformat(),
            "property": property_url,
            "sitemaps": sitemaps.get("sitemap") or [],
        }
        (GSC_DIR / "sitemaps_snapshot.json").write_text(
            json.dumps(snapshot, indent=2),
            encoding="utf-8",
        )
        print("  sitemaps_snapshot.json")
    except Exception as exc:
        print(f"  WARN sitemaps list failed: {exc}")

    write_metadata(start_date, end_date, property_url)
    write_indexing_note()


def main() -> int:
    configure_stdout()
    parser = argparse.ArgumentParser(description="Fetch GSC performance CSVs via API.")
    parser.add_argument("--days", type=int, help="Lookback days (default: YMO_GSC_DAYS or 487)")
    parser.add_argument("--list-sites", action="store_true", help="List accessible properties and exit")
    parser.add_argument("--property", help="Override YMO_GSC_PROPERTY")
    args = parser.parse_args()

    if args.list_sites:
        if credentials_path() is None:
            print("No credentials. Set YMO_GSC_CREDENTIALS in .env — see GSC_SETUP.md")
            return 2
        service = build_service()
        sites = list_accessible_sites(service)
        if not sites:
            print("No Search Console properties found for this service account.")
            print("Add the service account email as a Full user on the property.")
            return 1
        print("Accessible Search Console properties:")
        for s in sites:
            print(f"  {s}")
        return 0

    if credentials_path() is None:
        print("SKIP  GSC credentials not configured (YMO_GSC_CREDENTIALS).")
        print("      See deploy/marketing/GSC_SETUP.md")
        return 2

    property_url = args.property or site_url()
    start_date, end_date = date_range(args.days)

    try:
        service = build_service()
        fetch_performance(service, property_url, start_date, end_date)
    except FileNotFoundError as exc:
        print(f"ERROR {exc}")
        return 2
    except ImportError as exc:
        print(f"ERROR {exc}")
        return 1
    except Exception as exc:
        err = str(exc)
        if "403" in err or "Forbidden" in err or "insufficientPermissions" in err:
            print("ERROR Search Console API denied access.")
            print("  1. Enable 'Google Search Console API' in Google Cloud Console")
            print("  2. Add the service account email as a Full user on the property")
            print("  3. Run: python deploy/marketing/scripts/fetch_gsc.py --list-sites")
            return 1
        if "404" in err or "not found" in err.lower():
            print(f"ERROR Property not found: {property_url!r}")
            print("  Run --list-sites to see valid property URLs.")
            return 1
        raise

    print(f"\nOK    GSC data written to {GSC_DIR}")
    print("      Run: python deploy/marketing/scripts/gsc_report.py")
    return 0


if __name__ == "__main__":
    sys.exit(main())
