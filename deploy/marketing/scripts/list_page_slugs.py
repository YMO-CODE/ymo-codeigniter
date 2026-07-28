import re
from pathlib import Path

root = Path(__file__).resolve().parents[3] / "application" / "config"
for name in ("marketing_pages_data.php", "marketing_pages_option_a.php"):
    text = (root / name).read_text(encoding="utf-8")
    keys = re.findall(r"'([^']+)'\s*=>\s*array\s*\(", text)
    print(f"=== {name} ({len(keys)}) ===")
    for k in sorted(keys):
        print(k)
