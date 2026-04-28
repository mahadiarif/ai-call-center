<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class CallReports extends Page
{
    protected string $view = 'filament.pages.call-reports';
    protected static ?string $navigationLabel = '⏱️ Call Reports';
    protected static \UnitEnum|string|null $navigationGroup = 'রিপোর্ট';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
    protected static ?int $navigationSort = 11;

    public string $filter  = 'daily';  // daily, weekly, monthly, yearly, lifetime
    public array  $data    = [];
    public array  $summary = [];

    public function mount(): void { $this->loadData(); }
    
    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->loadData();
    }

    public function loadData(): void
    {
        // Load summary cards (always show)
        $lifeSec  = \App\Models\CallLog::where('status','completed')->sum('duration');
        $todaySec = \App\Models\CallLog::whereDate('created_at',today())->where('status','completed')->sum('duration');
        $weekSec  = \App\Models\CallLog::whereBetween('created_at',[now()->startOfWeek(),now()->endOfWeek()])->where('status','completed')->sum('duration');
        $monSec   = \App\Models\CallLog::whereMonth('created_at',now()->month)->whereYear('created_at',now()->year)->where('status','completed')->sum('duration');
        $yearSec  = \App\Models\CallLog::whereYear('created_at',now()->year)->where('status','completed')->sum('duration');
        $comp     = \App\Models\CallLog::where('status','completed')->count();

        $this->summary = [
            'today_minutes'    => round($todaySec/60,1),
            'week_minutes'     => round($weekSec/60,1),
            'month_minutes'    => round($monSec/60,1),
            'year_minutes'     => round($yearSec/60,1),
            'lifetime_minutes' => round($lifeSec/60,1),
            'total_calls'      => \App\Models\CallLog::count(),
            'completed_calls'  => $comp,
            'avg_minutes'      => $comp > 0 ? round(($lifeSec/$comp)/60,1) : 0,
        ];

        // Load data based on filter
        switch($this->filter) {
            case 'daily':
                $this->loadDailyData();
                break;
            case 'weekly':
                $this->loadWeeklyData();
                break;
            case 'monthly':
                $this->loadMonthlyData();
                break;
            case 'yearly':
                $this->loadYearlyData();
                break;
            case 'lifetime':
                $this->loadLifetimeData();
                break;
            default:
                $this->loadDailyData();
        }
    }

    private function loadDailyData(): void
    {
        $rows = \App\Models\CallLog::selectRaw("
            DATE(created_at) as date,
            COUNT(*) as total_calls,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'dropped'   THEN 1 ELSE 0 END) as dropped,
            SUM(CASE WHEN status = 'missed'    THEN 1 ELSE 0 END) as missed,
            SUM(CASE WHEN status = 'completed' THEN duration ELSE 0 END) as total_seconds
        ")
        ->where('created_at', '>=', now()->subDays(30))
        ->groupBy('date')->orderBy('date', 'desc')->get();

        $this->data = $rows->map(function ($row) {
            $min = round($row->total_seconds / 60, 1);
            $avg = $row->completed > 0 ? round(($row->total_seconds / $row->completed) / 60, 1) : 0;
            return [
                'period'    => \Carbon\Carbon::parse($row->date)->format('d M Y'),
                'total'     => $row->total_calls,
                'completed' => $row->completed,
                'dropped'   => $row->dropped,
                'missed'    => $row->missed,
                'minutes'   => $min,
                'avg_min'   => $avg,
            ];
        })->toArray();
    }

    private function loadWeeklyData(): void
    {
        $rows = \App\Models\CallLog::selectRaw("
            YEARWEEK(created_at, 1) as week,
            MIN(DATE(created_at)) as week_start,
            MAX(DATE(created_at)) as week_end,
            COUNT(*) as total_calls,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'dropped'   THEN 1 ELSE 0 END) as dropped,
            SUM(CASE WHEN status = 'missed'    THEN 1 ELSE 0 END) as missed,
            SUM(CASE WHEN status = 'completed' THEN duration ELSE 0 END) as total_seconds
        ")
        ->where('created_at', '>=', now()->subWeeks(12))
        ->groupBy('week')->orderBy('week', 'desc')->get();

        $this->data = $rows->map(function ($row) {
            $min = round($row->total_seconds / 60, 1);
            $avg = $row->completed > 0 ? round(($row->total_seconds / $row->completed) / 60, 1) : 0;
            $start = \Carbon\Carbon::parse($row->week_start)->format('d M');
            $end = \Carbon\Carbon::parse($row->week_end)->format('d M Y');
            return [
                'period'    => "Week: {$start} - {$end}",
                'total'     => $row->total_calls,
                'completed' => $row->completed,
                'dropped'   => $row->dropped,
                'missed'    => $row->missed,
                'minutes'   => $min,
                'avg_min'   => $avg,
            ];
        })->toArray();
    }

    private function loadMonthlyData(): void
    {
        $rows = \App\Models\CallLog::selectRaw("
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as total_calls,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'dropped'   THEN 1 ELSE 0 END) as dropped,
            SUM(CASE WHEN status = 'missed'    THEN 1 ELSE 0 END) as missed,
            SUM(CASE WHEN status = 'completed' THEN duration ELSE 0 END) as total_seconds
        ")
        ->where('created_at', '>=', now()->subMonths(12))
        ->groupBy('month')->orderBy('month', 'desc')->get();

        $this->data = $rows->map(function ($row) {
            $min = round($row->total_seconds / 60, 1);
            $avg = $row->completed > 0 ? round(($row->total_seconds / $row->completed) / 60, 1) : 0;
            return [
                'period'    => \Carbon\Carbon::createFromFormat('Y-m', $row->month)->format('F Y'),
                'total'     => $row->total_calls,
                'completed' => $row->completed,
                'dropped'   => $row->dropped,
                'missed'    => $row->missed,
                'minutes'   => $min,
                'avg_min'   => $avg,
            ];
        })->toArray();
    }

    private function loadYearlyData(): void
    {
        $rows = \App\Models\CallLog::selectRaw("
            YEAR(created_at) as year,
            COUNT(*) as total_calls,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'dropped'   THEN 1 ELSE 0 END) as dropped,
            SUM(CASE WHEN status = 'missed'    THEN 1 ELSE 0 END) as missed,
            SUM(CASE WHEN status = 'completed' THEN duration ELSE 0 END) as total_seconds
        ")
        ->groupBy('year')->orderBy('year', 'desc')->get();

        $this->data = $rows->map(function ($row) {
            $min = round($row->total_seconds / 60, 1);
            $avg = $row->completed > 0 ? round(($row->total_seconds / $row->completed) / 60, 1) : 0;
            return [
                'period'    => "Year {$row->year}",
                'total'     => $row->total_calls,
                'completed' => $row->completed,
                'dropped'   => $row->dropped,
                'missed'    => $row->missed,
                'minutes'   => $min,
                'avg_min'   => $avg,
            ];
        })->toArray();
    }

    private function loadLifetimeData(): void
    {
        // Show all-time total in single row
        $total = \App\Models\CallLog::count();
        $completed = \App\Models\CallLog::where('status','completed')->count();
        $dropped = \App\Models\CallLog::where('status','dropped')->count();
        $missed = \App\Models\CallLog::where('status','missed')->count();
        $seconds = \App\Models\CallLog::where('status','completed')->sum('duration');
        
        $min = round($seconds / 60, 1);
        $avg = $completed > 0 ? round(($seconds / $completed) / 60, 1) : 0;

        $this->data = [[
            'period'    => 'All Time',
            'total'     => $total,
            'completed' => $completed,
            'dropped'   => $dropped,
            'missed'    => $missed,
            'minutes'   => $min,
            'avg_min'   => $avg,
        ]];
    }
}
