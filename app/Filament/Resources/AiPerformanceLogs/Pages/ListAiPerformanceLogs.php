<?php

namespace App\Filament\Resources\AiPerformanceLogs\Pages;

use App\Filament\Resources\AiPerformanceLogs\AiPerformanceLogResource;
use Filament\Resources\Pages\ListRecords;

class ListAiPerformanceLogs extends ListRecords
{
    protected static string $resource = AiPerformanceLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
