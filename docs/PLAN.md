# Work Atlas — Delivery plan for Phase 0 and Phase 1

Based on `docs/01-vision.md` and `docs/02-implementation.md`. Phase 0 = weeks 1–4, Phase 1 = weeks 5–12.

## How to use this plan

- **One milestone = one working session = one PR.** At the end of a milestone: full test suite green,
  CI green, PR opened, then stop for the maintainer's review.
- Don't start a milestone until its dependencies are merged and its open items (O-numbers, listed at
  the end) are settled. Answered decisions are A-numbers. A milestone with an open item may start only
  on the parts the item doesn't touch.
- Acceptance criteria are checked by the reviewer. Each listed test must exist and pass.
- A phase gate is passed only when every condition holds, even if the calendar says otherwise.

## Overview

| M | Title | Phase | Depends on | Blocked by |
|---|---|---|---|---|
| M1 | Repository bootstrap and governance | 0 | — | — (push waits for O1) |
| M2 | Laravel skeleton, SSR and CI | 0 | M1 | — |
| M3 | Localization and module skeleton | 0 | M2 | — |
| M4 | Core data model | 0 | M3 | — |
| M5 | Knowledge write path and rule enforcement | 0 | M4 | — |
| M6 | ISCO-08 import | 0 | M4 | O9 (real file) |
| M6b | BSCO-2012 mapping and Bangla group titles | 0–1 | M6, M9 | O4 |
| M7 | Wave 1 field definitions and separate-occupation policy | 0 | M5 | O2 (settled inside M7) |
| M8 | Roles, signup consent, Filament admin shell | 1 | M5 | — |
| M9 | AI gateway and `ai_runs` logging | 1 | M4, M7 | O3 (real drivers only) |
| M10 | Occupation list expansion | 1 | M6, M8, M9 | O3 budget |
| M11 | Duplicate detection, merge and list review | 1 | M10 | — |
| M12 | Wave 1 generation with two-model cross-check | 1 | M7, M9, M11 | O3 budget |
| M13 | Public occupation page | 1 | M3, M12 | — |
| M14 | Index gate and sitemap | 1 | M13 | — |
| M15 | Bangla/English search | 1 | M11, M13 | — |
| M16 | Error reports, edit suggestions, profiles, credit | 1 | M8, M13 | — |
| M17 | Micro-task verification | 1 | M12, M16 | — |
| M18 | Moderation tools | 1 | M16, M17 | — |
| M19 | Talk pages | 1 | M16 | — |
| M20 | Guided filter and compare | 1 | M13, M17 | — |
| M21 | Public read-only API v1 + OpenAPI | 1 | M13, M15 | — |
| M22 | Production deploy, backups, monitoring | 1 | M2 (best after M13) | O1 (domain), H9 |
| M23 | Beta readiness | 1 | all above | — |

M6b waits only for the BSCO-2012 file (O4) and M9; it is not part of Gate 0.
M22 has no code dependencies beyond M2. It can run in parallel any time after M2. Doing it right
after M13 lets testers reach real pages early.

## Phase gates

- **Gate 0 → Phase 1:** data model and licenses final (M1–M7 merged, anchors and bands approved in M7);
  user-research summary written (H2). M6b is not required.
- **Gate 1 → Phase 2:** every occupation listed (M10–M11 done); most Wave 1 values at
  `community_supported` or better (M17 running, measured on the M23 dashboard); regular contributors
  from outside the core team.

## Maintainer track (non-code, Jayed)

| H | Task | When | Feeds |
|---|---|---|---|
| H1 | Buy the domain; set up a project email for the Code of Conduct; create a GitHub org if wanted (O1) | weeks 1–2 | M22, O1 |
| H2 | Interview 20–30 potential users and a few workers; written summary in `docs/research/` | weeks 1–4 | Gate 0, M15 benchmark queries, M20 questions |
| H3 | Publish 10 hand-written profiles on Facebook; note which get the most interest | weeks 3–4 | prioritisation |
| H4 | Approve the 20 separate-occupation examples drafted in M7 | during M7 | M7, M10 |
| H5 | Facebook group + first 1–2 university clubs | weeks 9–10 | M17 contributors |
| H6 | Beta test of the guided filter with 20 users | weeks 11–12 | M23 |
| H7 | Get the BSCO-2012 file (PDF/Excel, ideally with Bangla titles) from BBS | when possible | M6b |
| H8 | Open two AI provider accounts, keep the keys, set a monthly budget (O3) | before M9 | M9, M10, M12 |
| H9 | Open hosting accounts in the project's name: Hetzner, Cloudflare (+ R2), Amazon SES, Sentry | before M22 | M22 |

---

## Phase 0 — Foundation

### M1 — Repository bootstrap and governance

Depends on: — · Blocked by: — (the push to GitHub waits for O1; everything else is done locally)

Tasks
- `git init`, `main` branch. Push to `JayedHasan232/work-atlas` (A1; private at
  first, public since 2026-11-16).
- `LICENSE` (AGPL-3.0 full text; SPDX `AGPL-3.0-or-later`), `LICENSE-DATA.md` (CC BY-SA 4.0 for data and text, with the
  attribution format), `BRAND.md` stub (forks allowed; "official Work Atlas" claim not allowed).
- `README.md` (bn + en): what it is, status, how to contribute, licenses.
- `CONTRIBUTING.md`: DCO sign-off, small PRs, tests required, bn+en strings, no secrets, the rules,
  the docs sync rule (A3).
- `CODE_OF_CONDUCT.md` (Contributor Covenant 2.1, bn + en), `SECURITY.md`.
- `.github/`: PR template with a rules checklist, issue templates, DCO check workflow.
- `.editorconfig`, `.gitignore`, `.gitattributes`.
- First signed commit: `docs/`, `CLAUDE.md`, governance files.

Acceptance
- License files match A2. The repo is pushed (A1).
- A PR with an unsigned commit fails the DCO check; a signed one passes.

Tests
- DCO workflow, checked with a throwaway PR. No application tests yet.

### M2 — Laravel skeleton, SSR and CI

Depends on: M1 · Blocked by: —

Tasks
- New Laravel 13 app from the React starter kit (Inertia, React, TypeScript, Tailwind, shadcn/ui,
  Fortify) with Pest. Sanctum installed.
