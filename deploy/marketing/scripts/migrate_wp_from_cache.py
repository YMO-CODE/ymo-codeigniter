#!/usr/bin/env python3
"""Apply WordPress REST cache to marketing page configs (offline)."""
from __future__ import annotations

import json
import re
from html import unescape
from pathlib import Path
from urllib.parse import urlparse

ROOT = Path(__file__).resolve().parents[3]
CACHE_DIR = ROOT / "deploy" / "marketing" / "wp-cache"
PAGES_MAIN = ROOT / "application" / "config" / "marketing_pages_data.php"
PAGES_OPTION_A = ROOT / "application" / "config" / "marketing_pages_option_a.php"
ALIASES_FILE = ROOT / "application" / "config" / "marketing_wp_aliases.php"
REPORT = ROOT / "deploy" / "marketing" / "generated" / "wp_migration_report.json"
WP_BASE = "https://yourmechaniconline.com"

VC_TAG_RE = re.compile(r"\[\/?vc_[^\]]*\]", re.I)
VC_SELF_RE = re.compile(r"\[vc_[^\]]*\]", re.I)
REV_SLIDER_RE = re.compile(r"\[rev_slider_vc[^\]]*\]", re.I)
RAW_HTML_RE = re.compile(r"\[vc_raw_html\](.*?)\[/vc_raw_html\]", re.I | re.S)
SCRIPT_STYLE_RE = re.compile(r"<(script|style)[^>]*>.*?</\1>", re.I | re.S)
TAG_RE = re.compile(r"<[^>]+>")


def path_from_link(link: str) -> str:
    return urlparse(link.strip()).path.strip("/").lower()


def load_aliases() -> dict[str, str]:
    aliases: dict[str, str] = {}
    if not ALIASES_FILE.is_file():
        return aliases
    text = ALIASES_FILE.read_text(encoding="utf-8")
    for m in re.finditer(r"'([^']+)'\s*=>\s*'([^']+)'", text):
        aliases[m.group(1).lower()] = m.group(2).lower()
    return aliases


FIELD_NAMES = frozenset({
    "title", "meta_description", "h1", "intro", "body", "view", "page_type", "gsc_clicks",
})


def load_page_keys() -> set[str]:
    keys: set[str] = set()
    for path in (PAGES_MAIN, PAGES_OPTION_A):
        if not path.is_file():
            continue
        for m in re.finditer(r"^\s+'([^']+)'\s*=>", path.read_text(encoding="utf-8"), re.M):
            slug = m.group(1).lower()
            if slug not in FIELD_NAMES:
                keys.add(slug)
    return keys


def load_wp_pages() -> list[dict]:
    pages: list[dict] = []
    for file in sorted(CACHE_DIR.glob("pages-*.json")):
        data = json.loads(file.read_text(encoding="utf-8"))
        if isinstance(data, list):
            pages.extend(data)
    return pages


def build_parent_paths(pages: list[dict]) -> dict[int, str]:
    by_id = {int(p["id"]): p for p in pages}
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


def path_from_canonical(item: dict) -> str:
    aioseo = item.get("aioseo_meta_data") or {}
    canonical = (aioseo.get("canonical_url") or "").strip()
    if canonical:
        return path_from_link(canonical.split()[0])
    head = item.get("aioseo_head_json") or {}
    for key in ("canonical_url", "og:url"):
        if head.get(key):
            return path_from_link(str(head[key]).strip().split()[0])
    return path_from_link(item.get("link") or "")


def strip_vc(text: str) -> str:
    text = RAW_HTML_RE.sub(r"\1", text)
    text = REV_SLIDER_RE.sub("", text)
    text = VC_TAG_RE.sub("", text)
    text = VC_SELF_RE.sub("", text)
    return text


def strip_inner(html_fragment: str) -> str:
    text = TAG_RE.sub(" ", html_fragment)
    return re.sub(r"\s+", " ", unescape(text)).strip()


def clean_html(raw: str) -> str:
    if not raw:
        return ""
    text = strip_vc(raw)
    text = SCRIPT_STYLE_RE.sub("", text)
    text = re.sub(r"<(div|span|ul|ol|li|a)[^>]*>", " ", text, flags=re.I)
    text = re.sub(r"</(div|span|ul|ol|li|a)>", " ", text, flags=re.I)
    text = text.replace("http://quanticalabs.com/wptest/carservice/", f"{WP_BASE}/wp-content/uploads/")
    text = re.sub(r'href="https?://yourmechaniconline\.com/?([^"]*)"', r'href="/\1"', text, flags=re.I)

    blocks: list[str] = []
    for m in re.finditer(r"<h([2-4])[^>]*>(.*?)</h\1>", text, re.I | re.S):
        inner = strip_inner(m.group(2))
        if inner:
            blocks.append(f"<h{m.group(1)}>{inner}</h{m.group(1)}>")
    for m in re.finditer(r"<p[^>]*>(.*?)</p>", text, re.I | re.S):
        inner = strip_inner(m.group(1))
        if len(inner) > 30:
            blocks.append(f"<p>{inner}</p>")

    if blocks:
        return "\n".join(blocks)

    plain = strip_inner(text)
    if not plain:
        return ""
    chunks = [c.strip() for c in re.split(r"(?<=[.!?])\s+", plain) if len(c.strip()) > 40]
    if not chunks:
        return f"<p>{plain}</p>"
    return "\n".join(f"<p>{c}</p>" for c in chunks[:12])


