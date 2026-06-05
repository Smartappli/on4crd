# I18n Audit After Sequential Module Pass

Date: 2026-06-05

## Scope

- Domains: 86
- Locales: 31
- Loaded locale catalogs: 2666
- Checked string values: 72447

## Results

| Check | Result |
|---|---:|
| Module-by-module audit errors | 0 |
| Module-by-module audit warnings | 0 |
| Systematic i18n audit errors | 0 |
| Systematic i18n audit warnings | 0 |
| Sequential translation candidates | 0 |
| Placeholder/token leaks | 0 |
| PHPUnit tests | 413 tests / 15673 assertions OK |

## Additional Modules Corrected

- `article_propose`: form labels, errors and metadata moved to `articles` translations.
- `wiki`: theme proposal UI moved to `wiki` translations and mojibake removed from visible strings.
- `wiki_propose`: form labels, errors and metadata moved to `wiki_edit` translations.
- `forgot_password`: password reset email subject/body moved to `forgot_password` translations with `{reset_link}` preserved.

Detailed per-module output: `storage/i18n-module-audit-final.txt`.
