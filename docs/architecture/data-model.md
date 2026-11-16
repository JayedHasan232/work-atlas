# Data model

The core tables from M4. Migrations in `database/migrations/` are the source of truth; this page
explains them. Later milestones add `observations`, `submissions`, `verification_tasks` and
`discussions`.

```mermaid
erDiagram
    isco_groups ||--o{ isco_groups : "parent_code"
    isco_groups ||--o{ occupations : "isco_code (unit group)"
    occupations ||--o{ occupations : "merged_into_id"
    occupations ||--o{ occupation_names : has
    occupations ||--o{ field_values : has
    field_definitions ||--o{ field_values : "defines"
    field_values ||--o{ revisions : "history"
    field_values |o--o| revisions : "current_revision_id"
    revisions |o--o{ revisions : "reverts_revision_id"
    users |o--o{ revisions : "author (user)"
    ai_runs |o--o{ revisions : "author (AI)"
    revisions ||--o{ revision_source : cites
    sources ||--o{ revision_source : "cited by"
    revisions ||--o{ revision_reviews : reviewed
    users ||--o{ revision_reviews : reviewer

    isco_groups {
        string code UK "1-4 digits, length = level"
        smallint level "1 major to 4 unit group"
        string parent_code FK "left(code, level - 1)"
        text title_en
        text title_bn "nullable until M6b"
        string bsco_code "nullable until M6b"
    }
    occupations {
        string slug UK "ASCII, both locales (A10)"
        string isco_code FK
        string bsco_code
        string publish_state "candidate, listed, published, merged"
        bool indexable "never for merged"
        bigint merged_into_id FK "set iff merged"
    }
    occupation_names {
        bigint occupation_id FK
        string locale
        text name
        text normalized_name "search key"
        string type "primary, alias, local, regional"
        string region "set iff regional"
    }
    field_definitions {
        string key "unique with version"
        smallint version
        smallint wave "1-6"
        string group
        string type "scale_1_5, enum, multi_enum, boolean, band, text"
        jsonb options "enum, multi_enum, band"
        jsonb anchors "bn + en, required for scale_1_5"
        bool sensitive
        bool requires_source
    }
    field_values {
        bigint occupation_id FK
        bigint field_definition_id FK
        string locale "null = same in every language"
        string region "null = all of Bangladesh"
        bigint current_revision_id FK
        string status "ai_draft to verified, or disputed"
        smallint review_priority
    }
    revisions {
        bigint field_value_id FK
        jsonb old_value
        jsonb new_value
        bigint user_id FK "exactly one of user_id"
        bigint ai_run_id FK "and ai_run_id"
        text comment
        bigint reverts_revision_id FK
        timestamptz created_at "no updated_at"
    }
    revision_reviews {
        bigint revision_id FK
        bigint reviewer_id FK
        string decision "accepted, rejected"
        text comment
        timestamptz created_at
    }
    sources {
        text url "http(s)"
        text publisher
        text title
        string license
        date accessed_on
        text archive_url
    }
    revision_source {
        bigint revision_id PK
        bigint source_id PK
    }
    ai_runs {
        string provider
        string model
        string prompt_key
        smallint prompt_version
        char input_hash "sha256 hex"
        text raw_output
        jsonb parsed_output
        string status "pending, running, succeeded, failed"
        int input_tokens
        int output_tokens
        numeric cost_usd
        string batch_id
        text error "required when failed"
    }
```

## Which module owns what

| Module | Tables | Models |
|---|---|---|
| Taxonomy | `isco_groups`, `occupations`, `occupation_names` | `IscoGroup`, `Occupation`, `OccupationName` |
| Knowledge | `field_definitions`, `field_values`, `revisions`, `revision_reviews`, `sources`, `revision_source` | `FieldDefinition`, `FieldValue`, `Revision`, `RevisionReview`, `Source` |
| AiPipeline | `ai_runs` | `AiRun` |

A module never reads another module's tables or models. Across modules, rows are linked by a foreign
key, and the model keeps only the id (`FieldValue::$occupation_id`, `Revision::$ai_run_id`).

## Rules the database enforces

Each is a constraint, so it holds even for raw SQL. The tests are in
`tests/Feature/{Taxonomy,Knowledge}/`.

- **Append-only history (rule 5, D2).** A trigger rejects `UPDATE`, `DELETE` and `TRUNCATE` on
  `revisions`, `revision_reviews` and `revision_source`. The `AppendOnly` model trait throws first.
  `migrate:rollback` still works, because `DROP TABLE` is not blocked.
- **One author per revision.** `num_nonnulls(user_id, ai_run_id) = 1`. Authors can't be deleted while
  they have revisions (foreign keys `RESTRICT`).
- **One value slot** per occupation × field definition × locale × region, with `NULLS NOT DISTINCT`, so
  two "all regions" rows can't coexist.
- **Statuses and types** are check constraints, not Postgres enum types, so a later value is a small
  migration. PHP enums mirror them: `FieldStatus`, `FieldType`, `ReviewDecision`, `PublishState`,
  `NameType`, `AiRunStatus`.
- **ISCO codes**: digits only, length = level, and the parent is the code minus its last digit.
- **Merges**: `merged_into_id` is set if and only if `publish_state = 'merged'`. A merged occupation is
  never `indexable` and never points to itself.
- **Names**: one primary name per occupation and locale. Regional names carry a region; others don't.
- **Field definitions**: `key` + `version` is unique. A change of meaning, options or anchors is a new
  version. 1–5 scales need anchors in `bn` and `en`; choice fields need options.

## What the application checks instead

From M5, `Knowledge\Actions\RecordRevision` checks:

- that a value fits its field definition (type, options, the 1–5 range, `"unknown"`)
- the `requires_source` rule
- that AI never replaces a `verified` value
- status transitions

Those rules need context the database doesn't have.

Times are stored as `timestamptz` (UTC) and shown in Asia/Dhaka.
