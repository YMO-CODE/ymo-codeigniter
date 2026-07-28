#!/usr/bin/env python3
"""
Pull WordPress pages via REST API and merge into marketing page configs.

Usage (from repo root):
  python deploy/marketing/scripts/migrate_wp_content.py
  python deploy/marketing/scripts/migrate_wp_content.py --dry-run
  python deploy/marketing/scripts/migrate_wp_content.py --limit 5

Requires network access to yourmechaniconline.com.
"""
from __future__ import annotations

import argparse
import html
import json
import re
import ssl
import time
import urllib.error
import urllib.request
from pathlib import Path
from urllib.parse import urlparse, urljoin

ROOT = Path(__file__).resolve().parents[3]
WP_BASE = "https://yourmechaniconline.com"
IMG_DIR = ROOT / "public" / "assets" / "img" / "marketing"
PAGES_MAIN = ROOT / "application" / "config" / "marketing_pages_data.php"
PAGES_OPTION_A = ROOT / "application" / "config" / "marketing_pages_option_a.php"
REPORT = ROOT / "deploy" / "marketing" / "generated" / "wp_migration_report.json"

SLUG_ALIASES = {
    "ymo-spares": "ymo-car-spares-parts-india",
    "car-services-in-pune": "best-car-services-in-pune",
    "ymo-car-servicing-locations-in-pune/the-best-mercedes-servicing-in-viman-nagar":
        "best-car-servicing-in-pune-ymo/the-best-mercedes-servicing-in-viman-nagar",
}


def normalize_slug(slug: str) -> str:
    slug = slug.strip("/").lower()
    slug = slug.replace("%e2%82%b9", "₹").replace("\u20b9", "₹")
    return slug

VC_TAG_RE = re.compile(r"\[\/?vc_[^\]]*\]", re.I)
VC_SELF_RE = re.compile(r"\[vc_[^\]]*\]", re.I)
REV_SLIDER_RE = re.compile(r"\[rev_slider_vc[^\]]*\]", re.I)
REV_SLIDER_ALIAS_RE = re.compile(
    r"alias=(?:&#8221;|&#8243;|\"|'|&quot;)([^\"'&<>]+?)(?:&#8221;|&#8243;|\"|'|&quot;)",
    re.I,
)
RAW_HTML_RE = re.compile(r"\[vc_raw_html\](.*?)\[/vc_raw_html\]", re.I | re.S)
SCRIPT_STYLE_RE = re.compile(r"<(script|style)[^>]*>.*?</\1>", re.I | re.S)
TAG_RE = re.compile(r"<[^>]+>")
WHITESPACE_RE = re.compile(r"\s+")


def fetch_json(url: str, retries: int = 3) -> tuple[object, dict]:
    ctx = ssl.create_default_context()
    last_err: Exception | None = None
    for attempt in range(retries):
        try:
            req = urllib.request.Request(
                url,
                headers={"User-Agent": "YMO-Marketing-Migrator/1.0"},
            )
            with urllib.request.urlopen(req, timeout=60, context=ctx) as resp:
                body = resp.read()
                headers = dict(resp.headers)
                return json.loads(body.decode("utf-8", errors="replace")), headers
        except Exception as exc:  # noqa: BLE001
            last_err = exc
            time.sleep(1.5 * (attempt + 1))
    raise RuntimeError(f"Failed to fetch {url}: {last_err}")


def fetch_all_pages() -> list[dict]:
    pages: list[dict] = []
    page_num = 1
    while True:
        url = f"{WP_BASE}/wp-json/wp/v2/pages?per_page=100&page={page_num}&status=publish"
        batch, headers = fetch_json(url)
        if not isinstance(batch, list) or not batch:
            break
        pages.extend(batch)
        total_pages = int(headers.get("X-WP-TotalPages", "1"))
        if page_num >= total_pages:
            break
        page_num += 1
    return pages


def fetch_all_posts() -> list[dict]:
    posts: list[dict] = []
    page_num = 1
    while True:
        url = f"{WP_BASE}/wp-json/wp/v2/posts?per_page=100&page={page_num}&status=publish"
        batch, headers = fetch_json(url)
        if not isinstance(batch, list) or not batch:
            break
        posts.extend(batch)
        total_pages = int(headers.get("X-WP-TotalPages", "1"))
        if page_num >= total_pages:
            break
        page_num += 1
    return posts


def fetch_all_wp_items() -> list[dict]:
    return fetch_all_pages() + fetch_all_posts()


def path_from_link(link: str) -> str:
    return normalize_slug(urlparse(link).path)


def path_from_canonical(item: dict) -> str:
    aioseo = item.get("aioseo_meta_data") or {}
    canonical = (aioseo.get("canonical_url") or "").strip()
    if canonical:
        return path_from_link(canonical.split()[0])
    head = item.get("aioseo_head_json") or {}
    canonical = (head.get("canonical_url") or head.get("og:url") or "").strip()
    if canonical:
        return path_from_link(canonical.split()[0])
    return path_from_link(item.get("link") or "")


