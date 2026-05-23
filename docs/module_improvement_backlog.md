# Site Improvement Plan (Reset)

## Date
May 23, 2026

## Objective
Improve operator productivity and member trust by reducing UX inconsistency, tightening quality controls, and industrializing high-volume workflows.

## Critical Assessment

### 1) Product quality is uneven across modules
- Functional coverage is good, but admin workflows are not consistently ergonomic.
- Similar actions (filter, bulk action, retry, moderation) still behave differently by module.

### 2) i18n reliability remains a user-facing risk
- Automated checks exist, but some pages still show encoding/fallback artifacts.
- This directly impacts trust and clarity in multilingual contexts.

### 3) Business logic is still too page-centric
- Significant logic is embedded in `pages/*.php`.
- This increases regression risk and slows safe iteration.

### 4) Observability is baseline, not yet operational control
- Metrics/snapshot tooling exists.
- Cross-workflow operational dashboards and remediation signals are still limited.

### 5) Recommendations are controllable but not fully explainable
- Signal toggles and opt-in/out exist.
- Explainability and privacy communication are still thin for non-technical members.

### 6) Editorial workflow is partially industrialized
- Queue and retry exist.
- Structured failure taxonomy and robust remediation workflow are not complete.

---

## New Improvement Plan

## Phase A (2 weeks): Stabilization & Perceived Quality
Goal: remove visible friction and trust breakers.

### Scope
- Fix i18n text integrity on critical routes (`dashboard`, `admin_articles`, `admin_library`, high-traffic member pages).
- Standardize admin UX patterns for filters, bulk actions, confirmations, and feedback messages.
- Add CI quality gate for critical i18n defects (mojibake/key mismatch/fallback leakage).

### Exit KPIs
- 0 critical encoding artifacts on priority routes.
- 100% of priority admin pages aligned to common action patterns.
- CI blocks deployment on critical i18n integrity failures.

---

## Phase B (3 weeks): Operator Workflow Industrialization (Wave 5 complete)
Goal: move from feature presence to scalable operation.

### Scope
- Finalize Admin tagging co-pilot:
  - duplicate detection,
  - merge operations,
  - suggestion + bulk approve flows.
- Finalize Editorial command center:
  - scheduled queue board,
  - normalized blocked reasons taxonomy,
  - one-click retry (single and bulk),
  - action traceability.
- Finalize Library ingestion assistant:
  - metadata templates by document type,
  - controlled vocabulary enforcement,
  - guided ingestion checks.

### Exit KPIs
- 30% reduction in average moderation/editing handling time.
- 80% of tag corrections executed via bulk/suggested workflows.
- 100% of editorial retries captured with explicit action outcomes.

---

## Phase C (3 weeks): Member Trust & Experience (Wave 6 complete)
Goal: make personalization understandable, optional, and useful.

### Scope
- Explainable recommendations:
  - clearer “why this item” messaging,
  - per-signal controls,
  - privacy-aware global and granular opt-out.
- Unified activity timeline:
  - articles, classifieds, albums, library events.
- Contextual onboarding nudges:
  - role-aware guidance,
  - behavior-based prompts.

### Exit KPIs
- Recommendation disablement due to incomprehension decreases measurably.
- Unified timeline available for all member profiles.
- Lower support load for “why am I seeing this” questions.

---

## Phase D (Continuous): Structural Hardening
Goal: sustain delivery speed without regression growth.

### Scope
- Incrementally extract business logic from `pages/` into reusable service-level functions.
- Add targeted automated tests for high-risk flows:
  - scheduled publication/retry,
  - tag merge/approval,
  - recommendation preference behavior.
- Expand observability to include operational workflow health indicators.

### Exit KPIs
- Reduced regression rate over successive releases.
- Increased test coverage on critical workflows.
- Faster recovery time for production workflow incidents.

---

## Delivery Rules
- Ship in small, rollback-safe slices.
- Keep schema changes backward compatible.
- Prefer additive migrations and progressive activation flags.
- Track adoption and reliability per workflow, not only per module.
