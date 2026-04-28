<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class AiAnalytics extends Page
{
    protected string $view = 'filament.pages.ai-analytics';
    protected static ?string $navigationLabel = '📊 AI Analytics';
    protected static \UnitEnum|string|null $navigationGroup = 'রিপোর্ট';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static ?int $navigationSort = 10;

    public array $chartData = [];
    public array $ticketStats = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $labels = [];
        $data   = [];
        for ($i = 6; $i >= 0; $i--) {
            $date     = now()->subDays($i);
            $labels[] = $date->format('d M');
            $data[]   = \App\Models\AiTicket::whereDate('created_at', $date->toDateString())->count();
        }
        $this->chartData = ['labels' => $labels, 'data' => $data];

        $this->ticketStats = [
            'total'    => \App\Models\AiTicket::count(),
            'pending'  => \App\Models\AiTicket::where('status', 'Pending')->count(),
            'resolved' => \App\Models\AiTicket::where('status', 'Resolved')->count(),
            'today'    => \App\Models\AiTicket::whereDate('created_at', today())->count(),
        ];
    }
}
