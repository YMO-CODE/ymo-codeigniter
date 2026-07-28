#!/usr/bin/env python3
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
CONFIG = ROOT / "application" / "config"
BASE = "http://www.yourmechaniconline.com:8080"

pages: dict[str, str] = {}
for name in (
    "marketing_pages_data.php",
    "marketing_pages_option_a.php",
    "marketing_pages_city_services.php",
):
    text = (CONFIG / name).read_text(encoding="utf-8", errors="replace")
    for m in re.finditer(r"'([^']+)'\s*=>\s*array\s*\((.*?)\n\s*\),", text, re.S):
        slug, block = m.group(1), m.group(2)
        if slug in ("q", "a", "faq"):
            continue
        pt = re.search(r"'page_type'\s*=>\s*'([^']+)'", block)
        pages[slug] = pt.group(1) if pt else ""

locality = sorted(s for s, t in pages.items() if t == "locality")
issues: list[str] = []

for slug in locality:
    url = f"{BASE}/{slug}"
    proc = subprocess.run(
        ["curl.exe", "-s", "-L", "-w", "\n__HTTP__%{http_code}", "-o", "-", "--max-time", "25", url],
        capture_output=True,
        text=True,
        check=False,
    )
    raw = proc.stdout or ""
    body, code = raw.rsplit("__HTTP__", 1) if "__HTTP__" in raw else (raw, "ERR")
    code = code.strip()
    has_list = "services-list" in body
    has_hex = "hexagon" in body
    has_cat = "ymo-service-catalog" in body
    if code != "200":
        issues.append(f"{code} /{slug}/")
    elif has_list or has_hex:
        issues.append(f"legacy /{slug}/ list={has_list} hex={has_hex}")
    elif not has_cat:
        issues.append(f"no catalog /{slug}/")

proc = subprocess.run(
    [
        "curl.exe",
        "-s",
        "-o",
        "NUL",
        "-w",
        "%{http_code} %{redirect_url}",
        "--max-time",
        "20",
        f"{BASE}/car-services-in-pune",
    ],
    capture_output=True,
    text=True,
    check=False,
)
print("car-services-in-pune ->", (proc.stdout or "").strip())
print(f"Locality pages checked: {len(locality)}")
if issues:
    print("ISSUES:")
    for line in issues:
        print(" ", line)
else:
    print("All locality pages OK")
