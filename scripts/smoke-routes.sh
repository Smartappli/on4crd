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
  "${BASE_URL}?route=tools&ajax=tool_panel&id=tool-grid"
)

for url in "${urls[@]}"; do
  code=$(curl --max-time "$TIMEOUT_SECONDS" -s -o /dev/null -w "%{http_code}" "$url")
  if [[ "$code" != "200" ]]; then
    echo "FAIL $url -> $code"
    exit 1
  fi
  echo "OK   $url -> $code"
done

NON_DEFAULT_PANEL_ID=$(python - <<'PY'
from pathlib import Path
import re
content = Path('app/config/tools_panels.php').read_text()
pairs = re.findall(r"'([^']+)'\s*=>\s*'[^']+'", content)
for panel_id in pairs:
    if panel_id != 'tool-grid':
        print(panel_id)
        break
PY
)

tools_html=$(curl --max-time "$TIMEOUT_SECONDS" -s "${BASE_URL}?route=tools")
if [[ -n "$NON_DEFAULT_PANEL_ID" ]] && grep -q "id=\"${NON_DEFAULT_PANEL_ID}\"" <<<"$tools_html"; then
  echo "FAIL tools initial HTML should not contain lazy tool panel id=${NON_DEFAULT_PANEL_ID}"
  exit 1
fi
if ! grep -q 'id="tool-grid"' <<<"$tools_html"; then
  echo "FAIL tools initial HTML should contain default tool panel id=tool-grid"
  exit 1
fi
echo "OK   tools initial HTML lazy panel presence (grid only + no ${NON_DEFAULT_PANEL_ID:-non-default panel})"

unknown_panel_code=$(curl --max-time "$TIMEOUT_SECONDS" -s -o /dev/null -w "%{http_code}" "${BASE_URL}?route=tools&ajax=tool_panel&id=tool-does-not-exist")
if [[ "$unknown_panel_code" != "404" ]]; then
  echo "FAIL unknown tool panel should return 404 -> $unknown_panel_code"
  exit 1
fi
echo "OK   unknown tool panel -> $unknown_panel_code"

invalid_panel_code=$(curl --max-time "$TIMEOUT_SECONDS" -s -o /dev/null -w "%{http_code}" "${BASE_URL}?route=tools&ajax=tool_panel&id=not-a-tool")
if [[ "$invalid_panel_code" != "400" ]]; then
  echo "FAIL invalid tool panel id should return 400 -> $invalid_panel_code"
  exit 1
fi
echo "OK   invalid tool panel id -> $invalid_panel_code"

cache_header=$(curl --max-time "$TIMEOUT_SECONDS" -sI "${BASE_URL}?route=tools&ajax=tool_panel&id=tool-grid" | tr -d '\r' | awk -F': ' 'tolower($1)=="cache-control"{print $2; exit}')
if [[ -z "$cache_header" ]]; then
  echo "FAIL missing Cache-Control header on tool panel response"
  exit 1
fi
echo "OK   tool panel Cache-Control -> $cache_header"