def build_parent_paths(pages: list[dict]) -> dict[int, str]:
    by_id = {p["id"]: p for p in pages}
    cache: dict[int, str] = {}

    def walk(page_id: int) -> str:
        if page_id in cache:
            return cache[page_id]
        page = by_id.get(page_id)
        if not page:
            return ""
        slug = (page.get("slug") or "").strip("/")
        parent = int(page.get("parent") or 0)
        if parent and parent in by_id:
            parent_path = walk(parent)
            full = f"{parent_path}/{slug}" if parent_path else slug
        else:
            full = slug
        cache[page_id] = full.lower()
        return cache[page_id]

    for pid in by_id:
        walk(pid)
    return cache


def strip_vc_shortcodes(text: str) -> str:
    text = RAW_HTML_RE.sub(r"\1", text)
    text = REV_SLIDER_RE.sub("", text)
    text = VC_TAG_RE.sub("", text)
    text = VC_SELF_RE.sub(r"", text)
    return text


def rev_slider_alias(raw: str) -> str | None:
    for m in REV_SLIDER_RE.finditer(raw or ""):
        alias = REV_SLIDER_ALIAS_RE.search(m.group(0))
        if alias:
            name = html.unescape(alias.group(1).strip())
            name = name.strip("\"'""''")
            if "<" in name:
                name = name.split("<", 1)[0].strip()
            if name:
                return name
    return None


def clean_html(raw: str) -> str:
    if not raw:
        return ""
    text = strip_vc_shortcodes(raw)
    text = SCRIPT_STYLE_RE.sub("", text)
    text = re.sub(r"<\/(div|span|p|h[1-6]|li|ul|ol)>\s*<(div|span|p|h[1-6]|li|ul|ol])", r"</\1>\n<\2", text, flags=re.I)
    text = WHITESPACE_RE.sub(" ", text)
    text = re.sub(r">\s+<", "><", text)
    text = text.replace("http://quanticalabs.com/wptest/carservice/", f"{WP_BASE}/wp-content/uploads/")
    text = re.sub(r'href="https?://yourmechaniconline\.com/?([^"]*)"', r'href="/\1"', text, flags=re.I)
    text = re.sub(r'href="/+"', 'href="/', text)
    text = text.strip()
    return text


def plain_intro(raw: str, limit: int = 280) -> str:
    text = strip_vc_shortcodes(raw)
    text = TAG_RE.sub(" ", text)
    text = html.unescape(WHITESPACE_RE.sub(" ", text)).strip()
    if len(text) <= limit:
        return text
    cut = text[:limit].rsplit(" ", 1)[0]
    return cut.rstrip(".,;:") + "…"


def seo_title(item: dict) -> str:
    aioseo = item.get("aioseo_meta_data") or {}
    if aioseo.get("title"):
        return html.unescape(str(aioseo["title"]).strip())
    head = item.get("aioseo_head_json") or {}
    if head.get("title"):
        return html.unescape(str(head["title"]).strip())
    title = item.get("title") or {}
    rendered = title.get("rendered") if isinstance(title, dict) else str(title)
    return html.unescape(re.sub(r"<[^>]+>", "", rendered or "")).strip()


def seo_description(item: dict) -> str:
    aioseo = item.get("aioseo_meta_data") or {}
    if aioseo.get("description"):
        return html.unescape(str(aioseo["description"]).strip())
    head = item.get("aioseo_head_json") or {}
    if head.get("description"):
        return html.unescape(str(head["description"]).strip())
    excerpt = item.get("excerpt") or {}
    rendered = excerpt.get("rendered") if isinstance(excerpt, dict) else ""
    return plain_intro(rendered, 160)


def h1_from_title(title: str) -> str:
    title = re.sub(r"\s*[|\u2013-]\s*YMO.*$", "", title, flags=re.I)
    title = re.sub(r"\s*[|\u2013-]\s*Your Mechanic Online.*$", "", title, flags=re.I)
    return title.strip()


def load_page_keys() -> set[str]:
    keys: set[str] = set()
    slug_re = re.compile(r"^\s+'([a-z0-9][a-z0-9\-\/₹\.]*?)'\s*=>\s*array\s*\(", re.M | re.I)
    for path in (PAGES_MAIN, PAGES_OPTION_A):
        if not path.is_file():
            continue
        text = path.read_text(encoding="utf-8")
        for m in slug_re.finditer(text):
            keys.add(normalize_slug(m.group(1)))
    return keys


def php_str(value: str) -> str:
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


def replace_php_array_field(text: str, slug: str, field: str, value: str) -> tuple[str, bool]:
    lines = text.splitlines(keepends=True)
    slug_token = f"'{slug}'"
    i = 0
    while i < len(lines):
        if slug_token in lines[i] and "=> array" in lines[i]:
            j = i + 1
            field_idx = None
            view_idx = None
            while j < len(lines):
                stripped = lines[j].strip()
                if stripped.startswith(f"'{field}'"):
                    field_idx = j
                if stripped.startswith("'view'"):
                    view_idx = j
                    break
                if stripped == "),":
                    break
                j += 1
            indent = re.match(r"^(\s*)", lines[i + 1]).group(1) if i + 1 < len(lines) else "        "
            new_line = f"{indent}'{field}'            => {php_str(value)},\n"
            if field_idx is not None:
                lines[field_idx] = new_line
                return "".join(lines), True
            if view_idx is not None:
                lines.insert(view_idx, new_line)
                return "".join(lines), True
            return text, False
        i += 1
    return text, False