- `compose.yaml` for services only: PostgreSQL 18, Redis. `.env.example` without secrets.
- SSR on (SSR entry, `inertia:start-ssr`). Horizon installed (dashboard gated in M8).
- Tooling: Pint, Larastan (level 7, the starter kit's default), Oxlint + Oxfmt through `vp check`
  (the starter kit's Vite+ toolchain, D11), `tsc --noEmit`.
- Pest arch presets (`php`, `security`, `laravel`) + custom: no `dd`/`dump`/`ray`.
- E2E harness: Pest's browser plugin (A14), Playwright browsers installed in CI, one smoke test with
  an accessibility assertion.
- GitHub Actions: PHP job (Pint, Larastan, Pest on Postgres 18 + Redis services,
  migrate → rollback → migrate), JS job (lint, types, build, SSR build), SSR smoke job (boot the SSR
  server and the app, `curl /`, assert the HTML contains server-rendered content, not just an empty
  root `div`), e2e job.
- Fill the "Commands" section of `CLAUDE.md`; setup steps in `CONTRIBUTING.md`.

Acceptance
- A fresh clone runs by following the documented setup.
- CI is green on the PR.
- `curl /` returns server-rendered page content.

Tests
- `Feature/HomePageTest`, `Arch/BaselineTest`, `Browser/SmokeTest`, CI SSR smoke step.

### M3 — Localization and module skeleton

Depends on: M2 · Blocked by: —

Tasks
- Locales: `bn` default, `en` fallback. Locale resolution per A10 (`SetLocale` middleware),
  `<html lang>`, `hreflang` alternates, canonical URLs.
- Translation files in `lang/bn` and `lang/en`, shared to React through Inertia shared props (only
  the groups a page needs). Typed `t()` helper in React. Bangla-digit and date (Asia/Dhaka) formatters.
- Self-hosted, subset Bangla web font with `font-display: swap`.
- Public base layout: header, language switcher, footer with license notices (CC BY-SA, AGPL).
- Translate the starter kit's auth screens to Bangla (done for every kit screen: auth, settings,
  dashboard and the app shell).
- `app/Modules/*` skeleton with a short README per module; autoloading.
- Arch tests for module boundaries and thin controllers.
- CI translation parity check (`scripts/ci/check-translations`).

Acceptance
- Default SSR HTML is Bangla; the switcher changes UI strings and `<html lang>`.
- Removing one key from `lang/bn` makes CI fail.

Tests
- `Feature/Localization/LocaleResolutionTest`, `Feature/Localization/TranslationParityTest`,
  `Arch/ModuleBoundariesTest`, `Arch/ThinControllersTest`, `Browser/LanguageSwitchTest`.

### M4 — Core data model

Depends on: M3 · Blocked by: —

Tasks (migrations, models, enums, factories)
- `isco_groups` — code, level (1–4), parent, English title, Bangla title (nullable), BSCO code.
- `occupations` — slug, ISCO unit-group code, BSCO code, `publish_state` (candidate / listed /
  published / merged), `indexable`, `merged_into_id`.
- `occupation_names` — occupation, locale, name, normalized name, type (primary / alias / local /
  regional), region.
- `field_definitions` — key, version, wave, group, type (scale_1_5 / enum / multi_enum / boolean /
  band / text), options, anchors (bn + en), `sensitive`, `requires_source`.
- `field_values` — occupation × field × locale/region, `current_revision_id`, status, review priority.
- `revisions` — field value, old and new value (jsonb), author user **or** ai_run (check constraint:
  exactly one), comment, `reverts_revision_id`, `created_at` only.
- `revision_reviews` — revision, reviewer, decision, comment (append-only; see decision D1).
- `sources` + `revision_source` — URL, publisher, title, license, accessed date, archive URL.
- `ai_runs` — provider, model, prompt key + version, input hash, raw output, parsed output, status,
  tokens, cost, batch id, error, started/finished timestamps.
- Postgres trigger rejecting `UPDATE`/`DELETE` on `revisions` and `revision_reviews`, plus a model guard.
- `docs/architecture/data-model.md` with a Mermaid ER diagram.

Acceptance
- `migrate:fresh` → full `migrate:rollback` → `migrate` runs clean on Postgres 18.
- Raw SQL `UPDATE`/`DELETE` on `revisions` fails.

Tests
- `Feature/Knowledge/RevisionsAppendOnlyTest` (model and raw SQL), `Feature/Knowledge/RevisionAuthorConstraintTest`,
  factory smoke tests, CI migrate/rollback step.

### M5 — Knowledge write path and rule enforcement

Depends on: M4 · Blocked by: —

Tasks
- `RecordRevision` action, the only write path. It validates the value against the field definition
  (type, options, 1–5 range, `"unknown"`, confidence 0–1 for AI), applies the rules, sets status, moves
  the current pointer, attaches sources. One transaction with a row lock on the field value.
- `Actor` value object (user with role, or ai_run). Role-specific behaviour is stubbed until M8.
- Rules: `requires_source`; AI on a `verified` value → proposed revision (pointer unchanged, priority
  raised); AI initial status `ai_draft`, or `sourced` when a source is attached.
- `ChangeStatus` action with an explicit transition table. `ReviewRevision` (writes
  `revision_reviews`, accept moves the pointer). `RevertRevision` (new revision with the old value).
- Arch test: only `Knowledge\Actions` create `Revision` or change `FieldValue`.

Acceptance
- Every rule has a passing test. No code path outside the actions writes revisions.

Tests
- `RequiresSourceTest`, `AiCannotOverwriteVerifiedTest`, `InitialStatusTest`,
  `StatusTransitionsTest` (dataset of allowed and forbidden moves), `RevertCreatesNewRevisionTest`,
  `ConcurrentWriteTest`, `Arch/KnowledgeWritePathTest`.

### M6 — ISCO-08 import

Depends on: M4 · Blocked by: O9 for the real file (the importer is built on fixtures)

Tasks
- Get the ISCO-08 structure (ILO). Check reuse terms. Write `data/taxonomy/SOURCE.md` (URL, publisher,
  license/terms, access date, checksum). If terms are unclear, stop and ask.
- One-off script in `scripts/` to convert the source file into a committed `isco08.csv`.
- `atlas:taxonomy:import` command: idempotent upsert, creates `sources` rows. BSCO columns stay null
  until M6b.

Acceptance
- Counts: 10 major, 43 sub-major, 130 minor, 436 unit groups.
- Every unit group has a full parent chain.
- A second run changes nothing.

Tests
- `Feature/Taxonomy/IscoImportTest` (fixture subset), `IscoRealFileCountsTest`,
  `ImportIdempotentTest`, `TaxonomySourceRecordedTest`.

### M6b — BSCO-2012 mapping and Bangla group titles

Depends on: M6, M9 · Blocked by: O4 (the file, H7). Not part of Gate 0; runs whenever the file arrives.

Tasks
- Check the file's reuse terms and add it to `data/taxonomy/SOURCE.md`. If terms are unclear, stop and ask.
- Script in `scripts/` → `bsco2012.csv`, `bsco_isco_map.csv`. The import fills `isco_groups.bsco_code`
  and the Bangla titles BSCO provides.
- Groups still without a Bangla title: an `AiGateway` job drafts one, stored with
  `title_bn_status = ai_draft` (new migration). It is shown publicly only after a human approves it in
  Filament (A9).

Acceptance
- Mapping coverage report printed; unmapped groups listed. No AI-drafted Bangla title is public
  without approval.

Tests
- `Feature/Taxonomy/BscoMappingIntegrityTest`, `BanglaGroupTitleNeedsApprovalTest`.

### M7 — Wave 1 field definitions and the separate-occupation policy

Depends on: M5 · Blocked by: O2 (anchors and bands are approved inside this milestone)

Tasks
- `data/fields/wave1.v1.yaml`: keys, types, options (bn + en labels), anchors (bn + en), flags,
  help text. The 10 fields in Appendix A (A5).
- `atlas:fields:sync`: idempotent. Changing the anchors or options of a definition that already has
  values requires a new version (enforced).
- `FieldSchemaBuilder`: JSON schema for a wave's AI output (value or `"unknown"`, confidence, note).
  Generated schemas committed as `data/schemas/wave1/*.v1.json`.
- `docs/policies/separate-occupation.md`: the rule (A4) plus 20 worked examples, drafted by the lead
  engineer and approved by Jayed (H4).
- `docs/architecture/fields.md`: field table generated from the YAML.

Acceptance
- DB definitions match the YAML. Generated schema accepts a valid sample and rejects out-of-range
  values and missing confidence. Anchors, bands and the policy doc approved by the maintainer.

Tests
- `Feature/Knowledge/FieldDefinitionsSyncTest`, `AnchorsCompleteTest` (every 1–5 scale has five
  anchors in bn and en), `DefinitionVersioningTest`, `Unit/Knowledge/FieldSchemaBuilderTest`.

**→ Gate 0 review.**

---

## Phase 1 — All occupations and public beta

### M8 — Roles, signup consent, Filament admin shell

Depends on: M5 · Blocked by: — (mail uses the `log` driver until M22 sets up Amazon SES; Jayed is the
only maintainer and reviewer, A13)

Tasks
- `users.role` (contributor default, trusted, reviewer, expert, maintainer) and trust counters.
  Policies. Wire `Actor` into `RecordRevision` (contributor → proposed; trusted on descriptive
  fields → applied, patrolled later; sensitive fields always reviewed first).
- Signup: required consent that contributions are published under CC BY-SA 4.0, stored with
  terms version and timestamp. Contribution-terms page (bn + en).
- Filament panel at `/admin`, maintainers only. Read-only resources: occupations, sources, AI runs,
  revisions with diff. Horizon dashboard gated to maintainers.
- `atlas:user:promote` command.

Acceptance
- Non-maintainers get 403 on `/admin` and `/horizon`. Signup without consent fails.

Tests
- `Feature/Community/RolePolicyTest` (role × action matrix), `AdminAccessTest`,
  `SignupConsentTest`, `Browser/RegisterTest`.

### M9 — AI gateway and `ai_runs` logging

Depends on: M4, M7 · Blocked by: O3 for the real drivers only. The gateway, `ai_runs`, budget cap and
fake driver are built first.

Tasks
- `AiGateway` interface: structured completion, batch submit/poll (Horizon jobs). Drivers for the two
  providers chosen in O3 plus a fake driver for tests.
- JSON schema validation of every output. Invalid output → run marked failed, nothing written.
- `ai_runs` lifecycle: row created before the call, completed after (raw output, tokens, cost from a
  config pricing table, error).
- Retry with backoff. Budget cap per batch: refuse to submit above the cap.
- `PromptRegistry` for `data/prompts/<wave>/<name>.vN.md` (front matter: model params, schema ref).
- `scripts/ci/check-immutable`: fails if an existing prompt or schema file is modified or deleted.
- Filament view of AI runs (filters by status, model, prompt version).

Acceptance
- Every gateway call leaves exactly one `ai_runs` row, failures included.
- No provider SDK or provider HTTP call exists outside the gateway. Budget cap enforced.

Tests
- `Feature/AiPipeline/AiRunLoggedTest` (success, provider error, schema-invalid output),
  `BudgetCapTest`, `PromptRegistryTest`, `Arch/AiGatewayOnlyTest`, immutable-check fixture test.

### M10 — Occupation list expansion

Depends on: M6, M8, M9 · Blocked by: O3 (budget go-ahead for the paid run)

Status: the code is done (prompt v1, schema, action, job, command, tests; D44, D45). The pilot on 5
unit groups and the full run wait for O3 (providers, prices and a budget above 0) and for O7, which
must be decided before the first paid run.

Tasks
- Prompt `data/prompts/taxonomy/expand-unit-group.v1.md` + schema: occupations with bn/en names,
  local and regional aliases, informal/self-employed/seasonal flags, short reason for "separate vs
  specialization", confidence.
- `ExpandUnitGroup` job: candidate occupations (`publish_state = candidate`) and names written as
  `ai_draft` through `RecordRevision`, linked to the ai_run. Same input hash + same prompt version →
  skipped.
- `atlas:taxonomy:expand --groups= --dry-run` with a cost estimate.
- Pilot on 5 unit groups → maintainer reviews → prompt v1 approved or v2 written → full batch run on
  all 436 unit groups.

Acceptance
- Pilot reviewed and prompt version approved. Full run complete; total cost recorded.
- Every candidate links to its ai_run. Invalid outputs wrote nothing.

Tests
- `Feature/Taxonomy/ExpandUnitGroupJobTest` (fake driver), `ExpandIdempotentTest`,
  `InvalidOutputWritesNothingTest`, `ExpandDryRunTest`.

### M11 — Duplicate detection, merge and list review

Depends on: M10 · Blocked by: —

Status: the code is done (migration, detector, review and merge actions, Filament queues, redirect,
tests; D46–D49). Reviewing the real list waits for the M10 run (O3, O7). H4's separate-occupation
policy is still a draft; the rule shown in the review screens is its current text.

Tasks
- `pg_trgm`; `duplicate_candidates` table. Detector: trigram similarity on normalized names plus
  alias overlap, within the same ISCO minor group. No embeddings in Phase 1 (A8).
- Filament queues: candidate review (approve → `listed`, reject, edit names, mark as specialization →
  becomes an alias of the parent) and duplicate pairs (merge / keep separate). The separate-occupation
  rule is shown inline.
- `MergeOccupations` action: names become aliases via revisions, `merged_into_id` set, old slug
  301-redirects. "Keep separate" decisions are remembered.

Acceptance
- The maintainer can review the whole list in Filament. Merges leave no orphan names. Merged slugs
  redirect.

Tests
- `DuplicateDetectorTest`, `MergeOccupationsTest`, `KeptSeparateNotReflaggedTest`,
  `ApproveCandidateTest`, `MergedSlugRedirectTest`.

### M12 — Wave 1 generation with two-model cross-check

Depends on: M7, M9, M11 · Blocked by: O3 (budget go-ahead)

Status: the code is done (prompt `wave1/fields` v1, agreement rules, second-opinion revisions, the
cross-check on the slot, the review queue, `atlas:wave1:generate`, the Filament report with CSV;
D50, D51). The run itself waits for: O2 (anchors approved), O3 (two providers and a budget), O7 and
O10 (before the first paid run), the M10 list run and its review (M11), and Jayed's approval of
prompt v1 after a pilot. Each occupation is one queued job with two calls (as in M10, D45); the
provider batch API is left for the full run.

Tasks
- Wave 1 prompts with anchors and full context (ISCO code, bn/en names, aliases, Bangladesh).
- Batch runs on model A and model B. A's value is the current `ai_draft`; B's is stored as a proposed
  revision. Cross-check result saved on the field value.
- `AgreementCalculator` per field type (exact for enum/boolean, |a − b| ≤ 1 for scales, overlap for
  multi-enum and bands). Disagreement → top review priority.
- Disagreement report in Filament with CSV export.

Acceptance
- Every listed occupation has Wave 1 values (or `"unknown"`). Verified values untouched.
- Maintainer spot-checks 20 occupations.

Tests
- `Unit/AiPipeline/AgreementCalculatorTest` (dataset per type), `Feature/AiPipeline/Wave1JobTest`,
  `Wave1LeavesVerifiedUntouchedTest`, `ReviewPriorityOrderTest`.

### M13 — Public occupation page

Depends on: M3, M12 · Blocked by: —

Tasks
- Route per A10. `Publishing\OccupationPresenter` — the only public read path, shared later by the API.
- Sections: names and aliases, ISCO group, Wave 1 fields with a status badge each, sources list,
  "not yet verified" state for unverified sensitive fields, "not medical advice" on any health group,
  report-error entry point (wired in M16), attribution footer.
- Browse by ISCO group; occupation index. Mobile-first.

Acceptance
- Page renders through SSR in bn and en. Each field shows its status. Sensitive values are absent
  from Inertia props and HTML unless `verified`.

Tests
- `Feature/Publishing/OccupationPageTest`, `Feature/Publishing/SensitiveFieldGateTest`
  (props + HTML), `BrowseByGroupTest`, `Browser/OccupationPageTest`.

Status: done (`OccupationPresenter` and `BrowsePresenter`, `/occupations`, `/occupations/groups/{code}`
and `/occupations/{slug}` in bn and en, SSR smoke on an occupation page; D52–D54). The pages show
placeholder text until the M12 run (O2, O3); the report-error button is a disabled placeholder until
M16; contributor names on the page wait for the credit work.

### M14 — Index gate and sitemap

Depends on: M13 · Blocked by: —

Tasks
- `IndexGate` with the A11a rule in `config/atlas.php` (Phase 1: off, every page `noindex`; the
  Phase 2 threshold is configured and tested but not active). A job recomputes `indexable` after status
  changes.
- `<meta name="robots" content="noindex">` and `X-Robots-Tag: noindex` while not indexable.
- `sitemap.xml` with only indexable pages and static pages; `robots.txt`; hreflang; canonical.

Acceptance
- Default: everything `noindex`. Moving field statuses past the threshold flips `indexable` and the
  sitemap; moving back reverts it.

Tests
- `Feature/Publishing/IndexGateTest` (threshold dataset), `NoindexHeaderTest`, `SitemapTest`.

Status: done (`IndexGate`, `EvaluateIndexGate`, `RecomputeIndexableJob`, `atlas:index-gate:recompute`,
`RobotsTag` on every web page, `/sitemap.xml`, `/robots.txt`; D55–D57). The gate is off
(`ATLAS_INDEX_GATE=false`) until Phase 2; the Phase 2 threshold is configured and tested.

### M15 — Bangla/English search

Depends on: M11, M13 · Blocked by: — (benchmark queries come from H2)

Tasks
- Normalization: Unicode NFC, strip ZWJ/ZWNJ, unify common Bangla spelling variants, Bangla↔ASCII
  digits, lowercase English.
- PostgreSQL full-text (`simple` config) + `pg_trgm` indexes over all names and aliases. Ranking:
  exact > prefix > trigram.
- Search page and header search box.
- Benchmark: `tests/fixtures/search-benchmark.csv` (≥ 50 real queries with the expected occupation);
  result written to `docs/decisions/search-benchmark.md`.

Acceptance
- Recall@3 on the benchmark meets the agreed target (proposed 90%). Typical query under 100 ms.

Tests
- `Feature/Taxonomy/SearchAliasTest` (e.g. "নলমিস্ত্রি" finds the plumbing occupation),
  `Unit/Taxonomy/BanglaNormalizerTest`, `SearchBenchmarkTest`, `Browser/SearchTest`.

Status: the code is done (`NormalizeForSearch`, `occupation_names.search_key` with full-text and
trigram indexes, `SearchOccupations`, `/search` and the header search box, `atlas:search:benchmark`;
D58–D60). Open: the benchmark itself. Its ≥ 50 real queries come from the H2 interviews, and it can
only run once the real occupation list exists (M10 run, O3); the Recall@3 target (proposed 90%) is
the maintainer's call. Until then the runner is tested on a synthetic sample.

### M16 — Error reports, edit suggestions, contributor profiles and credit

Depends on: M8, M13 · Blocked by: —

Tasks
- Error report without login, anchored to a field; rate-limited; honeypot; Turnstile behind
  a config flag, off by default (A15).
- Edit suggestion: input per field type, sources, comment, conflict-of-interest declaration →
  `RecordRevision` as proposed. Diff preview.
- Contributor profile page. Credit list on each occupation page, computed from revisions; anonymous
  option in settings.
- New-account limits (edits per day, links need review).

Acceptance
- An anonymous visitor can report an error. A contributor can suggest a change with a source.
  `requires_source` holds through the UI. Credit appears after acceptance.

Tests
- `Feature/Community/ErrorReportTest` (anonymous, rate limit), `EditSuggestionTest`, `CreditsTest`,
  `AnonymousContributorTest`, `NewAccountLimitTest`, `Browser/SuggestEditTest`.

### M17 — Micro-task verification

Depends on: M12, M16 · Blocked by: —

Tasks
- `verification_tasks` and answers. Generator from Wave 1 values, ordered by disagreement and page views.
- Mobile UI: one question, yes/no or pick-one, skip.
- One answer per user per task. Consensus job (A11b: 3 agreeing
  answers, no dissent) → `ChangeStatus` to `community_supported` or `disputed`.
- Trust counters; promotion to trusted at 50 accepted contributions and 30 days (A11c, in config).

Acceptance
- A task is answerable on a phone in seconds. Consensus moves status; dissent sends the value to the
  dispute queue.

Tests
- `Feature/Community/ConsensusTest` (dataset), `OneAnswerPerUserTest`, `DissentDisputesTest`,
  `TrustPromotionTest`, `Browser/MicroTaskMobileTest`.

### M18 — Moderation tools

Depends on: M16, M17 · Blocked by: —

Tasks
- Patrol queue of recent edits (mark patrolled), diff view, one-click revert via `RevertRevision`.
- Report and spam queue. Promotional-edit heuristics (link domains, repeated organisation names,
  COI declarations). Rate limits per trust level. Block user.
- Reviewer access to the sensitive-field review queue.

Acceptance
- Any edit can be found, diffed and reverted in three clicks or fewer. Flagged edits land in the queue.

Tests
- `Feature/Community/RevertFromPatrolTest`, `PromoHeuristicsTest`, `RateLimitByTrustTest`,
  `ReviewerQueueAccessTest`.

### M19 — Talk pages

Depends on: M16 · Blocked by: —

Tasks
- `discussions` per occupation: threads, replies, link to a field; same rate limits and moderation
  hooks as edits; bn/en UI.

Acceptance
- Contributors can open and reply to threads; moderators can hide posts; hidden posts stay in history.

Tests
- `Feature/Community/DiscussionTest`, `DiscussionModerationTest`.

### M20 — Guided filter and compare

Depends on: M13, M17 · Blocked by: —

Tasks
- 8–10 questions (bn + en) mapped to Wave 1 fields: hours available and day/night, need income soon,
  education, physical capacity, indoor/outdoor, willingness to learn for how long, seasonal OK, and
  `part_time_possible` and `available_in` (A5).
- `MatchOccupations` query returning matches with reasons ("matches because…"), limits, and the data
  status behind each reason.
- Answers stay in the browser (URL/state). Only an anonymous completion event goes to analytics.
- Compare 2–3 occupations side by side.

Acceptance
- Finishable on a phone in under two minutes. Every result shows reasons and limits. No forbidden
  wording anywhere.

Tests
- `Feature/Discovery/MatchOccupationsTest` (dataset), `ExplanationTest`, `CompareTest`,
  `NoFilterAnswersStoredTest`, `Arch/ForbiddenWordingTest` (scans `lang/` and `data/prompts/` for
  "perfect job", "AI-proof", "future-proof" and Bangla equivalents), `Browser/GuidedFilterMobileTest`.

### M21 — Public read-only API v1 + OpenAPI

Depends on: M13, M15 · Blocked by: —

Tasks
- `/api/v1/occupations` (filter by group, paginated), `/occupations/{slug}`, `/fields`, `/search`,
  built on the `Publishing` presenters.
- Rate limiting, CORS, license and attribution in response meta.
- OpenAPI spec (generated, e.g. Scramble) and a docs page.

Acceptance
- The API never exposes unverified sensitive values, user personal data, submissions or raw AI output.
  The spec builds and covers every route.

Tests
- `Feature/Publishing/ApiSensitiveGateTest`, `ApiNoPersonalDataTest`, `ApiRateLimitTest`,
  `OpenApiSpecTest`.

### M22 — Production deploy, backups and monitoring

Depends on: M2 (best after M13) · Blocked by: O1 (domain), H9 (accounts). Stack per A12.

Tasks
- Provision the Hetzner Singapore VPS with the idempotent script in `ops/` (`docs/ops/server.md`):
  PHP-FPM, Nginx, PostgreSQL 18, Redis; SSR server, Horizon and the scheduler under a process
  supervisor. SSH keys only; password and root login off.
- Cloudflare: DNS, TLS full (strict), cache rules for static assets only.
- R2 for files. Nightly encrypted `pg_dump` to R2 with retention.
- `docs/ops/restore.md` runbook and the first restore test, logged.
- Sentry (personal-data scrubbing on), Umami (own database), external uptime check on `/up`.
- Amazon SES for auth mail; SPF, DKIM and DMARC set on the domain.
- Deployer pipeline from `main` through GitHub Actions (migrations included).
- Staging: small second VPS, HTTP basic auth, `noindex`, seeded fake data only.

Acceptance
- Deploy from `main` is one step. The restore test is done and logged. A test exception reaches the
  error monitor.

Tests
- `Feature/HealthCheckTest`, post-deploy e2e smoke against staging, restore-test log entry.

### M23 — Beta readiness

Depends on: all of the above · Blocked by: —

Tasks
- Policy pages (bn + en): Code of Conduct, editorial policy, privacy policy, contribution terms,
  not-medical-advice, neutrality and funding transparency.
- Accessibility pass (axe in e2e), mobile performance pass, full e2e suite.
- Security review of the whole app.
- Gate 1 dashboard in Filament: listed occupations, % Wave 1 values at `community_supported`+,
  monthly active contributors.
- Walk through the "Data and code" items of the success checklist in `docs/02-implementation.md`.
- Beta launch with the H6 user test.

Acceptance
- Every "Data and code" checklist item is true and proved by a test. Dashboard numbers match DB counts.

Tests
- `Browser/AccessibilityTest`, full suite, `Feature/Admin/Gate1DashboardTest`.

---

## Engineering decisions in this plan (reversible; tell me if you disagree)

- **D1** Review decisions go in an append-only `revision_reviews` table instead of a column on
  `revisions`, so revisions never change after insert.
- **D2** Append-only is enforced twice: model guard and a Postgres trigger.
- **D3** One write path (`RecordRevision`), one read path (`Publishing` presenters), one AI path
  (`AiGateway`), each guarded by arch tests.
- **D4** ISCO/BSCO hierarchy lives in its own `isco_groups` table (not in the original table list).
- **D5** Merged occupations keep a 301 redirect from the old slug.
- **D6** Search uses PostgreSQL's `simple` text config + `pg_trgm` (no Bangla stemmer exists in
  Postgres), with a normalizer for Bangla spelling variants.
