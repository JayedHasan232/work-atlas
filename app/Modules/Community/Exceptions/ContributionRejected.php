<?php

namespace App\Modules\Community\Exceptions;

use RuntimeException;

/**
 * A report or suggestion that cannot be taken: the occupation has no public page, the field does not
 * exist or cannot be suggested, or the message is empty or too long. `reason` is a translation key
 * under `contribute.errors`.
 */
final class ContributionRejected extends RuntimeException
{
    private function __construct(
        string $message,
        public readonly string $reason,
    ) {
        parent::__construct($message);
    }

    public static function because(string $reason, string $message): self
    {
        return new self($message, $reason);
    }
}
