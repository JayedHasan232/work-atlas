<?php

namespace App\Modules\Community\Data;

use App\Modules\Knowledge\Data\SourceInput;

/**
 * A person's suggested change to one field of a public occupation (PLAN M16). The value is as the
 * form sent it; `locale` is the reader's language, used for a field whose value differs by language.
 * `declaredInterest` is the person's conflict-of-interest declaration, if they have one.
 */
final readonly class EditSuggestion
{
    /**
     * @param  list<SourceInput>  $sources
     */
    public function __construct(
        public int $userId,
        public int $occupationId,
        public string $fieldKey,
        public mixed $value,
        public string $locale,
        public array $sources = [],
        public ?string $comment = null,
        public ?string $declaredInterest = null,
    ) {}
}
