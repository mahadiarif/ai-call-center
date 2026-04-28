<?php
// Pages directory তৈরি করো
$dir = dirname(__DIR__) . '/app/Filament/Pages';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
    echo "✅ Directory created: $dir\n";
} else {
    echo "✅ Directory already exists\n";
}

// ১. AI Analytics Page
file_put_contents($dir . '/AiAnalytics.php', '<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class AiAnalytics extends Page
{
    protected static string $view = \'filament.pages.ai-analytics\';
    protected static ?string $navigationLabel = \'📊 AI এনালিটিক্স\';
    protected static ?string $navigationGroup = \'রিপোর্ট\';
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
            $labels[] = $date->format(\'d M\');
            $data[]   = \App\Models\AiTicket::whereDate(\'created_at\', $date->toDateString())->count();
        }
        $this->chartData = [\'labels\' => $labels, \'data\' => $data];

        $this->ticketStats = [
            \'total\'    => \App\Models\AiTicket::count(),
            \'pending\'  => \App\Models\AiTicket::where(\'status\', \'Pending\')->count(),
            \'resolved\' => \App\Models\AiTicket::where(\'status\', \'Resolved\')->count(),
            \'today\'    => \App\Models\AiTicket::whereDate(\'created_at\', today())->count(),
        ];
    }
}
');

// ২. Call Reports Page
file_put_contents($dir . '/CallReports.php', '<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class CallReports extends Page
{
    protected static string $view = \'filament.pages.call-reports\';
    protected static ?string $navigationLabel = \'⏱️ কল মিনিট রিপোর্ট\';
    protected static ?string $navigationGroup = \'রিপোর্ট\';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
    protected static ?int $navigationSort = 11;

    public array $monthly  = [];
    public array $summary  = [];

    public function mount(): void { $this->loadData(); }

    public function loadData(): void
    {
        $rows = \App\Models\CallLog::selectRaw("
            DATE_FORMAT(created_at, \'%Y-%m\') as month,
            COUNT(*) as total_calls,
            SUM(CASE WHEN status = \'completed\' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = \'dropped\'   THEN 1 ELSE 0 END) as dropped,
            SUM(CASE WHEN status = \'missed\'    THEN 1 ELSE 0 END) as missed,
            SUM(CASE WHEN status = \'completed\' THEN duration ELSE 0 END) as total_seconds
        ")
        ->where(\'created_at\', \'>=\', now()->subMonths(6))
        ->groupBy(\'month\')->orderBy(\'month\', \'desc\')->get();

        $this->monthly = $rows->map(function ($row) {
            $min = round($row->total_seconds / 60, 1);
            $avg = $row->completed > 0 ? round(($row->total_seconds / $row->completed) / 60, 1) : 0;
            return [
                \'month\'     => \Carbon\Carbon::createFromFormat(\'Y-m\', $row->month)->format(\'F Y\'),
                \'total\'     => $row->total_calls, \'completed\' => $row->completed,
                \'dropped\'   => $row->dropped,     \'missed\'    => $row->missed,
                \'minutes\'   => $min,              \'avg_min\'   => $avg,
            ];
        })->toArray();

        $lifeSec  = \App\Models\CallLog::where(\'status\',\'completed\')->sum(\'duration\');
        $todaySec = \App\Models\CallLog::whereDate(\'created_at\',today())->where(\'status\',\'completed\')->sum(\'duration\');
        $monSec   = \App\Models\CallLog::whereMonth(\'created_at\',now()->month)->whereYear(\'created_at\',now()->year)->where(\'status\',\'completed\')->sum(\'duration\');
        $comp     = \App\Models\CallLog::where(\'status\',\'completed\')->count();

        $this->summary = [
            \'today_minutes\'    => round($todaySec/60,1),
            \'month_minutes\'    => round($monSec/60,1),
            \'lifetime_minutes\' => round($lifeSec/60,1),
            \'total_calls\'      => \App\Models\CallLog::count(),
            \'completed_calls\'  => $comp,
            \'avg_minutes\'      => $comp > 0 ? round(($lifeSec/$comp)/60,1) : 0,
        ];
    }
}
');

echo "✅ AiAnalytics.php created\n";
echo "✅ CallReports.php created\n";
echo "\nNow run: php artisan view:clear && php artisan config:clear\n";
