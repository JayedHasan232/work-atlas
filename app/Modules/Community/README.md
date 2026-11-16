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

See [the module rules](../README.md).
