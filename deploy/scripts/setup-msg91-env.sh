#!/usr/bin/env bash
# Merge MSG91 / DLT template env vars into .env on the VPS.
# Run from repo root: bash deploy/scripts/setup-msg91-env.sh [--write]
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

ENV_FILE="${ENV_FILE:-$ROOT/.env}"
WRITE=0
if [ "${1:-}" = "--write" ]; then
  WRITE=1
fi

echo "==> MSG91 / DLT env template ($(date -u +%Y-%m-%dT%H:%M:%SZ))"
echo ""
echo "# Paste Flow IDs from MSG91 (NOT Jio DLT IDs). See deploy/scripts/MSG91_SETUP.md"
echo "YMO_SMS_DRIVER=msg91"
echo "YMO_MSG91_AUTHKEY="
echo "YMO_MSG91_SENDER=YMOCAR"
echo "YMO_MSG91_ROUTE=4"
echo "YMO_TPL_OTP="
echo "YMO_TPL_BOOKING_OK="
echo "YMO_TPL_BOOKING_STATUS="
echo "YMO_TPL_SERVICE_REMIND="
echo "YMO_TPL_REVIEW="
echo "YMO_TPL_INVOICE="
echo "YMO_TPL_REFERRAL="
echo "YMO_TPL_CRM_CAMPAIGN="
echo ""
echo "Jio DLT IDs (reference only — do NOT put these in YMO_TPL_*):"
echo "# OTP            1277178591884069081"
echo "# BOOKING_OK     1277178591453817908"
echo "# BOOKING_STATUS 1277178591568547322"
echo "# SERVICE_REMIND 1277178591934852577"
echo "# REVIEW         1277178591508786260"
echo "# INVOICE        1277178591247861897"
echo "# REFERRAL       1277178591255421707"
echo "# CRM_CAMPAIGN   1277178591687073165"
echo ""
echo "After --write, restart: docker compose ... restart app"
echo "Test: bash deploy/scripts/smoke-sms.sh YOUR_MOBILE otp"
echo ""

if [ "$WRITE" -eq 0 ]; then
  echo "Dry run. To append missing keys: bash deploy/scripts/setup-msg91-env.sh --write"
  exit 0
fi

touch "$ENV_FILE"

set_kv_if_missing() {
  local key="$1"
  local val="$2"
  if grep -q "^${key}=" "$ENV_FILE" 2>/dev/null; then
    echo "    keep existing ${key}"
  else
    echo "${key}=${val}" >> "$ENV_FILE"
    echo "    added ${key}"
  fi
}

set_kv_if_missing "YMO_SMS_DRIVER" "msg91"
set_kv_if_missing "YMO_MSG91_SENDER" "YMOCAR"
set_kv_if_missing "YMO_MSG91_ROUTE" "4"
set_kv_if_missing "YMO_MSG91_AUTHKEY" ""
set_kv_if_missing "YMO_TPL_OTP" ""
set_kv_if_missing "YMO_TPL_BOOKING_OK" ""
set_kv_if_missing "YMO_TPL_BOOKING_STATUS" ""
set_kv_if_missing "YMO_TPL_SERVICE_REMIND" ""
set_kv_if_missing "YMO_TPL_REVIEW" ""
set_kv_if_missing "YMO_TPL_INVOICE" ""
set_kv_if_missing "YMO_TPL_REFERRAL" ""
set_kv_if_missing "YMO_TPL_CRM_CAMPAIGN" ""

echo ""
echo "==> Edit $ENV_FILE and fill YMO_MSG91_AUTHKEY + YMO_TPL_* Flow IDs."
