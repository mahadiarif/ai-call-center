<?php

namespace App\Filament\Resources\IvrServices\Pages;

use App\Filament\Resources\IvrServices\IvrServiceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewIvrService extends ViewRecord
{
    protected static string $resource = IvrServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
