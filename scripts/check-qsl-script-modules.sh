#!/usr/bin/env bash
set -euo pipefail

QSL_SCRIPT="pages/qsl_script.js.php"

if [[ ! -f "$QSL_SCRIPT" ]]; then
  echo "Missing $QSL_SCRIPT" >&2
  exit 1
fi

required=(
  "qsl_script_nav.js.php"
  "qsl_script_assistant.js.php"
  "qsl_script_draw_assistant.js.php"
  "qsl_script_qso_toggle.js.php"
  "qsl_script_manual_preview.js.php"
  "qsl_script_card_preview.js.php"
  "qsl_script_dropzone.js.php"
)

for module in "${required[@]}"; do
  if [[ ! -f "pages/$module" ]]; then
    echo "Missing module file: pages/$module" >&2
    exit 1
  fi
  if ! grep -Fq "$module" "$QSL_SCRIPT"; then
    echo "Missing include for module: $module" >&2
    exit 1
  fi
done

echo "OK qsl script modules: ${required[*]}"
