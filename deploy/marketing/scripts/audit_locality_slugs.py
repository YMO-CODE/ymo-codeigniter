#!/usr/bin/env python3
"""Verify marketing_cities locality slugs exist in page config sources."""
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
CONFIG = ROOT / "application" / "config"
HELPERS = ROOT / "application" / "helpers"

cities = (CONFIG / "marketing_cities.php").read_text(encoding="utf-8")
loc_slugs = re.findall(r"'slug'\s*=>\s*'([^']+)'", cities)

sources = [
    CONFIG / "marketing_pages_data.php",
    CONFIG / "marketing_pages_option_a.php",
    HELPERS / "marketing_seo_growth_helper.php",
]
blob = "\n".join(p.read_text(encoding="utf-8", errors="replace") for p in sources)
missing = [s for s in loc_slugs if s not in blob]

print(f"Locality slugs in marketing_cities: {len(loc_slugs)}")
if missing:
    print(f"Missing from codebase ({len(missing)}):")
    for s in missing:
        print(f"  - {s}")
else:
    print("All locality slugs present in page sources.")

# Optional live check
base = "https://www.yourmechaniconline.com"
print("\nLive HTTP check:")
for slug in loc_slugs:
    proc = subprocess.run(
        ["curl.exe", "-s", "-L", "-o", "NUL", "-w", "%{http_code}", "--max-time", "15", f"{base}/{slug}"],
        capture_output=True,
        text=True,
        check=False,
    )
    code = (proc.stdout or "ERR").strip()
    mark = "OK" if code == "200" else "FAIL"
    print(f"  [{mark}] {code}  /{slug}")
