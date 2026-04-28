<?php

namespace App\Filament\Resources\IvrServices\Pages;

use App\Filament\Resources\IvrServices\IvrServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIvrServices extends ListRecords
{
    protected static string $resource = IvrServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
