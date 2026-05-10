<?php
namespace App\Filament\Resources\QmComplaints\Pages;

use App\Filament\Resources\QmComplaints\QmComplaintResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQmComplaint extends EditRecord
{
    protected static string $resource = QmComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}