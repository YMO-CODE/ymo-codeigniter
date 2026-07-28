#!/usr/bin/env python3
"""Add SEO meta fields to tier-1 pages in marketing_pages_data.php."""

from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
DATA = ROOT / "application" / "config" / "marketing_pages_data.php"
text = DATA.read_text(encoding="utf-8")

PATCHES = {
    "'the-best-car-servicing-in-baner' => array(": "'page_type'            => 'locality',\n        'city_slug'            => 'pune',\n        'locality_slug'        => 'baner',\n        'updated_at'           => '2026-07-20',\n        'quick_answer'         => 'Best car servicing in Baner, Pune — periodic maintenance, AC repair, denting, and polishing with free doorstep pick-up from Your Mechanic Online.',\n        'view'",
    "'affordable-car-servicing-in-wakad-pune' => array(": "'page_type'            => 'locality',\n        'city_slug'            => 'pune',\n        'locality_slug'        => 'wakad',\n        'updated_at'           => '2026-07-20',\n        'quick_answer'         => 'Affordable car services in Wakad, Pune including AC repair, brake service, denting, polishing, and complete periodic maintenance with free pick-up.',\n        'view'",
    "'best-car-services-hinjewadi-pune' => array(": "'page_type'            => 'locality',\n        'city_slug'            => 'pune',\n        'locality_slug'        => 'hinjewadi',\n        'updated_at'           => '2026-07-20',\n        'quick_answer'         => 'Expert car services in Hinjewadi, Pune — AC repair, brake servicing, tyre alignment, and complete car care with free pick-up and drop.',\n        'view'",
    "'car-servicing-in-aundh' => array(": "'page_type'            => 'locality',\n        'city_slug'            => 'pune',\n        'locality_slug'        => 'aundh',\n        'updated_at'           => '2026-07-20',\n        'quick_answer'         => 'Top car servicing in Aundh, Pune with transparent pricing, luxury car care options, and free doorstep pick-up from YMO.',\n        'view'",
    "'premium-luxury-car-service-pune' => array(": "'page_type'            => 'hub',\n        'city_slug'            => 'pune',\n        'updated_at'           => '2026-07-20',\n        'quick_answer'         => 'Premium luxury car service in Pune for Mercedes, BMW, Audi, Jaguar, and more — trained specialists, genuine parts, and free pick-up.',\n        'view'",
    "'services/car-rubbing-and-polishing-in-pune-6500' => array(": "'og_image'             => '/assets/img/marketing/2022/03/car-rubbing-and-polishing.jpg',\n        'city_slug'            => 'pune',\n        'updated_at'           => '2026-07-20',\n        'quick_answer'         => 'Car rubbing and polishing in Pune from ₹6500 — restore gloss, remove fine scratches, and protect paint with YMO 3-stage polish.',\n        'faq'                  => array(\n            array('q' => 'How much does car rubbing and polishing cost in Pune?', 'a' => 'YMO 3-stage rubbing and polishing starts at ₹6500 in Pune with free pick-up and drop.'),\n            array('q' => 'Does YMO provide polishing services across Pune?', 'a' => 'Yes — Baner, Wakad, Hinjewadi, Aundh, Bavdhan, Viman Nagar, and all Pune areas.'),\n        ),\n        'view'",
    "'services/car-interior-cleaning-in-pune-2000' => array(": "'og_image'             => '/assets/img/marketing/2022/03/car-interior-cleaning-in-pune-scaled.jpg',\n        'city_slug'            => 'pune',\n        'updated_at'           => '2026-07-20',\n        'quick_answer'         => 'Car interior deep cleaning in Pune from ₹2000 — vacuum, seat cleaning, dashboard polish, and odour removal with free pick-up.',\n        'view'",
    "'bestcar-services-indore-affordable-solutions' => array(": "'page_type'            => 'locality',\n        'city_slug'            => 'indore',\n        'updated_at'           => '2026-07-20',\n        'quick_answer'         => 'Affordable car services in Indore — denting, painting, AC repair, and periodic maintenance with expert mechanics and free pick-up.',\n        'view'",
}

for slug_line, insert in PATCHES.items():
    if slug_line not in text:
        continue
    idx = text.find(slug_line)
    view_idx = text.find("'view'", idx)
    if view_idx == -1:
        continue
    text = text[:view_idx] + insert + text[view_idx + len("'view'"):]

DATA.write_text(text, encoding="utf-8")
print("Patched tier-1 SEO fields")
