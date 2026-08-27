#!/usr/bin/env python3
"""
Index-readiness audit for YMO marketing URLs.

Extends legacy URL checks with per-URL: HTTP 200, canonical, noindex, orphans, TTFB.

Examples:
  python deploy/marketing/scripts/index_readiness_audit.py
  python deploy/marketing/scripts/index_readiness_audit.py --base https://www.yourmechaniconline.com
"""

from __future__ import annotations

import argparse
import re
import ssl
import sys
import time
import urllib.error
import urllib.request
import xml.etree.ElementTree as ET
from pathlib import Path

if sys.platform == "win32" and hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

DEFAULT_BASE = "https://www.yourmechaniconline.com"
USER_AGENT = "YMO-Index-Readiness/1.0 (+https://www.yourmechaniconline.com)"
TTFB_WARN_MS = 800

LEGACY_MUST_REDIRECT = [
    "book-car-servicing-in-pune",
    "bestcar-services-indore-affordable-solutions",
]


class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def fetch(url: str, timeout: int = 45) -> tuple[int, str, dict[str, str], float]:
    req = urllib.request.Request(
        url,
        headers={"User-Agent": USER_AGENT, "Accept": "text/html,application/xml,*/*"},
        method="GET",
    )
    ctx = ssl.create_default_context()
    opener = urllib.request.build_opener(NoRedirectHandler, urllib.request.HTTPSHandler(context=ctx))
    t0 = time.perf_counter()
    try:
        with opener.open(req, timeout=timeout) as resp:
            body = resp.read(65536).decode("utf-8", errors="replace")
            elapsed_ms = (time.perf_counter() - t0) * 1000
            headers = {k.lower(): v for k, v in resp.headers.items()}
            return resp.status, body, headers, elapsed_ms
    except urllib.error.HTTPError as exc:
        elapsed_ms = (time.perf_counter() - t0) * 1000
        headers = {k.lower(): v for k, v in exc.headers.items()} if exc.headers else {}
        try:
            body = exc.read(65536).decode("utf-8", errors="replace")
        except Exception:
            body = ""
        return exc.code, body, headers, elapsed_ms
    except urllib.error.URLError as exc:
        elapsed_ms = (time.perf_counter() - t0) * 1000
        return 0, str(exc.reason), {}, elapsed_ms


def fetch_follow(url: str, timeout: int = 45) -> str:
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
    ctx = ssl.create_default_context()
    with urllib.request.urlopen(req, timeout=timeout, context=ctx) as resp:
        return resp.read().decode("utf-8", errors="replace")


def parse_sitemap_urls(base: str) -> list[str]:
    xml_text = fetch_follow(f"{base.rstrip('/')}/sitemap.xml")
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


