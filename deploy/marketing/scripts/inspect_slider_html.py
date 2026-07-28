#!/usr/bin/env python3
import re, ssl, urllib.request

URLS = [
    "https://yourmechaniconline.com/",
    "https://yourmechaniconline.com/affordable-car-servicing-in-wakad-pune/",
    "https://yourmechaniconline.com/bestcar-services-indore-affordable-solutions/",
]
ctx = ssl.create_default_context()

for url in URLS:
    req = urllib.request.Request(url, headers={"User-Agent": "YMO"})
    html = urllib.request.urlopen(req, timeout=60, context=ctx).read().decode("utf-8", "replace")
    print("===", url, "len", len(html))
    # rs-slide backgrounds
    bgs = re.findall(r"data-lazyload=['\"]([^'\"]+)['\"]", html, re.I)
    print(" data-lazyload", len(bgs))
    for u in bgs[:8]:
        print("  ", u[:100])
    bgs2 = re.findall(r"background-image:\s*url\(['\"]?([^'\"()]+)['\"]?\)", html, re.I)
    print(" bg-url", len(bgs2))
    for u in bgs2[:8]:
        print("  ", u[:100])
    imgs = re.findall(r"/wp-content/uploads/revslider/[^\"'\s>]+\.(?:jpe?g|png|webp)", html, re.I)
    print(" revslider paths", len(set(imgs)))
    for u in sorted(set(imgs))[:10]:
        print("  ", u)
