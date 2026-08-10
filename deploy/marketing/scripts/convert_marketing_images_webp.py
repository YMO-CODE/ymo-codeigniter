#!/usr/bin/env python3
"""Convert marketing JPEG/PNG images to WebP (run on server after deploy).

Requires: pip install pillow
Usage: python deploy/marketing/scripts/convert_marketing_images_webp.py
"""
from __future__ import annotations

import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
IMG_ROOT = ROOT / "public" / "assets" / "img" / "marketing"

try:
    from PIL import Image
except ImportError:
    print("Install Pillow: pip install pillow", file=sys.stderr)
    sys.exit(1)


def convert_file(path: Path) -> bool:
    dest = path.with_suffix(".webp")
    if dest.exists() and dest.stat().st_mtime >= path.stat().st_mtime:
        return False
    with Image.open(path) as im:
        im.save(dest, "WEBP", quality=78, method=6)
    saved = path.stat().st_size - dest.stat().st_size
    print(f"  {path.relative_to(ROOT)} -> {dest.name} ({saved // 1024} KiB saved)")
    return True


RESPONSIVE_WIDTHS = (768, 1280)


def responsive_variants(webp_path: Path) -> int:
    if not webp_path.is_file():
        return 0
    count = 0
    with Image.open(webp_path) as im:
        orig_w, orig_h = im.size
        for width in RESPONSIVE_WIDTHS:
            if orig_w <= width:
                continue
            dest = webp_path.with_name(f"{webp_path.stem}-{width}.webp")
            if dest.exists() and dest.stat().st_mtime >= webp_path.stat().st_mtime:
                continue
            height = max(1, round(orig_h * (width / orig_w)))
            resized = im.resize((width, height), Image.Resampling.LANCZOS)
            resized.save(dest, "WEBP", quality=78, method=6)
            print(f"  {webp_path.relative_to(ROOT)} -> {dest.name} ({width}w)")
            count += 1
    return count


def main() -> int:
    if not IMG_ROOT.is_dir():
        print(f"Missing {IMG_ROOT}")
        return 1
    count = 0
    variant_count = 0
    for ext in ("*.jpg", "*.jpeg", "*.png", "*.JPG", "*.JPEG", "*.PNG"):
        for path in IMG_ROOT.rglob(ext):
            if convert_file(path):
                count += 1
    for webp_path in IMG_ROOT.rglob("*.webp"):
        if "-768." in webp_path.name or "-1280." in webp_path.name:
            continue
        variant_count += responsive_variants(webp_path)
    print(f"Converted {count} image(s), {variant_count} responsive variant(s). Re-run PageSpeed after deploy.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
