#!/usr/bin/env python3
"""
Fill the 13 unmatched marketing pages:
- services/* from live WP HTML (custom post type URLs, not in REST pages API)
- hubs (services, locations/*, about-us) from aggregated WP content

Run from repo root:
  python deploy/marketing/scripts/migrate_remaining_pages.py
  python deploy/marketing/scripts/migrate_remaining_pages.py --dry-run
"""
from __future__ import annotations

import argparse
import html
import json
import re
import ssl
import time
import urllib.request
from pathlib import Path
from urllib.parse import unquote, urlparse

ROOT = Path(__file__).resolve().parents[3]
WP_BASE = "https://yourmechaniconline.com"
PAGES_MAIN = ROOT / "application" / "config" / "marketing_pages_data.php"
REPORT = ROOT / "deploy" / "marketing" / "generated" / "remaining_pages_report.json"

SCRIPT_STYLE_RE = re.compile(r"<(script|style|noscript)[^>]*>.*?</\1>", re.I | re.S)
TITLE_RE = re.compile(r"<title[^>]*>(.*?)</title>", re.I | re.S)
META_DESC_RE = re.compile(
    r'<meta\s+(?:name="description"|property="og:description")\s+content="([^"]*)"',
    re.I,
)
H1_RE = re.compile(
    r'(?:page-header-left[^>]*>\s*<h1[^>]*>(.*?)</h1>|<h1[^>]*>(.*?)</h1>)',
    re.I | re.S,
)
TAG_RE = re.compile(r"<[^>]+>")
WHITESPACE_RE = re.compile(r"\s+")

# Path variants for live WP (percent-encoded rupee in URLs).
SERVICE_LIVE_PATHS: dict[str, list[str]] = {
    "services/belts-and-hoses": ["services/belts-and-hoses/"],
    "services/car-air-conditioner-servicing-in-pune": ["services/car-air-conditioner-servicing-in-pune/"],
    "services/car-brake-repair": ["services/car-brake-repair/"],
    "services/car-denting-and-painting-3000": ["services/car-denting-and-painting-3000/"],
    "services/car-engine-services-in-pune": ["services/car-engine-services-in-pune/"],
    "services/car-interior-cleaning-in-pune-₹2000": [
        "services/car-interior-cleaning-in-pune-₹2000/",
        "services/car-interior-cleaning-in-pune-%e2%82%b92000/",
    ],
    "services/car-rubbing-and-polishing-in-pune-₹6500": [
        "services/car-rubbing-and-polishing-in-pune-₹6500/",
        "services/car-rubbing-and-polishing-in-pune-%e2%82%b96500/",
    ],
    "services/complete-car-servicing": ["services/complete-car-servicing/"],
    "services/lube-oil-and-filters": ["services/lube-oil-and-filters/"],
}

SERVICE_CARD_SOURCE = "the-best-car-servicing-in-baner"


def fetch_html(path: str) -> str:
    url = f"{WP_BASE}/{path.lstrip('/')}"
    ctx = ssl.create_default_context()
    req = urllib.request.Request(url, headers={"User-Agent": "YMO-Marketing-Migrator/1.0"})
    with urllib.request.urlopen(req, timeout=60, context=ctx) as resp:
        return resp.read().decode("utf-8", errors="replace")


def strip_tags(text: str) -> str:
    text = SCRIPT_STYLE_RE.sub("", text)
    text = TAG_RE.sub(" ", text)
    return html.unescape(WHITESPACE_RE.sub(" ", text)).strip()


def clean_body_fragment(raw: str) -> str:
    raw = SCRIPT_STYLE_RE.sub("", raw)
    raw = re.sub(r'href="https?://yourmechaniconline\.com/?([^"]*)"', r'href="/\1"', raw, flags=re.I)
    raw = re.sub(r'href="https?://www\.yourmechaniconline\.com/?([^"]*)"', r'href="/\1"', raw, flags=re.I)
    raw = re.sub(r"\s+", " ", raw)
    return raw.strip()


