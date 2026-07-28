#!/usr/bin/env python3
"""HTTP-check all marketing page slugs from config files."""

from __future__ import annotations

import re
import subprocess
import sys
from pathlib import Path

if sys.platform == "win32" and hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ROOT = Path(__file__).resolve().parents[3]
CONFIG = ROOT / "application" / "config"
BASE = "http://www.yourmechaniconline.com:8080"


def load_slugs() -> dict[str, str]:
    pages: dict[str, str] = {}
    for name in ("marketing_pages_data.php", "marketing_pages_option_a.php", "marketing_pages_city_services.php"):
        path = CONFIG / name
        if not path.is_file():
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        for m in re.finditer(r"'([^']+)'\s*=>\s*array\s*\(", text):
            slug = m.group(1)
            if slug in ("q", "a", "faq", "services"):
                continue
            if slug.startswith("services") and slug == "services":
                continue
            pages[slug] = name
    return pages


def status(url: str) -> str:
    proc = subprocess.run(
        ["curl.exe", "-s", "-o", "NUL", "-w", "%{http_code}", url],
        capture_output=True,
        text=True,
        check=False,
    )
    return (proc.stdout or "").strip() or "ERR"


def main() -> int:
    pages = load_slugs()
    buckets: dict[str, list[tuple[str, str, str]]] = {
        "200": [],
        "301": [],
        "404": [],
        "other": [],
    }
    for slug in sorted(pages):
        code = status(f"{BASE}/{slug}/")
        key = code if code in buckets else "other"
        buckets[key].append((slug, code, pages[slug]))

    print(f"TOTAL {len(pages)}")
    print(f"200   {len(buckets['200'])}")
    print(f"301   {len(buckets['301'])}")
    print(f"404   {len(buckets['404'])}")
    print(f"other {len(buckets['other'])}")

    if buckets["404"]:
        print("\n--- 404 ---")
        for slug, code, src in buckets["404"]:
            print(f"  {slug}  ({src})")

    if buckets["other"]:
        print("\n--- other ---")
        for slug, code, src in buckets["other"]:
            print(f"  {code} {slug}  ({src})")

    # Legacy rupee URLs
    legacy = [
        "services/car-interior-cleaning-in-pune-₹2000",
        "services/car-rubbing-and-polishing-in-pune-₹6500",
        "services/car-interior-cleaning-in-pune-%e2%82%b92000",
        "services/car-rubbing-and-polishing-in-pune-%e2%82%b96500",
    ]
    print("\n--- legacy rupee slugs ---")
    for slug in legacy:
        url = f"{BASE}/{slug}/"
        code = status(url)
        print(f"  {code} {slug}")

    return 1 if buckets["404"] else 0


if __name__ == "__main__":
    sys.exit(main())
