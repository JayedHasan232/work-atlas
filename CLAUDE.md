# CLAUDE.md — Work Atlas (কাজ)

Open, nonprofit, community-driven database of every occupation in Bangladesh (global structure, other
countries later). AI writes drafts; the community, reviewers and experts verify. Like Wikipedia, but
structured data. Code `AGPL-3.0-or-later` (small libraries/SDKs MIT); data and text CC BY-SA 4.0;
DCO, no CLA; copyright "Work Atlas contributors".

**Source of truth:** `docs/01-vision.md` (what and why), `docs/02-implementation.md` (stack, data model,
pipeline, roadmap). Work plan: `docs/PLAN.md` — one milestone per session, then stop for review;
answered decisions are its A-numbers, open items its O-numbers. `docs/` is the master copy. If the
maintainer edited the Claude Docs, sync that into `docs/` by PR; if the two disagree, ask.
If a task conflicts with these docs or the rules below, stop and ask the maintainer. Don't work around it.

## Non-negotiable rules

1. **No honest work is undignified.** Never belittle an occupation. No numeric prestige score; show
   "what society thinks" next to "what is actually true".
2. **Never invent numbers.** Income, demand, costs, statistics, growth rates, licensing/legal
   requirements need a source URL. No source → `null`. Never a guessed or "typical" figure.
3. **Every field value has a status:** `ai_draft` → `sourced` → `community_supported` → `verified`, or
   `disputed`. AI output enters as `ai_draft` (or `sourced` if a source was captured).
4. **AI never overwrites a `verified` value.** It can only add a proposed revision.
5. **Revisions are append-only.** Each records its author (user or `ai_run`), sources and comment.
   A revert is a new revision.
6. **No future guarantees.** Never "AI-proof" / "future-proof". Forecasts are labelled as forecasts
   with assumptions and confidence. AI exposure is not job loss — say so.
7. **Health:** levels (low / medium / high) with reasons and sources, never numeric risk. Benefits next
   to risks. Age bands 20–30, 30–45, 45–60, 60+. Never call anyone unfit for work because of age,
   gender or any other trait. Always "not medical advice". Expert review before anything is public.
8. **Privacy:** individual income/experience submissions are private. Publish only aggregates above a
   minimum count. No personal data in open data dumps or the public API. Guided-filter answers
   belong to the user.
9. **Neutrality:** donors, sponsors and employers never change data or recommendations. Sponsored
   content is always labelled.
10. **Bangla first, English alongside.** Local and regional names (বিদ্যুৎ মিস্ত্রি, নলমিস্ত্রি) are stored
    as aliases so search finds them.
11. **Explainable recommendations.** "Matches your situation", never "perfect job for you". Always show
    why and what the limits are.
12. **No mass unverified pages in search engines.** Every page is `noindex` during the Phase 1 beta;
    later only pages that pass the index gate are indexed (see below).

## Stack

- **Laravel 13 modular monolith**, one repo. PostgreSQL 18. PHP version pinned in CI.
- **Web:** Laravel React starter kit — Inertia + React + TypeScript, **SSR on** (public pages must be
  crawlable). Tailwind CSS + shadcn/ui. Apache ECharts for charts (check Bangla fonts).
- **Auth:** Fortify (web sessions) + Sanctum (API tokens).
- **Queues:** Redis + Horizon. Every AI call runs inside a queued job.
- **Admin:** Filament, core team only (imports, AI runs, list review/merge, spam queue). Community
  editing lives in the public React site, never in Filament.
- **API:** REST under `/api/v1`, OpenAPI-documented. Public read-only API from Phase 1.
- **Search:** PostgreSQL full-text + `pg_trgm` over `occupation_names`. Typesense/Meilisearch only
  after a Bangla search benchmark shows the need.
- **Ops:** Hetzner Cloud VPS (Singapore) + Cloudflare, R2 storage, nightly encrypted off-site DB
  backups with a monthly restore test. Provisioning script in `ops/`, deploys with Deployer from
  GitHub Actions. Amazon SES for mail, Sentry (PII scrubbed), self-hosted Umami. Staging on a second
  small VPS with fake data only.
- **Python:** only one-off import/analysis scripts in `scripts/`. Never application logic.
- **Later, not now:** pgvector and embeddings (duplicate detection is trigram + aliases), PostGIS,
  React Native (Expo) after the PWA.
