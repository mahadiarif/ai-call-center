<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\IvrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $sessionId   = $request->input('session_id'); // UUID

        $log = CallLog::create([
            'caller_number'  => $callerNum,
            'ivr_service_id' => $service ? $service->id : null,
            'duration'       => 0,
            'status'         => 'dropped',
            'session_id'     => $sessionId,
        ]);

        $cleanedNum = !empty($callerNum) ? preg_replace('/\D/', '', $callerNum) : null;

        // ── Check if registerCaller already created a ServiceRequest (sr_id in cache) ──
        $srFromCache = null;
        $callTypeFromCache = 'inbound';
        if ($sessionId) {
            $cached = \Illuminate\Support\Facades\Cache::get("asterisk_caller_{$sessionId}");
            $srFromCache       = $cached['sr_id'] ?? null;
            $callTypeFromCache = $cached['call_type'] ?? 'inbound';
        }

        // ── Outbound survey: NEVER create a ServiceRequest ─────────────────
        if ($callTypeFromCache === 'outbound_survey') {
            return response()->json([
                'status'             => 'success',
                'log_id'             => $log->id,
                'service_request_id' => null,
            ]);
        }

        $serviceRequestId = null;

        if ($srFromCache) {
            // registerCaller already created it — just update with call_log_id
            \App\Models\ServiceRequest::where('id', $srFromCache)->update([
                'extracted_data' => \Illuminate\Support\Facades\DB::raw(
                    "JSON_SET(COALESCE(extracted_data, '{}'), '$.call_log_id', {$log->id})"
                ),
            ]);
            $serviceRequestId = $srFromCache;
        } else {
            // Fallback: check by mobile number
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
                $sr = \App\Models\ServiceRequest::create([
                    'ivr_service_id' => $service ? $service->id : null,
                    'mobile_number'  => $cleanedNum ?? null,
                    'status'         => 'Incoming',
                    'extracted_data' => ['caller_number' => $callerNum, 'call_log_id' => $log->id],
                ]);
                $serviceRequestId = $sr->id;
            }
        }

        return response()->json([
            'status'             => 'success',
            'log_id'             => $log->id,
            'service_request_id' => $serviceRequestId,
        ]);
    }

    /**
     * Hangup হওয়ার সাথে সাথে SR "Drop Call" করো
     * Python bridge hangup detect করলে এখানে call করে — AI processing এর আগেই
     * POST /api/bridge/call-hangup
     */
    public function immediateHangup(Request $request)
    {
        $srId      = $request->input('service_request_id');
        $callLogId = $request->input('call_log_id');

        if ($srId) {
            \App\Models\ServiceRequest::where('id', $srId)
                ->where('status', 'Incoming')
                ->update(['status' => 'Drop Call']);
        }

        // পুরনো stuck Incoming গুলোও clear করো (20 মিনিটের বেশি পুরনো)
        // NOTE: real call 15+ মিনিট হতে পারে — তাই 20 মিনিট threshold
        \App\Models\ServiceRequest::where('status', 'Incoming')
            ->where('created_at', '<', now()->subMinutes(20))
            ->update(['status' => 'Drop Call']);

        return response()->json(['status' => 'success']);
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

        // কল শেষ হলে Incoming ServiceRequest আপডেট করো
        $srId = $request->input('service_request_id');

        if (in_array($callStatus, ['dropped', 'missed'])) {
            // Drop/missed call → Drop Call status
            if ($srId) {
                \App\Models\ServiceRequest::where('id', $srId)
                    ->where('status', 'Incoming')
                    ->update(['status' => 'Drop Call']);
            } elseif ($log->caller_number) {
                $cleanedNum = preg_replace('/\D/', '', $log->caller_number);
                \App\Models\ServiceRequest::where('mobile_number', $cleanedNum)
                    ->where('status', 'Incoming')
                    ->where('created_at', '>=', now()->subMinutes(60))
                    ->update(['status' => 'Drop Call']);
            }
        } elseif ($callStatus === 'completed') {
            // Completed call → Incoming থেকে Pending বা Resolved এ নিয়ে যাও
            // (AI ticket তৈরি হলে Pending হয়, তাই Incoming পড়ে থাকলে Drop Call করো)
            if ($srId) {
                \App\Models\ServiceRequest::where('id', $srId)
                    ->where('status', 'Incoming')
                    ->update(['status' => 'Drop Call']); // AI complete না করলে Drop
            } elseif ($log->caller_number) {
                $cleanedNum = preg_replace('/\D/', '', $log->caller_number);
                \App\Models\ServiceRequest::where('mobile_number', $cleanedNum)
                    ->where('status', 'Incoming')
                    ->where('created_at', '>=', now()->subMinutes(60))
                    ->update(['status' => 'Drop Call']);
            }
        }

        // যেকোনো কারণে 20 মিনিটের বেশি পুরনো Incoming SR গুলো auto-clear করো
        // (কল কেটে গেলে / browser বন্ধ হলে / crash এ Incoming stuck থাকে)
        // NOTE: real call 15+ মিনিট হতে পারে — 2 মিনিট threshold ছিল, এখন 20 মিনিট
        \App\Models\ServiceRequest::where('status', 'Incoming')
            ->where('created_at', '<', now()->subMinutes(20))
            ->update(['status' => 'Drop Call']);

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
        $callType  = $request->input('call_type', 'inbound'); // inbound | outbound_survey
        $surveyId  = $request->input('survey_id');             // FeedbackSurvey.id (outbound only)

        if (empty($callerNum)) {
            return response()->json(['status' => 'error', 'message' => 'No caller number']);
        }

        $cleanedNum = preg_replace('/\D/', '', $callerNum);
        $service    = IvrService::where('key_press', $ivrKey)->where('is_active', true)->first();

        // ── Outbound survey: skip ServiceRequest creation ──────────────────
        $srId = null;
        if ($callType !== 'outbound_survey') {
            // ── Immediately create "Incoming" ServiceRequest for Live Monitor ──
            $existing = \App\Models\ServiceRequest::where('mobile_number', $cleanedNum)
                ->whereIn('status', ['Incoming'])
                ->where('created_at', '>=', now()->subMinutes(2))
                ->first();

            if ($existing) {
                $srId = $existing->id;
            } else {
                $sr = \App\Models\ServiceRequest::create([
                    'ivr_service_id' => $service?->id,
                    'mobile_number'  => $cleanedNum,
                    'status'         => 'Incoming',
                    'extracted_data' => ['caller_number' => $callerNum, 'uuid' => $callUuid, 'registered_at' => time()],
                ]);
                $srId = $sr->id;
            }
        }

        // ── UUID-based storage (reliable, no IP/proxy issues) ──
        if ($callUuid) {
            \Illuminate\Support\Facades\Cache::put(
                "asterisk_caller_{$callUuid}",
                [
                    'caller_number' => $cleanedNum,
                    'ivr_key'       => $ivrKey,
                    'ts'            => time(),
                    'sr_id'         => $srId,
                    'call_type'     => $callType,
                    'survey_id'     => $surveyId,
                ],
                120
            );
        }

        // ── Also keep IP-based queue as fallback ──
        $callerIp = $request->header('X-Real-IP')
                 ?? $request->header('X-Forwarded-For')
                 ?? $request->ip();
        $queueKey = "asterisk_queue_{$callerIp}";
        $queue    = \Illuminate\Support\Facades\Cache::get($queueKey, []);
        $queue[]  = ['caller_number' => $cleanedNum, 'ivr_key' => $ivrKey, 'ts' => time(), 'uuid' => $callUuid, 'sr_id' => $srId];
        if (count($queue) > 20) $queue = array_slice($queue, -20);
        \Illuminate\Support\Facades\Cache::put($queueKey, $queue, 120);

        return response()->json(['status' => 'success', 'caller' => $cleanedNum, 'uuid' => $callUuid, 'sr_id' => $srId]);
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
            'call_type'     => $item['call_type']  ?? 'inbound',
            'survey_id'     => $item['survey_id']  ?? null,
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
    /**
     * Bridge -> মোবাইল নম্বর দিয়ে কাস্টমারের প্রোফাইল ও ইতিহাস নাও
     * GET /api/bridge/customer-profile?mobile=017XXXXXXXX
     */
    public function getCustomerProfile(Request $request)
    {
        $mobile = $request->input('mobile');
        if (!$mobile) {
            return response()->json(['status' => 'error', 'message' => 'Mobile number required']);
        }

        $cleanedNum = preg_replace('/\D/', '', $mobile);

        // ১. কাস্টমারের নাম ও জেন্ডার খোঁজো (ServiceRequest থেকে)
        $latestSr = \App\Models\ServiceRequest::where('mobile_number', $cleanedNum)
            ->whereNotNull('customer_name')
            ->latest()
            ->first();

        $name = $latestSr ? $latestSr->customer_name : 'সম্মানিত গ্রাহক';
        $gender = $latestSr ? strtolower($latestSr->gender) : 'unknown';
        
        // সম্মানসূচক সম্বোধন (Honorific)
        $honorific = 'স্যার'; // Default
        if ($gender === 'female' || $gender === 'madam' || $gender === 'mrs' || $gender === 'miss') {
            $honorific = 'ম্যাডাম';
        }

        // ২. আগের হিস্ট্রি (সর্বশেষ ২টা interaction)
        $history = \App\Models\ServiceRequest::where('mobile_number', $cleanedNum)
            ->whereNotNull('extracted_data')
            ->latest()
            ->take(2)
            ->get()
            ->map(function ($sr) {
                $product = $sr->extracted_data['product'] ?? $sr->ivrService?->service_name ?? 'অজানা';
                $status  = $sr->status;
                return "তারিখ: {$sr->created_at->format('d M')}, পণ্য: {$product}, অবস্থা: {$status}";
            })
            ->implode('; ');

        return response()->json([
            'status' => 'success',
            'data' => [
                'name' => $name,
                'honorific' => $honorific,
                'last_interaction' => $history ?: 'নাই (নতুন গ্রাহক)',
            ]
        ]);
    }
}
