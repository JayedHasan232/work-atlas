<?php

namespace App\Modules\Knowledge\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * The model half of D2: saving changes to an existing row, or deleting it, throws a LogicException
 * (a programming error, never a user error). Query-builder updates and deletes skip model events;
 * the Postgres trigger stops those.
 *
 * @mixin Model
 */
trait AppendOnly
{
    public static function bootAppendOnly(): void
    {
        static::updating(function (Model $model): never {
            throw self::appendOnlyViolation($model, 'update');
        });

        static::deleting(function (Model $model): never {
            throw self::appendOnlyViolation($model, 'delete');
        });
    }

    private static function appendOnlyViolation(Model $model, string $operation): LogicException
    {
        return new LogicException(sprintf(
            '%s is append-only: %s is not allowed. Record a new revision instead (a revert is a new revision).',
            $model::class,
            $operation,
        ));
    }
}
