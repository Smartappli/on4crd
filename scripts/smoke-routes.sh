#!/usr/bin/env bash
set -euo pipefail

if ! command -v curl >/dev/null 2>&1; then
  echo "curl is required for route smoke checks." >&2
  exit 1
fi

BASE_URL="${1:-http://127.0.0.1:8000/index.php}"
TIMEOUT_SECONDS="${SMOKE_TIMEOUT_SECONDS:-8}"

urls=(
  "${BASE_URL}?route=tools"
  "${BASE_URL}?route=articles&q=test"
  "${BASE_URL}?route=home"
)

for url in "${urls[@]}"; do
  code=$(curl --max-time "$TIMEOUT_SECONDS" -s -o /dev/null -w "%{http_code}" "$url")
  if [[ "$code" != "200" ]]; then
    echo "FAIL $url -> $code"
    exit 1
  fi
  echo "OK   $url -> $code"
done
