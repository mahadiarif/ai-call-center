<?php
namespace App\Filament\Resources\QmPartsQueries\Pages;

use App\Filament\Resources\QmPartsQueries\QmPartsQueryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQmPartsQuery extends EditRecord
{
    protected static string $resource = QmPartsQueryResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}