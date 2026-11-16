<?php

namespace App\Modules\Community\Filament\Resources\Users\Pages;

use App\Modules\Community\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;

final class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
