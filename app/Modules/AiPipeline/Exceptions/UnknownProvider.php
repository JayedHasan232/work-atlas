<?php

namespace App\Modules\AiPipeline\Exceptions;

use RuntimeException;

final class UnknownProvider extends RuntimeException
{
    public static function named(string $provider): self
    {
        return new self("No driver is configured for the provider \"{$provider}\" (atlas.ai.providers).");
    }
}
