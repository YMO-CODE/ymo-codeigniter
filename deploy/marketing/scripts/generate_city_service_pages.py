#!/usr/bin/env python3
"""Generate Indore/Nashik city hub + city-service pages from marketing_cities.php matrix."""

from __future__ import annotations

import re
from datetime import date
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
CONFIG = ROOT / "application" / "config"
OUT = CONFIG / "marketing_pages_city_services.php"
TODAY = date.today().isoformat()

# Parsed manually from marketing_cities.php structure for generation
CITIES = {
    "indore": {
        "name": "Indore",
        "hub_path": "locations/indore",
        "neighborhoods": ["Deoguradia", "Vijay Nagar", "Palasia", "Bhawarkua", "Rau", "Scheme 54", "AB Road", "Nipania"],
        "quick_answer": "Your Mechanic Online is among the best car service providers in Indore, offering affordable denting, painting, AC repair, periodic servicing, and free pick-up/drop with transparent pricing.",
        "faq": [
            ("What is the cost of car servicing in Indore?", "Car servicing in Indore starts from ₹1999 for periodic maintenance. YMO shares upfront estimates for denting, AC repair, and other jobs before starting work."),
            ("Do you provide same-day car service in Indore?", "Yes. Most maintenance and repair jobs are completed the same day. Book online or call +91-7744-065904."),
            ("Which areas in Indore do you serve?", "We serve Deoguradia, Vijay Nagar, Palasia, Bhawarkua, Rau, Scheme 54, AB Road, Nipania, and surrounding Indore areas with free pick-up and drop."),
        ],
    },
    "nashik": {
        "name": "Nashik",
        "hub_path": "locations/nashik",
        "neighborhoods": ["College Road", "Nashik Road", "Uday Nagar", "Indira Nagar", "Vidya Nagar", "Ashwamegh Nagar", "Ojhar", "Panchavati", "Satpur", "Gangapur Road"],
        "quick_answer": "Your Mechanic Online provides expert car servicing and repairs in Nashik with trained mechanics, transparent pricing, and doorstep pick-up across College Road, Nashik Road, Uday Nagar, and surrounding areas.",
        "faq": [
            ("Where can I find a reliable car mechanic in Nashik?", "Your Mechanic Online offers trained mechanics, modern diagnostic equipment, and upfront pricing for car repair and servicing across Nashik."),
            ("Do you serve Nashik Road and College Road?", "Yes. We provide free pick-up and drop from Nashik Road, College Road, Uday Nagar, Indira Nagar, Vidya Nagar, and nearby neighbourhoods."),
            ("What car services are available in Nashik?", "Complete servicing, AC repair, brake repair, denting and painting, engine diagnostics, interior cleaning, rubbing and polishing, belts and hoses, and oil changes."),
        ],
    },
}

SERVICES = [
    {"key": "complete-car-servicing", "title": "Complete Car Servicing", "slug": "services/complete-car-servicing-in-{city}", "price": 1999, "overview": "Periodic maintenance with oil and filter changes, brake cleaning, AC filter service, coolant top-up, and interior vacuum - with free pick-up and delivery."},
    {"key": "ac", "title": "Car Air Conditioner Servicing", "slug": "services/car-air-conditioner-servicing-in-{city}", "price": None, "overview": "AC gas recharge, leak test, compressor check, and filter cleaning for reliable cooling."},
    {"key": "brakes", "title": "Car Brake Repair", "slug": "services/car-brake-repair-in-{city}", "price": None, "overview": "Brake pads, rotors, calipers, and fluid inspection and replacement by trained technicians."},
    {"key": "denting", "title": "Car Denting And Painting", "slug": "services/car-denting-and-painting-in-{city}-3000", "price": 3000, "overview": "Panel dent repair and industry-approved paint from ₹3000 per panel with colour matching."},
    {"key": "engine", "title": "Car Engine Services", "slug": "services/car-engine-services-in-{city}", "price": None, "overview": "Engine diagnostics, tune-ups, belt replacement, and major repair with expert mechanics."},
    {"key": "interior", "title": "Car Interior Deep Cleaning", "slug": "services/car-interior-cleaning-in-{city}-2500", "price": 2500, "overview": "Vacuum, seat cleaning, dashboard polish, and odour removal from ₹2500."},
    {"key": "polishing", "title": "3 Stage Rubbing and Polishing", "slug": "services/car-rubbing-and-polishing-in-{city}-6500", "price": 6500, "overview": "Restore gloss and remove fine scratches with a professional 3-stage polish from ₹6500."},
    {"key": "belts", "title": "Belts and Hoses", "slug": "services/belts-and-hoses-in-{city}", "price": None, "overview": "Cooling system pressure test, hose replacement, and belt inspection."},
    {"key": "lube", "title": "Lube, Oil and Filters", "slug": "services/lube-oil-and-filters-in-{city}", "price": None, "overview": "Engine oil change, transmission fluid, and filter replacement for all car types."},
]


