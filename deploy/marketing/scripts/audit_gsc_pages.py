#!/usr/bin/env python3
"""Audit GSC exports vs marketing_pages_data.php for Option A migration."""
import csv
import re
from pathlib import Path
from urllib.parse import urlparse

ROOT = Path(__file__).resolve().parents[3]
GSC = ROOT / "deploy" / "marketing" / "gsc"
DATA_FILE = ROOT / "application" / "config" / "marketing_pages_data.php"

SKIP_PREFIXES = ("tag/", "category/", "galleries/", "team/", "wp-content/")


def path_from_url(url: str) -> str:
    return urlparse(url.strip()).path.strip("/").lower()


def load_existing() -> set[str]:
    existing = set()
    for rel in (
        "application/config/marketing_pages_data.php",
        "application/config/marketing_pages_option_a.php",
    ):
        path = ROOT / rel
        if not path.is_file():
            continue
        text = path.read_text(encoding="utf-8")
        for m in re.finditer(r"^\s+'([^']+)'\s*=>", text, re.M):
            existing.add(m.group(1).lower())
    return existing


def load_clicks() -> dict[str, int]:
    pages = {}
    with (GSC / "Pages.csv").open(encoding="utf-8") as f:
        for row in csv.DictReader(f):
            url = row.get("Top pages") or row.get("Page") or ""
            if "yourmechaniconline.com" not in url or "booking." in url:
                continue
            path = path_from_url(url)
            if not path:
                continue
            try:
                clicks = int(row.get("Clicks") or 0)
            except ValueError:
                clicks = 0
            pages[path] = max(pages.get(path, 0), clicks)
    return pages


def load_targets() -> set[str]:
    targets = set()
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
    if clicks >= 1:
        return True
    if in_targets:
        return True
    return False


def slug_to_title(slug: str) -> str:
    base = slug.split("/")[-1]
    base = re.sub(r"-\d+$", "", base)
    base = base.replace("₹", "Rs ")
    words = base.replace("-", " ").split()
    return " ".join(w.capitalize() for w in words)


def main():
    existing = load_existing()
    clicks = load_clicks()
    targets = load_targets()

    preserve = set()
    for path, c in clicks.items():
        if should_preserve(path, c, path in targets):
            preserve.add(path)
    for path in targets:
        if not path.startswith(SKIP_PREFIXES):
            preserve.add(path)

    missing = sorted(preserve - existing)
    print(f"existing={len(existing)} preserve={len(preserve)} missing={len(missing)}")
    for p in missing:
        print(f"{p}\tclicks={clicks.get(p, 0)}")


if __name__ == "__main__":
    main()