- **Never introduce without a maintainer decision:** a separate Next.js app, FastAPI, microservices,
  Kubernetes, Kafka, GraphQL, ClickHouse, a second backend language, a new external service, or a
  new dataset (check its license first).

## Module layout

```
app/Modules/
  Taxonomy/       occupations, ISCO-08/BSCO-2012 groups, names, aliases, merges
  Knowledge/      field definitions, field values, revisions, sources   ← the one write path
  Observations/   income/demand/cost by region, period, source; private submissions (Phase 2)
  Community/      roles, trust levels, verification tasks, discussions, credit
  AiPipeline/     AiGateway, ai_runs, prompt registry, drafts, cross-checks
  Discovery/      guided filter, compare, later recommendations
  Publishing/     public presenters, index gate, sitemap, public API, open data releases
  <Module>/{Actions,Queries,Models,Enums,Data,Jobs,Events,Policies,Http/Controllers/{Web,Api},Filament}
data/taxonomy/                  ISCO/BSCO source files + SOURCE.md (URL, license, accessed date)
data/fields/                    field definitions with scale anchors, versioned
data/prompts/<wave>/<name>.vN.md   data/schemas/<wave>/<name>.vN.json
database/migrations/            all modules, reversible
resources/js/pages/<module>/    lang/{bn,en}/
tests/{Unit,Feature,Arch,Browser}/<Module>/
scripts/                        one-off Python (run with python3 -I)
```

- Business logic lives in **Actions** (one public `handle()`) and **Queries**, never in controllers,
  Filament resources, jobs, commands or models. Web, API, Filament and Artisan call the same actions.
- A module uses another module's Actions/Queries, never its tables or models directly.
- **One write path:** every field-value change goes through `Knowledge\Actions\RecordRevision`.
- **One read path:** every public read (web props, SSR HTML, API, dumps) goes through `Publishing`
  presenters. That is where the sensitive-field gate lives.
- **One AI path:** every model call goes through `AiPipeline\AiGateway`.

## Rules enforced in code (each one has a test; never weaken or skip it)

| Rule | Enforced in | Test |
|---|---|---|
| A `requires_source` field cannot be saved without ≥ 1 source | `RecordRevision` | `Feature/Knowledge/RequiresSourceTest` |
| A `sensitive` field (income, health, legal) is not rendered publicly unless `verified` | `Publishing` presenters | `Feature/Publishing/SensitiveFieldGateTest` (web props, HTML, API) |
| AI writes never replace a `verified` value; they become a proposed revision | `RecordRevision` | `Feature/Knowledge/AiCannotOverwriteVerifiedTest` |
| Revisions are append-only; revert = new revision | `Revision` model guard + Postgres trigger | `Feature/Knowledge/RevisionsAppendOnlyTest` |
| Every AI call creates an `ai_runs` row, failures included | `AiGateway` | `Feature/AiPipeline/AiRunLoggedTest`, `Arch/AiGatewayOnlyTest` |
| Pages are `noindex` (meta + `X-Robots-Tag`, left out of sitemap) until fields pass `config('atlas.index_gate')` (off in Phase 1) | `Publishing\IndexGate` | `Feature/Publishing/IndexGateTest` |
| Prompt and schema files are immutable; a change is a new `vN` file | CI script | `scripts/ci/check-immutable` |

Arch tests also guard: no provider SDK/HTTP outside `AiGateway`; no `Revision`/`FieldValue` writes
outside `Knowledge\Actions`; no DB queries in controllers; no `dd`/`dump`.

## AI data generation

- **Waves:** one field group across all occupations, same prompt for every occupation. Order:
  1 filter fields (education, physical/mental workload, indoor/outdoor, day/night/shift, seasonal,
  time to learn, time to first income) · 2 description, tasks, skills, pros/cons, career paths ·
  3 income, demand, training cost (web search, mandatory sources, worker submissions) · 4 AI impact
  (ILO GenAI exposure index by ISCO 4-digit; AI writes only the task-level explanation) · 5 health and
  age (O*NET work context base, expert review) · 6 social perception, worker stories (community).
- **Taxonomy:** never ask for "all occupations" in one prompt. Expand one ISCO-08 unit group at a time,
  including local/regional names and informal, self-employed, gig, seasonal, care and household work.
  Separate occupation vs specialization: `docs/policies/separate-occupation.md`. A human decides merges;
  merged names become aliases.
