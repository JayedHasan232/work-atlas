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

See [the module rules](../README.md).
