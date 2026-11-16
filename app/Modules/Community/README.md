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

See [the module rules](../README.md).
