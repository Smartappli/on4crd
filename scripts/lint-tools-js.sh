#!/usr/bin/env bash
set -euo pipefail

if ! command -v node >/dev/null 2>&1; then
  echo "Node.js is required for JS syntax checks (node --check)." >&2
  exit 1
fi

TMP_JS="$(mktemp --suffix=.js)"
cleanup() {
  rm -f "$TMP_JS"
}
trap cleanup EXIT

python - <<'PY' > "$TMP_JS"
from pathlib import Path
import re
s=Path('pages/tools.php').read_text()
m=re.search(r'<script nonce="<\?= e\(csp_nonce\(\)\) \?>">\n(.*?)\n</script>', s, re.S)
if not m:
    raise SystemExit('tools inline script not found')
js=m.group(1)
js=re.sub(r'<\?=.*?\?>', 'null', js)
print(js)
PY

node --check "$TMP_JS"
echo "tools JS syntax OK"
