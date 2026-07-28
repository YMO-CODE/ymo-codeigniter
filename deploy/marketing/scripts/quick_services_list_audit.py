#!/usr/bin/env python3
"""Check pages whose config still contains services-list markup."""
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
CONFIG = ROOT / "application" / "config"
BASE = "http://www.yourmechaniconline.com:8080"

pages: dict[str, dict] = {}
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
        pages[slug] = {
            "page_type": pt.group(1) if pt else "",
            "config_services_list": "services-list" in block,
            "source": name,
        }

targets = [s for s, m in pages.items() if m["config_services_list"]]
issues: list[str] = []

for slug in sorted(targets):
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
    meta = pages[slug]
    has_list = "services-list" in body
    has_hex = "hexagon" in body
    if code != "200":
        issues.append(f"{code} /{slug}/ ({meta['source']})")
    elif meta["page_type"] == "locality" and (has_list or has_hex):
        issues.append(f"locality legacy /{slug}/ list={has_list} hex={has_hex}")
    elif meta["page_type"] != "locality" and has_list:
        issues.append(f"live services-list on /{slug}/ ({meta['page_type']})")

print(f"Pages with services-list in config: {len(targets)}")
if issues:
    print("ISSUES:")
    for line in issues:
        print(" ", line)
else:
    print("No broken services-list pages found.")
