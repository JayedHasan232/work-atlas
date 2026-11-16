<?php

namespace App\Modules\Knowledge\Events;

use App\Modules\Knowledge\Enums\FieldStatus;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * A slot's status changed without a new value (ChangeStatus). A new current value raises
 * CurrentValueChanged instead, which may change the status too. Dispatched inside the transaction.
 */
final readonly class FieldStatusChanged
{
    use Dispatchable;

    public function __construct(
        public int $occupationId,
        public int $fieldDefinitionId,
        public FieldStatus $from,
        public FieldStatus $to,
    ) {}
}
