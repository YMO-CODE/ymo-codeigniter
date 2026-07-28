#!/usr/bin/env python3
"""Apply SEO consolidations: strip thin pages from option_a, patch locality content."""

from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
OPTION_A = ROOT / "application" / "config" / "marketing_pages_option_a.php"

# Slugs removed from sitemap (301 via marketing_consolidations.php)
REMOVE_SLUGS = {
    "ymo-car-spares-parts-india",
    "the-best-audi-servicing-in-bavdhan",
    "the-best-audi-servicing-in-hinjewadi",
    "the-best-audi-servicing-in-wakad",
    "the-best-bmw-servicing-in-baner",
    "the-best-bmw-servicing-in-wakad",
    "the-best-mercedes-servicing-in-hinjewadi",
    "the-best-mercedes-servicing-in-wakad",
    "best-audi-servicing-in-aundh-pune",
    "best-bmw-servicing-in-hinjewadi-luxury-car-care",
    "ymo-car-servicing-locations-in-pune/the-best-mercedes-servicing-in-viman-nagar",
    "best-car-servicing-in-pune-ymo",
    "best-car-servicing-in-pune-ymo/the-best-audi-servicing-in-viman-nagar",
    "best-car-servicing-in-pune-ymo/the-best-mercedes-servicing-in-viman-nagar",
    "best-car-servicing-in-pune-ymo/top-mercedes-servicing-aundh",
    "best-car-servicing-in-pune-ymo/top-mercedes-servicing-baner",
    "best-car-servicing-in-pune-ymo/top-mercedes-servicing-baner/the-best-audi-servicing-baner-pune",
    "best-car-servicing-in-pune-ymo/top-mercedes-servicing-baner/the-best-audi-servicing-baner-pune/top-mercedes-servicing-in-bavdhan",
    "car-services-in-pune",
    # Stubs overridden by tier-1 in marketing_pages_data.php
    "services/belts-and-hoses",
    "services/car-brake-repair",
    "services/car-engine-services-in-pune",
    "services/lube-oil-and-filters",
}

LOCALITY_PATCHES = {
    "affordable-car-services-viman-nagar-pune": [
        ("Reasons To Get Your Car Serviced By Your Mechanic Online In Aundh", "Reasons To Get Your Car Serviced By Your Mechanic Online In Viman Nagar"),
        ("'page_type'            => 'locality',", "'page_type'            => 'locality',\n        'city_slug'            => 'pune',\n        'locality_slug'        => 'viman_nagar',\n        'quick_answer'         => 'Your Mechanic Online provides affordable car servicing in Viman Nagar, Pune with free pick-up, AC repair, denting, polishing, and periodic maintenance.',\n        'updated_at'           => '2026-07-20',"),
    ],
    "best-car-servicing-in-bavdhan-pune-expert-care": [
        ("'page_type'            => 'locality',", "'page_type'            => 'locality',\n        'city_slug'            => 'pune',\n        'locality_slug'        => 'bavdhan',\n        'quick_answer'         => 'Expert car servicing in Bavdhan, Pune — periodic maintenance, AC repair, denting, and luxury car care with free doorstep pick-up.',\n        'updated_at'           => '2026-07-20',"),
    ],
    "the-best-bmw-servicing-in-wakad": None,  # removed
}


def extract_entries(text: str) -> list[tuple[str, str]]:
    """Return list of (slug, full entry text) from PHP return array."""
    entries = []
    pattern = re.compile(r"(\s*)'([^']+)'\s*=>\s*array\s*\(", re.MULTILINE)
    matches = list(pattern.finditer(text))
    for i, m in enumerate(matches):
        slug = m.group(2)
        start = m.start()
        end = matches[i + 1].start() if i + 1 < len(matches) else text.rfind(");")
        entries.append((slug, text[start:end].rstrip()))
    return entries


def main() -> None:
    text = OPTION_A.read_text(encoding="utf-8")
    entries = extract_entries(text)
    kept = []
    removed = []
    for slug, block in entries:
        if slug in REMOVE_SLUGS:
            removed.append(slug)
            continue
        block = block
        if slug in LOCALITY_PATCHES and LOCALITY_PATCHES[slug]:
            for old, new in LOCALITY_PATCHES[slug]:
                block = block.replace(old, new)
        kept.append((slug, block))

    header = text[: text.find("return array(") + len("return array(")]
    footer = "\n);\n"
    body = "\n".join(block for _, block in kept)
    OPTION_A.write_text(header + "\n" + body + footer, encoding="utf-8")
    print(f"Removed {len(removed)} consolidated slugs from option_a")
    for s in removed:
        print(f"  - {s}")
    print(f"Kept {len(kept)} pages in option_a")


if __name__ == "__main__":
    main()
