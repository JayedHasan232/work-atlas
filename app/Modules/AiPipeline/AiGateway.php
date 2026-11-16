<?php

namespace App\Modules\AiPipeline;

use App\Modules\AiPipeline\Data\AiRequest;
use App\Modules\AiPipeline\Data\AiResult;
use App\Modules\AiPipeline\Data\BatchSubmission;
use App\Modules\AiPipeline\Data\CompletionRequest;
use App\Modules\AiPipeline\Data\CompletionResult;
use App\Modules\AiPipeline\Data\CostEstimate;
use App\Modules\AiPipeline\Data\Prompt;
use App\Modules\AiPipeline\Drivers\AiDriver;
use App\Modules\AiPipeline\Enums\AiRunStatus;
use App\Modules\AiPipeline\Exceptions\BudgetExceeded;
use App\Modules\AiPipeline\Exceptions\ProviderError;
use App\Modules\AiPipeline\Exceptions\UnknownProvider;
use App\Modules\AiPipeline\Models\AiRun;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;
use Throwable;

/**
 * The one path to an AI model (CLAUDE.md, "One AI path"). For every model call it:
 *
 * 1. refuses before sending when the estimated cost is above `atlas.ai.max_batch_usd` or a price is
 *    unknown (BudgetExceeded; nothing is sent, no run is made);
 * 2. creates the `ai_runs` row before the call (status `running`);
 * 3. calls the provider's driver, retrying retryable errors with backoff;
 * 4. completes the row: raw output, tokens, cost from `atlas.ai.pricing`, and `succeeded` only when the
 *    output is JSON that matches the prompt's schema; otherwise `failed` with the error.
 *
 * It writes nothing but `ai_runs`: callers turn a succeeded result into revisions through Knowledge.
 * Callers run it inside a queued job (Jobs\RunAiCompletion, Jobs\PollAiBatch).
 */
final class AiGateway
{
    public function __construct(
        private readonly PromptRegistry $prompts,
        private readonly Container $container,
    ) {}

    public function complete(AiRequest $request): AiResult
    {
        $prompt = $this->prompts->get($request->promptKey, $request->promptVersion);
        $text = $prompt->render($request->values);
        $this->ensureWithinBudget($this->estimateRendered([[$request, $prompt, $text]]));

        $driver = $this->driver($request->provider);
        $run = $this->startRun($request, $prompt, $text);

        try {
            $result = $this->withRetry(fn (): CompletionResult => $driver->complete($this->completion($run, $prompt, $text)));
        } catch (ProviderError $error) {
            return $this->fail($run, $error->getMessage());
        } catch (Throwable $error) {
            $this->fail($run, 'Unexpected error: '.$error::class.': '.$error->getMessage());

            throw $error;
        }

        return $this->finish($run, $prompt, $result);
    }

    /**
     * A dry-run cost estimate for a set of requests. Nothing is sent and no run is made.
     *
     * @param  list<AiRequest>  $requests
     */
    public function estimate(array $requests): CostEstimate
    {
        return $this->estimateRendered(array_map(function (AiRequest $request): array {
            $prompt = $this->prompts->get($request->promptKey, $request->promptVersion);

            return [$request, $prompt, $prompt->render($request->values)];
        }, $requests));
    }

