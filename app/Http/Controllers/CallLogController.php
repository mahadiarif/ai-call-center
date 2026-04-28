<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\IvrService;
use Illuminate\Http\Request;

class CallLogController extends Controller
{
    /**
     * কল শুরু হলে log entry তৈরি করো
     * ফ্রন্টএন্ড থেকে: POST /call-log/start
     */
    public function startCall(Request $request)
    {
        $ivrKey      = $request->input('ivr_key', '1');
        $service     = IvrService::where('key_press', $ivrKey)->where('is_active', true)->first();
        $callerNum   = $request->input('caller_number');

        $log = CallLog::create([
            'caller_number'  => $callerNum,
            'ivr_service_id' => $service ? $service->id : null,
            'duration'       => 0,
            'status'         => 'dropped', // কল শেষ না হলে dropped থাকবে
            'session_id'     => $request->input('session_id'),
        ]);

        // ── ServiceRequest に "Incoming" entry を作成する（caller number なしでも）──
        $serviceRequestId = null;
        $cleanedNum = !empty($callerNum) ? preg_replace('/\D/', '', $callerNum) : null;

        // Duplicate check — same number within last 2 min (number がある場合)
        $existing = null;
        if ($cleanedNum) {
            $existing = \App\Models\ServiceRequest::where('mobile_number', $cleanedNum)
                ->whereIn('status', ['Incoming', 'Pending', 'Drop Call'])
                ->where('created_at', '>=', now()->subMinutes(2))
                ->first();
        }

        if ($existing) {
            $serviceRequestId = $existing->id;
        } else {
            // caller number がなくても Incoming entry を作る（live call monitor 用）
            $sr = \App\Models\ServiceRequest::create([
                'ivr_service_id' => $service ? $service->id : null,
                'mobile_number'  => $cleanedNum ?? null,
                'status'         => 'Incoming',
                'extracted_data' => ['caller_number' => $callerNum, 'call_log_id' => $log->id],
            ]);
            $serviceRequestId = $sr->id;
        }

        return response()->json([
            'status'             => 'success',
            'log_id'             => $log->id,
            'service_request_id' => $serviceRequestId,
        ]);
    }

    /**
     * কল শেষ হলে log আপডেট করো
     * ফ্রন্টএন্ড থেকে: POST /call-log/end
     */
    public function endCall(Request $request)
    {
        $log = CallLog::find($request->input('log_id'));

        if (!$log) {
            return response()->json(['status' => 'error', 'message' => 'Log not found']);
        }

        $callStatus = $request->input('status', 'completed');

        $log->update([
            'duration'       => (int) $request->input('duration', 0),
            'status'         => $callStatus,
            'recording_path' => $request->input('recording_path'),
        ]);

        // Drop call হলে ServiceRequest status "Drop Call" করো
        if (in_array($callStatus, ['dropped', 'missed'])) {
            $srId = $request->input('service_request_id');
            if ($srId) {
                \App\Models\ServiceRequest::where('id', $srId)
                    ->where('status', 'Incoming')
                    ->update(['status' => 'Drop Call']);
            } elseif ($log->caller_number) {
                // ID না থাকলে number দিয়ে খোঁজো
                $cleanedNum = preg_replace('/\D/', '', $log->caller_number);
                \App\Models\ServiceRequest::where('mobile_number', $cleanedNum)
                    ->where('status', 'Incoming')
                    ->where('created_at', '>=', now()->subMinutes(60))
                    ->update(['status' => 'Drop Call']);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Asterisk dialplan → AudioSocket connect-এর আগে caller number register করো
     * FIFO queue per Asterisk-IP → concurrent calls handled correctly
     * POST /api/bridge/register-caller
     */
    public function registerCaller(Request $request)
    {
        $callerNum = $request->input('caller_number');
        $ivrKey    = $request->input('ivr_key', '1');
        $callUuid  = $request->input('uuid'); // Asterisk MY_UUID

        if (empty($callerNum)) {
            return response()->json(['status' => 'error', 'message' => 'No caller number']);
        }

        $cleanedNum = preg_replace('/\D/', '', $callerNum);

        // ── UUID-based storage (reliable, no IP/proxy issues) ──
        if ($callUuid) {
            \Illuminate\Support\Facades\Cache::put(
                "asterisk_caller_{$callUuid}",
                ['caller_number' => $cleanedNum, 'ivr_key' => $ivrKey, 'ts' => time()],
                120
            );
        }

        // ── Also keep IP-based queue as fallback ──
        $callerIp = $request->header('X-Real-IP')
                 ?? $request->header('X-Forwarded-For')
                 ?? $request->ip();
        $queueKey = "asterisk_queue_{$callerIp}";
        $queue    = \Illuminate\Support\Facades\Cache::get($queueKey, []);
        $queue[]  = ['caller_number' => $cleanedNum, 'ivr_key' => $ivrKey, 'ts' => time(), 'uuid' => $callUuid];
        if (count($queue) > 20) $queue = array_slice($queue, -20);
        \Illuminate\Support\Facades\Cache::put($queueKey, $queue, 120);

        return response()->json(['status' => 'success', 'caller' => $cleanedNum, 'uuid' => $callUuid]);
    }

    /**
     * Bridge → UUID দিয়ে caller number নাও (IP mismatch এড়াতে)
     * GET /api/bridge/caller-by-uuid?uuid=XXXX
     */
    public function getCallerByUuid(Request $request)
    {
        $uuid = $request->input('uuid');
        if (!$uuid) {
            return response()->json(['status' => 'error', 'caller_number' => null]);
        }

        $item = \Illuminate\Support\Facades\Cache::get("asterisk_caller_{$uuid}");
        if (!$item) {
            return response()->json(['status' => 'not_found', 'caller_number' => null]);
        }

        // One-time use — delete after fetch
        \Illuminate\Support\Facades\Cache::forget("asterisk_caller_{$uuid}");

        if (time() - ($item['ts'] ?? 0) > 60) {
            return response()->json(['status' => 'expired', 'caller_number' => null]);
        }

        return response()->json([
            'status'        => 'success',
            'caller_number' => $item['caller_number'],
            'ivr_key'       => $item['ivr_key'],
        ]);
    }

    /**
     * Bridge → AudioSocket connect হলে caller number নাও (FIFO pop)
     * GET /api/bridge/caller-by-ip?ip=X.X.X.X
     */
    public function getCallerByIp(Request $request)
    {
        $ip       = $request->input('ip', $request->ip());
        $queueKey = "asterisk_queue_{$ip}";
        $queue    = \Illuminate\Support\Facades\Cache::get($queueKey, []);

        if (empty($queue)) {
            return response()->json(['status' => 'not_found', 'caller_number' => null]);
        }

        // Pop oldest entry (FIFO)
        $item  = array_shift($queue);
        \Illuminate\Support\Facades\Cache::put($queueKey, $queue, 120);

        // Expire if too old (> 60 seconds — call should connect within this time)
        if (time() - ($item['ts'] ?? 0) > 60) {
            return response()->json(['status' => 'expired', 'caller_number' => null]);
        }

        return response()->json([
            'status'         => 'success',
            'caller_number'  => $item['caller_number'],
            'ivr_key'        => $item['ivr_key'],
        ]);
    }
}