- **D7** Time-to-learn and time-to-first-income are bands, not numbers, to avoid false precision.
- **D8** Prompt and schema immutability is enforced in CI, not by convention.
- **D9** Guided-filter answers are never stored on the server.
- **D10** Local dev: Docker Compose for Postgres and Redis only; PHP and Node run locally. Native
  PostgreSQL and Redis work too (CONTRIBUTING.md).
- **D11** Front-end lint and format use the starter kit's Vite+ toolchain (Oxlint + Oxfmt via
  `vp check`) instead of ESLint + Prettier, so we don't fight the kit's defaults. Markdown, license
  texts and `data/` are excluded from formatting.
- **D12** Tests run on PostgreSQL, not sqlite: later milestones rely on triggers and `pg_trgm`.
- **D13** Locale URLs (A10): public pages are registered twice, Bangla at the root (`home`) and
  English under `/en` (`en.home`); the locale lives in the route action. Auth, settings and dashboard
  have one URL each and take the locale from the session (last public page or the switcher), else
  `bn`. Only public pages carry `hreflang` alternates, `x-default` (Bangla) and a canonical URL.
- **D14** Translations are PHP groups in `lang/{bn,en}/`; a page receives only the groups listed for
  its component in `config/atlas.php`. Vendor sentences (Fortify, passkeys, mail) live in
  `lang/{bn,en}.json`. The React `TranslationKey` type is generated from `lang/en` and gitignored.
