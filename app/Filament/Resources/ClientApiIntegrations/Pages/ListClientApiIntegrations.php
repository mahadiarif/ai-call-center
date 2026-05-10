<?php
namespace App\Filament\Resources\ClientApiIntegrations\Pages;
use App\Filament\Resources\ClientApiIntegrations\ClientApiIntegrationResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\CreateAction;
class ListClientApiIntegrations extends ListRecords {
    protected static string $resource = ClientApiIntegrationResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}