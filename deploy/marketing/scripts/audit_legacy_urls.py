#!/usr/bin/env python3
"""
Audit marketing URLs: legacy 301 sources vs canonical pages.

For full index-readiness (canonical, noindex, orphans, TTFB per sitemap URL):
  python3 deploy/marketing/scripts/index_readiness_audit.py

For legacy WP retirement (410 vs 301, sitemap regression guard):
  python3 deploy/marketing/scripts/legacy_retirement_audit.py
"""

from __future__ import annotations

import argparse
import re
import ssl
import sys
import urllib.error
import urllib.request
import xml.etree.ElementTree as ET
from pathlib import Path

if sys.platform == "win32" and hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

DEFAULT_BASE = "https://www.yourmechaniconline.com"
ROOT = Path(__file__).resolve().parents[3]
USER_AGENT = "YMO-Legacy-Audit/1.0 (+https://www.yourmechaniconline.com)"

# Legacy paths that must 301 (not 200)
LEGACY_MUST_REDIRECT = [
    "book-car-servicing-in-pune",
    "bestcar-services-indore-affordable-solutions",
    "best-car-services-in-pune",
    "car-services-in-pune",
    "best-car-servicing-in-pune-ymo",
]

# Canonical hubs that must return 200
CANONICAL_MUST_200 = [
    "",
    "locations/pune",
    "locations/indore",
    "locations/nashik",
    "services",
    "services/car-denting-and-painting-3000",
    "the-best-car-servicing-in-baner",
    "contact-us",
    "sitemap.xml",
    "robots.txt",
]


class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def fetch(url: str, timeout: int = 30) -> tuple[int, str, str | None]:
    req = urllib.request.Request(
        url,
        headers={"User-Agent": USER_AGENT, "Accept": "*/*"},
        method="GET",
    )
    ctx = ssl.create_default_context()
    opener = urllib.request.build_opener(NoRedirectHandler, urllib.request.HTTPSHandler(context=ctx))
    try:
        with opener.open(req, timeout=timeout) as resp:
            body = resp.read(8192).decode("utf-8", errors="replace")
            loc = resp.headers.get("Location")
            return resp.status, body, loc
    except urllib.error.HTTPError as exc:
        loc = exc.headers.get("Location") if exc.headers else None
        try:
            body = exc.read(4096).decode("utf-8", errors="replace")
        except Exception:
            body = ""
        return exc.code, body, loc
    except urllib.error.URLError as exc:
        return 0, str(exc.reason), None


def parse_canonical(html: str) -> str | None:
    m = re.search(r'<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']', html, re.I)
    if m:
        return m.group(1)
    m = re.search(r'<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']canonical["\']', html, re.I)
    return m.group(1) if m else None


def fetch_sitemap_urls(base: str) -> list[str]:
    url = f"{base.rstrip('/')}/sitemap.xml"
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
    ctx = ssl.create_default_context()
    with urllib.request.urlopen(req, timeout=45, context=ctx) as resp:
        xml_text = resp.read().decode("utf-8", errors="replace")
    urls: list[str] = []
    try:
        root = ET.fromstring(xml_text)
        for el in root.iter():
            if el.tag.endswith("loc") and el.text:
                urls.append(el.text.strip())
    except ET.ParseError:
        for m in re.finditer(r"<loc>([^<]+)</loc>", xml_text, flags=re.I):
            urls.append(m.group(1).strip())
    return urls


def main() -> int:
    parser = argparse.ArgumentParser(description="Audit YMO legacy vs canonical URLs.")
    parser.add_argument("--base", default=DEFAULT_BASE)
    args = parser.parse_args()
    base = args.base.rstrip("/")

    print(f"Base: {base}\n")
    failures = 0

    print("=== Legacy URLs (expect 301) ===")
    for path in LEGACY_MUST_REDIRECT:
        url = f"{base}/{path}"
        code, _, loc = fetch(url)
        ok = code in (301, 308)
        status = "OK" if ok else "FAIL"
        if not ok:
            failures += 1
        print(f"  {status}  {code:>3}  /{path}")
        if loc:
            print(f"         → {loc}")

    print("\n=== Canonical URLs (expect 200) ===")
    for path in CANONICAL_MUST_200:
        url = f"{base}/{path}" if path else f"{base}/"
        code, body, _ = fetch(url)
        ok = code == 200
        status = "OK" if ok else "FAIL"
        if not ok:
            failures += 1
        label = path or "(homepage)"
        print(f"  {status}  {code:>3}  /{label}")
        if ok and path not in ("sitemap.xml", "robots.txt"):
            canon = parse_canonical(body)
            if canon and "://www." not in canon:
                print(f"         WARN canonical missing www: {canon}")
            elif canon:
                expected_suffix = path if path else ""
                if expected_suffix and expected_suffix not in canon.rstrip("/"):
                    print(f"         WARN canonical mismatch: {canon}")

    print("\n=== Sitemap (no legacy redirect sources) ===")
    try:
        sitemap_urls = fetch_sitemap_urls(base)
        legacy_in_sitemap = [
            u for u in sitemap_urls
            if any(leg in u for leg in ("book-car-servicing-in-pune", "bestcar-services-indore"))
        ]
        if legacy_in_sitemap:
            failures += len(legacy_in_sitemap)
            print(f"  FAIL  {len(legacy_in_sitemap)} legacy URL(s) still in sitemap:")
            for u in legacy_in_sitemap:
                print(f"         {u}")
        else:
            print(f"  OK    {len(sitemap_urls)} URLs, no legacy hub paths")
    except Exception as exc:
        failures += 1
        print(f"  FAIL  Could not read sitemap: {exc}")

    print(f"\n{'FAIL' if failures else 'OK'}  {failures} issue(s)")
    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main())
