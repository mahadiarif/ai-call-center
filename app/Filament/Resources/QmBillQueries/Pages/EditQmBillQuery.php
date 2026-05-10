<?php
namespace App\Filament\Resources\QmBillQueries\Pages;

use App\Filament\Resources\QmBillQueries\QmBillQueryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQmBillQuery extends EditRecord
{
    protected static string $resource = QmBillQueryResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}