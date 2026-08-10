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
        im.save(dest, "WEBP", quality=82, method=4)
    saved = path.stat().st_size - dest.stat().st_size
    print(f"  {path.relative_to(ROOT)} -> {dest.name} ({saved // 1024} KiB saved)")
    return True


def main() -> int:
    if not IMG_ROOT.is_dir():
        print(f"Missing {IMG_ROOT}")
        return 1
    count = 0
    for ext in ("*.jpg", "*.jpeg", "*.png", "*.JPG", "*.JPEG", "*.PNG"):
        for path in IMG_ROOT.rglob(ext):
            if convert_file(path):
                count += 1
    print(f"Converted {count} image(s). Re-run PageSpeed after deploy.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