- **Structured output only**, validated against a JSON schema. `"unknown"` is valid. Include confidence.
- **Anchored 1–5 scales** from `field_definitions`, always included in the prompt.
- **Context in every prompt:** ISCO code, Bangla and English names, aliases, Bangladesh context.
- **Numbers** only from web search with a captured source URL that resolves, plus access date. Else null.
- **Two-model cross-check:** agreement → lower review priority; disagreement → top of the queue.
- **Batch API** for large runs. A paid run needs the maintainer's go-ahead and a dry-run cost estimate.
  Tests always use the fake driver.
- **Time has three meanings**, kept as separate fields: hours a person can give, time to learn, time
  until first income.

## Data sources and licenses

- Record every source's license in `sources`. Check the license before importing any dataset.
  CC BY content may be combined into CC BY-SA data.
- **O*NET:** CC BY 4.0, attribute with the version number. Never translate or adapt the Interest
  Profiler / Career Exploration Tools (NoDerivatives).
- **ILO Working Paper 140 (GenAI exposure, 2025):** CC BY 4.0, by ISCO-08 4-digit code.
- **BBS Labour Force Survey / BSCO-2012:** structure and coarse national figures.

## Writing occupation content (Bangla)

Plain Bangla, short sentences, explain technical words. Respectful, gender-neutral, no stereotypes.
Benefits and difficulties side by side. Money only as ranges with region, period and source. Real
Bangladesh seasons (harvest, Eid, winter wedding season, monsoon). Crisis demand never means safe.

## Conventions

- **Tests:** Pest for unit, feature and arch tests; Pest's browser plugin (Playwright underneath) for
  end-to-end (`tests/Browser`). Tests run on PostgreSQL (`work_atlas_test`), never sqlite. While
  working, run only the test file you changed; run the full suite once before finishing a milestone.
  GitHub Actions must pass: Pint, Larastan (level 7), Pest, `vp check` (Oxlint + Oxfmt), `tsc`, build,
  SSR build, SSR smoke, e2e.
- **Commits:** DCO sign-off on every commit (`git commit -s` → `Signed-off-by:`). Small, focused PRs,
  one milestone per PR. Commit or push only when the maintainer asks.
- **Migrations:** reversible (`down()` works; CI runs migrate → rollback → migrate). Never edit a merged
  migration; add a new one.
- **Secrets:** never in the repo. Only `.env.example`.
- **Localization:** every user-facing string goes through translation keys in both `lang/bn` and
  `lang/en` — no hard-coded UI text in PHP or React. `bn` is the default locale, `en` the fallback.
  A parity check fails CI when a key is missing in either language. Content rows carry a `locale`.
- **Time:** store UTC, display Asia/Dhaka. Bangla digits in the `bn` locale.
- **Audience:** mobile-first, slow networks, accessible (accessibility assertion in e2e). No
  third-party scripts on public pages without a maintainer decision.

## Commands

Services: PostgreSQL 18 and Redis (`docker compose up -d`, or native; see CONTRIBUTING.md).

- `composer setup` — fresh clone: dependencies, `.env`, app key, migrations, front-end build
- `composer dev` — app server, Horizon, log tail (Pail) and Vite together
- `npm run build:ssr` then `php artisan inertia:start-ssr` — run with SSR locally
- `php artisan test --testsuite=Unit,Feature,Arch` — fast suites; `php artisan test <file>` — one file
- `php artisan test --testsuite=Browser` — e2e; needs `npm run build` and `npx playwright install chromium`
- `vendor/bin/pint` (`--test` to only check) · `vendor/bin/phpstan analyse`
- `npm run check` (lint + format; `npm run check:fix` fixes) · `npm run types:check`
- `scripts/ci/ssr-smoke [url]` — a running app must render `/` on the server
- `scripts/ci/check-dco-selftest` — self-test of the DCO check
- `scripts/ci/check-translations` — every key in `lang/bn` and `lang/en`, same placeholders
- `php artisan atlas:translation-types` — regenerate the React `TranslationKey` type from `lang/en`
  (Vite runs it on build and when `lang/en` changes)

## When unsure

Leave a field empty (`ai_draft`, with a note) rather than guess. Anything touching the rules above,
licenses, the legal entity, scope (e.g. a marketplace) or the stack goes to the maintainer.
