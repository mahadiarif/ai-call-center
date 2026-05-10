<?php
namespace App\Filament\Resources\SrTickets\Pages;

use App\Filament\Resources\SrTickets\SrTicketResource;
use App\Models\SrTicket;
use App\Models\ClientApiIntegration;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListSrTickets extends ListRecords
{
    protected static string $resource = SrTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('+ নতুন SR Ticket'),

            // 📤 Unsynced SR tickets Walton API তে push করো
            Actions\Action::make('push_to_walton')
                ->label('📤 Push to Walton')
                ->color('success')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Walton API তে SR Push করুন')
                ->modalDescription('যেসব SR ticket এখনো Walton এ push হয়নি (Client SR ID নেই), সেগুলো এখন push করবে।')
                ->modalSubmitActionLabel('হ্যাঁ, Push করো')
                ->action(function () {
                    $unsynced = SrTicket::whereNull('client_ticket_id')
                        ->whereNotNull('mobile_number')
                        ->latest()->take(50)->get();

                    if ($unsynced->isEmpty()) {
                        Notification::make()->title('সব SR ইতিমধ্যে synced!')->success()->send();
                        return;
                    }

                    $success = 0; $failed = 0;
                    foreach ($unsynced as $ticket) {
                        try {
                            \App\Services\ClientApiPushService::pushSrTicket($ticket);
                            $ticket->refresh();
                            $ticket->client_ticket_id ? $success++ : $failed++;
                        } catch (\Throwable $e) {
                            $failed++;
                            \Log::error("[PushToWalton] SR #{$ticket->id}: " . $e->getMessage());
                        }
                    }

                    Notification::make()
                        ->title("📤 Push সম্পন্ন — ✅ {$success} success, ❌ {$failed} failed")
                        ->success()->send();
                }),

            // 🔄 Client DB থেকে SR sync করো
            Actions\Action::make('sync_client_sr')
                ->label('🔄 Client DB Sync')
                ->color('info')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Client Database থেকে SR Sync করুন')
                ->modalDescription('সব active client API integration থেকে SR data pull করে local cache update করবে। এরপর list এ "Client Synced" filter দিয়ে দেখতে পারবেন।')
                ->modalSubmitActionLabel('হ্যাঁ, Sync করো')
                ->action(function () {
                    $integrations = ClientApiIntegration::where('is_active', true)->get();
                    if ($integrations->isEmpty()) {
                        Notification::make()->title('কোনো active integration নেই')->warning()->send();
                        return;
                    }
                    $total = 0;
                    foreach ($integrations as $integration) {
                        try {
                            $total += \App\Services\ClientApiDiscoveryService::cloneAll($integration);
                        } catch (\Throwable $e) {
                            \Log::warning("[SyncClientSR] {$integration->name}: " . $e->getMessage());
                        }
                    }
                    Notification::make()
                        ->title("✅ Sync সম্পন্ন — {$total} records update হয়েছে")
                        ->success()->send();
                }),
        ];
    }

    public function getSubheading(): ?string
    {
        $total    = SrTicket::count();
        $pending  = SrTicket::where('status', 'Pending')->count();
        $today    = SrTicket::whereDate('created_at', today())->count();
        $synced   = SrTicket::whereNotNull('client_ticket_id')->count();
        $syncedTxt = $synced > 0 ? " | Client Synced: {$synced}" : '';
        return "Total: {$total} | Pending: {$pending} | Today: {$today}{$syncedTxt}";
    }
}
