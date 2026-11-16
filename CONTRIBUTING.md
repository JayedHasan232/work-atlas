# Contributing to Work Atlas

> **সংক্ষেপে (বাংলা):** ছোট, একটা বিষয়ের pull request পাঠান। প্রতিটা commit-এ DCO sign-off দিন
> (`git commit -s`)। কোডের সাথে test দিন। ব্যবহারকারী যা পড়বেন এমন প্রতিটা লেখা বাংলা আর ইংরেজি, দুই
> ভাষাতেই দিন। উৎস ছাড়া কোনো সংখ্যা লিখবেন না। কোনো পেশাকে ছোট করে লিখবেন না। কোনো গোপন তথ্য
> (password, API key) repo-তে রাখবেন না।

Thank you for helping. Work Atlas is built in the open by volunteers. This guide explains how to send
a change.

## Before you start

- Read the vision and the implementation path: [docs/01-vision.md](docs/01-vision.md) and
  [docs/02-implementation.md](docs/02-implementation.md) (Bangla).
- Work follows [docs/PLAN.md](docs/PLAN.md): one milestone per pull request.
- The **non-negotiable rules** are listed in [CLAUDE.md](CLAUDE.md). They apply to human and AI
  contributors alike.
- Everyone follows the [Code of Conduct](CODE_OF_CONDUCT.md).
- Report security or privacy problems privately: see [SECURITY.md](SECURITY.md).

Right now (Phase 0) contributions are code and documentation through pull requests. Data contributions
through the website come later.

## Developer Certificate of Origin (DCO)

We use the [Developer Certificate of Origin 1.1](https://developercertificate.org/) instead of a CLA.
By signing off a commit you certify that you wrote the change, or otherwise have the right to submit it
under the project's licenses.

Sign off every commit:

```bash
git commit -s -m "Add occupation slug validation"
```

This adds a line like `Signed-off-by: Your Name <you@example.com>`. Use a name you are known by and the
same email as the commit author. A check on every pull request fails if any commit is missing a
matching sign-off.

Forgot? Fix the last commit with `git commit --amend -s --no-edit`, or every commit on your branch with
`git rebase --signoff main`, then force-push your branch.

## What you license

- Code: `AGPL-3.0-or-later` (MIT for libraries and SDKs that say so in their folder).
- Data and text: CC BY-SA 4.0. See [LICENSE-DATA.md](LICENSE-DATA.md).
- Don't add third-party code or data unless its license allows it. For a new dataset, open an issue
  first: its license must be checked and the maintainer must agree.

## Pull requests

- **Small and focused.** One topic or one milestone per pull request.
- **Tests are required.** Pest for unit, feature and architecture tests; browser tests for user flows.
  Every rule enforced in code has a test — never weaken or skip one.
- **CI must pass.** Pint, Larastan, Pest, `vp check` (lint and format), `tsc`, build, SSR build, SSR
  smoke, end-to-end, and the DCO check.
- **Migrations are reversible.** Never edit a merged migration; add a new one.
- **Bangla and English.** Every user-facing string goes through translation keys in both `lang/bn` and
  `lang/en`. No hard-coded UI text.
- **No secrets.** Only `.env.example` goes in the repo. No personal data in fixtures, seeds or dumps.
- Fill in the checklist in the pull request template.

## Data and content rules (short version)

- **Never invent numbers.** Income, demand, costs, statistics and legal requirements need a source
  URL. No source → leave it empty.
- Every value has a status: `ai_draft` → `sourced` → `community_supported` → `verified`, or
  `disputed`. AI never overwrites a `verified` value.
- Revisions are append-only. A revert is a new revision.
- Respectful, gender-neutral language. No prestige scores. Never "AI-proof", "future-proof" or
  "perfect job for you".
- Health content: levels with reasons and sources, never numeric risk; always "not medical advice".

## The planning docs

`docs/` is the master copy of the planning documents. The maintainer may also edit them as Claude Docs;
when that happens, the change is synced into `docs/` through a pull request. If the two disagree, ask
the maintainer — don't pick one.

## Using AI tools

You may use AI tools to help write code or text. You stay responsible for the change: review it,
test it, make sure it follows the rules above, and sign it off only if you have the right to submit it.

## Local setup

> **বাংলা:** লাগবে PHP 8.4, Composer, Node 22, PostgreSQL 18 আর Redis। নিচের ধাপগুলো একবার চালালেই
> app চলবে।

You need PHP 8.4 (extensions `pdo_pgsql`, `redis`, `intl`), Composer 2, Node 22, PostgreSQL 18 and
Redis. PHP and Node run on your machine; only the database and Redis may run in Docker.

**1. Start PostgreSQL and Redis.** Either way, the settings match `.env.example`.

- With Docker: `docker compose up -d` (creates the `work_atlas` and `work_atlas_test` databases).
- Without Docker (macOS example): `brew install postgresql@18 redis`, then
  `brew services run postgresql@18` and `brew services run redis`, and create the role and databases:

  ```bash
  psql -d postgres -c "CREATE ROLE work_atlas LOGIN PASSWORD 'password' CREATEDB"
  psql -d postgres -c "CREATE DATABASE work_atlas OWNER work_atlas"
  psql -d postgres -c "CREATE DATABASE work_atlas_test OWNER work_atlas"
  ```

  These credentials are for local development only.

**2. Install and build.**

```bash
composer setup
```

This installs the PHP and JS dependencies, creates `.env`, generates the app key, runs the migrations
and builds the front end.

**3. Run.** `composer dev` starts the app at <http://localhost:8000> with Horizon, a log tail and Vite.
If port 8000 is taken, run `php artisan serve --port=8001` and `npm run dev` yourself.

To try server-side rendering: `npm run build:ssr`, then `php artisan inertia:start-ssr` next to
`php artisan serve`. `scripts/ci/ssr-smoke http://127.0.0.1:8000/` checks the page came from the server.

**Tests and checks** (CI runs the same):

```bash
php artisan test --testsuite=Unit,Feature,Arch
vendor/bin/pint --test
vendor/bin/phpstan analyse
npm run check
npm run types:check
```

Browser tests need built assets and a Playwright browser: `npm run build`,
`npx playwright install chromium`, then `php artisan test --testsuite=Browser`.
