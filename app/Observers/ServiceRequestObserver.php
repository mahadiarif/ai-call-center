<?php

namespace App\Observers;

use App\Models\ServiceRequest;
use App\Models\AiTicket;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServiceRequestObserver
{
    // নতুন সার্ভিস রিকোয়েস্ট এলে সব admin কে real-time notification
    public function created(ServiceRequest $serviceRequest): void
    {
        // ── QM Ticket Type Auto-Detect & QM Number Generate ──────────────
        try {
            $needsUpdate = false;

            // extracted_data-এর qm_type দেখে ticket_type নির্ধারণ (যেকোনো IVR key-এর জন্য)
            if (empty($serviceRequest->ticket_type) || $serviceRequest->ticket_type === 'SR') {
                $extractedData = $serviceRequest->extracted_data ?? [];
                $qmType = strtolower(trim($extractedData['qm_type'] ?? ''));

                $typeMap = [
                    'complaint'    => 'QM_COMPLAINT',
                    'parts_query'  => 'QM_PARTS',
                    'bill_query'   => 'QM_BILL',
                    'escalation'   => 'QM_COMPLAINT',
                    'sr_escalation'=> 'QM_COMPLAINT', // SR + রাগান্বিত/জরুরি → QM complaint
                    // 'sr' বা empty → SR (পরিবর্তন নেই)
                ];

                if ($qmType && isset($typeMap[$qmType])) {
                    $serviceRequest->ticket_type = $typeMap[$qmType];
                    $needsUpdate = true;
                }
            }

            // QM Ticket হলে QM-XXXX নম্বর তৈরি করো (sequential: QM-1001, QM-1002...)
            if (
                in_array($serviceRequest->ticket_type, ['QM_COMPLAINT', 'QM_PARTS', 'QM_BILL'])
                && empty($serviceRequest->qm_number)
            ) {
                $qmCount = ServiceRequest::whereIn('ticket_type', ['QM_COMPLAINT', 'QM_PARTS', 'QM_BILL'])
                    ->where('id', '!=', $serviceRequest->id)
                    ->whereNotNull('qm_number')
                    ->count();
                $serviceRequest->qm_number = 'QM-' . (1001 + $qmCount);
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                // observer loop এড়াতে withoutEvents ব্যবহার
                ServiceRequest::withoutEvents(fn () =>
                    $serviceRequest->saveQuietly()
                );
            }
        } catch (\Throwable $e) {
            // QM number fail হলেও মূল save বন্ধ না হোক
        }

        try {
            $name   = $serviceRequest->customer_name ?? 'অজানা কাস্টমার';
            $mobile = $serviceRequest->mobile_number  ?? 'নম্বর নেই';

            $recipients = User::all();

            $isQm    = in_array($serviceRequest->ticket_type, ['QM_COMPLAINT', 'QM_PARTS', 'QM_BILL']);
            $qmLabel = $serviceRequest->qm_number ? " [{$serviceRequest->qm_number}]" : '';
            $title   = $isQm ? "🎫 নতুন QM Ticket{$qmLabel}!" : '🆕 নতুন সার্ভিস রিকোয়েস্ট!';

            Notification::make()
                ->title($title)
                ->body("{$name} ({$mobile}) — নতুন রিকোয়েস্ট এসেছে")
                ->icon('heroicon-o-phone-arrow-down-left')
                ->color('success')
                ->actions([
                    Action::make('view')
                        ->label('দেখুন')
                        ->url('/admin/service-requests')
                        ->markAsRead(),
                ])
                ->sendToDatabase($recipients);
        } catch (\Throwable $e) {
            // notification fail হলেও মূল save বন্ধ না হোক
        }
    }

    /**
     * Handle the ServiceRequest "updated" event.
     */
    public function updated(ServiceRequest $serviceRequest): void
    {
        // 🔁 AUTO RE-EXTRACT: যদি transcript থাকে কিন্তু critical field empty → background-এ আবার extract চালাও
        // (cache flag use করি যাতে infinite loop না হয়)
        $this->autoReExtractIfFieldsEmpty($serviceRequest);

        // যদি status "Resolved" হয়, তাহলে AiTicket তৈরি করো
        if ($serviceRequest->status === 'Resolved') {
            // চেক করো যে এই ServiceRequest এর জন্য ইতিমধ্যে AiTicket আছে কি না
            $existingTicket = AiTicket::where('service_request_id', $serviceRequest->id)->first();
            
            if (!$existingTicket) {
                // নতুন AiTicket তৈরি করো
                AiTicket::create([
                    'service_request_id' => $serviceRequest->id,
                    'ivr_service_id' => $serviceRequest->ivr_service_id ?? 1, // ডিফল্ট ভ্যালু
                    'customer_number' => $serviceRequest->mobile_number,
                    'extracted_data' => [
                        'customer_name' => $serviceRequest->customer_name,
                        'product_name' => $serviceRequest->product_name,
                        'problem_description' => $serviceRequest->problem_description,
                        'address' => $serviceRequest->address,
                        'district' => $serviceRequest->district,
                        'barcode' => $serviceRequest->barcode,
                    ],
                    'status' => 'Resolved',
                ]);
            } else {
                // যদি ইতিমধ্যে থাকে, তাহলে আপডেট করো
                $existingTicket->update([
                    'status' => 'Resolved',
                    'extracted_data' => [
                        'customer_name' => $serviceRequest->customer_name,
                        'product_name' => $serviceRequest->product_name,
                        'problem_description' => $serviceRequest->problem_description,
                        'address' => $serviceRequest->address,
                        'district' => $serviceRequest->district,
                        'barcode' => $serviceRequest->barcode,
                    ],
                ]);
            }
        }
    }

    /**
     * 🔁 Auto Re-Extract — যদি transcript filled থাকে কিন্তু critical field empty → আবার extract চালাও
     * Cache flag দিয়ে infinite loop prevent করি (per-SR একবারই retry হবে).
     */
    protected function autoReExtractIfFieldsEmpty(ServiceRequest $sr): void
    {
        try {
            // 1. Transcript থাকতে হবে substantial (>50 chars)
            $transcript = $sr->call_transcript ?? '';
            if (mb_strlen($transcript) < 50) return;

            // 2. ৫টা critical field — এদের মধ্যে অন্তত ৩টা empty হলে retry
            $criticalFields = ['customer_name', 'product_name', 'problem_description', 'address', 'district'];
            $emptyCount = 0;
            foreach ($criticalFields as $f) {
                if (empty(trim((string) ($sr->{$f} ?? '')))) {
                    $emptyCount++;
                }
            }
            if ($emptyCount < 3) return; // most fields filled — no retry needed

            // 3. Cache flag — same SR-এর জন্য একবারই retry
            $flagKey = "sr_reextract_done_{$sr->id}";
            if (Cache::has($flagKey)) return;
            Cache::put($flagKey, true, now()->addHours(24));

            // 4. dispatchAfterResponse — current request finish হওয়ার পর background-এ চলবে
            $srId      = $sr->id;
            $tx        = $transcript;
            $callerNum = $sr->mobile_number;
            $ivrId     = $sr->ivr_service_id;

            dispatch(function () use ($srId, $tx, $callerNum, $ivrId) {
                try {
                    // IVR key resolve
                    $ivrKey = '1';
                    if ($ivrId) {
                        $svc = \App\Models\IvrService::find($ivrId);
                        if ($svc) $ivrKey = (string) $svc->key_press;
                    }

                    // Internal HTTP call to processFinalText endpoint
                    $url = rtrim(config('app.url'), '/') . '/api/bridge/process-final-text';
                    Http::timeout(60)->post($url, [
                        'text'               => $tx,
                        'ivr_key'            => $ivrKey,
                        'caller_number'      => $callerNum,
                        'service_request_id' => $srId,
                    ]);
                    Log::info("[AutoReExtract] Triggered re-extraction for SR #{$srId}");
                } catch (\Throwable $e) {
                    Log::warning("[AutoReExtract] Failed for SR #{$srId}: " . $e->getMessage());
                }
            })->afterResponse();

        } catch (\Throwable $e) {
            // never break the main update flow
            Log::warning("[AutoReExtract] Pre-check failed: " . $e->getMessage());
        }
    }
}