- **D15** `scripts/ci/check-translations` fails CI on a key missing in either language, an empty
  value, or different `:placeholder` names.
- **D16** Font: Noto Sans Bengali (OFL-1.1), self-hosted from `@fontsource`, Bengali and Latin subsets,
  weights 400/500/600, `font-display: swap`. It replaces the kit's Bunny Fonts CDN, a third party.
- **D17** The default locale `bn` is set in `config/app.php`, not in `.env`: it is a project rule,
  not a per-environment setting.
- **D18** Module boundaries: a module's `Models`, `Jobs`, `Policies`, `Http` and `Filament` are
  private; other modules and `app/*` outside `Modules` may use its `Actions`, `Queries`, `Data`,
  `Enums` and `Events`. Controllers use no DB facade, query builder or models
  (`Arch/ModuleBoundariesTest`, `Arch/ThinControllersTest`). The kit's settings controllers still call
  `$request->user()` until they move to `Community` actions in M8.
- **D19** Statuses, types and decisions are `varchar` columns with check constraints, not Postgres
  enum types, mirrored by PHP enums. A new value is a small migration that replaces the constraint.
  Migrations spell the values out instead of reading the PHP enums, so an old migration replays the
  same way later.
- **D20** Across modules, rows are linked by a foreign key and the model keeps only the id
  (`FieldValue::$occupation_id`, `Revision::$ai_run_id`), with no Eloquent relation, because a module's
  models are private (D18).
