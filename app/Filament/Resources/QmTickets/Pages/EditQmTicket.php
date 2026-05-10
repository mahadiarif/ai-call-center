<?php
namespace App\Filament\Resources\QmTickets\Pages;

use App\Filament\Resources\QmTickets\QmTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQmTicket extends EditRecord
{
    protected static string $resource = QmTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
