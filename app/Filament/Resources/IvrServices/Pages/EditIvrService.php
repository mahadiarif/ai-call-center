<?php

namespace App\Filament\Resources\IvrServices\Pages;

use App\Filament\Resources\IvrServices\IvrServiceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditIvrService extends EditRecord
{
    protected static string $resource = IvrServiceResource::class;

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
