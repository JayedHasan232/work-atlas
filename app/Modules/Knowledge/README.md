# Knowledge

What is known about each occupation, field by field, with its history.

- **Owns:** field definitions (type, group, scale anchors, `sensitive`, `requires_source`), field
  values, revisions, sources.
- **The one write path:** every field-value change goes through `Knowledge\Actions\RecordRevision`.
- **Rules (each has a test):** a `requires_source` field needs at least one source; AI never replaces
  a `verified` value, it only adds a proposed revision; revisions are append-only, and a revert is a
  new revision. Every value has a status: `ai_draft`, `sourced`, `community_supported`, `verified` or
  `disputed`.

See [the module rules](../README.md).
