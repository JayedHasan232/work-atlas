# Community

The people who write and check Work Atlas.

- **Owns:** roles (reader, contributor, trusted contributor, reviewer, subject expert, maintainer),
  trust levels, verification micro-tasks and their consensus, discussions (a talk page per
  occupation), contribution credit.
- **Rules:** sensitive fields (income, health, legal) are always reviewed before they are public.
  Anonymous contribution is allowed. Community editing lives in the public site, never in Filament.

**Now (M8):**

- `Enums\Role`: the roles and the role × action matrix the Knowledge actions ask (PLAN D36).
- `Actions\ChangeRole`: the only way to change a role (`php artisan atlas:user:promote {email} {role}`).
- `Actions\CountReviewOutcome`: the trust counters on `users`, called by `Knowledge\Actions\ReviewRevision`.
- `Actions\RecordContributionConsent`, `Models\ContributionConsent`: the agreement to the
  contribution terms that signup requires (D39), with the terms version and the time.

**M16 (contributions):**

- `Actions\SubmitErrorReport`, `Actions\HandleErrorReport`, `Models\ErrorReport`: a reader's error
  report (no login, no IP address kept) and the admin queue (`/admin/error-reports`) (D61).
- `Actions\SuggestEdit`: a person's change to one field, through `Knowledge\Actions\RecordRevision`
  with sources, a comment and a conflict-of-interest declaration (D62).
- `Actions\CheckContributionLimits`: changes per day, fewer for a new account; a new account's links
  wait for a review (`config('atlas.community')`).
- `Actions\SetAnonymousCredit`, `Queries\PublicContributors`: the credit name, or none (D63).

**M17 (micro-tasks):**

- `Actions\GenerateVerificationTasks` (`atlas:tasks:generate`, hourly), `Models\VerificationTask`:
  one yes/no question per checkable value; a changed value makes the task stale (D64).
- `Queries\NextVerificationTask`: the next question for a person, highest review priority first,
  never one they answered or whose value they wrote.
- `Actions\AnswerVerificationTask`, `Models\VerificationAnswer`: one answer per person and task;
  agree and disagree queue `Jobs\EvaluateConsensusJob`.
- `Actions\EvaluateConsensus`: three agreeing answers and no dissent → `community_supported`, one
  "no" → `disputed`, both through `Knowledge\Actions\ApplyConsensus`; counts the agreeing answers.
- `Actions\PromoteTrustedContributors` (`atlas:trust:promote`, daily, and after each counted
  contribution): contributors with enough accepted contributions and account age become trusted (D65).

See [the module rules](../README.md).