- **D21** `revision_source` is append-only like `revisions` and `revision_reviews`: a revision's sources
  are part of what it claims. The trigger also blocks `TRUNCATE`. Its function is created with
  `OR REPLACE`, because `migrate:fresh` drops tables but not functions; CI runs `migrate:fresh` after
  the rollback cycle to catch that kind of leftover.
- **D22** `field_values.locale` null means the value is the same in every language (a 1–5 scale);
  `region` null means all of Bangladesh. Regions are short codes for now; a regions table comes with
  `Observations` (Phase 2).
- **D23** The Laravel arch preset assumes a flat app (enums in `App\Enums`, models in `App\Models`), so it
  skips `App\Modules`; `Arch/ModuleLayoutTest` applies the same placement rules inside each module.
- **D24** (M5) A revision is marked `proposed` when it is written and never changes; a pending proposal is
  a proposed revision without a review. The current revision and slot status a change replaced are
  stored with the change (`replaces_revision_id`, `old_status`: on the revision for a direct write, on
  the accepting review for a proposal), because a revert must restore the old value's sources and
  status, which `old_value` alone doesn't carry.
- **D25** (M5, replaced by D36 in M8) Until roles exist (M8), every person's write is a proposal and
  every person may change a status, review and revert. Accepting a proposal makes it `verified` ("a reviewer approved it"). AI
  runs never change a status, review or revert. `review_priority`: 0 nothing waits, 10 a proposal
  waits; it drops back to 0 when the last pending proposal is reviewed.
- **D26** (M5) `null` and `"unknown"` are stored as null. `requires_source` applies only to a known value
  (rule 2: no source, no value). An unknown value stays `ai_draft` even with a source. Text needs a
  supported locale; other types have none.
- **D27** (M5) Status transition table in `FieldStatus::allowedTargets()` and
  `docs/architecture/data-model.md`: up the ladder or to `disputed`; `ai_draft` is never a target.
- **D28** (M5) Every Knowledge action locks the slot row first. A slot is created with
  `ON CONFLICT DO NOTHING`, so two first writes don't collide. `ConcurrentWriteTest` holds the lock
  from a second database connection and checks that the write waits at the locking read.
- **D29** (M6) The ISCO-08 file is a CSV with the header `code,title_en`. Level and parent come from the
  code, so they can't be written wrong. Where the file comes from sits next to it in
  `isco08.source.json` (url, publisher, title, license, accessed_on, archive_url); `SOURCE.md` is the
  human-readable note with the checksum. The importer checks the whole file before writing, then writes
  in one transaction. It never deletes a group (occupations may point to it) and never touches
  `title_bn` or `bsco_code` (M6b).
- **D30** (M6) `Knowledge\Actions\RecordSource` is how other modules record a source. A source with the
  same url, access date and other fields is recorded once, so a re-import adds no row; a new download
  (a new access date) is a new row. URLs must be http(s); the access date can't be in the future
  (Asia/Dhaka).
- **D31** (M7) Field definitions live in `data/fields/wave<N>.v<M>.yaml` and reach the database only
  through `atlas:fields:sync`. A definition's meaning is its type, options, anchors and wave; once it
  has values these can't change in place (the sync refuses and asks for a new version). Labels, help
  text, group and flags can be reworded in place. Definitions no file lists are kept, never deleted.
- **D32** (M7) The words people see for a field (label, help, option labels) are content rows with a
  locale, stored on `field_definitions` (`label`, `help`, `option_labels` jsonb), not translation keys:
  they are data that grows with every wave, like the anchors.
- **D33** (M7) A wave's AI output schema is generated, never hand-written: `atlas:fields:generate`
  writes `data/schemas/wave<N>/fields.v<M>.json` (draft 2020-12; M = the newest wave file) and
  `docs/architecture/fields.md`. It never overwrites an existing schema file. `GeneratedFieldFilesTest`
  fails when a committed file is out of date.
- **D34** (M7) Libraries `symfony/yaml` (MIT) for the field files and `opis/json-schema` (Apache-2.0)
  to validate AI output against the schemas (M9, M12).
- **D35** (M7) A band includes its lower bound ("1–3 months" is from 1 month up to, not including, 3).
  `time_to_first_income` counts from the day a person starts learning or preparing; it is a separate
  field from `time_to_learn`, because in much work income starts while learning.
- **D36** (M8) Roles are `Community\Enums\Role` (contributor, trusted, reviewer, expert, maintainer),
  stored as `users.role` with a check constraint; the enum's methods are the role × action matrix the
  Knowledge actions ask. A contributor's write is a proposal; a trusted person's write on a
  non-sensitive field is applied (patrolled later, M16); a write on a sensitive field is always a
  proposal, whoever writes it. Anyone can mark a value `disputed`. `sourced` and
  `community_supported` need trusted (reviewer on sensitive fields); `verified` and reviews need a
  reviewer; nobody reviews their own proposal except the maintainer (A13). Reverting a verified or
  sensitive value needs a reviewer. Accepted and rejected proposals are counted on the author
  (trust counters). `role` is never mass-assigned; only `ChangeRole` (`atlas:user:promote`) sets it.
  Expert sign-off for health and legal fields is not modelled yet: it arrives with waves 5 and 3.
  Replaces D25.
- **D37** (M8) Admin resources live in `app/Modules/<Module>/Filament/Resources` and extend
  `App\Support\Filament\ReadOnlyResource` (list and view only) until a milestone adds an action that
  calls the module's Actions. `Arch/KnowledgeWritePathTest` allows `Knowledge\Filament` to use the
  revision models, because Filament needs the model class to show revisions;
  `Feature/Admin/ReadOnlyResourcesTest` checks that no resource can create, edit or delete.
