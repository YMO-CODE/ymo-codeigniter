#!/usr/bin/env python3
import json, re, ssl, urllib.request

WP = "https://yourmechaniconline.com"
ctx = ssl.create_default_context()
REV = re.compile(r"\[rev_slider_vc[^\]]*\]", re.I)
RS_IMG = re.compile(
    r"https?://(?:www\.)?yourmechaniconline\.com/wp-content/uploads/([^\"'\s>]+\.(?:jpe?g|png|webp))",
    re.I,
)


def get(url):
    req = urllib.request.Request(url, headers={"User-Agent": "YMO/1.0"})
    with urllib.request.urlopen(req, timeout=60, context=ctx) as r:
        return r.read().decode("utf-8", "replace")


pages = json.loads(get(f"{WP}/wp-json/wp/v2/pages?per_page=100&status=publish"))
print("pages", len(pages))
found = []
for p in pages:
    raw = (p.get("content") or {}).get("rendered", "")
    m = REV.findall(raw)
    if m:
        found.append((p.get("link"), m))
print("rev_slider in rendered:", len(found))
for link, m in found:
    print(" ", link, m[0][:100])

html = get(WP + "/")
print("homepage rs-module", "rs-module" in html.lower())
rs_imgs = sorted({u for u in RS_IMG.findall(html) if "revslider" in u.lower()})
print("homepage revslider images", len(rs_imgs))
for u in rs_imgs:
    print(" ", u)

# fetch each page HTML for rs-module (sample)
with_rs = []
for p in pages[:50]:
    link = p.get("link", "")
    if not link:
        continue
    try:
        ph = get(link)
    except Exception as exc:
        print("fail", link, exc)
        continue
    if "rs-module" in ph.lower() or REV.search(ph):
        imgs = [u for u in RS_IMG.findall(ph) if "revslider" in u.lower() or "uploads" in u.lower()]
        with_rs.append({"link": link, "imgs": imgs[:5]})
print("pages with rs-module in HTML:", len(with_rs))
for row in with_rs:
    print(" ", row["link"], len(row["imgs"]), "imgs")
