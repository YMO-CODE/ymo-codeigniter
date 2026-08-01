#!/usr/bin/env bash
# Bump deploy session epoch so admin sessions re-login after deploy.
# Customer (booking) sessions are kept — only admins are signed out via the epoch hook.
# Run from repo root: bash deploy/scripts/reset-sessions.sh
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

EPOCH="$(git rev-parse HEAD 2>/dev/null || echo "manual-$(date +%s)")"
EPOCH_FILE="$ROOT/storage/.session_epoch"

mkdir -p "$ROOT/storage"
printf '%s' "$EPOCH" > "$EPOCH_FILE"
chmod 644 "$EPOCH_FILE" 2>/dev/null || true

echo "==> Session epoch: ${EPOCH:0:12}..."
echo "    Written to storage/.session_epoch"
echo "    Admin users will be signed out on their next request."
echo "    Customer sessions are unchanged."
