#!/usr/bin/env python3
"""
Download WordPress wp-content/uploads images referenced in marketing page
configs and rewrite URLs to local /assets/img/marketing/ paths.

Usage (repo root):
  python deploy/marketing/scripts/migrate_marketing_images.py
  python deploy/marketing/scripts/migrate_marketing_images.py --dry-run
  python deploy/marketing/scripts/migrate_marketing_images.py --rewrite-only
"""
from __future__ import annotations

import argparse
import json
import re
import ssl
import time
import urllib.error
import urllib.request
from pathlib import Path
from urllib.parse import unquote, urlparse

ROOT = Path(__file__).resolve().parents[3]
WP_BASE = "https://yourmechaniconline.com"
IMG_ROOT = ROOT / "public" / "assets" / "img" / "marketing"
CONFIG_FILES = [
    ROOT / "application" / "config" / "marketing_pages_data.php",
    ROOT / "application" / "config" / "marketing_pages_option_a.php",
    ROOT / "application" / "config" / "marketing_sliders.php",
]
# Known RevSlider assets (homepage alias "main") - fetch even if not yet referenced in HTML.
EXTRA_UPLOAD_PATHS = [
    "revslider/main/image_01.jpg",
    "revslider/main/image_021.jpg",
    "revslider/main/image_02.jpg",
    "revslider/main/image_03.jpg",
    "revslider/main/image_03-870x580.jpg",
]
REPORT = ROOT / "deploy" / "marketing" / "generated" / "image_migration_report.json"
LOCAL_WEB_PREFIX = "/assets/img/marketing/"

WP_UPLOAD_RE = re.compile(
    r"https?://(?:www\.)?yourmechaniconline\.com/wp-content/uploads/([^\"'\s>]+)",
    re.I,
)
RESIZED_RE = re.compile(r"-\d+x\d+\.(jpe?g|png|webp|gif)$", re.I)
SLIDER_SRC_RE = re.compile(r"'src'\s*=>\s*'((?:revslider|20\d\d)/[^']+)'")
IMG_TAG_RE = re.compile(r"<img\b[^>]*>", re.I)
ATTR_RE = re.compile(
    r'(\w[\w-]*)\s*=\s*("([^"]*)"|\'([^\']*)\')',
    re.I,
)


def upload_rel_path(url: str) -> str | None:
    m = WP_UPLOAD_RE.search(url)
    if not m:
        return None
    path = unquote(m.group(1)).split("?")[0].strip("/")
    return path or None


def stem_without_size(rel_path: str) -> str:
    """Group key: drop -300x200 style suffix before extension."""
    return RESIZED_RE.sub(r".\1", rel_path)


def collect_upload_paths(text: str) -> set[str]:
    paths: set[str] = set()
    for m in WP_UPLOAD_RE.finditer(text):
        rel = unquote(m.group(1)).split("?")[0].strip("/")
        if rel:
            paths.add(rel)
    return paths


def choose_download_set(all_paths: set[str]) -> set[str]:
    """Prefer full-size assets; skip thumbnails when a larger sibling exists."""
    by_stem: dict[str, list[str]] = {}
    for rel in sorted(all_paths):
        by_stem.setdefault(stem_without_size(rel), []).append(rel)

    chosen: set[str] = set()
    for variants in by_stem.values():
        full = [p for p in variants if not RESIZED_RE.search(p)]
        if full:
            # Prefer non-scaled, then scaled, then longest path name.
            full.sort(key=lambda p: ("-scaled" in p.lower(), -len(p)))
            chosen.add(full[0])
        else:
            # Only thumbnails - take the largest width from suffix if parseable.
            def width_key(p: str) -> int:
                m = re.search(r"-(\d+)x\d+\.", p, re.I)
                return int(m.group(1)) if m else 0

            variants.sort(key=width_key, reverse=True)
            chosen.add(variants[0])
    return chosen


