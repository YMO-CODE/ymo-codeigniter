#!/usr/bin/env python3
"""
Fetch the live marketing sitemap and HTTP-check every listed URL.

Use before resubmitting to Google Search Console.

Examples:
  python deploy/marketing/scripts/check_sitemap_live.py
  python deploy/marketing/scripts/check_sitemap_live.py --base https://www.yourmechaniconline.com
  python deploy/marketing/scripts/check_sitemap_live.py --base http://www.yourmechaniconline.com:8080
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
from urllib.parse import urlparse

if sys.platform == "win32" and hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

DEFAULT_BASE = "https://www.yourmechaniconline.com"
ROOT = Path(__file__).resolve().parents[3]
USER_AGENT = "YMO-Sitemap-Checker/1.0 (+https://www.yourmechaniconline.com)"


class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def _build_opener(follow_redirects: bool = True) -> urllib.request.OpenerDirector:
    ctx = ssl.create_default_context()
    handlers: list = [urllib.request.HTTPSHandler(context=ctx)]
    if not follow_redirects:
        handlers.insert(0, NoRedirectHandler)
    return urllib.request.build_opener(*handlers)


def fetch(url: str, timeout: int = 45) -> tuple[int, str]:
    """Return (status_code, final_url_or_error). Does not follow redirects."""
    req = urllib.request.Request(
        url,
        headers={"User-Agent": USER_AGENT, "Accept": "*/*"},
        method="GET",
    )
    opener = _build_opener(follow_redirects=False)
    try:
        with opener.open(req, timeout=timeout) as resp:
            resp.read(4096)
            return resp.status, resp.geturl()
    except urllib.error.HTTPError as exc:
        return exc.code, str(exc.reason)
    except urllib.error.URLError as exc:
        return 0, str(exc.reason)


def fetch_text(url: str, timeout: int = 45) -> str:
    req = urllib.request.Request(
        url,
        headers={"User-Agent": USER_AGENT, "Accept": "application/xml,text/xml,*/*"},
    )
    opener = _build_opener(follow_redirects=True)
    with opener.open(req, timeout=timeout) as resp:
        return resp.read().decode("utf-8", errors="replace")


def parse_sitemap_urls(xml_text: str) -> list[str]:
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


def normalize_base(base: str) -> str:
    return base.rstrip("/")


def check_robots_sitemap(base: str) -> tuple[bool, str]:
    robots_url = f"{base}/robots.txt"
    try:
        text = fetch_text(robots_url)
    except Exception as exc:
        return False, f"robots.txt unreadable: {exc}"

    expected = f"{base}/sitemap.xml"
    for line in text.splitlines():
        if line.lower().startswith("sitemap:"):
            declared = line.split(":", 1)[1].strip()
            if declared.rstrip("/") == expected.rstrip("/"):
                return True, declared
            return False, f"robots declares {declared!r}, expected {expected!r}"
    return False, "robots.txt has no Sitemap: line"


def main() -> int:
    parser = argparse.ArgumentParser(description="Validate live marketing sitemap URLs.")
    parser.add_argument(
        "--base",
        default=DEFAULT_BASE,
        help=f"Marketing site origin (default: {DEFAULT_BASE})",
    )
    parser.add_argument(
        "--sitemap-path",
        default="/sitemap.xml",
        help="Sitemap path on the host (default: /sitemap.xml)",
    )
    args = parser.parse_args()

    base = normalize_base(args.base)
    sitemap_url = f"{base}{args.sitemap_path}"

    print(f"Sitemap: {sitemap_url}")

    try:
        xml_text = fetch_text(sitemap_url)
    except Exception as exc:
        print(f"FAIL  Could not fetch sitemap: {exc}")
        return 1

    urls = parse_sitemap_urls(xml_text)
    if not urls:
        print("FAIL  No <loc> entries found in sitemap.")
        return 1

    dupes = sorted({u for u in urls if urls.count(u) > 1})
    if dupes:
        print(f"WARN  Duplicate URLs in sitemap ({len(dupes)}):")
        for u in dupes[:10]:
            print(f"  {u}")
        if len(dupes) > 10:
            print(f"  ... and {len(dupes) - 10} more")

    robots_ok, robots_detail = check_robots_sitemap(base)
    if robots_ok:
        print(f"OK    robots.txt → {robots_detail}")
    else:
        print(f"WARN  robots.txt sitemap mismatch: {robots_detail}")

    non_www = [u for u in urls if "yourmechaniconline.com" in u and "://www." not in u]
    if non_www:
        print(f"WARN  {len(non_www)} sitemap URL(s) omit www (should be canonical www):")
        for u in non_www[:5]:
            print(f"  {u}")

    http_urls = [u for u in urls if u.startswith("http://")]
    if http_urls:
        print(f"WARN  {len(http_urls)} sitemap URL(s) use http:// instead of https://")

    buckets: dict[str, list[str]] = {}
    for label in ("200", "301", "302", "404", "5xx", "other"):
        buckets[label] = []

    print(f"\nChecking {len(urls)} URLs (no redirect follow)...\n")

    for url in urls:
        code, detail = fetch(url)
        if 500 <= code <= 599:
            key = "5xx"
        elif code in (301, 302, 404):
            key = str(code)
        elif code == 200:
            key = "200"
        else:
            key = "other"
        buckets[key].append(url)
        if code != 200:
            print(f"  {code:>3}  {url}" + (f"  ({detail})" if key == "other" else ""))

    print("\n--- summary ---")
    print(f"total   {len(urls)}")
    print(f"200     {len(buckets['200'])}")
    print(f"301     {len(buckets['301'])}")
    print(f"302     {len(buckets['302'])}")
    print(f"404     {len(buckets['404'])}")
    print(f"5xx     {len(buckets['5xx'])}")
    print(f"other   {len(buckets['other'])}")

    bad = len(buckets["301"]) + len(buckets["302"]) + len(buckets["404"]) + len(buckets["5xx"]) + len(buckets["other"])
    if bad:
        print(f"\nFAIL  {bad} sitemap URL(s) are not HTTP 200. Fix before GSC resubmit.")
        return 1

    print("\nOK    All sitemap URLs returned HTTP 200.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
