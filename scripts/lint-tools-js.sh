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

script_file = Path('pages/tools_script.js.php')
if not script_file.is_file():
    raise SystemExit('tools script file not found: pages/tools_script.js.php')

js = script_file.read_text()
js = re.sub(r'<\?(?:php|=).*?\?>', 'null', js, flags=re.S)
print(js)
PY

node --check "$TMP_JS"

python - <<'PY' "$TMP_JS"
from pathlib import Path
import re
import sys

js = Path(sys.argv[1]).read_text()
pattern = re.compile(r"\b(?:const|let|var)\s+([A-Za-z_$][\w$]*)\s*=\s*document\.getElementById\(")
names = pattern.findall(js)
counts = {}
for name in names:
    counts[name] = counts.get(name, 0) + 1
dups = sorted(name for name, count in counts.items() if count > 1)
if dups:
    raise SystemExit(f"Duplicate DOM id binding declarations found: {', '.join(dups)}")
PY

echo "tools JS syntax OK"