def download_file(rel_path: str, dry_run: bool) -> tuple[bool, str]:
    dest = IMG_ROOT / rel_path.replace("/", "\\")
    url = f"{WP_BASE}/wp-content/uploads/{rel_path}"
    if dest.is_file() and dest.stat().st_size > 0:
        return True, "cached"
    if dry_run:
        return True, "dry-run"
    dest.parent.mkdir(parents=True, exist_ok=True)
    ctx = ssl.create_default_context()
    req = urllib.request.Request(url, headers={"User-Agent": "YMO-Marketing-Images/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=120, context=ctx) as resp:
            data = resp.read()
        if len(data) < 128:
            return False, "too-small"
        dest.write_bytes(data)
        return True, "downloaded"
    except urllib.error.HTTPError as exc:
        return False, f"HTTP {exc.code}"
    except Exception as exc:  # noqa: BLE001
        return False, str(exc)[:120]


def rewrite_wp_urls(text: str, downloaded: set[str]) -> str:
    """Replace WP upload URLs with local paths for downloaded (and mapped) assets."""

    def map_rel(rel: str) -> str | None:
        rel = unquote(rel).split("?")[0].strip("/")
        if not rel:
            return None
        stem = stem_without_size(rel)
        for candidate in downloaded:
            if candidate == rel or stem_without_size(candidate) == stem:
                return LOCAL_WEB_PREFIX + candidate
        return None

    def replace_url(match: re.Match[str]) -> str:
        prefix = match.group(0).split("wp-content/uploads/")[0] + "wp-content/uploads/"
        rel = match.group(1)
        local = map_rel(rel)
        if local:
            return local
        return match.group(0)

    out = WP_UPLOAD_RE.sub(replace_url, text)

    # Also rewrite bare wp-content paths that lost the domain in earlier migration.
    def replace_bare(match: re.Match[str]) -> str:
        rel = match.group(1)
        local = map_rel(rel)
        return local if local else match.group(0)

    out = re.sub(
        r"(?<![\w:/])wp-content/uploads/([^\"'\s>]+)",
        replace_bare,
        out,
        flags=re.I,
    )
    return out


def normalize_img_tag(tag: str) -> str:
    """Use local data-src as src; drop lazy placeholders and responsive srcset clutter."""
    attrs = ATTR_RE.findall(tag)
    flat: dict[str, str] = {}
    for key, _quoted, dq, sq in attrs:
        flat[key.lower()] = dq or sq

    src = flat.get("src", "")
    data_src = flat.get("data-src", "")
    pick = data_src if data_src.startswith(LOCAL_WEB_PREFIX) else src
    if pick.startswith(LOCAL_WEB_PREFIX):
        flat["src"] = pick
    elif data_src.startswith(LOCAL_WEB_PREFIX):
        flat["src"] = data_src

    if flat.get("src", "").startswith("data:image"):
        if data_src.startswith(LOCAL_WEB_PREFIX):
            flat["src"] = data_src

    # Rebuild a minimal img tag preserving alt/class/width/height.
    keep_keys = ("src", "alt", "class", "width", "height", "decoding", "loading")
    parts = ["<img"]
    for key in keep_keys:
        val = flat.get(key)
        if val:
            esc = val.replace('"', "&quot;")
            parts.append(f' {key}="{esc}"')
    if flat.get("src", "").startswith(LOCAL_WEB_PREFIX):
        parts.append(' loading="lazy"')
    parts.append(">")
    return "".join(parts)


def normalize_html_images(html: str) -> str:
    return IMG_TAG_RE.sub(lambda m: normalize_img_tag(m.group(0)), html)


def process_file(path: Path, downloaded: set[str], dry_run: bool) -> dict:
    original = path.read_text(encoding="utf-8")
    rewritten = rewrite_wp_urls(original, downloaded)
    rewritten = normalize_html_images(rewritten)
    changed = rewritten != original
    if changed and not dry_run:
        path.write_text(rewritten, encoding="utf-8")
    return {
        "file": str(path.relative_to(ROOT)),
        "changed": changed,
        "wp_urls_before": len(WP_UPLOAD_RE.findall(original)),
        "wp_urls_after": len(WP_UPLOAD_RE.findall(rewritten)),
    }


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dry-run", action="store_true")
    parser.add_argument(
        "--rewrite-only",
        action="store_true",
        help="Skip downloads; rewrite using files already in assets/img/marketing/",
    )
    args = parser.parse_args()

    IMG_ROOT.mkdir(parents=True, exist_ok=True)

    combined = ""
    for cfg in CONFIG_FILES:
        if cfg.is_file():
            combined += cfg.read_text(encoding="utf-8")

    all_paths = collect_upload_paths(combined)
    for cfg in CONFIG_FILES:
        if cfg.is_file():
            for m in SLIDER_SRC_RE.finditer(cfg.read_text(encoding="utf-8")):
                all_paths.add(m.group(1))
    all_paths.update(EXTRA_UPLOAD_PATHS)
    to_fetch = choose_download_set(all_paths)

    print(f"Found {len(all_paths)} upload URL variants -> {len(to_fetch)} files to store locally")

    download_log: list[dict] = []
    downloaded: set[str] = set()

    if args.rewrite_only:
        for rel in to_fetch:
            if (IMG_ROOT / rel).is_file():
                downloaded.add(rel)
    else:
        for i, rel in enumerate(sorted(to_fetch), 1):
            ok, status = download_file(rel, args.dry_run)
            download_log.append({"path": rel, "ok": ok, "status": status})
            if ok and status != "dry-run":
                downloaded.add(rel)
            elif ok and args.dry_run:
                downloaded.add(rel)
            print(f"  [{i}/{len(to_fetch)}] {rel} - {status}")
            if not args.dry_run and status == "downloaded":
                time.sleep(0.15)

    file_reports = []
    for cfg in CONFIG_FILES:
        if cfg.is_file():
            file_reports.append(process_file(cfg, downloaded, args.dry_run))

    report = {
        "download_total": len(to_fetch),
        "downloaded": len(downloaded),
        "downloads": download_log,
        "files": file_reports,
    }
    REPORT.parent.mkdir(parents=True, exist_ok=True)
    if not args.dry_run:
        REPORT.write_text(json.dumps(report, indent=2), encoding="utf-8")

    print(f"\nDownloaded/cached: {len(downloaded)}/{len(to_fetch)}")
    for fr in file_reports:
        print(
            f"  {fr['file']}: changed={fr['changed']} "
            f"wp_urls {fr['wp_urls_before']} -> {fr['wp_urls_after']}"
        )
    if not args.dry_run:
        print(f"Report: {REPORT.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