- **D38** (M8) The panel provider is `App\Providers\AdminPanelServiceProvider` (the Laravel arch preset
  wants the `ServiceProvider` suffix). It has no login page of its own: a guest goes to the site's
  Fortify login, so there is one login and one session. The panel runs `SetLocale`, so it follows the
  language the person chose. Horizon's gate is the same check (`Role::canUseAdmin`).
- **D39** (M8) Signup stores consent in `contribution_consents` (user, terms version, time), written
  in the same transaction as the account. The version is `atlas.contribution_terms_version`
  (`2026-11-draft` until O12 approves the text); a new text gets a new version, and a person agreeing
  again to the same version keeps the first time.
- **D40** (M9) `AiPipeline\AiGateway` is a final class, not an interface; providers sit behind
  `Drivers\AiDriver` (`complete`, `submitBatch`, `pollBatch`), chosen per provider in
  `atlas.ai.providers.<name>.driver`. Only `FakeDriver` exists until O3; a real driver is a new class in
  `Drivers` plus a config entry, and only `Drivers` may make HTTP calls (`Arch/AiGatewayOnlyTest`).
  The run row is created before the call and finished after it; a non-provider exception still marks
  the run failed and is rethrown. Retries (`atlas.ai.retry.backoff_ms`) happen inside one run, so a
  run counts calls, not attempts, and `RunAiCompletion` runs once. The gateway writes only `ai_runs`:
  callers turn a succeeded result into revisions through `Knowledge\Actions\RecordRevision`.
