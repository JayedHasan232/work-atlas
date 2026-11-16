## What and why

<!-- What does this change, and why? Link the milestone (docs/PLAN.md) or issue. -->

Milestone / issue:

## How it was tested

<!-- Commands you ran, tests you added. -->

## Rules checklist

Tick each box, or write "n/a" next to it if it doesn't apply.

- [ ] Every commit is signed off (`git commit -s`)
- [ ] Tests added or updated; the full suite and CI pass
- [ ] No rule-enforcing test was weakened or skipped
- [ ] Field-value changes go only through `Knowledge\Actions\RecordRevision`
- [ ] Public reads go only through `Publishing` presenters; AI calls only through `AiPipeline\AiGateway`
- [ ] No invented numbers: every income, cost, statistic or legal requirement has a source, or is empty
- [ ] Sensitive fields (income, health, legal) are not shown publicly unless `verified`
- [ ] AI output cannot overwrite a `verified` value; revisions stay append-only
- [ ] Prompt or schema changes are new `vN` files, not edits
- [ ] User-facing strings are in both `lang/bn` and `lang/en`
- [ ] Migrations are reversible (`down()` works)
- [ ] No secrets, no personal data in fixtures, seeds or dumps
- [ ] Wording is respectful: no prestige scores, no "AI-proof"/"future-proof", no "perfect job for you"
- [ ] New dataset or dependency? Its license is checked and the maintainer agreed
