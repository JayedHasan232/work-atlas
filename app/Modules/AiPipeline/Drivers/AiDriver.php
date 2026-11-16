<?php

namespace App\Modules\AiPipeline\Drivers;

use App\Modules\AiPipeline\Data\CompletionRequest;
use App\Modules\AiPipeline\Data\CompletionResult;
use App\Modules\AiPipeline\Exceptions\ProviderError;

/**
 * One AI provider. Only AiGateway calls a driver (Arch/AiGatewayOnlyTest), and drivers are the only
 * code that talks to a provider. A driver never logs, validates or retries: the gateway does.
 */
interface AiDriver
{
    /**
     * @throws ProviderError
     */
    public function complete(CompletionRequest $request): CompletionResult;

    /**
     * Sends requests as one provider batch and returns the provider's batch id.
     *
     * @param  list<CompletionRequest>  $requests
     *
     * @throws ProviderError
     */
    public function submitBatch(array $requests): string;

    /**
     * The batch's results keyed by request id, or null while the provider is still working. (PHP turns
     * a numeric id like "12" into the integer key 12.)
     *
     * @return array<array-key, CompletionResult|ProviderError>|null
     *
     * @throws ProviderError
     */
    public function pollBatch(string $batchId): ?array;
}
