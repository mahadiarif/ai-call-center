<?php

namespace App\Filament\Widgets;

use App\Models\CallLog;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;

class CallBillingReportWidget extends Widget
{
    protected string $view = 'filament.widgets.call-billing-report-widget';

    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';
    protected ?string $pollingInterval = '60s';

    // Dashboard থেকে সরানো হয়েছে — Call Reports পেজে দেখাবে
    public static function canView(): bool { return false; }

    public array $monthly  = [];
    public array $summary  = [];

    public function mount(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        if (!Schema::hasTable('call_logs')) {
            $this->monthly = [];
            $this->summary = [];
            return;
        }

        // গত ৬ মাসের মাসিক রিপোর্ট
        $rows = CallLog::selectRaw("
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total_calls,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'dropped'   THEN 1 ELSE 0 END) as dropped,
                SUM(CASE WHEN status = 'missed'    THEN 1 ELSE 0 END) as missed,
                SUM(CASE WHEN status = 'completed' THEN duration ELSE 0 END) as total_seconds
            ")
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        $this->monthly = $rows->map(function ($row) {
            $minutes = round($row->total_seconds / 60, 1);
            $avgSec  = $row->completed > 0 ? round($row->total_seconds / $row->completed) : 0;
            $avgMin  = round($avgSec / 60, 1);
            return [
                'month'     => \Carbon\Carbon::createFromFormat('Y-m', $row->month)->format('F Y'),
                'total'     => $row->total_calls,
                'completed' => $row->completed,
                'dropped'   => $row->dropped,
                'missed'    => $row->missed,
                'minutes'   => $minutes,
                'avg_min'   => $avgMin,
            ];
        })->toArray();

        // সার্বিক সারাংশ
        $lifetimeSec   = CallLog::where('status', 'completed')->sum('duration');
        $lifetimeMin   = round($lifetimeSec / 60, 1);

        $todaySec      = CallLog::whereDate('created_at', today())->where('status', 'completed')->sum('duration');
        $todayMin      = round($todaySec / 60, 1);

        $monthSec      = CallLog::whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->where('status', 'completed')->sum('duration');
        $monthMin      = round($monthSec / 60, 1);

        $totalCalls    = CallLog::count();
        $completedAll  = CallLog::where('status', 'completed')->count();
        $avgSecAll     = $completedAll > 0 ? round($lifetimeSec / $completedAll) : 0;
        $avgMinAll     = round($avgSecAll / 60, 1);

        $this->summary = [
            'today_minutes'    => $todayMin,
            'month_minutes'    => $monthMin,
            'lifetime_minutes' => $lifetimeMin,
            'total_calls'      => $totalCalls,
            'completed_calls'  => $completedAll,
            'avg_minutes'      => $avgMinAll,
        ];
    }
}
