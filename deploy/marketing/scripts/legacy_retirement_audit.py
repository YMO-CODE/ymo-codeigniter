#!/usr/bin/env python3
"""
Legacy WordPress URL retirement audit for YMO marketing site.

Asserts:
  - All sitemap URLs still 200 + self-canonical (protected set untouched)
  - Known legacy 301 sources return single-hop 301 to a real target
  - Legacy junk (tags/feeds/wp-*/archives without redirect rules) returns 410
  - No legacy sample returns 200, 403, or redirect chains

Run after deploy:
  python3 deploy/marketing/scripts/legacy_retirement_audit.py
  python3 deploy/marketing/scripts/index_readiness_audit.py
"""

from __future__ import annotations

import argparse
import re
import ssl
import subprocess
import sys
import urllib.error
import urllib.request
import xml.etree.ElementTree as ET
from pathlib import Path

if sys.platform == "win32" and hasattr(sys.stdout, "reconfigure"):
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")

ROOT = Path(__file__).resolve().parents[3]
DEFAULT_BASE = "https://www.yourmechaniconline.com"
USER_AGENT = "YMO-Legacy-Retirement/1.0 (+https://www.yourmechaniconline.com)"

# Explicit 301 sources (must stay 301, never 410)
LEGACY_MUST_301 = [
    "book-car-servicing-in-pune",
    "bestcar-services-indore-affordable-solutions",
    "tag/car-service-pune",
    "tag/car-denting-and-painting",
    "category/brakes",
    "galleries/brake-repair",
    "team/philip-brower",
    "ymo-car-spares-parts-india",
]

# No modern equivalent — must return 410 after retirement deploy
LEGACY_MUST_410 = [
    "feed",
    "comments/feed",
    "author/admin",
    "wp-admin",
    "wp-content/uploads/2022/03/example.jpg",
    "wp-json/wp/v2/posts",
    "xmlrpc.php",
    "2023/01",
    "2023/07",
    "tag/legacy-junk-tag-not-in-redirect-map",
    "category/legacy-junk-category-not-in-redirect-map",
    "galleries/legacy-junk-gallery",
    "team/legacy-junk-author",
    "category/car-servicing/page/2",
    "tag/car-service-pune/feed",
]

# Sitemap blog post — must remain 200 (protected)
PROTECTED_MUST_200 = [
    "2023/07/18/know-the-benefits-of-regular-oil-changes-for-your-car-in-summer",
]


