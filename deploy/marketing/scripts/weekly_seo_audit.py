#!/usr/bin/env python3
"""Weekly SEO audit — fetches fresh GSC data when configured, then runs checks."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

if sys.platform == "win32" and hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ROOT = Path(__file__).resolve().parents[3]
SCRIPTS = ROOT / "deploy" / "marketing" / "scripts"


def run(name: str, *extra: str) -> int:
    path = SCRIPTS / name
    print(f"\n=== {name} ===")
    proc = subprocess.run([sys.executable, str(path), *extra], cwd=str(ROOT))
    return proc.returncode


def main() -> int:
    gsc = ROOT / "deploy" / "marketing" / "gsc"
    fetch_code = run("fetch_gsc.py")
    if fetch_code == 2:
        print("\nNote: GSC fetch skipped (no API credentials). Using existing CSVs in gsc/.")
        print("      Setup: deploy/marketing/GSC_SETUP.md")
    elif fetch_code != 0:
        print("\nWARN: GSC fetch failed; continuing with existing exports if any.")

    if not gsc.is_dir() or not any(gsc.glob("Chart.csv")):
        print("Warning: no GSC Chart.csv in deploy/marketing/gsc/")

    codes = [
        fetch_code if fetch_code not in (0, 2) else 0,
        run("gsc_report.py"),
        run("audit_gsc_pages.py"),
        run("check_sitemap_live.py"),
        run("check_marketing_pages.py"),
        run("check_marketing_links.py"),
    ]
    print("\nSubmit sitemap: https://www.yourmechaniconline.com/sitemap.xml")
    print("GSC setup: deploy/marketing/GSC_SETUP.md")
    print("See deploy/marketing/GBP_ALIGNMENT.md for NAP checks.")
    return max(codes)


if __name__ == "__main__":
    sys.exit(main())
