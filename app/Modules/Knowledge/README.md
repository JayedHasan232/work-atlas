# Knowledge

What is known about each occupation, field by field, with its history.

- **Owns:** field definitions (type, group, scale anchors, `sensitive`, `requires_source`), field
  values, revisions, sources.
- **The one write path:** every field-value change goes through `Knowledge\Actions`: `RecordRevision`
  (new values and proposals), `ChangeStatus`, `ReviewRevision` and `RevertRevision`. Callers pass ids
  and a `Data\Actor` (a user or an AI run); a broken rule throws an `Exceptions\KnowledgeRuleViolation`
  and writes nothing. Each action locks the value slot's row first. Details and the status
  transition table: [the data model](../../../docs/architecture/data-model.md#the-write-path-m5).
- **Rules (each has a test):** a `requires_source` field needs at least one source; AI never replaces
  a `verified` value, it only adds a proposed revision; revisions are append-only, and a revert is a
  new revision. Every value has a status: `ai_draft`, `sourced`, `community_supported`, `verified` or
  `disputed`.
- **Append-only, twice (D2):** `revisions`, `revision_reviews` and `revision_source` reject `UPDATE`,
  `DELETE` and `TRUNCATE` in a Postgres trigger, and `Models\Concerns\AppendOnly` makes the models
  throw before the query is sent. A revision has exactly one author, a user or an AI run (a check
  constraint).
- **Field definitions (M7):** written in `data/fields/wave<N>.v<M>.yaml`, read by `ReadFieldFiles`,
  synced by `SyncFieldDefinitions` (`atlas:fields:sync`). Once a definition has values, its type,
  options, anchors and wave are fixed; a change is a new version. `FieldSchemaBuilder` makes a wave's
  AI output schema and `RenderFieldTable` the field table, both through `atlas:fields:generate`.
- **Events:** a change of a current value (direct write, accepted proposal, revert) raises
  `Events\CurrentValueChanged` inside the transaction, so other modules can keep copies in step
  (Taxonomy's `occupation_names`, D44). `Queries\FieldDefinitionByKey` gives a field's id.
- **Cross-check (M12):** a `RevisionInput` with `secondOpinion` is an AI value kept as a proposal;
  `RecordCrossCheck` saves the two-model result on the slot. `Queries\ReviewQueue` lists slots by
  priority (model disagreements first); `Queries\DisagreementReport` is the CSV in /admin (D50).
- **Public read (M13):** `Queries\OccupationFields` gives an occupation's fields of some waves with
  their current national values and sources, unfiltered; Publishing decides what a reader sees (D52).
- **Sources:** `RecordSource` records a source once per url, access date and details (M6).
- **Tables:** see [the data model](../../../docs/architecture/data-model.md).

See [the module rules](../README.md).
