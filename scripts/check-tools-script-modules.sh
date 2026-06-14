#!/usr/bin/env bash
set -euo pipefail

python - <<'PY'
from pathlib import Path

script_path = Path('assets/js/modules/tools.js')
page_path = Path('pages/tools.php')
layout_path = Path('app/layout_renderer.php')
for path in (script_path, page_path, layout_path):
    if not path.is_file():
        raise SystemExit(f'tools script contract file not found: {path}')

script = script_path.read_text(encoding='utf-8')
page = page_path.read_text(encoding='utf-8')
layout = layout_path.read_text(encoding='utf-8')

expected = [
    "const readJsonConfig",
    "const refreshDomRefs",
    "const toolInitializers",
    "const initToolIfNeeded",
    "const loadToolPanel",
    "const setActiveTool",
    "data-tool-target",
    "ajax', 'tool_panel'",
]

missing = [fragment for fragment in expected if fragment not in script]
if missing:
    raise SystemExit('Missing tools.js contract fragment(s): ' + ', '.join(missing))

if 'id="tools-i18n"' not in page:
    raise SystemExit('pages/tools.php must expose #tools-i18n for assets/js/modules/tools.js')

if "$module = $moduleByRoute[$route] ?? $route;" not in layout:
    raise SystemExit('app/layout_renderer.php must default module JS assets to the route name')
if "'assets/js/modules/' . $candidate . '.js'" not in layout:
    raise SystemExit('app/layout_renderer.php must load module JS from assets/js/modules')

print('OK tools script module: assets/js/modules/tools.js')
PY
