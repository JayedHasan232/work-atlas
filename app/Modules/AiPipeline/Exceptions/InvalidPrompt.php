<?php

namespace App\Modules\AiPipeline\Exceptions;

use RuntimeException;

final class InvalidPrompt extends RuntimeException
{
    public static function because(string $file, string $reason): self
    {
        return new self("{$file}: {$reason}");
    }
}
