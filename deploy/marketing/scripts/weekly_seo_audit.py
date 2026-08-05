#!/usr/bin/env python3
"""Weekly SEO audit - run after refreshing GSC exports in deploy/marketing/gsc/."""

from __future__ import annotations

import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
SCRIPTS = ROOT / "deploy" / "marketing" / "scripts"


def run(name: str) -> int:
    path = SCRIPTS / name
    print(f"\n=== {name} ===")
    proc = subprocess.run([sys.executable, str(path)], cwd=str(ROOT))
    return proc.returncode


def main() -> int:
    gsc = ROOT / "deploy" / "marketing" / "gsc"
    if not gsc.is_dir() or not any(gsc.glob("*.csv")):
        print("Warning: no GSC CSV exports in deploy/marketing/gsc/")
    codes = [
        run("audit_gsc_pages.py"),
        run("check_sitemap_live.py"),
        run("check_marketing_pages.py"),
        run("check_marketing_links.py"),
    ]
    print("\nSubmit sitemap: https://www.yourmechaniconline.com/sitemap.xml")
    print("See deploy/marketing/GBP_ALIGNMENT.md for NAP checks.")
    return max(codes)


if __name__ == "__main__":
    sys.exit(main())