def php_str(s: str) -> str:
    return "'" + s.replace("\\", "\\\\").replace("'", "\\'") + "'"


def service_cards(city: str, city_name: str) -> str:
    cards = []
    for svc in SERVICES:
        slug = svc["slug"].format(city=city)
        price = f" from ₹{svc['price']}" if svc["price"] else ""
        cards.append(
            f'<div class="md-card-elevated mb-3"><h3>{svc["title"]}</h3>'
            f'<p>{svc["overview"].replace("₹", "₹")} Available in {city_name}{price}.</p>'
            f'<p><a href="/{slug}">Learn more</a></p></div>'
        )
    return "".join(cards)


def faq_html(faq: list[tuple[str, str]]) -> str:
    items = []
    for q, a in faq:
        items.append(f'<li><div><h3>{q}</h3></div><div class="clearfix"><p>{a}</p></div></li>')
    return '<ul class="accordion margin-top-40">' + "".join(items) + "</ul>"


def nap_block(city_name: str) -> str:
    return (
        f'<div class="md-card-filled p-4 my-4 ymo-nap-block">'
        f'<h3 class="md-title-md">Contact YMO - {city_name}</h3>'
        f'<p class="mb-1"><strong>Phone:</strong> <a href="tel:+917744065904">+91-7744-065904</a></p>'
        f'<p class="mb-1"><strong>Email:</strong> <a href="mailto:contactus@yourmechaniconline.com">contactus@yourmechaniconline.com</a></p>'
        f'<p class="mb-0"><strong>Service area:</strong> Doorstep pick-up and drop across {city_name}.</p></div>'
    )


def generate_hub(city: str, data: dict) -> dict:
    name = data["name"]
    path = data["hub_path"]
    hoods = ", ".join(data["neighborhoods"][:8])
    body = (
        f'<p class="ymo-quick-answer"><strong>Best car servicing in {name}?</strong> {data["quick_answer"]}</p>'
        f'<h2>Car services in {name}</h2>{service_cards(city, name)}'
        f'<h2>Areas we serve in {name}</h2><p>We provide free pick-up and drop across {hoods}, and surrounding neighbourhoods.</p>'
        f'<h2>Frequently asked questions</h2>{faq_html(data["faq"])}'
        f'{nap_block(name)}'
        f'<p><a href="/services">View all services</a> · <a href="/contact-us">Contact us</a></p>'
    )
    return {
        "path": path,
        "title": f"Car Servicing in {name} - Your Mechanic Online",
        "meta_description": f"Book expert car servicing in {name} - AC repair, denting, periodic service, and more. Free pick-up, transparent pricing. Call +91-7744-065904.",
        "h1": f"Car servicing in {name}",
        "intro": f"Professional car care with doorstep pick-up across {name}.",
        "body": body,
        "page_type": "hub",
        "city_slug": city,
        "quick_answer": data["quick_answer"],
        "faq": data["faq"],
        "updated_at": TODAY,
        "og_image": "/assets/img/marketing/revslider/main/image_01.jpg",
    }


