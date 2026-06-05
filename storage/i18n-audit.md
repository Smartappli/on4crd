# I18n Sequential Module Audit

Date: 2026-06-05

## Scope

- Modules audited: 86
- Locales per module: 31
- Locale catalogs loaded: 2666
- String values checked: 71083

## Corrections Applied

- Sequential fallback scan after the previous native translation pass: 2508 actionable candidate values.
- Automatic protected translation pass: 1935 values updated across 247 files.
- Manual targeted corrections after review: 33 additional labels/sentences corrected.
- Technical/shared terms are explicitly allowlisted in the audit tooling to avoid false positives for radio terms, units, brands, route/code identifiers, filenames, and terms that are natively shared across target languages.

## Final Audit Results

| Check | Result |
|---|---:|
| Module-by-module audit errors | 0 |
| Module-by-module audit warnings | 0 |
| Systematic i18n audit errors | 0 |
| Systematic i18n audit warnings | 0 |
| Placeholder/token leaks | 0 |
| PHP syntax errors in new tools | 0 |
| PHPUnit tests | 413 tests / 15673 assertions OK |

## Tools Added

- `tools/i18n_module_sequential_audit.php`: strict module-by-module audit with structural checks, placeholder/tag checks, mojibake checks, suspicious fragment checks, and isolated fallback detection.
- `tools/i18n_translate_identical_values.php`: protected sequential fixer for isolated values identical to `fr` or `en`, with placeholders and radio/technical terms preserved.

## Detailed Report

- `storage/i18n-module-audit-final.txt` contains the final per-module audit output. Every module is reported with `errors=0 warnings=0`.
