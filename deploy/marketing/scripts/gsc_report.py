#!/usr/bin/env python3
"""Print a Search Console status summary from deploy/marketing/gsc/*.csv."""

from __future__ import annotations

import csv
import sys
from collections import defaultdict
from pathlib import Path

_SCRIPTS = Path(__file__).resolve().parent
if str(_SCRIPTS) not in sys.path:
    sys.path.insert(0, str(_SCRIPTS))

from gsc_lib import GSC_DIR, configure_stdout

ROOT = Path(__file__).resolve().parents[3]


def read_chart() -> list[dict[str, str]]:
    path = GSC_DIR / "Chart.csv"
    if not path.is_file():
        return []
    return list(csv.DictReader(path.open(encoding="utf-8")))


def read_queries() -> list[tuple[int, int, float, str]]:
    path = GSC_DIR / "Queries.csv"
    if not path.is_file():
        return []
    rows = []
    for row in csv.DictReader(path.open(encoding="utf-8")):
        q = row.get("Top queries") or row.get("Query") or ""
        try:
            c = int(row.get("Clicks") or 0)
        except ValueError:
            c = 0
        try:
            i = int(row.get("Impressions") or 0)
        except ValueError:
            i = 0
        try:
            pos = float(row.get("Position") or 0)
        except ValueError:
            pos = 0.0
        rows.append((c, i, pos, q))
    rows.sort(key=lambda x: (-x[0], -x[1]))
    return rows


def read_pages() -> list[tuple[int, int, float, str]]:
    path = GSC_DIR / "Pages.csv"
    if not path.is_file():
        return []
    rows = []
    for row in csv.DictReader(path.open(encoding="utf-8")):
        url = row.get("Top pages") or row.get("Page") or ""
        if "yourmechaniconline.com" not in url or "booking." in url:
            continue
        try:
            c = int(row.get("Clicks") or 0)
        except ValueError:
            c = 0
        if c <= 0:
            continue
        try:
            i = int(row.get("Impressions") or 0)
        except ValueError:
            i = 0
        try:
            pos = float(row.get("Position") or 0)
        except ValueError:
            pos = 0.0
        path_part = url.split("yourmechaniconline.com")[-1].rstrip("/") or "/"
        rows.append((c, i, pos, path_part))
    rows.sort(reverse=True)
    return rows


def metadata_line(key: str) -> str | None:
    path = GSC_DIR / "Metadata.csv"
    if not path.is_file():
        return None
    for row in csv.DictReader(path.open(encoding="utf-8")):
        if row.get("Property") == key:
            return row.get("Value")
    return None


def main() -> int:
    configure_stdout()
    chart = read_chart()
    if not chart:
        print("No Chart.csv in deploy/marketing/gsc/")
        print("Run: python deploy/marketing/scripts/fetch_gsc.py")
        return 1

    clicks = sum(int(r["Clicks"]) for r in chart)
    imps = sum(int(str(r["Impressions"]).replace(",", "")) for r in chart)
    pos_w = sum(
        float(r["Position"]) * int(str(r["Impressions"]).replace(",", ""))
        for r in chart
    )

    fetched = metadata_line("Fetched at") or "(unknown — manual export?)"
    date_range = metadata_line("Date range") or f"{chart[0]['Date']} to {chart[-1]['Date']}"

    print("=== GSC SUMMARY ===")
    print(f"Data:     {date_range}")
    print(f"Fetched:  {fetched}")
    print(f"Clicks:   {clicks:,}  |  Impressions: {imps:,}  |  CTR: {clicks/imps*100:.2f}%  |  Avg pos: {pos_w/imps:.1f}")

    last28 = chart[-28:]
    prev28 = chart[-56:-28] if len(chart) >= 56 else []
    if last28:
        c28 = sum(int(r["Clicks"]) for r in last28)
        i28 = sum(int(str(r["Impressions"]).replace(",", "")) for r in last28)
        print(f"\nLast 28d: {c28} clicks ({c28/28:.1f}/day), {i28:,} imp, CTR {c28/i28*100:.2f}%")
    if prev28:
        cprev = sum(int(r["Clicks"]) for r in prev28)
        iprev = sum(int(str(r["Impressions"]).replace(",", "")) for r in prev28)
        chg = 100 * (c28 - cprev) / cprev if cprev else 0
        print(f"Prior 28d: {cprev} clicks — change {chg:+.0f}%")

    monthly: dict[str, dict[str, float]] = defaultdict(lambda: {"c": 0, "i": 0, "pw": 0.0})
    for r in chart:
        m = r["Date"][:7]
        i = int(str(r["Impressions"]).replace(",", ""))
        monthly[m]["c"] += int(r["Clicks"])
        monthly[m]["i"] += i
        monthly[m]["pw"] += float(r["Position"]) * i

    print("\n=== MONTHLY (last 6) ===")
    for m in sorted(monthly.keys())[-6:]:
        d = monthly[m]
        print(f"{m}: {int(d['c']):3d} clicks | {int(d['i']):5d} imp | pos {d['pw']/d['i']:.1f}")

    pages = read_pages()
    print("\n=== TOP 10 PAGES ===")
    for c, i, pos, p in pages[:10]:
        print(f"{c:4d} | pos {pos:5.1f} | {p}")

    queries = read_queries()
    print("\n=== TOP 10 QUERIES ===")
    for c, i, pos, q in queries[:10]:
        print(f"{c:4d} | {i:5d} imp | pos {pos:5.1f} | {q}")

    zeros = [(c, i, pos, q) for c, i, pos, q in queries if c == 0 and i >= 150 and pos <= 12]
    zeros.sort(key=lambda x: (-x[1], x[2]))
    if zeros:
        print("\n=== CTR OPPORTUNITIES (0 clicks, imp≥150, pos≤12) ===")
        for c, i, pos, q in zeros[:8]:
            print(f"{i:5d} imp | pos {pos:5.1f} | {q}")

    crit = GSC_DIR / "Critical issues.csv"
    if crit.is_file():
        print("\n=== INDEXING (from last manual export) ===")
        for row in csv.DictReader(crit.open(encoding="utf-8")):
            print(f"{row.get('Reason', '')}: {row.get('Pages', '')} pages")
    else:
        print("\n(Indexing issues: export manually — see gsc/INDEXING_EXPORT.md)")

    return 0


if __name__ == "__main__":
    sys.exit(main())
