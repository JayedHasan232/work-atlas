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

See [the module rules](../README.md).
