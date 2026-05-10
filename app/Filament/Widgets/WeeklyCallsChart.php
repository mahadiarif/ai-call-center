<?php

namespace App\Filament\Widgets;

use App\Models\CallLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class WeeklyCallsChart extends ChartWidget
{
    protected ?string $heading = 'Weekly Call Volume';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = CallLog::select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Calls',
                    'data' => $data->pluck('count')->toArray(),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data->pluck('date')->map(fn($d) => date('M d', strtotime($d)))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
