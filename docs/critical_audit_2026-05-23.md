# Critical Audit — May 23, 2026

## Scope
- Public pages and admin pages quality snapshot.
- i18n consistency and native-language coverage checks.
- Wave 3 deliverables sanity check.

## Inputs and commands
- `php tools/site_quality_audit.php`
- `php tools/i18n_audit.php --public-only`
- `php scripts/check_i18n_native.php --strict`
- `php scripts/check_i18n_coverage.php`
- `php scripts/check_chatbot_i18n_quality.php`

## Snapshot findings

### P1 — Localization debt still high on public pages
- `tools/site_quality_audit.php` reports:
  - `translation_findings_all_lines: 442`
  - `translation_findings_public_lines: 392`
- `tools/i18n_audit.php --public-only` reports:
  - `TOTAL_POTENTIAL_FRENCH_LINES=345`
  - Main hotspots:
    - `pages/home.php` (193 lines flagged)
    - `pages/qsl.php` (42)
    - `pages/articles.php` (13)
    - `pages/bandplan_harec.php` (10)
    - `pages/bandplan_on2.php` (10)

Impact:
- High probability of mixed-language UI and inconsistent UX for non-FR locales.

### P1 — Encoding artifacts remain in legacy areas
- Existing Wave 3 chatbot i18n checks are now green.
- However, audit outputs still reveal legacy mojibake patterns in non-chatbot modules and legacy text blocks.

Impact:
- Trust and readability issues for non-Latin locales.

### P2 — Admin localization consistency gap
- `site_quality_audit.php` flags `admin_translation_reviews.php` as still carrying hardcoded layout-title behavior.

Impact:
- Inconsistent admin language rendering and maintainability overhead.

### P2 — Recommendations are delivered but not yet explainable
- Wave 3 recommendations are functional and integrated in dashboard.
- No end-user explanation labels yet ("why this recommendation") and no explicit opt-out control at UI level.

Impact:
- Lower transparency and controllability for members.

## Verified strengths
- `check_i18n_native.php --strict`: OK.
- `check_i18n_coverage.php`: OK (14 locales complete).
- `check_chatbot_i18n_quality.php`: OK.
- Wave 3 items are fully implemented from functional standpoint.

## Action plan (next execution order)

1. Localization extraction pass (public pages first)
- Prioritize:
  - `pages/home.php`
  - `pages/qsl.php`
  - `pages/articles.php`
  - `pages/bandplan_harec.php`
  - `pages/bandplan_on2.php`
- Target:
  - Replace hardcoded strings with i18n keys.
  - Keep locale fallbacks deterministic.

2. Encoding normalization pass
- Enforce UTF-8 consistency for i18n files and legacy templates.
- Keep automated checks in CI path (`check_chatbot_i18n_quality.php` + global audit checks).

3. Admin i18n consistency pass
- Remove remaining hardcoded layout/fallback behavior in `admin_translation_reviews.php`.
- Ensure all visible admin labels come from locale dictionaries.

4. Recommendation transparency controls (Wave 6 bridge)
- Add "why this suggestion" label per recommendation card.
- Add member-level opt-out toggle for personalized recommendations.

## Progress update (same-day follow-up)
- Executed first extraction pass on `pages/tools_panels/*` fallback labels and output placeholders.
- Public audit metric moved from `TOTAL_POTENTIAL_FRENCH_LINES=345` to `322` (delta `-23`).
- Remaining hotspots are still concentrated in:
  - `pages/home.php`
  - `pages/qsl.php`
  - `pages/articles.php`
  - `pages/bandplan_harec.php`
  - `pages/bandplan_on2.php`

## Exit criteria for this audit cycle
- Public translation findings reduced by at least 60%.
- Zero mojibake/suspicious encoding in audited modules.
- No admin page flagged for hardcoded layout title.
- Recommendation UI includes explainability and opt-out control.
