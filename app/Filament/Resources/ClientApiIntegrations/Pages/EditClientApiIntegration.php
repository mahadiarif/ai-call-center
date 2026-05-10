<?php
namespace App\Filament\Resources\ClientApiIntegrations\Pages;
use App\Filament\Resources\ClientApiIntegrations\ClientApiIntegrationResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\DeleteAction;
class EditClientApiIntegration extends EditRecord {
    protected static string $resource = ClientApiIntegrationResource::class;
    protected function getHeaderActions(): array { return [DeleteAction::make()]; }
}