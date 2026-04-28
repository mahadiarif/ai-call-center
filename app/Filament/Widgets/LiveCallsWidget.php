<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Schema;

class LiveCallsWidget extends Widget
{
    protected string $view = 'filament.widgets.live-calls-widget';

    // প্রতি ৫ সেকেন্ডে auto refresh
    protected ?string $pollingInterval = '5s';

    protected static ?int $sort = -2;

    protected int | string | array $columnSpan = 'full';

    public int $activeCalls = 0;
    public int $todayTotal  = 0;
    public int $lastHour    = 0;
    public string $currentTime = '';
    public $recentCalls;

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->currentTime = now()->format('H:i:s');
        $this->recentCalls = collect();

        if (!Schema::hasTable('service_requests')) return;

        $this->todayTotal  = ServiceRequest::whereDate('created_at', today())->count();
        $this->lastHour    = ServiceRequest::where('created_at', '>=', now()->subHour())->count();
        $this->activeCalls = ServiceRequest::where('status', 'Pending')
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();
        $this->recentCalls = ServiceRequest::with('ivrService')
            ->latest()
            ->take(10)
            ->get();
    }
}
