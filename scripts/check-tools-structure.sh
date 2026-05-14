#!/usr/bin/env bash
set -euo pipefail

python - <<'PY'
from pathlib import Path
import re

map_php = Path('app/config/tools_panels.php').read_text()

map_match = re.search(r"return\s*\[(.*?)\];", map_php, re.S)
if not map_match:
    raise SystemExit('tool panel map not found')
map_block = map_match.group(1)
map_pairs = re.findall(r"'([^']+)'\s*=>\s*'([^']+)'", map_block)
map_entries = dict(map_pairs)

if len(map_entries) != len(map_pairs):
    raise SystemExit('Duplicate tool ids detected in toolPanelMap')

partial_values = [partial for _, partial in map_pairs]
if len(set(partial_values)) != len(partial_values):
    raise SystemExit('Duplicate partial file mapping detected in toolPanelMap')

if not map_entries:
    raise SystemExit('toolPanelMap is empty')

# 1) every mapped partial exists
missing = [f for f in map_entries.values() if not Path('pages/tools_panels', f).is_file()]
if missing:
    raise SystemExit('Missing panel partial files: ' + ', '.join(sorted(missing)))

# 2) every mapped partial exposes an <article id="tool-..."> matching its key
article_ids = {}
for tool_id, partial_file in sorted(map_entries.items()):
    panel = Path('pages/tools_panels', partial_file).read_text()
    expected = f'id="{tool_id}"'
    if expected not in panel:
        raise SystemExit(f'Panel {partial_file} does not declare expected {expected}')

    ids = re.findall(r'id="([^"]+)"', panel)
    for panel_id in ids:
        owner = article_ids.get(panel_id)
        if owner is not None and owner != partial_file:
            raise SystemExit(f'Duplicate DOM id across partials: {panel_id} in {owner} and {partial_file}')
        article_ids[panel_id] = partial_file

print(f'OK tools structure: {len(map_entries)} mapped panels')
PY
