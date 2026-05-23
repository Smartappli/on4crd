# Module Improvement Backlog

## Objective
Deliver functional upgrades by waves without changing existing business rules.

## Wave 1 (Quick Wins)
- [x] Unified global search across `articles`, `wiki`, `classified_ads`, `member_library_documents`, `albums`.
- [x] Cross-module favorites/bookmarks (articles, albums, classifieds, library docs).
- [x] In-app notification center (publication, moderation, import completion).
- [x] Unified admin list UX (filters, pagination, bulk actions patterns).

## Wave 2 (Core Modules)
- [x] Classifieds workflow: draft/publish/expire/renew + moderation/reporting.
- [x] Articles workflow: revisions, scheduling, editorial preview.
- [x] Library: collections/tags, better discovery, related documents.
- [x] Albums: bulk upload UX, ordering, metadata enrichment.
- [x] Tools: saved conversion presets + conversion history.

## Wave 3 (Differentiators)
- [~] Chatbot connected to site knowledge sources (RAG).
- [ ] Admin assistant for taxonomy/tagging/i18n QA.
- [ ] Personalized recommendations by user activity.

## Critical Audit (May 23, 2026)
- P1: Several locale files contain encoding artifacts (mojibake), reducing trust and comprehension for non-Latin languages.
- P1: Chatbot answers were previously shown with a single source hint; weak provenance impacts confidence and moderation review.
- P2: Search relevance is not yet personalized by profile intent (operator/member/admin), creating noisy result ordering.
- P2: Cross-module UX is still uneven on dense workflows (library curation, article editorial queue, classifieds moderation).
- P3: Recommendation logic exists in backlog but lacks transparent controls/explanations and user-level opt-out knobs.

## Wave 4 (Reliability & Quality)
- [ ] i18n hardening: UTF-8 normalization, key completeness gates, screenshot QA per locale.
- [ ] RAG quality pipeline: source freshness scoring, answer confidence thresholds, fallback responses.
- [ ] Observability baseline: module health dashboard, query latency/error SLOs, alert rules.

## Wave 5 (Operator Productivity)
- [ ] Admin co-pilot for tagging: suggest/merge tags, duplicate detection, bulk approve flows.
- [ ] Editorial command center: scheduled queue board, blocked content reasons, one-click retries.
- [ ] Library ingestion assistant: metadata extraction templates and controlled vocabulary enforcement.

## Wave 6 (Member Experience)
- [ ] Explainable recommendations with per-signal controls and privacy-aware opt-out.
- [ ] Unified activity timeline across articles, classifieds, albums, and documents.
- [ ] Contextual onboarding nudges by role and recent behavior.

## Delivery Notes
- Ship by small slices with rollback-safe changes.
- Keep schema additions backward compatible.
- Track adoption per module (search usage, CTR, content views, conversion flows).