    /**
     * Sends requests to one provider as a batch. Each request gets its run before the batch is sent;
     * if the provider refuses the batch, every run fails with the error.
     *
     * @param  list<AiRequest>  $requests
     */
    public function submitBatch(array $requests): BatchSubmission
    {
        if ($requests === []) {
            throw new InvalidArgumentException('A batch needs at least one request.');
        }

        $providers = array_unique(array_map(fn (AiRequest $request): string => $request->provider, $requests));

        if (count($providers) !== 1) {
            throw new InvalidArgumentException('A batch goes to one provider; this one mixes '.implode(', ', $providers).'.');
        }

        $rendered = array_map(function (AiRequest $request): array {
            $prompt = $this->prompts->get($request->promptKey, $request->promptVersion);

            return [$request, $prompt, $prompt->render($request->values)];
        }, $requests);

        $this->ensureWithinBudget($this->estimateRendered($rendered));
        $driver = $this->driver($requests[0]->provider);

        /** @var list<array{AiRun, CompletionRequest}> $runs */
        $runs = DB::transaction(fn (): array => array_map(function (array $item): array {
            [$request, $prompt, $text] = $item;
            $run = $this->startRun($request, $prompt, $text);

            return [$run, $this->completion($run, $prompt, $text)];
        }, $rendered));

        $runIds = array_map(fn (array $pair): int => $pair[0]->id, $runs);

        try {
            $batchId = $this->withRetry(fn (): string => $driver->submitBatch(array_map(fn (array $pair): CompletionRequest => $pair[1], $runs)));
        } catch (Throwable $error) {
            $message = $error instanceof ProviderError ? $error->getMessage() : 'Unexpected error: '.$error::class.': '.$error->getMessage();

            foreach ($runs as [$run]) {
                $this->fail($run, "The batch was not accepted: {$message}");
            }

            if (! $error instanceof ProviderError) {
                throw $error;
            }

            return new BatchSubmission(null, $runIds, $message);
        }

        AiRun::query()->whereKey($runIds)->update(['batch_id' => $batchId]);

        return new BatchSubmission($batchId, $runIds);
    }

    /**
     * Collects a batch's results once the provider has them. Returns false while it is still working.
     */
    public function collectBatch(string $batchId): bool
    {
        $runs = AiRun::query()->where('batch_id', $batchId)->where('status', AiRunStatus::Running)->orderBy('id')->get();

        if ($runs->isEmpty()) {
            return true;
        }

        $first = $runs->firstOrFail();

        try {
            $results = $this->withRetry(fn (): ?array => $this->driver($first->provider)->pollBatch($batchId));
        } catch (ProviderError $error) {
            foreach ($runs as $run) {
                $this->fail($run, "The batch could not be collected: {$error->getMessage()}");
            }

            return true;
        }

        if ($results === null) {
            return false;
        }

        foreach ($runs as $run) {
            $result = $results[$run->id] ?? ProviderError::because($run->provider, 'the batch has no result for this request');

            $result instanceof ProviderError
                ? $this->fail($run, $result->getMessage())
                : $this->finish($run, $this->prompts->get($run->prompt_key, $run->prompt_version), $result);
        }

        return true;
    }

    private function startRun(AiRequest $request, Prompt $prompt, string $text): AiRun
    {
        return AiRun::query()->create([
            'provider' => $request->provider,
            'model' => $request->model,
            'prompt_key' => $prompt->key,
            'prompt_version' => $prompt->version,
            'input_hash' => hash('sha256', (string) json_encode([
                $request->provider, $request->model, $prompt->key, $prompt->version, $text,
                $prompt->maxOutputTokens, $prompt->temperature,
            ], JSON_UNESCAPED_UNICODE)),
            'status' => AiRunStatus::Running,
            'started_at' => now(),
        ]);
    }

    private function completion(AiRun $run, Prompt $prompt, string $text): CompletionRequest
    {
        return new CompletionRequest(
            id: (string) $run->id,
            model: $run->model,
            prompt: $text,
            schemaJson: $prompt->schemaJson,
            maxOutputTokens: $prompt->maxOutputTokens,
            temperature: $prompt->temperature,
        );
    }

    private function finish(AiRun $run, Prompt $prompt, CompletionResult $result): AiResult
    {
        $run->fill([
            'raw_output' => $result->rawOutput,
            'input_tokens' => $result->inputTokens,
            'output_tokens' => $result->outputTokens,
            'cost_usd' => $this->cost($run->provider, $run->model, $result->inputTokens, $result->outputTokens),
        ]);

        $error = $this->schemaError($result->rawOutput, $prompt->schemaJson);

        if ($error !== null) {
            return $this->fail($run, $error);
        }

        /** @var array<array-key, mixed> $output */
        $output = json_decode($result->rawOutput, true);
        $run->fill(['parsed_output' => $output, 'status' => AiRunStatus::Succeeded, 'finished_at' => now()])->save();

        return new AiResult($run->id, AiRunStatus::Succeeded, $output);
    }

