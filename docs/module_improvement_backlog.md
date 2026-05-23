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
- [ ] Chatbot connected to site knowledge sources (RAG).
- [ ] Admin assistant for taxonomy/tagging/i18n QA.
- [ ] Personalized recommendations by user activity.

## Delivery Notes
- Ship by small slices with rollback-safe changes.
- Keep schema additions backward compatible.
- Track adoption per module (search usage, CTR, content views, conversion flows).