def parse_live_page(html_text: str) -> dict:
    title_m = TITLE_RE.search(html_text)
    title = strip_tags(title_m.group(1)) if title_m else ""
    title = re.sub(r"\s*[|\u2013-]\s*Your Mechanic Online.*$", "", title, flags=re.I).strip()

    # Prefer longest unique meta description (skip site-wide default tagline).
    desc_candidates: list[str] = []
    for m in META_DESC_RE.finditer(html_text):
        val = html.unescape(m.group(1).strip())
        if val and val not in desc_candidates:
            desc_candidates.append(val)
    desc = ""
    for val in sorted(desc_candidates, key=len, reverse=True):
        if val.lower() != "the best car servicing found anywhere in pune":
            desc = val
            break
    if not desc and desc_candidates:
        desc = desc_candidates[0]

    h1_m = re.search(r'page-header-left[^>]*>\s*<h1[^>]*>(.*?)</h1>', html_text, re.I | re.S)
    if h1_m:
        h1 = strip_tags(h1_m.group(1))
    else:
        h1_m = H1_RE.search(html_text)
        h1_raw = (h1_m.group(1) or h1_m.group(2)) if h1_m else ""
        h1 = strip_tags(h1_raw) if h1_raw else ""
    if not h1:
        h1 = title

    body = ""
    for pattern in (
        r'class="theme-page[^"]*"[^>]*>.*?vc_col-sm-9.*?<div class="wpb_wrapper">(.*?)</div>\s*</div>\s*</div>\s*</div>\s*</div>\s*<div class="background-overlay"',
        r'class="theme-page[^"]*"[^>]*>(.*?)</div>\s*</div>\s*<div class="background-overlay"',
        r'data-widget_type="theme-post-content\.default"[^>]*>.*?<div class="elementor-widget-container">(.*?)</div>\s*</div>\s*</div>\s*</div>\s*</section>',
    ):
        m = re.search(pattern, html_text, re.I | re.S)
        if m:
            body = clean_body_fragment(m.group(1))
            if len(strip_tags(body)) > 80:
                break

    if not body:
        h1_end = html_text.find("</h1>")
        if h1_end > 0:
            chunk = html_text[h1_end : h1_end + 15000]
            paras = re.findall(r"<p[^>]*>(.*?)</p>", chunk, re.I | re.S)
            if paras:
                body = "".join(f"<p>{clean_body_fragment(p)}</p>" for p in paras[:10])

    intro = strip_tags(body)[:280] if body else desc[:280]
    if len(intro) >= 280:
        intro = intro[:277].rsplit(" ", 1)[0] + "…"

    return {
        "title": title + (" - Your Mechanic Online" if "ymo" not in title.lower() else ""),
        "meta_description": desc[:160] if desc else intro[:160],
        "h1": h1,
        "intro": intro,
        "body": body,
    }


def extract_service_cards_from_html(html_text: str) -> dict[str, dict]:
    """Parse services-list cards keyed by href path."""
    cards: dict[str, dict] = {}
    for m in re.finditer(
        r'<div class="service-content">\s*<h4[^>]*>\s*<a href="([^"]+)"[^>]*>(.*?)</a>\s*</h4>\s*<p>(.*?)</p>',
        html_text,
        re.I | re.S,
    ):
        href = unquote(urlparse(m.group(1)).path.strip("/").lower())
        href = href.replace("%e2%82%b9", "₹")
        title = strip_tags(m.group(2))
        para = clean_body_fragment(m.group(3))
        cards[href] = {
            "title": title + " - Your Mechanic Online",
            "meta_description": strip_tags(para)[:160],
            "h1": title,
            "intro": strip_tags(para)[:280],
            "body": f"<p>{para}</p>",
        }
        # alias without trailing encoding variants
        cards[href.rstrip("/")] = cards[href]
    return cards


def load_service_cards_from_option_a() -> dict[str, dict]:
    path = ROOT / "application" / "config" / "marketing_pages_option_a.php"
    if not path.is_file():
        return {}
    text = path.read_text(encoding="utf-8")
    m = re.search(
        r"'affordable-car-services-viman-nagar-pune'\s*=>\s*array\s*\(.*?'body'\s*=>\s*'((?:\\.|[^'\\])*)'",
        text,
        re.S,
    )
    if not m:
        return {}
    raw = m.group(1).replace("\\'", "'").replace("\\\\", "\\")
    return extract_service_cards_from_html(raw)


def load_baner_body() -> str:
    text = PAGES_MAIN.read_text(encoding="utf-8")
    m = re.search(
        r"'the-best-car-servicing-in-baner'\s*=>\s*array\s*\(.*?'body'\s*=>\s*'((?:\\.|[^'\\])*)'",
        text,
        re.S,
    )
    if not m:
        return ""
    raw = m.group(1).replace("\\'", "'").replace("\\\\", "\\")
    return raw


