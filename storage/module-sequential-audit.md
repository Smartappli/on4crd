# Sequential Module Audit Report

Date: 2026-06-05

## Method

Chaque module signalé par les audits a été traité dans l'ordre suivant : diagnostic, correction, validation locale, puis passage au module suivant.

## Modules Corrigés Dans Cette Passe

| Module | Diagnostic | Correction | Validation |
|---|---|---|---|
| upload_helpers / uploads | Appel `finfo_*` non protégé si l'extension `fileinfo` est absente. | Ajout d'un garde `function_exists()` avant détection MIME. | `function_call_audit`: `MISSING_CALLS=0`; lint OK. |
| function_call_audit | Faux positifs sur fonctions optionnelles `gd/fileinfo`. | Allowlist explicite des fonctions optionnelles utilisées avec garde-fous. | `MISSING_CALLS=0`. |
| article_propose | Textes UI, erreurs et métadonnées codés en dur. | Raccordement au domaine `articles`; ajout des clés dans 31 langues. | Audit i18n strict OK. |
| wiki | Textes de proposition de thématique codés en dur et mojibake. | Raccordement au domaine `wiki`; ajout/traduction des clés dans 31 langues. | Lint OK; audit i18n strict OK. |
| wiki_propose | Textes UI, erreurs et métadonnées codés en dur. | Raccordement au domaine `wiki_edit`; ajout/traduction des clés dans 31 langues. | Lint OK; audit i18n strict OK. |
| forgot_password | Sujet et corps d'e-mail de reset codés en dur en français. | Ajout de `email_subject` et `email_body` dans les 31 langues; placeholder `{reset_link}` conservé. | Audit i18n strict OK; lint OK. |
| site_quality_audit | Faux positif sur les fallbacks passés à `$tr()`. | Regex durcie pour détecter seulement les vrais seconds arguments littéraux de `render_layout`. | `admin_pages_with_hardcoded_layout_title=[]`. |

## Final Checks

- I18n module-by-module audit: `86` modules, `31` langues, `72447` chaînes, `0` erreur, `0` warning.
- I18n systematic audit: `0` erreur, `0` warning.
- Sequential translation dry-run: `TOTAL_CANDIDATES=0`, `FAILED_MODULES=0`.
- Function call audit: `MISSING_CALLS=0`.
- Site quality audit: `admin_pages_with_hardcoded_layout_title=[]`.
- Targeted PHP lint: OK on modified runtime/tools files.
- PHPUnit: `413` tests, `15673` assertions, OK.

## Notes

`site_quality_audit` conserve une liste `pages_without_render_layout` pour les endpoints techniques attendus (`robots`, `sitemap`, flux, JSON, rendu widget, exports). Ces fichiers ne doivent pas forcément appeler `render_layout()`.
