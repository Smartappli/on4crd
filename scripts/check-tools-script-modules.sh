#!/usr/bin/env bash
set -euo pipefail

python - <<'PY'
from pathlib import Path

main = Path('pages/tools_script.js.php').read_text()
expected = [
    "tools_script_helpers.js.php",
    "tools_script_domrefs.js.php",
    "tools_script_initializers.js.php",
    "tools_script_computes.js.php",
    "tools_script_loader.js.php",
]

missing = [name for name in expected if name not in main]
if missing:
    raise SystemExit('Missing script module include(s): ' + ', '.join(missing))

print('OK tools script modules: ' + ', '.join(expected))
PY
