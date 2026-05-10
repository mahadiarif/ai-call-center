<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Schema;

class LiveCallsWidget extends Widget
{
    protected string $view = 'filament.widgets.live-calls-widget';

    // প্রতি ২ সেকেন্ডে auto refresh — real-time live call monitor
    protected ?string $pollingInterval = '2s';

    protected static ?int $sort = -2;

    protected int | string | array $columnSpan = 'full';

    // getViewData() প্রতিটি render/poll-এ fresh data দেয়
    protected function getViewData(): array
    {
        $activeCalls = 0;
        $todayTotal  = 0;
        $lastHour    = 0;
        $currentTime = now()->format('H:i:s');
        $recentCalls = collect();

        if (!Schema::hasTable('service_requests')) {
            return compact('activeCalls', 'todayTotal', 'lastHour', 'currentTime', 'recentCalls');
        }

        // 20 মিনিটের বেশি পুরনো Incoming SR auto-clear করো (কল কেটে গেলে)
        // NOTE: real call 15+ মিনিট হতে পারে — তাই 20 মিনিট threshold
        ServiceRequest::where('status', 'Incoming')
            ->where('created_at', '<', now()->subMinutes(20))
            ->update(['status' => 'Drop Call']);

        $todayTotal = ServiceRequest::whereDate('created_at', today())->count();
        $lastHour   = ServiceRequest::where('created_at', '>=', now()->subHour())->count();

        // Live count — Incoming + created within last 20 minutes only
        $incomingSr = ServiceRequest::where('status', 'Incoming')
            ->where('created_at', '>=', now()->subMinutes(20))
            ->count();

        // activeCalls = শুধু Incoming ServiceRequest — dropped/ended call গণনায় নেই
        $activeCalls = $incomingSr;

        // AI Performance (Suggestion #5) - Safe Check
        $avgLatency = 0;
        if (Schema::hasTable('ai_performance_logs') && Schema::hasColumn('ai_performance_logs', 'latency_ms')) {
            $avgLatency = \DB::table('ai_performance_logs')
                ->where('created_at', '>=', now()->subHours(24))
                ->avg('latency_ms') ?? 0;
        }

        $recentCalls = ServiceRequest::with('ivrService')
            ->latest()
            ->take(10)
            ->get();

        return compact('activeCalls', 'todayTotal', 'lastHour', 'currentTime', 'recentCalls', 'avgLatency');
    }

    public function transferCall($id)
    {
        $sr = ServiceRequest::find($id);
        if (!$sr || $sr->status !== 'Incoming') return;

        try {
            // Call the bridge API to transfer
            $resp = \Http::post(url('/api/bridge/transfer-to-agent'), [
                'service_request_id' => $id,
                'caller_number' => $sr->mobile_number,
                'reason' => 'manual_intervention',
            ]);

            \Filament\Notifications\Notification::make()
                ->title('Transferring to Agent...')
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Transfer Failed')
                ->danger()
                ->send();
        }
    }

    public function hangupCall($id)
    {
        $sr = ServiceRequest::find($id);
        if (!$sr) return;

        try {
            // Logic to hangup via Asterisk AMI or Bridge API
            // For now, we update status and let the bridge handle the disconnect
            $sr->update(['status' => 'Drop Call']);

            \Filament\Notifications\Notification::make()
                ->title('Call Terminated')
                ->warning()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Action Failed')
                ->danger()
                ->send();
        }
    }
}
