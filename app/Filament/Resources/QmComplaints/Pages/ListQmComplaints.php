<?php
namespace App\Filament\Resources\QmComplaints\Pages;

use App\Filament\Resources\QmComplaints\QmComplaintResource;
use App\Models\QmComplaint;
use App\Models\ClientApiIntegration;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListQmComplaints extends ListRecords
{
    protected static string $resource = QmComplaintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ নতুন Complaint'),

            Actions\Action::make('sync_client_db')
                ->label('🔄 Client DB Sync')
                ->color('info')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Client Database Sync')
                ->modalDescription('Active client API integration থেকে QM/SR data pull করে local cache update করবে।')
                ->modalSubmitActionLabel('হ্যাঁ, Sync করো')
                ->action(function () {
                    $integrations = ClientApiIntegration::where('is_active', true)->get();
                    if ($integrations->isEmpty()) {
                        Notification::make()->title('কোনো active integration নেই')->warning()->send();
                        return;
                    }
                    $total = 0;
                    foreach ($integrations as $integration) {
                        try { $total += \App\Services\ClientApiDiscoveryService::cloneAll($integration); }
                        catch (\Throwable $e) { \Log::warning("[ClientSync] {$integration->name}: " . $e->getMessage()); }
                    }
                    Notification::make()->title("✅ Sync সম্পন্ন — {$total} records")->success()->send();
                }),
        ];
    }

    public function getSubheading(): ?string
    {
        $total  = QmComplaint::count();
        $pending = QmComplaint::where('status', 'Pending')->count();
        $synced  = QmComplaint::whereNotNull('client_ticket_id')->count();
        $syncedTxt = $synced > 0 ? " | Client Synced: {$synced}" : '';
        return "Total: {$total} | Pending: {$pending}{$syncedTxt}";
    }
}