    private function fail(AiRun $run, string $error): AiResult
    {
        $run->fill(['status' => AiRunStatus::Failed, 'error' => $error, 'finished_at' => now()])->save();

        return new AiResult($run->id, AiRunStatus::Failed, error: $error);
    }

    /**
     * Why the output is not acceptable, or null when it is a JSON object that matches the schema.
     */
    private function schemaError(string $raw, string $schemaJson): ?string
    {
        $data = json_decode($raw);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_object($data)) {
            return 'The output is not a JSON object.';
        }

        $result = (new Validator)->validate($data, $schemaJson);

        if ($result->isValid()) {
            return null;
        }

        $problems = [];

        foreach ((new ErrorFormatter)->format($result->error() ?? throw new LogicException('An invalid result has an error.')) as $path => $messages) {
            $problems[] = $path.': '.implode('; ', (array) $messages);
        }

        return 'The output does not match the schema: '.implode(' | ', array_slice($problems, 0, 5));
    }

    /**
     * @template T
     *
     * @param  callable(): T  $call
     * @return T
     */
    private function withRetry(callable $call): mixed
    {
        /** @var list<int> $backoff */
        $backoff = config('atlas.ai.retry.backoff_ms', [1000, 5000]);

        return retry(
            count($backoff) + 1,
            $call,
            fn (int $attempt): int => $backoff[$attempt - 1] ?? 0,
            fn (Throwable $error): bool => $error instanceof ProviderError && $error->retryable,
        );
    }

    private function driver(string $provider): AiDriver
    {
        $class = config("atlas.ai.providers.{$provider}.driver");

        if (! is_string($class) || ! is_a($class, AiDriver::class, true)) {
            throw UnknownProvider::named($provider);
        }

        return $this->container->make($class);
    }

    /**
     * @return array{input: float, output: float}|null USD per million tokens, or null when unknown
     */
    private function price(string $provider, string $model): ?array
    {
        $price = config("atlas.ai.pricing.{$provider}.{$model}") ?? config("atlas.ai.pricing.{$provider}.*");

        if (! is_array($price) || ! is_numeric($price['input_per_million'] ?? null) || ! is_numeric($price['output_per_million'] ?? null)) {
            return null;
        }

        return ['input' => (float) $price['input_per_million'], 'output' => (float) $price['output_per_million']];
    }

    private function cost(string $provider, string $model, ?int $inputTokens, ?int $outputTokens): ?string
    {
        $price = $this->price($provider, $model);

        if ($price === null || $inputTokens === null || $outputTokens === null) {
            return null;
        }

        return number_format(($inputTokens * $price['input'] + $outputTokens * $price['output']) / 1_000_000, 6, '.', '');
    }

    /**
     * Input tokens are guessed from the prompt's length (`atlas.ai.chars_per_token`, kept low so the
     * guess runs high); output tokens are the prompt's maximum.
     *
     * @param  list<array{AiRequest, Prompt, string}>  $items
     */
    private function estimateRendered(array $items): CostEstimate
    {
        $charsPerToken = max(0.1, (float) config('atlas.ai.chars_per_token', 1));
        $inputTokens = $outputTokens = 0;
        $usd = 0.0;
        $unpriced = [];

        foreach ($items as [$request, $prompt, $text]) {
            $input = (int) ceil(mb_strlen($text) / $charsPerToken);
            $inputTokens += $input;
            $outputTokens += $prompt->maxOutputTokens;
            $price = $this->price($request->provider, $request->model);

            if ($price === null) {
                $unpriced[] = "{$request->provider}/{$request->model}";

                continue;
            }

            $usd += ($input * $price['input'] + $prompt->maxOutputTokens * $price['output']) / 1_000_000;
        }

        $unpriced = array_values(array_unique($unpriced));

        return new CostEstimate(count($items), $inputTokens, $outputTokens, $unpriced === [] ? round($usd, 6) : null, $unpriced);
    }

    private function ensureWithinBudget(CostEstimate $estimate): void
    {
        if ($estimate->usd === null) {
            throw BudgetExceeded::unknownPrice($estimate->unpriced);
        }

        $cap = (float) config('atlas.ai.max_batch_usd', 0);

        if ($estimate->usd > $cap) {
            throw BudgetExceeded::overCap($estimate->usd, $cap);
        }
    }
}