def build_hub_pages(service_cards: dict[str, dict]) -> dict[str, dict]:
    """Compose hub pages from service card summaries."""
    cards_html = []
    for slug in sorted(SERVICE_LIVE_PATHS):
        card = service_cards.get(slug) or service_cards.get(slug.replace("₹", "%e2%82%b9"))
        if not card:
            continue
        cards_html.append(
            f'<div class="md-card-elevated mb-3"><h3>{html.escape(card["h1"])}</h3>'
            f'<p>{html.escape(strip_tags(card["body"]))}</p>'
            f'<p><a href="/{slug}">Learn more</a></p></div>'
        )

    services_hub = {
        "title": "Car Services in Pune & Indore - Your Mechanic Online",
        "meta_description": "Periodic service, AC repair, denting & painting, rubbing & polishing, interior cleaning, brake repair - book online with YMO.",
        "h1": "Our car services",
        "intro": "Expert car care with transparent pricing and doorstep pick-up across Pune and Indore.",
        "body": "".join(cards_html),
        "page_type": "hub",
    }

    pune_localities = [
        ("the-best-car-servicing-in-baner", "Baner"),
        ("affordable-car-servicing-in-wakad-pune", "Wakad"),
        ("best-car-services-hinjewadi-pune", "Hinjewadi"),
        ("car-servicing-in-aundh", "Aundh"),
        ("affordable-car-services-viman-nagar-pune", "Viman Nagar"),
        ("best-car-servicing-in-bavdhan-pune-expert-care", "Bavdhan"),
    ]
    loc_links = []
    main_text = PAGES_MAIN.read_text(encoding="utf-8")
    for slug, label in pune_localities:
        if f"'{slug}'" in main_text:
            loc_links.append(f'<li><a href="/{slug}">Car servicing in {label}</a></li>')

    locations_pune = {
        "title": "Car Servicing in Pune - Your Mechanic Online",
        "meta_description": "Book car service in Pune - Baner, Wakad, Hinjewadi, Aundh, Bavdhan, Viman Nagar and more. Doorstep pick-up.",
        "h1": "Car servicing in Pune",
        "intro": "Doorstep pick-up, periodic service, AC repair, denting & polishing across Pune.",
        "body": "<ul>" + "".join(loc_links) + "</ul><p><a href=\"/book-car-servicing-in-pune/\">Book car servicing in Pune</a></p>",
        "page_type": "hub",
    }

    locations_indore = {
        "title": "Car Servicing in Indore - Your Mechanic Online",
        "meta_description": "Affordable car servicing in Indore - denting, AC repair, periodic service. Book online with YMO.",
        "h1": "Car servicing in Indore",
        "intro": "Professional car care and transparent pricing for Indore drivers.",
        "body": '<p><a href="/bestcar-services-indore-affordable-solutions/">Affordable car services in Indore</a></p>',
        "page_type": "hub",
    }

    about_us = {
        "title": "About Your Mechanic Online",
        "meta_description": "Your Mechanic Online - trusted car servicing, repairs, and doorstep pick-up across Pune and Indore.",
        "h1": "About Your Mechanic Online",
        "intro": "We bring expert car servicing to your doorstep with transparent pricing and trained technicians.",
        "body": (
            "<p>Your Mechanic Online (YMO) is a leading car service provider in Pune and Indore. "
            "We offer periodic maintenance, AC repair, denting & painting, luxury car care, and more - "
            "with free doorstep pick-up and drop.</p>"
            "<p><a href=\"/contact-us\">Contact us</a> · "
            "<a href=\"/book-car-servicing-in-pune/\">Book a service</a></p>"
        ),
        "page_type": "hub",
    }

    return {
        "services": services_hub,
        "locations/pune": locations_pune,
        "locations/indore": locations_indore,
        "about-us": about_us,
    }


def php_str(value: str) -> str:
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


def replace_php_array_field(text: str, slug: str, field: str, value: str) -> tuple[str, bool]:
    """Replace or insert a field within one slug's array block (line-scoped, no cross-page bleed)."""
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
        for field in ("title", "meta_description", "h1", "intro", "body", "page_type"):
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
    args = parser.parse_args()

    baner_body = load_baner_body()
    service_cards = load_service_cards_from_option_a()
    if not service_cards and baner_body:
        service_cards = extract_service_cards_from_html(baner_body)

    updates: dict[str, dict] = {}
    fetch_log: dict[str, str] = {}

    for slug, live_paths in SERVICE_LIVE_PATHS.items():
        data: dict | None = None
        # Prefer live page (full content + SEO)
        for live_path in live_paths:
            try:
                html_text = fetch_html(live_path)
                parsed = parse_live_page(html_text)
                if parsed.get("body") or parsed.get("title"):
                    data = parsed
                    data["page_type"] = "service"
                    fetch_log[slug] = f"live:{live_path}"
                    break
            except Exception as exc:  # noqa: BLE001
                fetch_log[slug] = f"live-fail:{exc}"
            time.sleep(0.3)

        # Fallback: service card from Baner page
        if not data:
            for key in (slug, slug.replace("₹", "%e2%82%b9")):
                if key in service_cards:
                    data = dict(service_cards[key])
                    data["page_type"] = "service"
                    fetch_log[slug] = "baner-card"
                    break

        if data:
            updates[slug] = data

    updates.update(build_hub_pages(service_cards))

    report = {
        "updated_slugs": sorted(updates.keys()),
        "fetch_log": fetch_log,
        "service_cards_found": len(service_cards),
    }
    REPORT.parent.mkdir(parents=True, exist_ok=True)
    REPORT.write_text(json.dumps(report, indent=2), encoding="utf-8")

    print(f"Prepared updates for {len(updates)} pages")
    for slug in sorted(updates):
        src = fetch_log.get(slug, "hub")
        line = f"  {slug} ({src})"
        print(line.encode("ascii", "replace").decode("ascii"))

    if args.dry_run:
        return

    changed = update_php_file(PAGES_MAIN, updates)
    print(f"Updated fields in marketing_pages_data.php: {changed}")


if __name__ == "__main__":
    main()