def generate_service_page(city: str, city_name: str, svc: dict) -> dict:
    slug = svc["slug"].format(city=city)
    price_line = f" starting from ₹{svc['price']}" if svc["price"] else ""
    hoods = ", ".join(CITIES[city]["neighborhoods"][:6])
    qa = (
        f"Your Mechanic Online offers {svc['title'].lower()} in {city_name}{price_line} "
        f"with free pick-up and drop across {hoods}. Book online or call +91-7744-065904."
    )
    faq = [
        (f"How much does {svc['title'].lower()} cost in {city_name}?", f"Pricing{price_line or ' varies by vehicle'}. YMO provides upfront estimates before work begins."),
        (f"Do you offer pick-up for {svc['title'].lower()} in {city_name}?", f"Yes. Free doorstep pick-up and delivery is available across {city_name}."),
    ]
    body = (
        f'<p class="ymo-quick-answer"><strong>{svc["title"]} in {city_name}?</strong> {qa}</p>'
        f'<h3>SERVICE OVERVIEW</h3><p>{svc["overview"]}</p>'
        f'<h3>Areas we serve in {city_name}</h3><p>{hoods} and nearby areas.</p>'
        f'<h3>Related services</h3><p><a href="/locations/{city}">All car services in {city_name}</a> · '
        f'<a href="/services">Service catalogue</a></p>'
        f'<h3>Popular questions</h3>{faq_html(faq)}'
        f'{nap_block(city_name)}'
    )
    title_suffix = f" @ ₹{svc['price']}" if svc["price"] and "Deep Cleaning" not in svc["title"] and "Rubbing" not in svc["title"] else (f" @ ₹{svc['price']}" if svc["price"] else "")
    return {
        "path": slug,
        "title": f"{svc['title']} in {city_name}{title_suffix} - Your Mechanic Online",
        "meta_description": f"{svc['title']} in {city_name}{price_line}. Expert mechanics, transparent pricing, free pick-up. Book with Your Mechanic Online.",
        "h1": f"{svc['title']} in {city_name}",
        "intro": svc["overview"],
        "body": body,
        "page_type": "service",
        "city_slug": city,
        "service_key": svc["key"],
        "quick_answer": qa,
        "faq": faq,
        "updated_at": TODAY,
        "og_image": "/assets/img/marketing/revslider/main/image_01.jpg",
    }


def render_page_entry(page: dict) -> str:
    path = page.pop("path")
    lines = [f"    {php_str(path)} => array("]
    for key, val in page.items():
        if key == "faq":
            lines.append(f"        'faq' => array(")
            for q, a in val:
                lines.append(f"            array('q' => {php_str(q)}, 'a' => {php_str(a)}),")
            lines.append("        ),")
        else:
            lines.append(f"        {php_str(key)} => {php_str(val)},")
    lines.append("        'view' => 'marketing/page',")
    lines.append("    ),")
    return "\n".join(lines)


def main() -> None:
    pages = []
    for city, data in CITIES.items():
        pages.append(generate_hub(city, data))
        for svc in SERVICES:
            pages.append(generate_service_page(city, data["name"], svc))

    out = [
        "<?php",
        "defined('BASEPATH') OR exit('No direct script access allowed');",
        "",
        "/** Auto-generated city hub + service pages - run generate_city_service_pages.py */",
        "return array(",
    ]
    for p in pages:
        entry = dict(p)
        out.append(render_page_entry(entry))
    out.append(");")
    out.append("")
    OUT.write_text("\n".join(out), encoding="utf-8")
    print(f"Wrote {len(pages)} pages to {OUT}")


if __name__ == "__main__":
    main()
