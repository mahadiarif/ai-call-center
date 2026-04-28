<?php

namespace App\Filament\Widgets;

use App\Models\AiTicket;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Schema;

class WeeklyTicketsChartWidget extends ChartWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    // Dashboard থেকে সরানো হয়েছে — AI Analytics পেজে দেখাবে
    public static function canView(): bool { return false; }

    protected ?string $heading = '📊 গত ৭ দিনের টিকেট ট্রেন্ড';

    protected function getData(): array
    {
        $labels = [];
        $data   = [];

        for ($i = 6; $i >= 0; $i--) {
            $date     = now()->subDays($i);
            $labels[] = $date->format('d M'); // যেমন: 17 Apr

            $count = Schema::hasTable('ai_tickets')
                ? AiTicket::whereDate('created_at', $date->toDateString())->count()
                : 0;

            $data[] = $count;
        }

        return [
            'datasets' => [
                [
                    'label'           => 'AI টিকেট',
                    'data'            => $data,
                    'backgroundColor' => 'rgba(251, 191, 36, 0.2)', // হালকা হলুদ
                    'borderColor'     => 'rgb(251, 191, 36)',        // গাঢ় হলুদ
                    'borderWidth'     => 2,
                    'fill'            => true,
                    'tension'         => 0.4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
