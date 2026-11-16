<?php

namespace App\Modules\Knowledge\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A slot's current value changed: a direct write, an accepted proposal or a revert. Dispatched inside
 * the write's transaction, so a listener's own writes (Taxonomy's copy of the names, D44) commit or
 * roll back with it. A proposal that is still pending doesn't change the current value.
 */
final readonly class CurrentValueChanged
{
    use Dispatchable;

    public function __construct(
        public int $occupationId,
        public int $fieldDefinitionId,
        public string $fieldKey,
        public ?string $locale,
        public ?string $region,
        public mixed $value,
    ) {}
}
