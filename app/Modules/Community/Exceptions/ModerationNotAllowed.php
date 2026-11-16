<?php

namespace App\Modules\Community\Exceptions;

use RuntimeException;

/**
 * The person may not use a moderation tool (PLAN M18): not a reviewer, or blocked.
 */
final class ModerationNotAllowed extends RuntimeException
{
    public static function because(string $message): self
    {
        return new self($message);
    }
}
