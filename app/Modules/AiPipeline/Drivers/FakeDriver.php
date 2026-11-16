<?php

namespace App\Modules\AiPipeline\Drivers;

use App\Modules\AiPipeline\Data\CompletionRequest;
use App\Modules\AiPipeline\Data\CompletionResult;
use App\Modules\AiPipeline\Exceptions\ProviderError;

/**
 * A provider that answers from a queue of scripted responses, for tests and local runs. It never
 * makes a network call. With nothing queued, a call fails as a provider error.
 *
 * Tests bind one instance: `app()->instance(FakeDriver::class, $fake)` (see fakeAi() in tests/Pest.php).
 */
final class FakeDriver implements AiDriver
{
    /** @var list<CompletionResult|ProviderError> */
    private array $responses = [];

    /** @var list<CompletionRequest> */
    private array $requests = [];

    /** @var array<string, list<CompletionRequest>> */
    private array $batches = [];

    private int $pendingPolls = 0;

    public function respond(string $rawOutput, int $inputTokens = 100, int $outputTokens = 50): self
    {
        $this->responses[] = new CompletionResult($rawOutput, $inputTokens, $outputTokens);

        return $this;
    }

    /**
     * @param  array<array-key, mixed>  $output
     */
    public function respondWithJson(array $output, int $inputTokens = 100, int $outputTokens = 50): self
    {
        return $this->respond((string) json_encode($output, JSON_UNESCAPED_UNICODE), $inputTokens, $outputTokens);
    }

    public function failWith(ProviderError $error): self
    {
        $this->responses[] = $error;

        return $this;
    }

    /**
     * The next polls of any batch report "still working".
     */
    public function holdBatches(int $polls): self
    {
        $this->pendingPolls = $polls;

        return $this;
    }

    /**
     * Every request this driver received, batched ones included.
     *
     * @return list<CompletionRequest>
     */
    public function requests(): array
    {
        return $this->requests;
    }

    public function complete(CompletionRequest $request): CompletionResult
    {
        $this->requests[] = $request;
        $response = $this->next();

        if ($response instanceof ProviderError) {
            throw $response;
        }

        return $response;
    }

    public function submitBatch(array $requests): string
    {
        $id = 'fake-batch-'.(count($this->batches) + 1);
        $this->batches[$id] = $requests;
        array_push($this->requests, ...$requests);

        return $id;
    }

    public function pollBatch(string $batchId): ?array
    {
        if (! isset($this->batches[$batchId])) {
            throw ProviderError::because('fake', "unknown batch {$batchId}");
        }

        if ($this->pendingPolls > 0) {
            $this->pendingPolls--;

            return null;
        }

        $results = [];

        foreach ($this->batches[$batchId] as $request) {
            $results[$request->id] = $this->next();
        }

        return $results;
    }

    private function next(): CompletionResult|ProviderError
    {
        return array_shift($this->responses) ?? ProviderError::because('fake', 'no fake response is queued');
    }
}
