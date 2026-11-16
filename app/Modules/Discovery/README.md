# Discovery

Helping a person find work that fits their situation.

- **Owns:** the guided filter, comparing occupations, later recommendations.
- **Rules:** say "matches your situation", never "perfect job for you", and always show why and what
  the limits are. Guided-filter answers belong to the user. Sponsors never change results.

**M20 (guided filter and compare):**

- `Data\GuideQuestions`: the nine questions, their answers and the Wave 1 field each one reads;
  `Data\GuideAnswers` keeps only offered answers.
- `Queries\MatchOccupations`: the listed occupations that fit the answers, each with its criteria
  (met, partly, not known) and notes, best first (D72). It reads values through
  `Knowledge\Queries\MatchableValues`, which leaves out sensitive values that are not verified.
- `Http\Controllers\Web\{Guide,Compare}Controller`: `/guide` and `/compare`, both `noindex`; the
  answers are never stored (D73). The props come from `Publishing\Presenters\{Guide,Compare}Presenter`.

See [the module rules](../README.md).
