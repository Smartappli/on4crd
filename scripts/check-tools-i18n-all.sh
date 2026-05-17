#!/usr/bin/env bash
set -euo pipefail
python - <<'PY'
from pathlib import Path
import re
base=Path('app/i18n/tools')
files=sorted(base.glob('*.php'))
if not files:
    raise SystemExit('No tools locale files found')
key_re=re.compile(r"'([^']+)'\s*=>")
ref=(base/'fr.php').read_text()
ref_keys=set(key_re.findall(ref))
for f in files:
    keys=set(key_re.findall(f.read_text()))
    missing=sorted(ref_keys-keys)
    extra=sorted(keys-ref_keys)
    if missing or extra:
        print(f'Locale mismatch: {f.name}')
        if missing:
            print(' missing:', ', '.join(missing[:20]))
        if extra:
            print(' extra:', ', '.join(extra[:20]))
        raise SystemExit(1)
print(f'OK tools i18n all locales: {len(ref_keys)} keys across {len(files)} locales')
PY
