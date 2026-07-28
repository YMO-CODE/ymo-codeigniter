#!/usr/bin/env python3
"""
Pull RevSlider aliases + slide images from live WordPress (no auth required).

Reads [rev_slider_vc ...] from REST rendered content (HTML-encoded quotes),
fetches each page HTML for rs-module slide images, writes:
  - deploy/marketing/generated/rev_slider_inventory.json
  - application/config/marketing_sliders.php

Usage (repo root):
  python deploy/marketing/scripts/sync_rev_sliders_from_wp.py
  python deploy/marketing/scripts/sync_rev_sliders_from_wp.py --dry-run
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
from urllib.parse import unquote, urlparse

ROOT = Path(__file__).resolve().parents[3]
WP = "https://yourmechaniconline.com"
OUT = ROOT / "deploy" / "marketing" / "generated" / "rev_slider_inventory.json"
SLIDERS_PHP = ROOT / "application" / "config" / "marketing_sliders.php"
PAGES_MAIN = ROOT / "application" / "config" / "marketing_pages_data.php"
PAGES_OPTION_A = ROOT / "application" / "config" / "marketing_pages_option_a.php"

REV = re.compile(r"\[rev_slider_vc[^\]]*\]", re.I)
ALIAS = re.compile(r"alias=(?:&#8221;|&#8243;|\"|'|&quot;)([^\"'&<>]+?)(?:&#8221;|&#8243;|\"|'|&quot;)", re.I)
IMG_ATTR = re.compile(
    r'(?:data-lazyload|data-src|data-lazy-src|src)=["\']((?://|https?://)[^"\']+/wp-content/uploads/[^"\']+\.(?:jpe?g|png|webp))["\']',
    re.I,
)
LOGO_RE = re.compile(r"logo|favicon|icon|placeholder", re.I)
THUMB_RE = re.compile(r"-100x50\.(jpe?g|png|webp)$", re.I)
RESIZED = re.compile(r"-\d+x\d+\.(jpe?g|png|webp)$", re.I)
SLUG_RE = re.compile(r"^\s+'([^']+)'\s*=>\s*array\s*\(", re.M)


def fetch(url: str, retries: int = 3) -> str:
    ctx = ssl.create_default_context()
    last: Exception | None = None
    for attempt in range(retries):
        try:
            req = urllib.request.Request(url, headers={"User-Agent": "YMO-RevSlider-Sync/1.0"})
            with urllib.request.urlopen(req, timeout=90, context=ctx) as resp:
                return resp.read().decode("utf-8", errors="replace")
        except Exception as exc:  # noqa: BLE001
            last = exc
            time.sleep(1.0 * (attempt + 1))
    raise RuntimeError(f"Failed to fetch {url}: {last}")


def fetch_json(url: str) -> list | dict:
    return json.loads(fetch(url))


def path_from_link(link: str) -> str:
    return urlparse(link).path.strip("/").lower()


def normalize_alias(raw: str) -> str:
    raw = html.unescape(raw.strip())
    raw = raw.strip("\"'""''")
    # Some broken exports embed HTML inside alias=
    if "<" in raw:
        raw = raw.split("<", 1)[0].strip()
    return raw


def rev_slider_aliases(text: str) -> list[str]:
    aliases: list[str] = []
    for m in REV.finditer(text or ""):
        am = ALIAS.search(m.group(0))
        if am:
            alias = normalize_alias(am.group(1))
            if alias and alias not in aliases:
                aliases.append(alias)
    return aliases


def upload_rel(url: str) -> str | None:
    url = url.strip()
    if url.startswith("//"):
        url = "https:" + url
    m = re.search(r"/wp-content/uploads/(.+)$", url, re.I)
    if not m:
        return None
    return unquote(m.group(1).split("?")[0])


def best_slide_paths(html_text: str) -> list[str]:
    """Pick slide paths from RevSlider lazy-load attrs; skip logos/thumbnails."""
    ordered: list[str] = []
    seen_stems: set[str] = set()

    def consider(url: str) -> None:
        rel = upload_rel(url)
        if not rel or THUMB_RE.search(rel) or LOGO_RE.search(rel):
            return
        if not rel.lower().endswith((".jpg", ".jpeg", ".png", ".webp")):
            return
        # Prefer real banner photos over site logo PNGs
        if rel.lower().endswith(".png") and "revslider" not in rel.lower():
            return
        stem = RESIZED.sub(r".\1", rel)
        if stem in seen_stems:
            return
        seen_stems.add(stem)
        ordered.append(rel)

    # RevSlider lazy slides first (most reliable)
    for url in IMG_ATTR.findall(html_text):
        if "data-lazyload" in html_text:  # ensure we're in rs context — still collect all lazy attrs
            pass
        consider(url)

    # Re-scan prioritising data-lazyload specifically, preserving order
    lazy_re = re.compile(
        r'data-lazyload=["\']((?://|https?://)[^"\']+/wp-content/uploads/[^"\']+\.(?:jpe?g|png|webp))["\']',
        re.I,
    )
    ordered = []
    seen_stems = set()
    for url in lazy_re.findall(html_text):
        rel = upload_rel(url)
        if not rel or THUMB_RE.search(rel) or LOGO_RE.search(rel):
            continue
        if rel.lower().endswith(".png") and "revslider" not in rel.lower():
            continue
        stem = RESIZED.sub(r".\1", rel)
        if stem in seen_stems:
            continue
        seen_stems.add(stem)
        # prefer non-resized variant when we later dedupe
        ordered.append(rel)

    if ordered:
        # Normalize to best variant per stem (full size if known from HTML)
        by_stem: dict[str, list[str]] = {}
        for rel in ordered:
            stem = RESIZED.sub(r".\1", rel)
            by_stem.setdefault(stem, []).append(rel)
        final: list[str] = []
        for stem in by_stem:
            variants = by_stem[stem]
            full = [p for p in variants if not RESIZED.search(p) and not p.lower().endswith(".webp")]
            if full:
                final.append(full[0])
            else:
                non_webp = [p for p in variants if not p.lower().endswith(".webp")]
                final.append((non_webp or variants)[0])
        return final

    # Fallback: any revslider upload paths embedded in HTML
    for m in re.finditer(r"/wp-content/uploads/(revslider/[^\"'\s>]+\.(?:jpe?g|png|webp))", html_text, re.I):
        consider("https://x/" + m.group(1))
    return ordered


def load_page_keys() -> set[str]:
    keys: set[str] = set()
    for path in (PAGES_MAIN, PAGES_OPTION_A):
        if not path.is_file():
            continue
        for m in SLUG_RE.finditer(path.read_text(encoding="utf-8")):
            keys.add(m.group(1).lower())
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


def write_sliders_php(sliders: dict[str, dict]) -> str:
    blocks: list[str] = []
    for alias, cfg in sorted(sliders.items()):
        slide_lines = []
        for slide in cfg["slides"]:
            slide_lines.append(
                "            array(\n"
                f"                'src' => {php_str(slide['src'])},\n"
                f"                'alt' => {php_str(slide['alt'])},\n"
                "            ),"
            )
        blocks.append(
            f"    {php_str(alias)} => array(\n"
            f"        'interval_ms' => {int(cfg.get('interval_ms', 6000))},\n"
            f"        'slides'      => array(\n"
            + "\n".join(slide_lines)
            + "\n        ),\n    ),"
        )
    return (
        "<?php\n"
        "defined('BASEPATH') OR exit('No direct script access allowed');\n\n"
        "/**\n"
        " * Revolution Slider replacements — synced from live WordPress.\n"
        " * Regenerate: python deploy/marketing/scripts/sync_rev_sliders_from_wp.py\n"
        " */\n"
        "return array(\n"
        + "\n".join(blocks)
        + "\n);\n"
    )


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument("--skip-page-update", action="store_true")
    args = parser.parse_args()

    print("Fetching WordPress pages (public REST)…")
    wp_pages = fetch_json(f"{WP}/wp-json/wp/v2/pages?per_page=100&status=publish")
    if not isinstance(wp_pages, list):
        raise SystemExit("Unexpected WP API response")

    target_keys = load_page_keys()
    alias_to_paths: dict[str, list[str]] = {}
    page_map: list[dict] = []

    for item in wp_pages:
        link = item.get("link") or ""
        path = path_from_link(link)
        rendered = (item.get("content") or {}).get("rendered") or ""
        aliases = rev_slider_aliases(str(rendered))
        if not aliases:
            continue
        alias = aliases[0]
        page_map.append({"path": path, "link": link, "aliases": aliases, "primary": alias})
        if alias not in alias_to_paths:
            alias_to_paths[alias] = []
        if path not in alias_to_paths[alias]:
            alias_to_paths[alias].append(path)

    print(f"Pages with rev_slider: {len(page_map)} | Unique aliases: {len(alias_to_paths)}")

    sliders: dict[str, dict] = {}
    for alias, paths in sorted(alias_to_paths.items()):
        slides: list[str] = []
        source_path = paths[0]
        for candidate in paths:
            if candidate in target_keys:
                source_path = candidate
                break
        if alias == "main":
            source_path = ""
        url = f"{WP}/{source_path}/" if source_path else f"{WP}/"
        print(f"  alias {alias!r} <- {source_path or '/'}")
        try:
            page_html = fetch(url)
            slides = best_slide_paths(page_html)
        except Exception as exc:  # noqa: BLE001
            print(f"    WARN: could not fetch slides: {exc}")
        if not slides and alias == "main":
            home_html = fetch(f"{WP}/")
            slides = best_slide_paths(home_html)
        if not slides:
            print(f"    WARN: no slide images found for {alias!r}")
            continue
        sliders[alias] = {
            "interval_ms": 6000,
            "slides": [{"src": s, "alt": "Your Mechanic Online"} for s in slides],
            "source_path": source_path,
        }
        print(f"    -> {len(slides)} slide(s)")

    inventory = {
        "pages_with_rev_slider": len(page_map),
        "unique_aliases": len(sliders),
        "pages": page_map,
        "alias_sources": {a: cfg["source_path"] for a, cfg in sliders.items()},
        "sliders": {a: [s["src"] for s in cfg["slides"]] for a, cfg in sliders.items()},
    }
    OUT.parent.mkdir(parents=True, exist_ok=True)
    if not args.dry_run:
        OUT.write_text(json.dumps(inventory, indent=2), encoding="utf-8")

    matched_slider_pages = 0
    if not args.skip_page_update and not args.dry_run:
        for cfg_path in (PAGES_MAIN, PAGES_OPTION_A):
            if not cfg_path.is_file():
                continue
            text = cfg_path.read_text(encoding="utf-8")
            changed = 0
            for row in page_map:
                path = row["path"]
                if path not in target_keys:
                    continue
                alias = row["primary"]
                if alias not in sliders:
                    continue
                text, ok = replace_php_array_field(text, path, "slider", alias)
                if ok:
                    changed += 1
                    matched_slider_pages += 1
            if changed:
                cfg_path.write_text(text, encoding="utf-8")
                print(f"Updated {changed} slider field(s) in {cfg_path.name}")

    if not args.dry_run and sliders:
        SLIDERS_PHP.write_text(write_sliders_php(sliders), encoding="utf-8")

    print(f"\nSliders configured: {len(sliders)}")
    print(f"Marketing pages stamped with slider: {matched_slider_pages}")
    if not args.dry_run:
        print(f"Inventory: {OUT.relative_to(ROOT)}")
        print(f"Config: {SLIDERS_PHP.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
