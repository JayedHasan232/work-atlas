<?php

namespace App\Modules\Community\Data;

/**
 * A reader's error report (PLAN M16): about a public occupation, optionally one field (by key), in the
 * language the reader used. `userId` is set only when the reader is signed in.
 */
final readonly class ErrorReportInput
{
    public function __construct(
        public int $occupationId,
        public ?string $fieldKey,
        public string $message,
        public string $locale,
        public ?int $userId = null,
    ) {}
}
