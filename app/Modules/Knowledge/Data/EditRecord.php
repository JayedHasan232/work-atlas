<?php

namespace App\Modules\Knowledge\Data;

use App\Modules\Knowledge\Enums\FieldStatus;
use App\Modules\Knowledge\Enums\ReviewDecision;
use Carbon\CarbonInterface;

/**
 * One revision as moderators see it (PLAN M18): who changed what, from what to what, with which
 * sources, comment and declared interest, and where it stands now. Unfiltered: sensitive values are
 * included, because only reviewers and above see moderation pages.
 */
final readonly class EditRecord
{
    /**
     * @param  list<array{url: string, title: string|null}>  $sources
     */
    public function __construct(
        public int $revisionId,
        public int $fieldValueId,
        public int $occupationId,
        public FieldDefinitionData $definition,
        public ?string $locale,
        public ?string $region,
        public ?int $authorUserId,
        public bool $proposed,
        public ?ReviewDecision $review,
        public bool $current,
        public ?int $revertsRevisionId,
        public mixed $oldValue,
        public mixed $newValue,
        public ?string $comment,
        public ?string $declaredInterest,
        public array $sources,
        public FieldStatus $slotStatus,
        public ?CarbonInterface $createdAt,
    ) {}

    /**
     * Every piece of text a person wrote in this edit: the strings in the value, the comment and the
     * declared interest.
     *
     * @return list<string>
     */
    public function texts(): array
    {
        $texts = [];
        $value = [$this->newValue];
        array_walk_recursive($value, function (mixed $item) use (&$texts): void {
            if (is_string($item)) {
                $texts[] = $item;
            }
        });

        foreach ([$this->comment, $this->declaredInterest] as $text) {
            if ($text !== null) {
                $texts[] = $text;
            }
        }

        return $texts;
    }
}
