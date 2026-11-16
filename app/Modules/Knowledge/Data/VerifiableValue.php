<?php

namespace App\Modules\Knowledge\Data;

/**
 * A value people can check with a yes/no question (PLAN M17): the current revision of a value slot,
 * with its author when a person wrote it (so they are not asked about their own value).
 */
final readonly class VerifiableValue
{
    public function __construct(
        public int $fieldValueId,
        public int $revisionId,
        public int $occupationId,
        public ?int $authorUserId,
        public int $priority,
    ) {}
}
