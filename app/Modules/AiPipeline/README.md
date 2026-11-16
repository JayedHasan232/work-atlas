# AiPipeline

Every call to an AI model.

- **Owns:** `AiGateway` (the one AI path), `ai_runs` (model, prompt version, input hash, raw output,
  cost, time), the prompt registry (`data/prompts/<wave>/<name>.vN.md`, schemas in `data/schemas/`),
  drafts, two-model cross-checks.
- **Rules (each has a test):** every model call goes through `AiGateway` inside a queued job and
  creates an `ai_runs` row, failures included. No provider SDK or HTTP call anywhere else. Output is
  structured and validated against a JSON schema; `"unknown"` is a valid answer. Prompt and schema
  files never change in place; a change is a new `vN` file. Tests use the fake driver; a paid run
  needs the maintainer's go-ahead and a dry-run cost estimate.

## How a call works (M9)

```php
$result = app(AiGateway::class)->complete(new AiRequest(
    promptKey: 'wave1/fields', promptVersion: 1,
    values: ['occupation' => '…', 'isco_code' => '7411'],
    provider: 'fake', model: 'fake-model',
));
// $result->succeeded(), $result->output (the parsed JSON), $result->runId
```

1. `PromptRegistry` reads `data/prompts/<key>.v<N>.md` and its schema (front matter, PLAN D42) and
   fills the `{{ placeholders }}`.
2. The gateway estimates the cost and refuses above `AI_MAX_BATCH_USD` (0 by default) or for a model
   without a price (D41). `estimate()` is the dry run.
3. It creates the `ai_runs` row (`running`), calls the driver with retries, then stores the raw output,
   tokens and cost, and marks the run `succeeded` only if the output matches the schema (D43).
4. Batches: `submitBatch()` makes a run per request and sends them together; the `PollAiBatch` job
   calls `collectBatch()` until the provider is done.

Run it from a job (`Jobs\RunAiCompletion`, `Jobs\PollAiBatch`), never inside a web request.

**Drivers.** Only `Drivers\FakeDriver` exists; the two real providers wait for O3. In tests, `fakeAi()`
(tests/Pest.php) binds a `FakeDriver` you script with `respondWithJson()`, `respond()`, `failWith()` and
`holdBatches()`, and points the registry at `tests/Fixtures/{prompts,schemas}`.

**Wave 1 (M12).** `Actions\GenerateWave1` runs prompt `wave1/fields` (built by `BuildWave1Requests`
from the field definitions and the occupation's context) on model A and model B
(`atlas.ai.tasks.wave1`), and `ApplyWave1` writes A's value, B's second opinion and the cross-check
(`AgreementCalculator`), all or nothing (PLAN D50, D51). `php artisan atlas:wave1:generate
--occupations=<slugs>|--all --dry-run` prints the estimate; without `--dry-run` it checks the cap and
queues `Jobs\GenerateWave1Job` per occupation.

See [the module rules](../README.md).
