<?php

namespace App\Filament\Widgets;

use App\Models\AiTicket;
use App\Models\ServiceRequest;
use App\Models\CallLog;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;

class SmartNotificationWidget extends Widget
{
    protected static ?int $sort = 0;
    protected int | string | array $columnSpan = 'full'; // full width

    protected string $view = 'filament.widgets.smart-notification-widget';

    protected ?string $pollingInterval = '60s';

    public array $alerts = [];

    public function mount(): void
    {
        $this->loadAlerts();
    }

    public function loadAlerts(): void
    {
        $alerts = [];

        // ১. পেন্ডিং টিকেট বেশি হলে
        if (Schema::hasTable('ai_tickets')) {
            $pending = AiTicket::where('status', 'Pending')->count();
            if ($pending >= 5) {
                $alerts[] = [
                    'type'    => 'danger',
                    'icon'    => '🔴',
                    'message' => "{$pending}টি AI টিকেট এখনো Pending! দ্রুত দেখুন।",
                    'link'    => '/admin/ai-tickets',
                ];
            }
        }

        // ২. ৩ দিনের বেশি পুরনো pending service request
        if (Schema::hasTable('service_requests')) {
            $oldPending = ServiceRequest::where('status', 'Pending')
                ->where('created_at', '<', now()->subDays(3))
                ->count();
            if ($oldPending > 0) {
                $alerts[] = [
                    'type'    => 'warning',
                    'icon'    => '⚠️',
                    'message' => "{$oldPending}টি সার্ভিস রিকোয়েস্ট ৩ দিনের বেশি সময় ধরে Pending আছে!",
                    'link'    => '/admin/service-requests',
                ];
            }
        }

        // ৩. আজকে missed call আছে কিনা
        if (Schema::hasTable('call_logs')) {
            $missedToday = CallLog::where('status', 'missed')
                ->whereDate('created_at', today())
                ->count();
            if ($missedToday > 0) {
                $alerts[] = [
                    'type'    => 'warning',
                    'icon'    => '📵',
                    'message' => "আজকে {$missedToday}টি কল মিস হয়েছে!",
                    'link'    => '/admin/call-logs',
                ];
            }
        }

        // ৪. সব ঠিক থাকলে success দেখাও
        if (empty($alerts)) {
            $alerts[] = [
                'type'    => 'success',
                'icon'    => '✅',
                'message' => 'সব ঠিকঠাক আছে! কোনো জরুরি সমস্যা নেই।',
                'link'    => null,
            ];
        }

        $this->alerts = $alerts;
    }
}
