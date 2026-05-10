<?php

namespace App\Http\Controllers;

use App\Services\AsteriskAmiService;
use App\Models\IvrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EscalationController extends Controller
{
    /**
     * Bridge/AI থেকে escalation trigger হলে এই endpoint call হবে।
     * ১. caller_number দিয়ে active Asterisk channel খোঁজো
     * ২. Channel পেলে → Redirect to escalation-forward context
     * ৩. Channel না পেলে → Originate করে agent কে সরাসরি call দাও
     */
    public function transferToAgent(Request $request)
    {
        try {
            $ivrKey          = $request->input('ivr_key', '1');
            $customerChannel = $request->input('customer_channel'); // optional
            $callerNumber    = $request->input('caller_number');
            $reason          = $request->input('reason', 'customer_requested');

            // IVR service থেকে agent নম্বর নাও
            $service = IvrService::where('key_press', $ivrKey)->where('is_active', true)->first()
                    ?? IvrService::where('is_active', true)->first();

            $agentNumber = $service?->escalation_agent_number;

            if (!$agentNumber) {
                return response()->json([
                    'status'      => 'no_agent',
                    'message'     => 'কোনো agent নম্বর সেট করা নেই',
                    'calm_script' => $service?->escalation_calm_script ?? "এই মুহূর্তে agent available নেই।",
                ]);
            }

            $ami = new AsteriskAmiService();

            // ── Step 1: Channel না থাকলে caller_number দিয়ে খোঁজো ──────────
            if (!$customerChannel && $callerNumber) {
                $customerChannel = $ami->findChannelByCallerNumber($callerNumber);
                Log::info("[Escalation] Channel lookup for {$callerNumber}: " . ($customerChannel ?: 'not found'));
            }

            // ── Step 2: Channel পেলে Redirect, না পেলে Originate ────────────
            if ($customerChannel) {
                $result = $ami->transferToAgent($customerChannel, $agentNumber);
                Log::info("[Escalation] Redirect: channel={$customerChannel} → agent={$agentNumber}");
            } else {
                $result = $ami->originateToAgent($agentNumber);
                Log::info("[Escalation] Originate to agent={$agentNumber} (no channel found)");
            }

            return response()->json([
                'status'       => $result['success'] ? 'transferred' : 'transfer_failed',
                'agent_number' => $agentNumber,
                'calm_script'  => $service->escalation_calm_script ?? "আপনাকে আমাদের agent এর সাথে কানেক্ট করছি।",
                'hold_script'  => $service->escalation_hold_script ?? "একটু অপেক্ষা করুন।",
                'message'      => $result['message'],
            ]);

        } catch (\Throwable $e) {
            Log::error("[Escalation] error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * AI Prompt থেকে escalation trigger হলে — agent কে সরাসরি call দাও
     */
    public function callAgent(Request $request)
    {
        try {
            $ivrKey      = $request->input('ivr_key', '1');
            $service     = IvrService::where('key_press', $ivrKey)->where('is_active', true)->first();
            $agentNumber = $service?->escalation_agent_number;

            if (!$agentNumber) {
                return response()->json(['status' => 'missing_data', 'message' => 'Agent নম্বর নেই']);
            }

            $ami    = new AsteriskAmiService();
            $result = $ami->originateToAgent($agentNumber);

            return response()->json([
                'status'  => $result['success'] ? 'calling' : 'failed',
                'message' => $result['message'],
            ]);

        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}

