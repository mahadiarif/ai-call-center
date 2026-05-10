<?php
namespace App\Filament\Resources\SrTickets\Pages;

use App\Filament\Resources\SrTickets\SrTicketResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSrTicket extends EditRecord
{
    protected static string $resource = SrTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}