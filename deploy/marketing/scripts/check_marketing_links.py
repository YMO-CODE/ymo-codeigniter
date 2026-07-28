#!/usr/bin/env python3
"""Check internal href paths embedded in marketing page HTML."""

from __future__ import annotations

import re
import subprocess
import sys
from pathlib import Path
from urllib.parse import unquote

ROOT = Path(__file__).resolve().parents[3]
CONFIG = ROOT / "application" / "config"
BASE = "http://www.yourmechaniconline.com:8080"


def load_page_keys() -> set[str]:
    keys: set[str] = set()
    for name in ("marketing_pages_data.php", "marketing_pages_option_a.php"):
        text = (CONFIG / name).read_text(encoding="utf-8", errors="replace")
        for m in re.finditer(r"'([^']+)'\s*=>\s*array\s*\(", text):
            keys.add(m.group(1))
    return keys


def extract_hrefs() -> set[str]:
    hrefs: set[str] = set()
    for name in ("marketing_pages_data.php", "marketing_pages_option_a.php"):
        text = (CONFIG / name).read_text(encoding="utf-8", errors="replace")
        for m in re.finditer(r'href="(/[^"#?]+)"', text, flags=re.I):
            hrefs.add(unquote(m.group(1).strip("/")))
    return hrefs


def status(url: str) -> str:
    proc = subprocess.run(
        ["curl.exe", "-s", "-o", "NUL", "-w", "%{http_code}", url],
        capture_output=True,
        text=True,
        check=False,
    )
    return (proc.stdout or "").strip() or "ERR"


def main() -> int:
    keys = load_page_keys()
    hrefs = extract_hrefs()
    missing: list[tuple[str, str]] = []
    redirects: list[tuple[str, str]] = []

    for href in sorted(hrefs):
        if href in keys:
            continue
        code = status(f"{BASE}/{href}/")
        if code == "200":
            continue
        if code == "301":
            redirects.append((href, code))
        else:
            missing.append((href, code))

    print(f"Unique hrefs: {len(hrefs)}")
    print(f"In config:    {sum(1 for h in hrefs if h in keys)}")
    print(f"OK (200):     {len(hrefs) - len(missing) - len(redirects) - sum(1 for h in hrefs if h in keys)}")
    print(f"301 redirect: {len(redirects)}")
    print(f"Broken:       {len(missing)}")

    if redirects:
        print("\n--- hrefs that 301 (legacy) ---")
        for href, code in redirects:
            print(f"  {code} /{href}/")

    if missing:
        print("\n--- broken hrefs ---")
        for href, code in missing:
            print(f"  {code} /{href}/")

    return 1 if missing else 0


if __name__ == "__main__":
    sys.exit(main())
