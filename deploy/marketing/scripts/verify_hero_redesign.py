#!/usr/bin/env python3
import re
import subprocess
import sys

BASE = "http://www.yourmechaniconline.com:8080"
PAGES = [
    ("home", ""),
    ("service", "services/complete-car-servicing"),
    ("hub", "locations/pune"),
    ("locality", "the-best-car-servicing-in-baner"),
    ("about", "about-us"),
    ("privacy", "privacy-policy"),
]

issues = []
for label, path in PAGES:
    url = f"{BASE}/{path}" if path else f"{BASE}/"
    proc = subprocess.run(
        ["curl.exe", "-s", "-L", "--max-time", "25", url],
        capture_output=True,
        text=True,
        check=False,
    )
    html = proc.stdout or ""
    m = re.search(r'<section class="ymo-hero[^"]*"[^>]*>.*?</section>', html, re.S)
    if not m:
        issues.append(f"{label}: hero section missing")
        continue
    hero = m.group(0)
    cls = re.search(r'class="(ymo-hero[^"]+)"', hero)
    classes = cls.group(1) if cls else ""

    if label == "home" and "ymo-hero--home" not in classes:
        issues.append(f"{label}: expected ymo-hero--home, got {classes}")
    if label == "service":
        if "ymo-hero--split" not in classes or "ymo-hero--service" not in classes:
            issues.append(f"{label}: expected split+service, got {classes}")
        if "ymo-hero__service-photo" in hero:
            issues.append(f"{label}: legacy service-photo strip still present")
        if "ymo-hero__media" not in hero:
            issues.append(f"{label}: missing side media panel")
        if "fetchpriority=\"high\"" not in hero:
            issues.append(f"{label}: missing fetchpriority on hero image")
        if "SERVICE OVERVIEW" in hero:
            issues.append(f"{label}: SERVICE OVERVIEW leaked into hero")
        if "Complete car servicing in Pune from" not in hero:
            issues.append(f"{label}: quick_answer subtitle missing from hero")
    if label in ("hub", "locality") and "ymo-hero--split" not in classes:
        issues.append(f"{label}: expected split hero, got {classes}")
    if label == "about" and "ymo-hero--minimal" not in classes:
        issues.append(f"{label}: expected minimal hero, got {classes}")
    if label == "privacy" and "ymo-hero--minimal" not in classes:
        issues.append(f"{label}: expected minimal hero, got {classes}")
    if label == "privacy" and "ymo-hero__proof" in hero:
        issues.append(f"{label}: trust proof should be hidden on privacy page")
    if label != "privacy" and "ymo-hero__proof" not in hero:
        issues.append(f"{label}: trust proof missing")
    if label == "about" and "Book now" not in hero:
        issues.append(f"{label}: Book now CTA missing")

if issues:
    print("HERO VERIFY FAILED:")
    for line in issues:
        print(" ", line)
    sys.exit(1)

print("All hero types verified OK.")