def parse_canonical(html: str) -> str | None:
    m = re.search(r'<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']', html, re.I)
    if m:
        return m.group(1)
    m = re.search(r'<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']canonical["\']', html, re.I)
    return m.group(1) if m else None


def is_noindex(html: str, headers: dict[str, str]) -> bool:
    if headers.get("x-robots-tag", "").lower().find("noindex") >= 0:
        return True
    if re.search(r'<meta[^>]+name=["\']robots["\'][^>]+content=["\'][^"\']*noindex', html, re.I):
        return True
    return False


def extract_internal_hrefs(html: str, base: str) -> set[str]:
    paths: set[str] = set()
    host = re.sub(r"^https?://", "", base.rstrip("/"))
    for m in re.finditer(r'href=["\']([^"\']+)["\']', html, re.I):
        href = m.group(1).strip()
        if href.startswith("#") or href.startswith("mailto:") or href.startswith("tel:"):
            continue
        if href.startswith("http://") or href.startswith("https://"):
            if host not in href:
                continue
            path = re.sub(r"^https?://[^/]+", "", href).strip("/")
        else:
            path = href.strip("/")
        if path:
            paths.add(path)
    return paths


def build_inbound_graph(base: str, sitemap_paths: list[str], full_crawl: bool = True) -> dict[str, set[str]]:
    """Build inbound link map from server-rendered hrefs on marketing pages."""
    inbound: dict[str, set[str]] = {p: set() for p in sitemap_paths}
    inbound[""] = set()

    pages_to_crawl = list(sitemap_paths) if full_crawl else (
        [""] + [
            p for p in sitemap_paths
            if p in (
                "locations/pune", "locations/indore", "locations/nashik",
                "services", "brands", "about-us",
            )
        ]
    )
    for from_path in pages_to_crawl:
        url = f"{base}/{from_path}" if from_path else f"{base}/"
        try:
            html = fetch_follow(url)
        except Exception:
            continue
        for target in extract_internal_hrefs(html, base):
            if target not in inbound:
                inbound[target] = set()
            inbound[target].add(from_path or "(homepage)")
    return inbound


def main() -> int:
    parser = argparse.ArgumentParser(description="YMO index-readiness audit.")
    parser.add_argument("--base", default=DEFAULT_BASE)
    parser.add_argument("--sample-ttfb", action="store_true", help="Also measure TTFB for /locations/pune")
    parser.add_argument(
        "--quick-graph",
        action="store_true",
        help="Build orphan graph from homepage + key hubs only (faster, pre-deploy snapshot)",
    )
    args = parser.parse_args()
    base = args.base.rstrip("/")

    print(f"Index readiness audit: {base}\n")
    failures = 0

    print("=== Legacy redirects (301) ===")
    for path in LEGACY_MUST_REDIRECT:
        code, _, headers, _ = fetch(f"{base}/{path}")
        ok = code in (301, 308)
        if not ok:
            failures += 1
        loc = headers.get("location", "")
        print(f"  {'OK' if ok else 'FAIL':4}  {code:>3}  /{path}" + (f" -> {loc}" if loc else ""))

    print("\n=== Sitemap ===")
    try:
        sitemap_urls = parse_sitemap_urls(base)
        sitemap_paths = sorted({
            re.sub(r"^https?://[^/]+/", "", u).strip("/")
            for u in sitemap_urls
        })
        print(f"  OK   {len(sitemap_urls)} URLs")
    except Exception as exc:
        print(f"  FAIL  {exc}")
        return 1

    print("\n=== Per-URL checks ===")
    print(f"{'URL':<55} {'ST':>3} {'Canon':<5} {'noidx':<5} {'in':>3} {'TTFB':>6}")
    print("-" * 85)

    inbound = build_inbound_graph(base, sitemap_paths, full_crawl=not args.quick_graph)

    for path in sitemap_paths[:120]:
        url = f"{base}/{path}"
        code, body, headers, ttfb = fetch(url)
        canon = parse_canonical(body) if code == 200 else None
        canon_ok = bool(canon and canon.rstrip("/") == url.rstrip("/"))
        noindex = is_noindex(body, headers) if code == 200 else False
        in_count = len(inbound.get(path, set()))
        orphan = in_count == 0 and path != ""

        ok = code == 200 and canon_ok and not noindex and not orphan
        if not ok:
            failures += 1

        ttfb_flag = "!" if ttfb > TTFB_WARN_MS else ""
        print(
            f"/{path:<54} {code:>3} "
            f"{'OK' if canon_ok else 'BAD':<5} "
            f"{'YES' if noindex else 'no':<5} "
            f"{in_count:>3} "
            f"{ttfb:6.0f}ms{ttfb_flag}"
        )

    orphans = [p for p in sitemap_paths if p and len(inbound.get(p, set())) == 0]
    if orphans:
        print(f"\n=== Orphans ({len(orphans)}) — zero inbound links from crawled hub pages ===")
        for p in orphans[:30]:
            print(f"  /{p}")
        if len(orphans) > 30:
            print(f"  ... and {len(orphans) - 30} more")
        failures += len(orphans)

    if args.sample_ttfb:
        _, _, _, ttfb = fetch(f"{base}/locations/pune")
        print(f"\nTTFB /locations/pune: {ttfb:.0f}ms")

    print(f"\n{'FAIL' if failures else 'OK'}  {failures} issue(s)")
    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main())
