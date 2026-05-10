<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SmsSetting;
use App\Models\SmsLog;

class SmsService
{
    protected SmsSetting $settings;

    public function __construct()
    {
        $this->settings = SmsSetting::current();
    }

    /**
     * SR তৈরির পর auto SMS (auto_send_on_sr enabled হলে)
     */
    public static function sendSrConfirmation(
        string $mobile,
        string $waltonSr,
        string $customerName = '',
        string $product = '',
        int $srTicketId = 0
    ): bool {
        $instance = new self();
        if (!$instance->settings->is_active || !$instance->settings->auto_send_on_sr) {
            return false;
        }
        $message = $instance->buildMessage($instance->settings->sr_template, [
            '{name}'      => $customerName ?: 'গ্রাহক',
            '{sr_number}' => $waltonSr,
            '{product}'   => $product,
            '{qm_number}' => '',
        ]);
        return $instance->send($mobile, $message, 'auto', 'sr_ticket', $srTicketId, $waltonSr);
    }

    /**
     * QM তৈরির পর auto SMS (auto_send_on_qm enabled হলে)
     */
    public static function sendQmConfirmation(
        string $mobile,
        string $qmNumber,
        string $customerName = '',
        int $qmTicketId = 0
    ): bool {
        $instance = new self();
        if (!$instance->settings->is_active || !$instance->settings->auto_send_on_qm) {
            return false;
        }
        $message = $instance->buildMessage($instance->settings->qm_template, [
            '{name}'      => $customerName ?: 'গ্রাহক',
            '{sr_number}' => '',
            '{product}'   => '',
            '{qm_number}' => $qmNumber,
        ]);
        return $instance->send($mobile, $message, 'auto', 'qm_ticket', $qmTicketId);
    }

    /**
     * Manual SMS — admin panel থেকে পাঠানো (সবসময় পাঠাবে, auto_send flag মানে না)
     */
    public static function sendManual(
        string $mobile,
        string $message,
        string $referenceType = '',
        int $referenceId = 0,
        string $sentBy = 'admin'
    ): array {
        $instance = new self();
        if (!$instance->settings->is_active) {
            return ['success' => false, 'message' => 'SMS Gateway নিষ্ক্রিয়। Settings থেকে চালু করুন।'];
        }
        $success = $instance->send($mobile, $message, 'manual', $referenceType, $referenceId, null, $sentBy);
        return [
            'success' => $success,
            'message' => $success ? 'SMS সফলভাবে পাঠানো হয়েছে!' : 'SMS পাঠাতে সমস্যা হয়েছে।',
        ];
    }

    /**
     * Core send method — log করে
     */
    public function send(
        string $mobile,
        string $message,
        string $type = 'auto',
        string $referenceType = '',
        int $referenceId = 0,
        ?string $waltonSr = null,
        string $sentBy = 'system'
    ): bool {
        $mobile = $this->normalizeMobile($mobile);

        if (!$mobile) {
            Log::warning('[SMS] Invalid mobile number');
            return false;
        }
        if (empty($this->settings->api_token) || empty($this->settings->sid)) {
            Log::warning('[SMS] Gateway not configured — api_token or sid missing');
            return false;
        }

        $log = SmsLog::create([
            'mobile'         => $mobile,
            'message'        => $message,
            'type'           => $type,
            'status'         => 'pending',
            'reference_type' => $referenceType ?: null,
            'reference_id'   => $referenceId ?: null,
            'walton_sr'      => $waltonSr,
            'sent_by'        => $sentBy,
        ]);

        try {
            $payload = [
                'api_token' => $this->settings->api_token,
                'sid'       => $this->settings->sid,
                'msisdn'    => $mobile,
                'sms'       => $message,
                'csms_id'   => uniqid('WLT', true),
            ];

            $response = Http::timeout(10)->post($this->settings->gateway_url, $payload);
            $responseData = $response->json() ?? ['body' => $response->body()];

            if ($response->successful()) {
                $log->update(['status' => 'sent', 'gateway_response' => $responseData]);
                Log::info('[SMS] ✅ Sent', ['mobile' => $mobile, 'walton_sr' => $waltonSr]);
                return true;
            } else {
                $log->update(['status' => 'failed', 'gateway_response' => $responseData]);
                Log::warning('[SMS] ❌ Failed', ['status' => $response->status(), 'body' => $response->body()]);
                return false;
            }
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'gateway_response' => ['error' => $e->getMessage()]]);
            Log::error('[SMS] ❌ Exception: ' . $e->getMessage());
            return false;
        }
    }

    private function normalizeMobile(string $mobile): string
    {
        $mobile = preg_replace('/\D/', '', $mobile);
        if (strlen($mobile) === 11 && str_starts_with($mobile, '0')) {
            return '88' . $mobile;
        } elseif (strlen($mobile) === 10) {
            return '880' . $mobile;
        }
        return $mobile;
    }

    private function buildMessage(string $template, array $replacements): string
    {
        return str_replace(array_keys($replacements), array_values($replacements), $template ?: '');
    }
}
