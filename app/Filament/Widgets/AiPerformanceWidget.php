<?php

namespace App\Filament\Widgets;

use App\Models\AiPerformanceLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class AiPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 2; // StatsOverview এর পরে
    protected ?string $pollingInterval = '60s';
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        if (!Schema::hasTable('ai_performance_logs')) {
            return [];
        }

        $avgScore     = (int) AiPerformanceLog::avg('score');
        $totalLogs    = AiPerformanceLog::count();
        $poorCalls    = AiPerformanceLog::where('score', '<', 50)->count();
        $todayAvg     = (int) AiPerformanceLog::whereDate('created_at', today())->avg('score');

        // সবচেয়ে বেশি miss হওয়া field
        $allMissing = AiPerformanceLog::whereNotNull('missing_fields')->pluck('missing_fields')->flatten();
        $topMissing = $allMissing->countBy()->sortDesc()->keys()->first() ?? 'N/A';

        return [
            Stat::make('🎯 গড় AI Score', $avgScore . '%')
                ->description("আজকে: {$todayAvg}% | মোট কল: {$totalLogs}")
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($avgScore >= 80 ? 'success' : ($avgScore >= 50 ? 'warning' : 'danger')),

            Stat::make('❌ দুর্বল কল', $poorCalls)
                ->description('৫০% এর নিচে score পেয়েছে')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($poorCalls > 5 ? 'danger' : 'warning'),

            Stat::make('⚠️ সবচেয়ে বেশি মিস', $topMissing)
                ->description('এই field টি AI সবচেয়ে বেশি miss করছে')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
        ];
    }
}
