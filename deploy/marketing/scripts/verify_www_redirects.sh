#!/usr/bin/env bash
# Verify apex/http → www HTTPS redirects for marketing site.
# Usage: bash deploy/marketing/scripts/verify_www_redirects.sh [base_apex_host]
set -euo pipefail

APEX="${1:-yourmechaniconline.com}"
WWW="www.${APEX}"

check_redirect() {
  local url="$1"
  local expect_location="$2"
  local label="$3"
  echo "==> $label"
  echo "    GET $url"
  local headers
  headers="$(curl -sS -I -L --max-redirs 0 "$url" 2>/dev/null || true)"
  local code
  code="$(echo "$headers" | head -1 | awk '{print $2}')"
  local location
  location="$(echo "$headers" | awk 'tolower($1)=="location:" {print $2}' | tr -d '\r')"
  echo "    Status: $code"
  echo "    Location: ${location:-<none>}"
  if [[ "$code" != "301" && "$code" != "308" ]]; then
    echo "    FAIL expected 301/308" >&2
    return 1
  fi
  if [[ -n "$expect_location" && "$location" != *"$expect_location"* ]]; then
    echo "    FAIL expected Location to contain: $expect_location" >&2
    return 1
  fi
  echo "    OK"
}

fail=0
check_redirect "https://${APEX}/" "https://${WWW}/" "HTTPS apex → www" || fail=1
check_redirect "http://${WWW}/" "https://${WWW}/" "HTTP www → HTTPS www" || fail=1

echo ""
if [[ "$fail" -eq 0 ]]; then
  echo "All redirect checks passed."
else
  echo "Some checks failed — fix nginx/DNS before GSC resubmit." >&2
  exit 1
fi
