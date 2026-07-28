#!/usr/bin/env python3
"""Generate Option A page stubs and GSC redirect rules for marketing migration."""
from __future__ import annotations

import csv
import re
from pathlib import Path
from urllib.parse import urlparse

ROOT = Path(__file__).resolve().parents[3]
GSC = ROOT / "deploy" / "marketing" / "gsc"
OUT_PAGES = ROOT / "deploy" / "marketing" / "generated" / "pages_option_a.php"
OUT_REDIRECTS = ROOT / "deploy" / "marketing" / "generated" / "redirects_option_a.php"

SKIP_PREFIXES = ("tag/", "category/", "galleries/", "team/", "wp-content/")

# Keyword → redirect target for tag/category long-tail (nearest hub).
HUB_KEYWORDS: list[tuple[tuple[str, ...], str]] = [
    (("indore",), "bestcar-services-indore-affordable-solutions"),
    (("viman",), "affordable-car-services-viman-nagar-pune"),
    (("bavdhan",), "best-car-servicing-in-bavdhan-pune-expert-care"),
    (("wakad",), "affordable-car-servicing-in-wakad-pune"),
    (("baner",), "the-best-car-servicing-in-baner"),
    (("hinjewadi",), "best-car-services-hinjewadi-pune"),
    (("aundh",), "car-servicing-in-aundh"),
    (("mercedes", "bmw", "audi", "luxury", "exotic"), "premium-luxury-car-service-pune"),
    (("denting", "painting", "polish", "rubbing", "ceramic", "cleaning"), "services"),
    (("ac ", "air-condition", "cooling"), "services/car-air-conditioner-servicing-in-pune"),
    (("tyre", "tire", "wheel", "alignment"), "services"),
    (("brake",), "services/car-brake-repair"),
    (("engine", "oil", "lube", "filter"), "services/lube-oil-and-filters"),
    (("spare", "parts"), "ymo-spares"),
    (("blog",), "blog/best-oil-change-service-in-pune"),
]

PREFIX_HUBS = {
    "tag/": "locations/pune",
    "category/": "services",
    "galleries/": "services",
    "team/": "about-us",
    "wp-content/": "",
}


def path_from_url(url: str) -> str:
    return urlparse(url.strip()).path.strip("/").lower()


def load_existing() -> set[str]:
    text = (ROOT / "application/config/marketing_pages_data.php").read_text(encoding="utf-8")
    return {m.group(1).lower() for m in re.finditer(r"^\s+'([^']+)'\s*=>", text, re.M)}


def load_gsc_paths() -> dict[str, int]:
    out: dict[str, int] = {}
    with (GSC / "Pages.csv").open(encoding="utf-8") as f:
        for row in csv.DictReader(f):
            url = row.get("Top pages") or ""
            if "yourmechaniconline.com" not in url or "booking." in url:
                continue
            path = path_from_url(url)
            if not path:
                continue
            try:
                clicks = int(row.get("Clicks") or 0)
            except ValueError:
                clicks = 0
            out[path] = max(out.get(path, 0), clicks)
    return out


def load_targets() -> set[str]:
    targets: set[str] = set()
    for csv_path in GSC.glob("*Top target pages*.csv"):
        with csv_path.open(encoding="utf-8") as f:
            for row in csv.DictReader(f):
                url = row.get("Target page", "")
                if "yourmechaniconline.com" not in url or "booking." in url:
                    continue
                path = path_from_url(url)
                if path:
                    targets.add(path)
    return targets


def should_preserve(path: str, clicks: int, in_targets: bool) -> bool:
    if path.startswith(SKIP_PREFIXES):
        return False
    return clicks >= 1 or in_targets


def infer_page_type(path: str) -> str:
    if re.match(r"^\d{4}/\d{2}/\d{2}/", path) or path.startswith("blog"):
        return "blog"
    if path.startswith("services/"):
        return "service"
    if "mercedes" in path or "bmw" in path or "audi" in path or "luxury" in path or "premium" in path:
        return "luxury"
    if path.startswith("locations/") or any(
        x in path for x in ("baner", "wakad", "hinjewadi", "aundh", "bavdhan", "viman", "pune", "indore")
    ):
        return "locality"
    if path in ("services", "about-us", "blog") or path.endswith("-ymo") or path == "best-car-servicing-in-pune-ymo":
        return "hub"
    return "locality"


