# Publishing

Everything the public can read.

- **Owns:** public presenters, the index gate, the sitemap, the public read-only API under `/api/v1`
  (OpenAPI-documented), open data releases.
- **The one read path:** web props, SSR HTML, the API and data dumps all go through Publishing
  presenters. That is where the sensitive-field gate lives: income, health and legal fields are not
  shown unless `verified`.
- **Rules (each has a test):** pages are `noindex` (meta and `X-Robots-Tag`, left out of the sitemap)
  until their fields pass `config('atlas.index_gate')`, which is off in Phase 1. No personal data in
  dumps or the public API.
- **Occupation pages (M13):** `/occupations/{slug}` renders a listed or published occupation through
  `Presenters\OccupationPresenter` (names, ISCO path, fields with a status each, numbered sources)
  and 301-redirects a merged slug (PLAN D49, D52). `Presenters\BrowsePresenter` serves
  `/occupations` and `/occupations/groups/{code}`. The sensitive gate is in `OccupationPresenter`
  (D53).
- **Index gate (M14):** `IndexGate` holds the A11a rule (PLAN D55); `Actions\EvaluateIndexGate`
  stores the answer on the occupation through Taxonomy, queued by `Actions\QueueIndexGateCheck` after
  every value or status change while the gate is on (D56). `PageRobots` and
  `Http\Middleware\RobotsTag` mark every page that may not be indexed (`X-Robots-Tag` and the meta
  tag). `Sitemap` and `RobotsTxt` serve `/sitemap.xml` and `/robots.txt` (D57).
- **Search page (M15):** `/search?q=` renders `Presenters\SearchPresenter` (Taxonomy's search, with the
  name that matched); result pages are never indexed and are blocked in `robots.txt` (PLAN D60). The
  public layout has a search box in the header.
- **Contributions (M16):** each field on an occupation page links to its suggestion form
  (`/occupations/{slug}/suggest/{field}`, signed in) and the report form
  (`/occupations/{slug}/report`, no login), both built by `Presenters\ContributionFormPresenter`
  (the sensitive gate holds in the form too). `OccupationPresenter` adds the credit list;
  `Presenters\ContributorPresenter` serves `/contributors/{id}`. All three pages are `noindex`
  (D61–D63). `Presenters\FieldDisplay` turns a stored value into reader text for both.
- **Micro-tasks (M17):** `Presenters\MicroTaskPresenter` serves the "Check facts" page (`/tasks`,
  signed in, `noindex`): one question with the value as reader text, how many people must agree and
  how many questions the person answered (D64).
- **Talk pages (M19):** `Presenters\TalkPresenter` serves `/occupations/{slug}/talk` and its thread
  pages (`noindex`): the threads (filtered by field), the posts, and the hide and show links for
  moderators. A hidden post's text and author are left out for everyone else (D70). The occupation
  page links its talk page and, per field, a "Discuss" link.
- **Guide and compare (M20):** `Presenters\GuidePresenter` turns the matches into reasons (the value
  as reader text and its status) and limits; `Presenters\ComparePresenter` builds the side-by-side
  rows with the sensitive-field gate (D72–D74). `RobotsTxt` keeps answered guides and compare pages
  out of crawling.
- **Public API v1 (M21):** `Presenters\ApiPresenter` and `Http\Controllers\Api\*` serve `/api/v1`
  (`routes/api.php`), rate-limited (`throttle:public-api`) and open to any origin for reads
  (`config/cors.php`). `OpenApiSpec` reads the hand-written description in
  `resources/api/openapi.v1.yaml` for `/api/v1/openapi.json` and the `/developers` page (D75–D76). `Http\ApiResponse` sends the JSON.

See [the module rules](../README.md).
