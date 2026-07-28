#!/usr/bin/env python3
"""Count marketing pages with hero sliders."""
from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
CONFIG = ROOT / "application" / "config"
FILES = ["marketing_pages_data.php", "marketing_pages_option_a.php"]
SLUG_RE = re.compile(r"^\s+'([^']+)'\s*=>\s*array\s*\(", re.M)
SLIDER_RE = re.compile(r"'slider'\s*=>\s*'([^']+)'")
HERO_RE = re.compile(r'data-vc-full-width="true"[^>]*class="[^"]*full-width', re.I)


def page_blocks(text: str) -> list[tuple[str, str]]:
    matches = list(SLUG_RE.finditer(text))
    blocks: list[tuple[str, str]] = []
    for i, m in enumerate(matches):
        end = matches[i + 1].start() if i + 1 < len(matches) else len(text)
        blocks.append((m.group(1), text[m.start() : end]))
    return blocks


def main() -> None:
    explicit: dict[str, str] = {}
    body_hero: set[str] = set()

    for fn in FILES:
        path = CONFIG / fn
        if not path.is_file():
            continue
        for slug, block in page_blocks(path.read_text(encoding="utf-8")):
            sm = SLIDER_RE.search(block)
            if sm:
                explicit[slug] = sm.group(1)
            if "'body'" in block and HERO_RE.search(block):
                body_hero.add(slug)

    body_only = body_hero - set(explicit.keys())
    total = 1 + len(explicit) + len(body_only)  # +1 homepage

    print(f"Homepage (multi-slide, alias main): 1")
    print(f"Config pages with 'slider' key: {len(explicit)}")
    for slug, alias in sorted(explicit.items()):
        print(f"  - {slug} -> {alias}")
    print(f"Service pages with extracted body hero (single slide): {len(body_only)}")
    for slug in sorted(body_only):
        print(f"  - {slug}")
    print(f"---")
    print(f"Total pages showing a hero carousel/banner: {total}")
    print(f"  Multi-slide RevSlider replacement: {1 + len(explicit)}")
    print(f"  Single-image banner (from WP body): {len(body_only)}")


if __name__ == "__main__":
    main()
