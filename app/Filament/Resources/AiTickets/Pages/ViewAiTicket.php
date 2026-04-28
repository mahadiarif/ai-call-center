<?php

namespace App\Filament\Resources\AiTickets\Pages;

use App\Filament\Resources\AiTickets\AiTicketResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAiTicket extends ViewRecord
{
    protected static string $resource = AiTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
