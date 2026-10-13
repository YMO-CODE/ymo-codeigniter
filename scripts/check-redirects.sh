#!/usr/bin/env bash
# Verify marketing redirects: status code, Location header, and hop count.
# Usage:
#   bash scripts/check-redirects.sh
#   BASE=https://staging.example.com bash scripts/check-redirects.sh
set -euo pipefail

BASE="${BASE:-https://www.yourmechaniconline.com}"
APEX="${APEX:-https://yourmechaniconline.com}"
HTTP_WWW="${HTTP_WWW:-http://www.yourmechaniconline.com}"
HTTP_APEX="${HTTP_APEX:-http://yourmechaniconline.com}"

URLS=(
  "${HTTP_APEX}/"
  "${APEX}/"
  "${HTTP_WWW}/"
  "${BASE}/"
  "${BASE}/about-us/"
  "${BASE}/the-best-bmw-servicing-in-baner/"
  "${BASE}/the-best-bmw-servicing-in-baner"
  "${BASE}/the-best-audi-servicing-in-wakad/"
  "${BASE}/tag/bmw-workshop-pune/"
  "${BASE}/category/car-servicing-maintenance/"
  "${BASE}/bestcar-services-indore-affordable-solutions/"
  "${BASE}/services/car-rubbing-and-polishing-in-pune-%E2%82%B96500/"
  "${BASE}/book-car-servicing-in-pune/"
)

fail=0

check_one() {
  local url="$1"
  local status location hops effective

  status="$(curl -sS -o /dev/null -w '%{http_code}' -I --max-redirs 0 "$url" 2>/dev/null || echo '000')"
  location="$(curl -sS -I --max-redirs 0 "$url" 2>/dev/null | awk 'BEGIN{IGNORECASE=1} /^Location:/ {sub(/^Location:[ \t]*/,""); gsub(/\r$/,""); print; exit}')"
  hops="$(curl -sS -o /dev/null -w '%{num_redirects}' -L --max-redirs 10 "$url" 2>/dev/null || echo '?')"
  effective="$(curl -sS -o /dev/null -w '%{url_effective}' -L --max-redirs 10 "$url" 2>/dev/null || echo '?')"

  printf '%s\n' "URL: $url"
  printf '  first-hop HTTP: %s\n' "$status"
  if [[ -n "$location" ]]; then
    printf '  Location: %s\n' "$location"
  fi
  printf '  redirects to final: %s\n' "$hops"
  printf '  final URL: %s\n' "$effective"

  if [[ "$status" != "301" && "$status" != "200" && "$status" != "410" ]]; then
    printf '  WARN: expected 301, 200, or 410 on first hop\n'
    fail=1
  fi
  if [[ "$status" == "302" || "$status" == "307" || "$status" == "308" ]]; then
    printf '  FAIL: temporary redirect on first hop\n'
    fail=1
  fi
  if [[ "$hops" != "?" && "$hops" -gt 1 ]]; then
    printf '  WARN: more than 1 redirect hop to final URL\n'
    fail=1
  fi
  echo ""
}

echo "Marketing redirect check (BASE=$BASE)"
echo ""

for u in "${URLS[@]}"; do
  check_one "$u"
done

if [[ "$fail" -ne 0 ]]; then
  echo "Some checks failed or need review."
  exit 1
fi

echo "All checks passed."