- **D41** (M9) The budget cap is `AI_MAX_BATCH_USD` (`atlas.ai.max_batch_usd`), **0 by default**, so
  nothing paid can run until the maintainer sets it. Before anything is sent, the gateway estimates the
  cost (input tokens from the prompt length at `chars_per_token`, kept low so the guess runs high;
  output tokens at the prompt's maximum) and refuses above the cap, or when a model has no price in
  `atlas.ai.pricing`. A refused call makes no run, because nothing reached a provider. A single call
  counts as a batch of one. `estimate()` is the dry run that every paid run shows first. The pricing
  table holds only the fake provider's zero price; real prices are added with the O3 drivers, from the
  provider's published price page with its URL and date in a comment (rule 2).
- **D42** (M9) Prompts are `data/prompts/<wave>/<name>.v<N>.md` with YAML front matter holding exactly
  `version`, `schema` (a path under `data/schemas`), `max_output_tokens`, `temperature` and
  `variables`; the body uses `{{ name }}` placeholders, and the registry refuses a file whose
  placeholders and `variables` differ. The model is not in the file: the same prompt runs on both
  cross-check models. `scripts/ci/check-immutable` fails a pull request that changes, renames or
  deletes a committed file under `data/prompts` or `data/schemas` (a new `vN` is always fine); it runs
  from the base branch like the DCO check.
- **D43** (M9) An output is accepted only when it is a JSON object that matches the prompt's schema
  (opis/json-schema). Otherwise the run is `failed` with the reason, the raw output is kept for
  review, and nothing is parsed.
- **D44** (M10, Jayed's choice 2026-11-16) Names are a Knowledge field: `names`, wave 0 (taxonomy),
  type `name_list`, one value per language. A value is a list of `{name, type}` (`primary`, `alias`,
  `local`, `regional` + a division key), exactly one primary, no name twice. So AI names enter as
  `ai_draft` with full revisions, sources and statuses like every other value. `occupation_names` is
  a search copy of the current value: `RecordRevision`, an accepted review and a revert raise
  `Knowledge\Events\CurrentValueChanged`, and `Taxonomy\Actions\SyncOccupationNames` rewrites that
  occupation's rows for the language in the same transaction. A pending proposal is not copied.
- **D45** (M10) A candidate links to the run that proposed it (`occupations.ai_run_id`, `RESTRICT`).
  A rerun is recognised by the gateway's input hash (`AiGateway::previousSuccess`): an earlier
  succeeded run is reused with no new call, and skipped when its occupations exist or it proposed
  none; `--force` makes a new call. So the context lists only `listed` and `published` occupations,
  never candidates, or each run would change the next input. Specializations become aliases; work
  arrangements, the reason for a separate occupation and the note stay in the run's parsed output
  for the list review (M11). Regional names use the 8 division keys. The whole command is checked
  against the cap before any job is queued (`AiGateway::checkBudget`). The provider batch API is
  left for the full run (M10 pilot first, O3).
- **D46** (M11) A reviewed occupation records who reviewed it, when and why (`reviewed_by_id`,
  `reviewed_at`, `review_note`). A new state `rejected` (never indexable, a reason required) keeps a
  rejected candidate and its run instead of deleting them; a rejected candidate can still be approved
  later. Approving needs a primary name in Bangla and English. Only reviewers and above review the list.
- **D47** (M11) Possible duplicates: `pg_trgm` similarity on `occupation_names.normalized_name` (same
  language, threshold `atlas.taxonomy.duplicate_threshold`, 0.5), or the same name (an alias overlap),
  only within the same ISCO-08 minor group (first 3 digits). A pair is stored once (lower id first)
  with its closest names; a decided pair, merged or kept separate, is never flagged again. Run with
  `atlas:taxonomy:duplicates`. No embeddings in Phase 1 (A8).
- **D48** (M11) A merge moves the old occupation's names to the target as aliases through a
  `names` revision by the reviewer (a primary becomes an alias, a name the target has is dropped), sets
  `merged_into_id`, re-points earlier merges into the old one, removes the old one's search copy and
  closes its open pairs, in one transaction. "Mark as specialization" is a merge into the parent with
  a note. A merge needing more than 50 names in one language is refused (the `names` limit).
- **D49** (M11) `/occupations/{slug}` (and `/en/occupations/{slug}`) exists now only to 301-redirect
  a merged slug to the canonical one, following the merge chain (at most 10 steps); everything else is
  404 until the page itself (M13).
- **D50** (M12) Cross-check. Model A's answer is written like any AI value (current `ai_draft`, or a
  proposal on a verified value). Model B's answer is a **second opinion**: an AI revision that is
  always a proposal and does not raise the queue on its own (`RevisionInput::$secondOpinion`). The
  result is saved on the slot (`field_values.cross_check`: agreed or disagreed, with the time). A
  disagreement raises `review_priority` to 20, above a pending proposal (10); an agreement leaves it.
  Agreement: 1–5 scales at most 1 apart; bands the same or neighbours; option lists share at least
  half of all options given (|A ∩ B| / |A ∪ B| ≥ 0.5); everything else exactly equal; "unknown" agrees
  only with "unknown". Both answers are written in one transaction, or nothing; a failed call writes
  nothing, and a rerun reuses the run that succeeded.
- **D51** (M12) The Wave 1 prompt holds no anchor or option: they are rendered from the field
  definitions into `{{ fields }}`, so the prompt file stays fixed while the input hash still changes
  with a new field version. The fields must be exactly the ones the prompt's schema asks for; a new
  field or version needs a new prompt version. The two models come from `atlas.ai.tasks.wave1.a` and
  `.b` and must differ. Wave 1 covers `listed` and `published` occupations only.
- **D52** (M13) A `listed` or `published` occupation has a public page; a candidate, a rejected
  occupation or an unknown slug is 404, a merged slug 301 (D49). The page shows the fields of the
  waves in `atlas.publishing.page_waves` (0 names, 1 now), national values only (regional values come
  with Wave 3). For each key it uses the newest definition version that has a value, so a new version
  does not blank the page before the next run. A field without a value says "not filled in yet" and
  has no status badge; `"unknown"` says "not known yet". Text values carry their language (`lang`).
- **D53** (M13) The sensitive-field gate is in `Publishing\Presenters\OccupationPresenter`, the only
  public read path (the API reuses it). A sensitive value that is not `verified`, `disputed`
  included, is sent as `withheld` with its status and without its value or sources, so neither the
  props nor the HTML hold it. Field groups in `atlas.publishing.medical_groups` carry the "not medical
  advice" note.
- **D54** (M13) Until the index gate (M14), every occupation route is noindex through
  `Publishing\Http\Middleware\NoIndex`: an `X-Robots-Tag` header and a `robots` prop rendered as a
  meta tag by `LocaleHead`. Browse is `/occupations` (major groups and an index sorted by primary name
  in the reader's language, `atlas.publishing.per_page` per page) and `/occupations/groups/{code}`
  (child groups with counts, or a unit group's occupations). CI's SSR smoke renders a placeholder
  occupation from `SsrSmokeSeeder`, which has names only and no field values.
- **D55** (M14) The A11a rule is `config('atlas.index_gate')`: an occupation page passes when at
  least `min_share` (0.8) of the fields of `waves` (1 and 2) have reached `min_status`
  (`community_supported`), and every field in `required` has reached its own status (`description`:
  `verified`). A field reaches a status when it has a value and every current value (each language)
  is at least that far up the ladder; `disputed` and "no value yet" never reach. `enabled` is false in
  Phase 1, so nothing passes. The thresholds are a first proposal, to be checked with real Wave 1 and
  2 data before the gate is turned on.
- **D56** (M14) `occupations.indexable` stores the answer. A changed value (`CurrentValueChanged`) or
  status (`FieldStatusChanged`, new) queues `RecomputeIndexableJob` after the commit, once per
  occupation at a time, only while the gate is on; `atlas:index-gate:recompute` catches up after the
  gate is turned on or its thresholds change. Only a listed or published occupation can be indexable.
  `Publishing\Http\Middleware\RobotsTag` replaces D54's `NoIndex` and runs on every web page: a page
  is indexable only when the gate is on and the page qualifies (public pages registered per language
  do; an occupation page only when `indexable`; sign-in, settings and the dashboard never). Otherwise
  it sends `X-Robots-Tag: noindex` and a `robots` prop that the Blade layout renders as
  `<meta name="robots">`, so the tag is in the HTML even without SSR. Turning the gate off makes every
  page noindex again at once, whatever the stored flags say.
- **D57** (M14) `/sitemap.xml` is empty while the gate is off. When on, it lists the home page, the
  occupation index, the contribution terms and every indexable occupation page, each in both
  languages with `xhtml:link` hreflang alternates. Group pages may be indexed but are not listed:
  crawlers reach them from the index. `/robots.txt` allows public pages (a blocked page could not
  show its `noindex`), blocks `/admin`, `/horizon`, `/dashboard` and `/settings`, and names the
  sitemap. It replaces the static `public/robots.txt`.
- **D58** (M15) Search matches `occupation_names.search_key`, a looser key than `normalized_name`:
  `NormalizeForSearch` also turns Bangla digits into ASCII and folds the spellings people mix up (ী/ি,
  ূ/ু, ঈ/ই, ঊ/উ, শ/ষ/স, ণ/ন, ৎ/ত and a word-final hasanta, and drops chandrabindu). The duplicate
  detector keeps the strict key, so folding never makes two occupations look alike. Both keys are
  written by `SyncOccupationNames`; the migration fills in existing rows. More folds (ঙ/ং, ড়/র, য/জ)
  wait for the benchmark, since each one also makes unrelated words meet.
- **D59** (M15) A name matches exactly, by prefix (of the whole name, or of each query word against
  the name's words through `to_tsvector('simple', search_key)`), or by trigram similarity: word
  similarity ≥ 0.6 (pg_trgm's default) or whole-name similarity ≥ `atlas.search.min_similarity`
  (0.4). Each occupation counts once, by its best name; the order is exact > prefix > similar, then
  similarity, then a primary name, then a name in the reader's language. All languages are searched
  on every page, since people type English on the Bangla site and the other way round. Only listed
  and published occupations are found; a merged occupation's names are aliases of the one it went
  into (M11). The result shows the name that matched when it is not the one shown ("also called
  নলমিস্ত্রি").
- **D60** (M15) `/search?q=` (bn and en) is never indexed and `robots.txt` blocks `/search` and
  `/en/search`, because every query would be a page. The header search box is a plain GET form, so
  it works before the script loads. The benchmark file is `tests/Fixtures/search-benchmark.csv`
  (`query,expected_slug,locale,note`); `atlas:search:benchmark --report` writes
  `docs/decisions/search-benchmark.md`. A query whose occupation is not in the database counts as a
  miss and is named in the report.

## Maintainer decisions (answered 2026-11-16)

"Jayed" = answered by the maintainer. "Delegated" = Jayed asked the lead engineer to decide; the reason
is written down so the choice can be reversed later.

- **A1 — Name and repo.** Work Atlas (Jayed). The Bangla name stays কাজ, as in the docs. The repo is
  `JayedHasan232/work-atlas`, **public since 2026-11-16** (Jayed; it was private at first, and GitHub
  Free runs Actions on a private repo only while included minutes and billing allow). It can move to
  an org later (O1). Domain still to be bought → O1, H1. When it went public: private vulnerability
  reporting turned on, and `main` protected (pull requests only; the DCO check and the four CI jobs
  required; no force-push or deletion; the maintainer can bypass in an emergency).
- **A2 — Licenses** (delegated). Platform `AGPL-3.0-or-later`; small libraries/SDKs MIT; data and text
  CC BY-SA 4.0; DCO 1.1, no CLA; copyright line "Work Atlas contributors". Why "or later": with DCO and
  no CLA there is no single copyright holder, so "or later" is the only way the project can move to a
  future AGPL version without asking every past contributor.
- **A3 — Docs master copy** (Jayed: both are fine). The repo's `docs/` is the master for the code:
  agents and CI read it, and changes go by PR. Jayed may keep editing the Claude Docs; the next session
  syncs that change into `docs/` as a PR. If the two disagree, ask Jayed — never pick one silently.
- **A4 — Separate-occupation rule** (Jayed). Separate when main tasks or training differ substantially;
  income-based splits are allowed later, once income data exists. Gendered name variants are aliases.
  The lead engineer drafts the 20 examples in M7; Jayed approves them (H4).
- **A5 — Wave 1 fields** (Jayed). The 8 fields from the docs plus `part_time_possible` and
  `available_in`. All Wave 1 fields: `sensitive = false`, `requires_source = false`. Start-up money is a
  cost figure → Wave 3, with sources.
- **A6 — Anchors and bands.** Not decided yet → O2. Appendix A is the working default until M7.
- **A7 — AI providers and budget.** Not decided yet → O3.
- **A8 — Duplicate detection** (delegated). Trigram similarity on normalized names plus alias overlap,
  within the same ISCO minor group. No embeddings in Phase 1: they need another provider, key and bill,
  and the stack lists pgvector under "later". The docs mention embedding similarity; revisit only if
  the M11 review shows trigram missing real duplicates.
- **A9 — BSCO-2012** (Jayed). No file yet; Jayed will get it (H7). Missing Bangla group titles may be
  filled with AI drafts that a human approves. Because of this, BSCO moved out of M6 into M6b, so the
  ISCO-08 import and everything after it don't wait for the file.
- **A10 — URLs** (Jayed). Bangla at the root (`/occupations/electrician`), English under `/en/…`, ASCII
  English slugs in both.
- **A11 — Thresholds** (Jayed), all in `config/atlas.php`:
  a. Index gate: every page `noindex` during the Phase 1 beta. From Phase 2, index when ≥ 80% of
     Wave 1–2 fields are `community_supported` or better and the description is reviewed.
  b. Micro-task consensus: 3 agreeing answers and no dissent.
  c. Trusted contributor: 50 accepted contributions and 30 days since signup.
- **A12 — Hosting** (delegated). Prices and traffic allowances are checked at order time, not assumed.
  - Production: Hetzner Cloud, Singapore region (low latency to Bangladesh), starting at
    4 vCPU / 8 GB RAM and resized when needed. Cloudflare in front.
  - No paid server panel: an idempotent provisioning script in `ops/` with `docs/ops/server.md`, and
    Deployer (open source) for zero-downtime deploys from GitHub Actions. Laravel Forge is the fallback
    if server upkeep takes too much of Jayed's time.
  - Registrar: Cloudflare Registrar if it sells the chosen TLD; otherwise any registrar, DNS on Cloudflare.
  - Auth mail: Amazon SES (pay per message, no monthly fee, built-in Laravel driver).
  - Errors: Sentry's hosted free plan with personal-data scrubbing on. The same SDK works with
    GlitchTip, so moving to self-hosted later only changes the DSN.
  - Analytics: Umami, self-hosted on the same VPS with its own database. Not Plausible: self-hosted
    Plausible needs ClickHouse, which the stack rules out.
  - Uptime: one external free uptime check on `/up`.
  - Staging: yes — a small second VPS (2 vCPU / 4 GB) behind HTTP basic auth and `noindex`, with seeded
    fake data only, never a copy of production user data.
  - Server access: Jayed only (SSH keys; password and root login off) plus one deploy key for GitHub
    Actions. Accounts are opened by Jayed in the project's name (H9).
- **A13 — Core team** (Jayed). Jayed alone holds maintainer and reviewer. Nobody holds `expert`, so
  health and legal fields stay hidden (they are not in Phase 1 anyway). Because one person reviews, the
  queue order (model disagreement first) matters more.
- **A14 — E2E runner** (delegated). Pest's browser plugin (Playwright underneath; Pest 5 since M2): tests in PHP, the same
  factories and database setup as feature tests, one test runner, built-in accessibility assertion.
- **A15 — Spam** (delegated). At launch: honeypot, rate limits, limits for new accounts, and the
  moderation queue. Cloudflare Turnstile is wired behind a config flag, off by default, and switched on
  only if spam gets through. This keeps third-party scripts off public pages (privacy, slow networks).

## Still open

1. **O1 — Domain, project email and GitHub org** (H1). The org isn't needed to start: the repo lives
   under Jayed's account and can be transferred later (GitHub redirects the old URL). The domain
   blocks M22. A project email (not a personal one) goes into the Code of Conduct as the contact for
   reports; until then it shows a marked placeholder. Needed before outside contributors are invited.
2. **O2 — Anchors and bands** (Appendix A). Jayed approves them in M7, where the bn + en file is shown
   with a plain explanation. Blocks the end of M7. The draft is `data/fields/wave1.v1.yaml` (table:
   `docs/architecture/fields.md`). Points to look at: "SSC/HSC or equivalent" (covers Dakhil/Alim and
   vocational SSC); bands include their lower bound (D35); time to first income counts from the start
   of learning; the anchor examples (porter is "মালবাহী শ্রমিক"). Until approved, nothing generates
   values for these fields (M12 is later), so a change is still a free edit of the v1 file.
3. **O3 — AI providers and budget** (H8). Proposal: provider A = Anthropic (Claude), provider B = a
   different company (OpenAI or Google), so the two models make independent mistakes. Jayed opens the
   accounts, keeps the API keys (server `.env` only, never in the repo) and sets a monthly cap. Every
   paid run gets a dry-run cost estimate first. Blocks the real drivers in M9 and the paid runs in M10
   and M12.
4. **O4 — BSCO-2012 file** (H7). Blocks M6b only.
5. ~~**O5 — When the repo goes public.**~~ Settled 2026-11-16: public now (A1). The Code of Conduct
   contact email moved to O1.
6. **O6 — Deleting an account that has contributions** (raised in M4). Revisions keep their author
   and can never change, so the database refuses to delete a user who wrote or reviewed one. Proposal:
   account deletion removes the person's data (name, email, password, passkeys, 2FA, tokens) and keeps
   an anonymous user row, shown as "deleted contributor", so the history and its credit stay intact.
   It does not block anything today: nothing writes revisions until M5, and contributors arrive in
   M16. Decide before M8 ships signup. M8 built signup without it: nothing is deployed until M22,
   and no account can be deleted yet while it has revisions. Still to decide before M16.
7. **O7 — AI over a `community_supported` value** (raised in M5). The rules protect only `verified`
   values, so a new AI run replaces a community-supported value and drops it to `ai_draft`. Proposal:
   treat `community_supported` like `verified` (the AI write becomes a proposal), so community work is
   never undone by a re-run. Decide before the first paid AI run (M10).
8. **O8 — History of status changes** (raised in M5). Revisions record every value change, but
   `ChangeStatus` only updates the slot, so who verified or disputed a value, and when, is not kept.
   Proposal: an append-only `status_changes` table (slot, from, to, actor, reason, time) added in M8
   with the roles. Decide before M8. M8 shipped the roles without it (not guessed); still to decide,
   before reviewers other than Jayed are added.
9. **O9 — ISCO-08 reuse terms** (raised in M6). The ILO puts work published on or after 3 May 2023
   under CC BY 4.0; ISCO-08 is © ILO 2012, so it may need written permission (rights@ilo.org), as
   ESCO got. The ILOSTAT classification page could not be opened here (bot check). Until this is
   clear, the importer is built and tested with placeholder fixtures only, and no ILO text is in the
   repo. Blocks the real `isco08.csv`, `SOURCE.md` and `IscoRealFileCountsTest`.
10. **O10 — `formal_training: required` is a licensing claim** (raised in M7). Rule 2 says licensing
    and legal requirements need a source, but A5 sets `requires_source = false` for all Wave 1
    fields. Proposal: keep the field without a source requirement, but have M12 store the AI's
    `required` answers as `ai_draft` with a note and never show "required" publicly until a reviewer
    adds a source; or make `formal_training` v2 with `requires_source = true`. Decide before M12.
11. **O11 — Status of a person's direct write** (raised in M8). A trusted person's applied write
    without a source becomes `community_supported` (with a source, `sourced`). One person is not yet
    "the community". Proposal: keep it for now, and in
    M16 (micro-tasks) make `community_supported` need agreement from several people, with a single
    person's write staying at the status it would have as a draft. Decide before M16.
12. **O12 — Contribution terms text** (raised in M8). The page (`/contribution-terms`, `lang/*/terms.php`)
    is a draft marked as such: CC BY-SA 4.0 for contributions, history with authors, share only what
    you may share, private submissions stay private, respect. It is legal text, so Jayed approves it
    (ideally with legal advice) before outside contributors are invited; approval changes
    `atlas.contribution_terms_version` to a final value.
13. **O13 — GitHub's `pull_request_target` policy** (raised in M10). On 2026-11-16 the DCO and
    immutable-files workflows (both `pull_request_target`, D42) failed to start: GitHub ran a
    scheduled brownout of its new default that blocks `pull_request_target` for affected public
    repositories. If that default becomes permanent, those required checks never run and no PR can
    merge. It is a repository security setting, so Jayed decides: allow `pull_request_target` for
    this repo in Settings → Actions (see gh.io/securely-using-pull_request_target), or move both
    checks to `pull_request` with read-only permissions.

---

## Appendix A — Wave 1 field draft (fields per A5; anchors and bands await approval, O2)

Bangla labels and anchors get written in M7 after approval. Examples in anchors describe workload
only, never the worth of the work.

| Key | Type | Values |
|---|---|---|
| `education_required` | enum | none · can read and write · primary (class 5) · SSC · HSC · vocational certificate/diploma · bachelor's · master's or higher |
| `formal_training` | enum | not needed · helpful · required (license/certificate) |
| `physical_workload` | scale 1–5 | anchors below |
| `mental_workload` | scale 1–5 | anchors below |
| `work_setting` | multi-enum | indoor · outdoor · on the move (vehicle/travel) · home-based |
| `work_time` | multi-enum | day · evening · night · rotating shifts · self-set hours |
| `seasonal` | enum | year-round · year-round with peak seasons · seasonal only |
| `time_to_learn` | band | under 1 month · 1–3 months · 3–12 months · 1–2 years · 2–5 years · over 5 years |
| `time_to_first_income` | band | within a week · under 1 month · 1–6 months · 6–12 months · 1–3 years · over 3 years |
| `part_time_possible` | enum | no · sometimes · yes |
| `available_in` | multi-enum | rural · urban |

`physical_workload`
1. Sitting most of the day, light hand work (office clerk, call-centre agent)
2. Mostly standing or walking, light items (shop salesperson, teacher)
3. On your feet all day with regular light-to-medium lifting (cook, electrician)
4. Frequent lifting, carrying and bending; tiring most days (mason, field farm work in season)
5. Heavy lifting and carrying most of the day (construction labourer, porter)

`mental_workload`
1. Routine, repetitive tasks with few decisions (packing-line work)
2. Routine with some judgement (shop assistant, delivery rider)
3. Regular problem solving, moderate responsibility (electrician, accountant)
4. Complex problems or responsibility for other people's safety or money most days (nurse, software engineer)
5. Constant high-stakes decisions under time pressure (surgeon, air traffic controller)
