<?php

namespace App\Modules\Community\Filament\Resources\Users\Pages;

use App\Modules\Community\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return UserResource::blockActions();
    }
}
