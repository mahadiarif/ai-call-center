<?php

namespace App\Filament\Widgets;

use App\Models\AiTicket;
use App\Models\ServiceRequest;
use App\Models\IvrService;
use App\Models\CallLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = -1; // LiveCalls এর ঠিক পরে, সবার উপরে
    // প্রতি ৩০ সেকেন্ডে অটো রিফ্রেশ
    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // ডাটাবেস টেবিল আছে কিনা চেক করে নেওয়া — এরর থেকে বাঁচতে
        $totalTickets    = Schema::hasTable('ai_tickets')       ? AiTicket::count()                                         : 0;
        $pendingTickets  = Schema::hasTable('ai_tickets')       ? AiTicket::where('status', 'Pending')->count()             : 0;
        $resolvedTickets = Schema::hasTable('ai_tickets')       ? AiTicket::where('status', 'Resolved')->count()            : 0;
        $todayTickets    = Schema::hasTable('ai_tickets')       ? AiTicket::whereDate('created_at', today())->count()       : 0;

        $totalRequests   = Schema::hasTable('service_requests') ? ServiceRequest::count()                                   : 0;
        $todayRequests   = Schema::hasTable('service_requests') ? ServiceRequest::whereDate('created_at', today())->count() : 0;
        $pendingRequests = Schema::hasTable('service_requests') ? ServiceRequest::where('status', 'Pending')->count()       : 0;

        $activeIvr       = Schema::hasTable('ivr_services')    ? IvrService::where('is_active', true)->count()              : 0;
        $totalIvr        = Schema::hasTable('ivr_services')    ? IvrService::count()                                        : 0;

        $todayCalls      = Schema::hasTable('call_logs')        ? CallLog::whereDate('created_at', today())->count()         : 0;
        $totalCalls      = Schema::hasTable('call_logs')        ? CallLog::count()                                           : 0;
        $missedCalls     = Schema::hasTable('call_logs')        ? CallLog::where('status', 'missed')->whereDate('created_at', today())->count() : 0;

        // ⏱️ মিনিট হিসাব (duration সেকেন্ডে সেভ থাকে)
        $todaySeconds    = Schema::hasTable('call_logs')        ? CallLog::whereDate('created_at', today())->where('status', 'completed')->sum('duration') : 0;
        $totalSeconds    = Schema::hasTable('call_logs')        ? CallLog::where('status', 'completed')->sum('duration')    : 0;
        $todayMinutes    = round($todaySeconds / 60, 1);
        $totalMinutes    = round($totalSeconds / 60, 1);

        $syncedRequests  = Schema::hasTable('service_requests') ? ServiceRequest::where('crm_sync_status', 'synced')->count() : 0;
        $syncRate = $totalRequests > 0 ? round(($syncedRequests / $totalRequests) * 100, 1) : 0;

        return [
            Stat::make('Total Service Requests', $totalRequests)
                ->description("Today: {$todayRequests} | Pending: {$pendingRequests}")
                ->descriptionIcon('heroicon-m-phone-arrow-down-left')
                ->color('info')
                ->chart([$totalRequests - $todayRequests, $todayRequests]),

            Stat::make('CRM Sync Success', $syncRate . '%')
                ->description("{$syncedRequests} synced successfully")
                ->descriptionIcon('heroicon-m-cloud-arrow-up')
                ->color($syncRate > 80 ? 'success' : 'warning'),

            Stat::make('AI Tickets', $totalTickets)
                ->description("Today: {$todayTickets} | Resolved: {$resolvedTickets}")
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary')
                ->chart([$totalTickets - $todayTickets, $todayTickets]),

            Stat::make('Today\'s Calls', $todayCalls)
                ->description("Minutes: {$todayMinutes}m | Missed: {$missedCalls}")
                ->descriptionIcon('heroicon-m-phone-arrow-down-left')
                ->color($missedCalls > 0 ? 'danger' : 'success'),

            Stat::make('Lifetime Talk Time', $totalMinutes . ' min')
                ->description("Across {$totalCalls} total calls")
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Active IVR Routes', $activeIvr)
                ->description("{$activeIvr} out of {$totalIvr} active")
                ->descriptionIcon('heroicon-m-signal')
                ->color('success'),
        ];
    }
}
