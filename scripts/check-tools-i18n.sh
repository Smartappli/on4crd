#!/usr/bin/env bash
set -euo pipefail

python - <<'PY'
from pathlib import Path
import re

base = Path('app/i18n/tools')
locales = ['fr', 'en', 'de', 'nl']
key_sets = {}
for locale in locales:
    path = base / f'{locale}.php'
    if not path.is_file():
        raise SystemExit(f'Missing locale file: {path}')
    text = path.read_text()
    keys = set(re.findall(r"'([^']+)'\s*=>", text))
    if not keys:
        raise SystemExit(f'No translation keys found in {path}')
    key_sets[locale] = keys

fr_keys = key_sets['fr']
for locale in locales[1:]:
    missing = sorted(fr_keys - key_sets[locale])
    extra = sorted(key_sets[locale] - fr_keys)
    if missing or extra:
        msg = [f'i18n key mismatch for locale {locale}:']
        if missing:
            msg.append(' missing: ' + ', '.join(missing[:10]) + (' ...' if len(missing) > 10 else ''))
        if extra:
            msg.append(' extra: ' + ', '.join(extra[:10]) + (' ...' if len(extra) > 10 else ''))
        raise SystemExit('\n'.join(msg))

print(f'OK tools i18n keys: {len(fr_keys)} keys across {len(locales)} locales')
PY
