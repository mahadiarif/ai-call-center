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

        return [
            // ১. মোট Service Requests (Live Calls থেকে আসা)
            Stat::make('📋 সার্ভিস রিকোয়েস্ট', $totalRequests)
                ->description("আজকে: {$todayRequests}টি | পেন্ডিং: {$pendingRequests}টি")
                ->descriptionIcon('heroicon-m-phone-arrow-down-left')
                ->color('info')
                ->chart([$totalRequests - $todayRequests, $todayRequests]),

            // ২. পেন্ডিং Service Requests (Action দরকার)
            Stat::make('⏳ পেন্ডিং রিকোয়েস্ট', $pendingRequests)
                ->description("মোট {$totalRequests}টির মধ্যে")
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingRequests > 5 ? 'danger' : 'warning')
                ->url('/admin/service-requests?tableFilters[status][value]=Pending'),

            // ৩. মোট AI টিকেট (Manual + Auto Created)
            Stat::make('🎫 এআই টিকেটসমূহ', $totalTickets)
                ->description("আজকে: {$todayTickets}টি | পেন্ডিং: {$pendingTickets}টি")
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary')
                ->chart([$totalTickets - $todayTickets, $todayTickets]),

            // ৪. একটিভ IVR সার্ভিস
            Stat::make('📞 একটিভ IVR সার্ভিস', $activeIvr)
                ->description("মোট {$totalIvr}টির মধ্যে {$activeIvr}টি চলছে")
                ->descriptionIcon('heroicon-m-phone')
                ->color('success'),

            // ৫. আজকের কল + মিনিট
            Stat::make('📲 আজকের কল', $todayCalls)
                ->description("কথার সময়: {$todayMinutes} মিনিট | মিসড: {$missedCalls}")
                ->descriptionIcon('heroicon-m-phone-arrow-down-left')
                ->color($missedCalls > 0 ? 'danger' : 'success'),

            // ৬. লাইফটাইম মিনিট
            Stat::make('⏱️ মোট কথার সময়', $totalMinutes . ' মিনিট')
                ->description("মোট {$totalCalls}টি কলে")
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}
