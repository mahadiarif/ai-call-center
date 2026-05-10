<?php
namespace App\Filament\Resources\QmTickets\Pages;

use App\Filament\Resources\QmTickets\QmTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQmTickets extends ListRecords
{
    protected static string $resource = QmTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ নতুন QM Ticket'),
        ];
    }
}
