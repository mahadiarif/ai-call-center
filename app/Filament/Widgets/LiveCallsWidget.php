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

        $recentCalls = ServiceRequest::with('ivrService')
            ->latest()
            ->take(10)
            ->get();

        return compact('activeCalls', 'todayTotal', 'lastHour', 'currentTime', 'recentCalls');
    }
}
