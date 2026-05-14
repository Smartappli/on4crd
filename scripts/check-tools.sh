#!/usr/bin/env bash
set -euo pipefail

RUN_SMOKE=0
SMOKE_BASE_URL="${TOOLS_SMOKE_BASE_URL:-}"

usage() {
  cat <<'USAGE'
Usage: ./scripts/check-tools.sh [--with-smoke] [--smoke-base-url URL]

Runs toolbox quality checks:
  - structure consistency
  - inline JS lint/syntax checks
  - PHP syntax check for pages/tools.php

Options:
  --with-smoke             run HTTP smoke checks (requires a base URL)
  --smoke-base-url URL     override TOOLS_SMOKE_BASE_URL for this run
  -h, --help               show this help
USAGE
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --with-smoke)
      RUN_SMOKE=1
      shift
      ;;
    --smoke-base-url)
      if [[ $# -lt 2 || -z "${2:-}" ]]; then
        echo "--smoke-base-url requires a non-empty URL" >&2
        exit 2
      fi
      SMOKE_BASE_URL="$2"
      shift 2
      ;;
    -h|--help)
      usage
      exit 0
      ;;
    *)
      echo "Unknown argument: $1" >&2
      echo "Use --help for usage." >&2
      exit 2
      ;;
  esac
done

./scripts/check-tools-structure.sh
./scripts/check-tools-i18n.sh
./scripts/lint-tools-js.sh
./scripts/check-tools-script-modules.sh
php -l pages/tools.php

if [[ "$RUN_SMOKE" -eq 1 || -n "$SMOKE_BASE_URL" ]]; then
  if [[ -z "$SMOKE_BASE_URL" ]]; then
    echo "A smoke base URL is required (set TOOLS_SMOKE_BASE_URL or use --smoke-base-url)." >&2
    exit 1
  fi
  ./scripts/smoke-routes.sh "$SMOKE_BASE_URL"
else
  echo "SKIP smoke-routes (set TOOLS_SMOKE_BASE_URL or pass --with-smoke --smoke-base-url URL)"
fi
