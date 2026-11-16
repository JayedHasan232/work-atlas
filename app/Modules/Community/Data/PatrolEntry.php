<?php

namespace App\Modules\Community\Data;

use App\Modules\Community\Enums\FlagReason;
use App\Modules\Knowledge\Data\EditRecord;

/**
 * One edit in the patrol queue (PLAN M18), with its promotional-edit flags.
 */
final readonly class PatrolEntry
{
    /**
     * @param  list<array{reason: FlagReason, detail: string|null}>  $flags
     */
    public function __construct(
        public EditRecord $edit,
        public array $flags,
        public bool $patrolled,
    ) {}
}