def update_php_file(path: Path, updates: dict[str, dict]) -> int:
    text = path.read_text(encoding="utf-8")
    changed = 0
    for slug, fields in updates.items():
        if f"'{slug}'" not in text:
            continue
        for field in ("title", "meta_description", "h1", "intro", "body", "slider"):
            if field not in fields or fields[field] is None:
                continue
            val = fields[field]
            if val == "" and field in ("h1", "intro", "body"):
                continue
            text, ok = replace_php_array_field(text, slug, field, val)
            if ok:
                changed += 1
    path.write_text(text, encoding="utf-8")
    return changed


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--limit", type=int, default=0, help="Only migrate N matched pages")
    args = parser.parse_args()

    print("Fetching WordPress pages and posts…")
    wp_pages = fetch_all_wp_items()
    parent_paths = build_parent_paths(wp_pages)
    target_keys = load_page_keys()

    wp_by_path: dict[str, dict] = {}
    for item in wp_pages:
        paths = {
            path_from_link(item.get("link") or ""),
            path_from_canonical(item),
            parent_paths.get(item["id"], ""),
        }
        for p in paths:
            if p:
                wp_by_path[p] = item
                wp_by_path[p.rstrip("/")] = item

    matched: dict[str, dict] = {}
    unmatched_targets: list[str] = []
    unmatched_wp: list[str] = []

    for key in sorted(target_keys):
        lookup = SLUG_ALIASES.get(key, key)
        item = wp_by_path.get(lookup) or wp_by_path.get(lookup.rstrip("/"))
        if not item:
            unmatched_targets.append(key)
            continue
        content = item.get("content") or {}
        raw_html = content.get("rendered") if isinstance(content, dict) else ""
        slider_alias = rev_slider_alias(str(raw_html))
        title = seo_title(item)
        body = clean_html(raw_html)
        if not body and not title:
            continue
        entry = {
            "title": title,
            "meta_description": seo_description(item),
            "h1": h1_from_title(title),
            "intro": plain_intro(raw_html),
            "body": body,
            "wp_link": item.get("link"),
            "wp_id": item.get("id"),
        }
        if slider_alias:
            entry["slider"] = slider_alias
        matched[key] = entry
        if args.limit and len(matched) >= args.limit:
            break

    for item in wp_pages:
        p = path_from_link(item.get("link") or "")
        if p and p not in target_keys and p not in {"home", ""}:
            unmatched_wp.append(p)

    report = {
        "wp_items_total": len(wp_pages),
        "target_pages": len(target_keys),
        "matched": len(matched),
        "unmatched_targets": unmatched_targets,
        "unmatched_wp_sample": unmatched_wp[:30],
        "matched_slugs": sorted(matched.keys()),
    }
    REPORT.parent.mkdir(parents=True, exist_ok=True)
    REPORT.write_text(json.dumps(report, indent=2), encoding="utf-8")

    print(f"WP items: {len(wp_pages)} | Targets: {len(target_keys)} | Matched: {len(matched)}")
    print(f"Report: {REPORT.relative_to(ROOT)}")

    if args.dry_run:
        for slug in sorted(matched.keys())[:10]:
            print(f"  [dry-run] {slug} — {matched[slug]['title'][:70]}")
        if len(matched) > 10:
            print(f"  … and {len(matched) - 10} more")
        return

    main_updates = {k: v for k, v in matched.items() if (PAGES_MAIN.read_text(encoding='utf-8') and k in PAGES_MAIN.read_text(encoding='utf-8'))}
    option_updates = {k: v for k, v in matched.items() if k not in main_updates}

    # Re-read keys properly
    main_text = PAGES_MAIN.read_text(encoding="utf-8")
    main_updates = {k: v for k, v in matched.items() if f"'{k}'" in main_text}
    option_updates = {k: v for k, v in matched.items() if k not in main_updates}

    c1 = update_php_file(PAGES_MAIN, main_updates) if main_updates else 0
    c2 = update_php_file(PAGES_OPTION_A, option_updates) if option_updates else 0
    print(f"Updated fields: marketing_pages_data.php={c1}, marketing_pages_option_a.php={c2}")

    if unmatched_targets:
        print(f"Unmatched targets ({len(unmatched_targets)}) — may need manual fetch or URL alias:")
        for slug in unmatched_targets[:15]:
            print(f"  - {slug}".encode("ascii", "replace").decode("ascii"))
        if len(unmatched_targets) > 15:
            print(f"  … and {len(unmatched_targets) - 15} more")


if __name__ == "__main__":
    main()
