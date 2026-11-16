<?php

namespace App\Modules\AiPipeline\Exceptions;

use RuntimeException;

/**
 * A provider refused or failed a call. A retryable error (rate limit, overload, timeout) is tried
 * again with backoff; any other ends the run as failed.
 */
final class ProviderError extends RuntimeException
{
    public function __construct(string $message, public readonly bool $retryable = false)
    {
        parent::__construct($message);
    }

    public static function because(string $provider, string $reason, bool $retryable = false): self
    {
        return new self("{$provider}: {$reason}", $retryable);
    }
}
