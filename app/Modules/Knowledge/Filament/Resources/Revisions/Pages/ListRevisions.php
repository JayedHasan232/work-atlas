<?php

namespace App\Modules\Knowledge\Filament\Resources\Revisions\Pages;

use App\Modules\Knowledge\Filament\Resources\Revisions\RevisionResource;
use Filament\Resources\Pages\ListRecords;

final class ListRevisions extends ListRecords
{
    protected static string $resource = RevisionResource::class;
}
