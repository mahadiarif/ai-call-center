<?php

namespace App\Filament\Resources\IvrServices\Pages;

use App\Filament\Resources\IvrServices\IvrServiceResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateIvrService extends CreateRecord
{
    protected static string $resource = IvrServiceResource::class;

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::Full;
    }
}
