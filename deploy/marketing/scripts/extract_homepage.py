#!/usr/bin/env python3
import json, re, ssl, urllib.request
from html import unescape
from pathlib import Path

ROOT = Path(__file__).resolve().parents[3]
OUT = ROOT / "deploy/marketing/generated/homepage_extract.json"

def strip_tags(s):
    return re.sub(r"\s+", " ", re.sub(r"<[^>]+>", " ", s)).strip()

def get(url):
    req = urllib.request.Request(url, headers={"User-Agent": "YMO"})
    with urllib.request.urlopen(req, timeout=60, context=ssl.create_default_context()) as r:
        return r.read().decode("utf-8", "replace")

html = get("https://yourmechaniconline.com/")
title = re.search(r"<title[^>]*>(.*?)</title>", html, re.I | re.S)
metas = re.findall(r'<meta\s+(?:name="description"|property="og:description")\s+content="([^"]*)"', html, re.I)
h1s = re.findall(r"<h1[^>]*>(.*?)</h1>", html, re.I | re.S)
# service cards
cards = re.findall(
    r'<div class="service-content">\s*<h4[^>]*>\s*<a href="([^"]+)"[^>]*>(.*?)</a>\s*</h4>\s*<p>(.*?)</p>',
    html, re.I | re.S,
)
features = re.findall(r'<div class="feature-item"><h5>(.*?)</h5>', html, re.I | re.S)
data = {
    "title": unescape(strip_tags(title.group(1))) if title else "",
    "metas": [unescape(m) for m in metas[:5]],
    "h1s": [unescape(strip_tags(h))[:120] for h in h1s[:5]],
    "service_cards": [
        {"href": h, "title": unescape(strip_tags(t)), "text": unescape(strip_tags(p))[:200]}
        for h, t, p in cards[:12]
    ],
    "features": [unescape(strip_tags(f))[:80] for f in features[:8]],
}
OUT.parent.mkdir(parents=True, exist_ok=True)
OUT.write_text(json.dumps(data, indent=2), encoding="utf-8")
print(json.dumps(data, indent=2)[:4000])
