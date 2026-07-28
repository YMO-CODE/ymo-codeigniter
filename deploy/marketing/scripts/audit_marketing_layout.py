#!/usr/bin/env python3
"""Audit marketing pages for legacy layout markers in live HTML."""

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

MARKERS = {
    "services_list": re.compile(r'class="services-list', re.I),
    "hexagon": re.compile(r'class="hexagon', re.I),
    "elementor": re.compile(r'class="elementor', re.I),
    "service_catalog": re.compile(r'ymo-service-catalog', re.I),
    "marketing_hero": re.compile(r'ymo-hero', re.I),
    "canonical": re.compile(r'rel="canonical"', re.I),
}


def load_pages() -> dict[str, dict[str, str]]:
    pages: dict[str, dict[str, str]] = {}
    for name in (
        "marketing_pages_data.php",
        "marketing_pages_option_a.php",
        "marketing_pages_city_services.php",
    ):
        path = CONFIG / name
        if not path.is_file():
            continue
        text = path.read_text(encoding="utf-8", errors="replace")
        for m in re.finditer(
            r"'([^']+)'\s*=>\s*array\s*\((.*?)\n\s*\),",
            text,
            flags=re.S,
        ):
            slug = m.group(1)
            block = m.group(2)
            if slug in ("q", "a", "faq"):
                continue
            page_type = ""
            pt = re.search(r"'page_type'\s*=>\s*'([^']+)'", block)
            if pt:
                page_type = pt.group(1)
            has_services_list = "services-list" in block
            pages[slug] = {
                "source": name,
                "page_type": page_type,
                "config_services_list": "yes" if has_services_list else "no",
            }
    return pages


def fetch(url: str) -> tuple[str, str]:
    proc = subprocess.run(
        ["curl.exe", "-s", "-L", "-w", "\n__HTTP__%{http_code}", url],
        capture_output=True,
        text=True,
        check=False,
    )
    raw = proc.stdout or ""
    if "__HTTP__" in raw:
        body, code = raw.rsplit("__HTTP__", 1)
        return code.strip(), body
    return "ERR", raw


def main() -> int:
    pages = load_pages()
    locality_slugs = [s for s, p in pages.items() if p.get("page_type") == "locality"]
    priority = locality_slugs + [
        "services",
        "locations/pune",
        "locations/indore",
        "locations/nashik",
        "car-services-in-pune",
        "bestcar-services-indore-affordable-solutions",
        "premium-luxury-car-service-pune",
        "affordable-car-services-viman-nagar-pune",
        "best-car-servicing-in-bavdhan-pune-expert-care",
        "the-best-car-servicing-in-baner",
        "privacy-policy",
    ]
    seen: set[str] = set()
    ordered: list[str] = []
    for slug in priority:
        if slug in pages and slug not in seen:
            ordered.append(slug)
            seen.add(slug)
    for slug in sorted(pages):
        if slug not in seen:
            ordered.append(slug)
            seen.add(slug)

    issues: list[str] = []
    ok_catalog: list[str] = []
    legacy_live: list[str] = []
    not_200: list[str] = []

    print(f"Auditing {len(ordered)} pages against {BASE}\n")

    for slug in ordered:
        url = f"{BASE}/{slug}"
        code, html = fetch(url)
        if code != "200":
            not_200.append(f"{code} /{slug}/ ({pages[slug]['source']})")
            continue

        meta = pages[slug]
        has_list = bool(MARKERS["services_list"].search(html))
        has_catalog = bool(MARKERS["service_catalog"].search(html))
        has_hex = bool(MARKERS["hexagon"].search(html))

        if meta.get("page_type") == "locality":
            if has_list or has_hex:
                legacy_live.append(
                    f"/{slug}/ — legacy services-list/hexagon still in HTML ({meta['source']})"
                )
            elif has_catalog:
                ok_catalog.append(slug)
            else:
                issues.append(
                    f"/{slug}/ — locality page missing service card grid ({meta['source']})"
                )

        if slug in ("services", "locations/indore", "locations/nashik") and not has_catalog:
            issues.append(f"/{slug}/ — expected service card grid missing")

        if slug == "car-services-in-pune" and (has_list or not has_catalog):
            issues.append(
                f"/{slug}/ — legacy car-services-in-pune page still broken (list={has_list}, catalog={has_catalog})"
            )

        if meta.get("config_services_list") == "yes" and meta.get("page_type") != "locality":
            if has_list:
                legacy_live.append(
                    f"/{slug}/ — config has services-list and live HTML still renders it"
                )

    print(f"Locality pages with card grid: {len(ok_catalog)}")
    print(f"Non-200 responses: {len(not_200)}")
    print(f"Layout issues: {len(issues) + len(legacy_live)}")

    if not_200:
        print("\n--- HTTP not 200 ---")
        for line in not_200[:20]:
            print(f"  {line}")
        if len(not_200) > 20:
            print(f"  ... and {len(not_200) - 20} more")

    if legacy_live:
        print("\n--- Legacy markup still live ---")
        for line in legacy_live:
            print(f"  {line}")

    if issues:
        print("\n--- Other layout issues ---")
        for line in issues:
            print(f"  {line}")

    if not issues and not legacy_live and not not_200:
        print("\nAll audited pages look good.")

    return 1 if (issues or legacy_live or not_200) else 0


if __name__ == "__main__":
    sys.exit(main())