class NoRedirectHandler(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def fetch_no_redirect(url: str, timeout: int = 30) -> tuple[int, dict[str, str]]:
    req = urllib.request.Request(
        url,
        headers={"User-Agent": USER_AGENT, "Accept": "*/*"},
        method="GET",
    )
    ctx = ssl.create_default_context()
    opener = urllib.request.build_opener(NoRedirectHandler, urllib.request.HTTPSHandler(context=ctx))
    try:
        with opener.open(req, timeout=timeout) as resp:
            return resp.status, {k.lower(): v for k, v in resp.headers.items()}
    except urllib.error.HTTPError as exc:
        headers = {k.lower(): v for k, v in exc.headers.items()} if exc.headers else {}
        return exc.code, headers


def fetch_follow_chain(url: str, max_hops: int = 5) -> tuple[int, list[str]]:
    chain: list[str] = []
    current = url
    for _ in range(max_hops):
        code, headers = fetch_no_redirect(current)
        if code not in (301, 302, 303, 307, 308):
            return code, chain
        loc = headers.get("location", "")
        if not loc:
            return code, chain
        chain.append(loc)
        current = loc
    return 0, chain


def parse_sitemap_paths(base: str) -> list[str]:
    req = urllib.request.Request(f"{base.rstrip('/')}/sitemap.xml", headers={"User-Agent": USER_AGENT})
    ctx = ssl.create_default_context()
    with urllib.request.urlopen(req, timeout=45, context=ctx) as resp:
        xml_text = resp.read().decode("utf-8", errors="replace")
    paths: list[str] = []
    try:
        root = ET.fromstring(xml_text)
        for el in root.iter():
            if el.tag.endswith("loc") and el.text:
                path = re.sub(r"^https?://[^/]+/", "", el.text.strip()).strip("/")
                paths.append(path)
    except ET.ParseError:
        for m in re.finditer(r"<loc>([^<]+)</loc>", xml_text, flags=re.I):
            path = re.sub(r"^https?://[^/]+/", "", m.group(1).strip()).strip("/")
            paths.append(path)
    return sorted(set(paths))


def parse_canonical(html: str) -> str | None:
    m = re.search(r'<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']', html, re.I)
    if m:
        return m.group(1)
    m = re.search(r'<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']canonical["\']', html, re.I)
    return m.group(1) if m else None


def fetch_body(url: str) -> tuple[int, str]:
    req = urllib.request.Request(url, headers={"User-Agent": USER_AGENT})
    ctx = ssl.create_default_context()
    try:
        with urllib.request.urlopen(req, timeout=30, context=ctx) as resp:
            return resp.status, resp.read(65536).decode("utf-8", errors="replace")
    except urllib.error.HTTPError as exc:
        try:
            body = exc.read(65536).decode("utf-8", errors="replace")
        except Exception:
            body = ""
        return exc.code, body


def load_php_redirect_sources() -> dict[str, str]:
    """Parse exact redirect keys from PHP config files (for reporting)."""
    sources: dict[str, str] = {}
    files = [
        ROOT / "application/config/marketing_redirects.php",
        ROOT / "application/config/marketing_redirects_option_a.php",
        ROOT / "application/config/marketing_consolidations.php",
    ]
    pattern = re.compile(r"^\s*'([^']+)'\s*=>\s*'([^']*)'\s*,?\s*$")
    for path in files:
        if not path.is_file():
            continue
        for line in path.read_text(encoding="utf-8", errors="replace").splitlines():
            m = pattern.match(line)
            if m:
                src, dst = m.group(1).strip("/"), m.group(2).strip("/")
                if src and dst and src != dst:
                    sources[src] = dst
    return sources


def main() -> int:
    parser = argparse.ArgumentParser(description="YMO legacy URL retirement audit.")
    parser.add_argument("--base", default=DEFAULT_BASE)
    parser.add_argument("--skip-index-audit", action="store_true")
    args = parser.parse_args()
    base = args.base.rstrip("/")
    failures = 0

    print(f"Legacy retirement audit: {base}\n")

    redirect_map = load_php_redirect_sources()
    print(f"Loaded {len(redirect_map)} explicit 301 rules from PHP config\n")

    print("=== Protected sitemap URLs (must stay 200) ===")
    try:
        sitemap_paths = parse_sitemap_paths(base)
        print(f"  Sitemap count: {len(sitemap_paths)}")
    except Exception as exc:
        print(f"  FAIL  could not load sitemap: {exc}")
        return 1

    sample = sitemap_paths if len(sitemap_paths) <= 130 else sitemap_paths[:130]
    for path in sample:
        url = f"{base}/{path}" if path else f"{base}/"
        code, body = fetch_body(url)
        canon = parse_canonical(body) if code == 200 else None
        canon_ok = bool(canon and canon.rstrip("/") == url.rstrip("/"))
        ok = code == 200 and canon_ok
        if not ok:
            failures += 1
            print(f"  FAIL  {code:>3}  /{path}" + (f"  canon={canon}" if canon else ""))
    if failures == 0:
        print(f"  OK    all {len(sample)} sampled sitemap URLs are 200 + self-canonical")

    for path in PROTECTED_MUST_200:
        url = f"{base}/{path}"
        code, _ = fetch_body(url)
        if code != 200:
            failures += 1
            print(f"  FAIL  protected blog post /{path} returned {code}")

    print("\n=== Legacy 301 sources (single hop) ===")
    for path in LEGACY_MUST_301:
        url = f"{base}/{path}"
        code, headers = fetch_no_redirect(url)
        loc = headers.get("location", "")
        ok = code in (301, 308) and bool(loc)
        if not ok:
            failures += 1
            print(f"  FAIL  {code:>3}  /{path}")
            continue
        final_code, chain = fetch_follow_chain(url)
        if len(chain) > 1:
            failures += 1
            print(f"  FAIL  chain>{len(chain)}  /{path}")
        elif final_code != 200:
            failures += 1
            print(f"  FAIL  target {final_code}  /{path} -> {chain[-1] if chain else loc}")
        else:
            print(f"  OK    301  /{path} -> {loc[:70]}")

    print("\n=== Legacy junk (must be 410 Gone) ===")
    for path in LEGACY_MUST_410:
        url = f"{base}/{path}"
        code, headers = fetch_no_redirect(url)
        ok = code == 410
        if not ok:
            failures += 1
            loc = headers.get("location", "")
            print(f"  FAIL  {code:>3}  /{path}" + (f" -> {loc}" if loc else ""))
        else:
            print(f"  OK    410  /{path}")

    print("\n=== Prefix catch-all check (unlisted tag → 410, not 301 to hub) ===")
    probe = "tag/zzz-retirement-audit-probe-not-in-map"
    code, headers = fetch_no_redirect(f"{base}/{probe}")
    loc = headers.get("location", "")
    if code == 410:
        print(f"  OK    410  /{probe}")
    elif code in (301, 308) and "locations/pune" in loc:
        failures += 1
        print(f"  FAIL  old prefix rule still active: /{probe} -> {loc}")
    else:
        failures += 1
        print(f"  FAIL  /{probe} returned {code}" + (f" -> {loc}" if loc else ""))

    if not args.skip_index_audit:
        print("\n=== Index readiness (subprocess) ===")
        script = ROOT / "deploy/marketing/scripts/index_readiness_audit.py"
        if script.is_file():
            proc = subprocess.run(
                [sys.executable, str(script), "--base", base],
                cwd=str(ROOT),
                capture_output=True,
                text=True,
            )
            tail = proc.stdout.strip().splitlines()[-3:] if proc.stdout else []
            for line in tail:
                print(f"  {line}")
            if proc.returncode != 0:
                failures += 1
                print("  FAIL  index_readiness_audit.py reported issues")
            else:
                print("  OK    index_readiness_audit.py passed")
        else:
            print("  SKIP  index_readiness_audit.py not found")

    print(f"\n{'FAIL' if failures else 'OK'}  {failures} issue(s)")
    return 1 if failures else 0


if __name__ == "__main__":
    sys.exit(main())
