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

See [the module rules](../README.md).
