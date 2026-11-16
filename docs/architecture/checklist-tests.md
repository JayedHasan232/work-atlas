# "Data and code" checklist → tests

PLAN M23 asks that every "Data and code" item of the success checklist in
[`02-implementation.md`](../02-implementation.md) ("সাফল্যের চেকলিস্ট") is true and proved by a test.
This table is that proof. `tests/Arch/ChecklistTestsTest.php` fails when a test named here no longer
exists, so a renamed or deleted test cannot silently drop an item.

| # | Item | Proved by | Where it is enforced |
|---|---|---|---|
| 1 | Every field value has a status and a revision history | `tests/Feature/Knowledge/FieldConstraintsTest.php`, `tests/Feature/Knowledge/InitialStatusTest.php`, `tests/Feature/Knowledge/StatusTransitionsTest.php`, `tests/Feature/Knowledge/RevisionsAppendOnlyTest.php`, `tests/Feature/Knowledge/RevertCreatesNewRevisionTest.php`, `tests/Feature/Knowledge/RevisionAuthorConstraintTest.php`, `tests/Arch/KnowledgeWritePathTest.php` | `field_values.status` check constraint, `Knowledge\Actions\RecordRevision` (the one write path), the append-only trigger on `revisions` |
| 2 | No number is saved without a source | `tests/Feature/Knowledge/RequiresSourceTest.php`, `tests/Feature/Knowledge/RecordSourceTest.php` | `RecordRevision` (fields with `requires_source`) |
| 3 | Every AI call is in `ai_runs`; prompts are versioned in the repo | `tests/Feature/AiPipeline/AiRunLoggedTest.php`, `tests/Arch/AiGatewayOnlyTest.php`, `tests/Feature/AiPipeline/PromptRegistryTest.php`, `scripts/ci/check-immutable-selftest` | `AiPipeline\AiGateway`, `data/prompts/<wave>/<name>.vN.md` checked by `scripts/ci/check-immutable` in CI |
| 4 | AI never overwrites a `verified` value | `tests/Feature/Knowledge/AiCannotOverwriteVerifiedTest.php`, `tests/Feature/AiPipeline/Wave1LeavesVerifiedUntouchedTest.php` | `RecordRevision` (an AI write on a verified value becomes a proposal) |
| 5 | The `noindex` gate works | `tests/Feature/Publishing/IndexGateTest.php`, `tests/Feature/Publishing/NoindexHeaderTest.php`, `tests/Feature/Publishing/SitemapTest.php`, `tests/Feature/Ops/StagingTest.php` | `Publishing\IndexGate`, `Publishing\PageRobots`, `StagingGate` (staging is always `noindex`) |
| 6 | A restore from backup was tested last month | **Not provable by a test.** The scripts are checked by ShellCheck in CI; the proof is a dated line in the restore-test log of [`docs/ops/restore.md`](../ops/restore.md). | Blocked until production exists (O1, H9); then a maintainer runs the monthly test |

Item 6 stays open until the first restore test after the production setup is logged.
