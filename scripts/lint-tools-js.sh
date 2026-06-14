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

script_file = Path('assets/js/modules/tools.js')
if not script_file.is_file():
    raise SystemExit('tools script file not found: assets/js/modules/tools.js')

print(script_file.read_text(encoding='utf-8'))
PY

node --check "$TMP_JS"

python - <<'PY' "$TMP_JS"
from pathlib import Path
import re
import sys

js = Path(sys.argv[1]).read_text(encoding='utf-8')
pattern = re.compile(r"^\s{4}let\s+([A-Za-z_$][\w$]*)\s*=\s*null\s*;", re.M)
names = pattern.findall(js)
counts = {}
for name in names:
    counts[name] = counts.get(name, 0) + 1
dups = sorted(name for name, count in counts.items() if count > 1)
if dups:
    raise SystemExit(f"Duplicate tools DOM handle declarations found: {', '.join(dups)}")
PY

echo "tools JS syntax OK"