def plain_intro(raw: str, limit: int = 280) -> str:
    text = unescape(TAG_RE.sub(" ", strip_vc(raw)))
    text = re.sub(r"\s+", " ", text).strip()
    if len(text) <= limit:
        return text
    return text[: limit - 1].rsplit(" ", 1)[0].rstrip(".,;:") + "…"


def seo_title(item: dict) -> str:
    aioseo = item.get("aioseo_meta_data") or {}
    if aioseo.get("title"):
        return unescape(str(aioseo["title"]).strip())
    head = item.get("aioseo_head_json") or {}
    if head.get("title"):
        return unescape(str(head["title"]).strip())
    title = (item.get("title") or {}).get("rendered", "")
    return unescape(TAG_RE.sub("", title)).strip()


def seo_description(item: dict) -> str:
    aioseo = item.get("aioseo_meta_data") or {}
    if aioseo.get("description"):
        return unescape(str(aioseo["description"]).strip())
    head = item.get("aioseo_head_json") or {}
    if head.get("description"):
        return unescape(str(head["description"]).strip())
    return plain_intro((item.get("excerpt") or {}).get("rendered", ""), 160)


def h1_from_title(title: str) -> str:
    title = re.sub(r"\s*[|\u2013-]\s*YMO.*$", "", title, flags=re.I)
    title = re.sub(r"\s*[|\u2013-]\s*Your Mechanic Online.*$", "", title, flags=re.I)
    return title.strip()


def php_str(value: str) -> str:
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


def update_php_file(path: Path, updates: dict[str, dict]) -> int:
    text = path.read_text(encoding="utf-8")
    changed = 0
    for slug, fields in updates.items():
        for field in ("title", "meta_description", "h1", "intro", "body"):
            if field not in fields:
                continue
            val = php_str(fields[field])
            pattern = rf"('{re.escape(slug)}'\s*=>\s*array\s*\(.*?'{field}'\s*=>\s*)('(?:\\.|[^'\\])*'|'')"
            if re.search(pattern, text, re.S):
                text, n = re.subn(pattern, rf"\1{val}", text, count=1, flags=re.S)
                changed += n
            elif field in ("body", "intro"):
                insert = rf"('{re.escape(slug)}'\s*=>\s*array\s*\()(\s*\n\s*'title')"
                if re.search(insert, text, re.S):
                    text, n = re.subn(insert, rf"\1\n        '{field}'            => {val},\2", text, count=1, flags=re.S)
                    changed += n
    path.write_text(text, encoding="utf-8")
    return changed


def main() -> None:
    wp_pages = load_wp_pages()
    if not wp_pages:
        raise SystemExit(f"No cache in {CACHE_DIR} — run migrate_wp_content.php first or add pages-*.json")

    parent_paths = build_parent_paths(wp_pages)
    aliases = load_aliases()
    target_keys = load_page_keys()

    wp_by_path: dict[str, dict] = {}
    for item in wp_pages:
        paths = {
            path_from_link(item.get("link") or ""),
            path_from_canonical(item),
            parent_paths.get(int(item["id"]), ""),
        }
        for p in paths:
            if p:
                wp_by_path[p] = item
                wp_by_path[p.rstrip("/")] = item

    # Reverse alias: WP path may map to our slug
    for wp_path, our_slug in aliases.items():
        if wp_path in wp_by_path:
            wp_by_path[our_slug] = wp_by_path[wp_path]

    matched: dict[str, dict] = {}
    unmatched: list[str] = []
    for key in sorted(target_keys):
        item = wp_by_path.get(key)
        if not item:
            unmatched.append(key)
            continue
        raw = (item.get("content") or {}).get("rendered", "")
        matched[key] = {
            "title": seo_title(item),
            "meta_description": seo_description(item),
            "h1": h1_from_title(seo_title(item)),
            "intro": plain_intro(raw),
            "body": clean_html(raw),
        }

    main_text = PAGES_MAIN.read_text(encoding="utf-8")
    main_updates = {k: v for k, v in matched.items() if f"'{k}'" in main_text}
    option_updates = {k: v for k, v in matched.items() if k not in main_updates}

    c1 = update_php_file(PAGES_MAIN, main_updates)
    c2 = update_php_file(PAGES_OPTION_A, option_updates)

    report = {
        "wp_pages_cached": len(wp_pages),
        "target_pages": len(target_keys),
        "matched": len(matched),
        "unmatched_targets": unmatched,
        "matched_slugs": sorted(matched.keys()),
    }
    REPORT.parent.mkdir(parents=True, exist_ok=True)
    REPORT.write_text(json.dumps(report, indent=2), encoding="utf-8")

    print(f"Cached WP pages: {len(wp_pages)}")
    print(f"Matched: {len(matched)}/{len(target_keys)}")
    print(f"Updated fields: main={c1}, option_a={c2}")
    print(f"Report: {REPORT.relative_to(ROOT)}")
    if unmatched:
        print(f"Unmatched ({len(unmatched)}):")
        for slug in unmatched[:20]:
            print(f"  - {slug}".encode("ascii", "replace").decode("ascii"))


if __name__ == "__main__":
    main()