def slug_to_h1(slug: str) -> str:
    part = slug.split("/")[-1]
    part = re.sub(r"-\d+$", "", part)
    part = part.replace("₹", "Rs ")
    words = part.replace("-", " ").split()
    small = {"in", "and", "for", "the", "a", "an", "of", "to", "your"}
    return " ".join(w.lower() if w.lower() in small and i else w.capitalize() for i, w in enumerate(words))


def meta_for(path: str, h1: str) -> str:
    ptype = infer_page_type(path)
    if ptype == "service":
        return f"{h1} — book online with Your Mechanic Online. Transparent pricing and doorstep pick-up in Pune."
    if ptype == "luxury":
        return f"{h1} — specialist luxury car care in Pune. Book Mercedes, BMW, and Audi servicing with YMO."
    if "indore" in path:
        return f"{h1} — affordable car servicing in Indore. Book online with Your Mechanic Online."
    return f"{h1} — trusted car servicing with doorstep pick-up. Book online with Your Mechanic Online."


def intro_for(path: str) -> str:
    ptype = infer_page_type(path)
    if ptype == "blog":
        return "Expert advice from the Your Mechanic Online team — when to service, what to expect, and how to book."
    if ptype == "service":
        return "Professional service, clear pricing, and convenient scheduling — book your slot in minutes."
    if ptype == "luxury":
        return "OEM-grade processes, trained technicians, and transparent pricing for premium vehicles."
    return "Doorstep pick-up and drop, trained technicians, and transparent pricing for local drivers."


def php_str(s: str) -> str:
    return "'" + s.replace("\\", "\\\\").replace("'", "\\'") + "'"


def hub_for_path(path: str) -> str:
    lower = path.lower()
    for keywords, target in HUB_KEYWORDS:
        if any(k in lower for k in keywords):
            return target
    for prefix, target in PREFIX_HUBS.items():
        if lower.startswith(prefix):
            return target
    if lower.startswith("best-car-servicing-in-pune-ymo/"):
        return "best-car-servicing-in-pune-ymo"
    if lower.startswith("ymo-car-servicing-locations-in-pune/"):
        return "locations/pune"
    return "locations/pune"


def main() -> None:
    existing = load_existing()
    clicks = load_gsc_paths()
    targets = load_targets()

    preserve: set[str] = set()
    for path, c in clicks.items():
        if should_preserve(path, c, path in targets):
            preserve.add(path)
    for path in targets:
        if not path.startswith(SKIP_PREFIXES):
            preserve.add(path)

    missing = sorted(preserve - existing)

    OUT_PAGES.parent.mkdir(parents=True, exist_ok=True)
    lines = [
        "<?php",
        "defined('BASEPATH') OR exit('No direct script access allowed');",
        "",
        "/** Auto-generated Option A page stubs — replace body copy during WP migration. */",
        "return array(",
    ]
    for path in missing:
        h1 = slug_to_h1(path)
        lines.append(f"    {php_str(path)} => array(")
        lines.append(f"        'title'            => {php_str(h1 + ' — Your Mechanic Online')},")
        lines.append(f"        'meta_description' => {php_str(meta_for(path, h1))},")
        lines.append(f"        'h1'               => {php_str(h1)},")
        lines.append(f"        'intro'            => {php_str(intro_for(path))},")
        lines.append(f"        'body'             => '',")
        lines.append(f"        'page_type'        => {php_str(infer_page_type(path))},")
        lines.append(f"        'view'             => 'marketing/page',")
        lines.append(f"        'gsc_clicks'       => {clicks.get(path, 0)},")
        lines.append("    ),")
    lines.append(");")
    OUT_PAGES.write_text("\n".join(lines) + "\n", encoding="utf-8")

    exact: dict[str, str] = {}
    for path in sorted(clicks):
        if path in preserve:
            continue
        target = hub_for_path(path)
        exact[path] = target
        exact[path + "/"] = target

    # Remove self-redirects
    exact = {k: v for k, v in exact.items() if k.rstrip("/") != v.rstrip("/")}

    redir_lines = [
        "<?php",
        "defined('BASEPATH') OR exit('No direct script access allowed');",
        "",
        "/** Auto-generated Option A legacy 301 rules from GSC (tag/category/nested). */",
        "return array(",
    ]
    for src, dst in sorted(exact.items()):
        redir_lines.append(f"    {php_str(src)} => {php_str(dst)},")
    redir_lines.append(");")
    OUT_REDIRECTS.write_text("\n".join(redir_lines) + "\n", encoding="utf-8")

    print(f"missing_pages={len(missing)} -> {OUT_PAGES.relative_to(ROOT)}")
    print(f"redirect_rules={len(exact)} -> {OUT_REDIRECTS.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
