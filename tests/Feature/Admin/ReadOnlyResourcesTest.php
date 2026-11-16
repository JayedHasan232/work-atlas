<?php

use App\Support\Filament\ReadOnlyResource;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

/*
 * Every admin resource is read-only until a milestone gives it an action (M8). This keeps the one
 * write path (D3): the Knowledge resources read revisions but can never change them.
 */

it('registers the module resources', function () {
    expect(Filament::getPanel('admin')->getResources())->toHaveCount(4);
});

it('gives every admin resource only list and view pages, and no create, edit or delete', function () {
    $record = new class extends Model {};

    foreach (Filament::getPanel('admin')->getResources() as $resource) {
        /** @var class-string<resource> $resource */
        expect(is_subclass_of($resource, ReadOnlyResource::class))->toBeTrue("{$resource} is not read-only")
            ->and(array_keys($resource::getPages()))->toBe(['index', 'view'])
            ->and($resource::canCreate())->toBeFalse()
            ->and($resource::canEdit($record))->toBeFalse()
            ->and($resource::canDelete($record))->toBeFalse()
            ->and($resource::canDeleteAny())->toBeFalse();
    }
});
