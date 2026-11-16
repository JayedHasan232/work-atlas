<?php

namespace App\Support\Filament;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

/**
 * An admin resource that only lists and shows records. Every admin resource extends it until a
 * milestone adds an action (the writes then go through that module's Actions, never Eloquent here).
 * `Feature/Admin/ReadOnlyResourcesTest` checks that each resource has no create, edit or delete.
 */
abstract class ReadOnlyResource extends Resource
{
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return false;
    }

    public static function canForceDeleteAny(): bool
    {
        return false;
    }

    public static function canReorder(): bool
    {
        return false;
    }

    public static function canReplicate(Model $record): bool
    {
        return false;
    }

    public static function canRestore(Model $record): bool
    {
        return false;
    }

    public static function canRestoreAny(): bool
    {
        return false;
    }
}
