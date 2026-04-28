<?php

namespace App\Http\Controllers;

use App\Services\AsteriskAmiService;
use App\Models\IvrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EscalationController extends Controller
{
    /**
     * রাগী কাস্টমার detect হলে AI frontend থেকে এই endpoint call করবে
     * AI নিজে transfer করতে পারে না — কিন্তু frontend signal দিলে server transfer করবে
     */
    public function transferToAgent(Request $request)
    {
        try {
            $ivrKey         = $request->input('ivr_key', '1');
            $customerChannel = $request->input('customer_channel'); // Asterisk channel ID
            $reason         = $request->input('reason', 'customer_requested'); // কেন transfer

            // IVR service থেকে agent নম্বর নাও
            $service = IvrService::where('key_press', $ivrKey)->where('is_active', true)->first();
            if (!$service) {
                $service = IvrService::where('is_active', true)->first();
            }

            $agentNumber = $service?->escalation_agent_number;

            if (!$agentNumber) {
                return response()->json([
                    'status'   => 'no_agent',
                    'message'  => 'এই সার্ভিসের জন্য কোনো agent নম্বর সেট করা নেই',
                    'calm_script' => $service?->escalation_calm_script ?? "আমি দুঃখিত, এই মুহূর্তে agent available নেই।",
                ]);
            }

            // Customer channel না থাকলে — শুধু নম্বর দাও (fallback)
            if (!$customerChannel) {
                return response()->json([
                    'status'      => 'number_only',
                    'agent_number' => $agentNumber,
                    'calm_script' => $service->escalation_calm_script ?? "আমাদের agent এর সাথে কথা বলতে {$agentNumber} নম্বরে call করুন।",
                    'hold_script' => $service->escalation_hold_script ?? "একটু অপেক্ষা করুন।",
                    'message'     => "Agent নম্বর: {$agentNumber}",
                ]);
            }

            // Asterisk AMI দিয়ে transfer করো
            $ami    = new AsteriskAmiService();
            $result = $ami->transferToAgent($customerChannel, $agentNumber);

            Log::info("Escalation transfer: IVR={$ivrKey}, Agent={$agentNumber}, Reason={$reason}, Result=" . json_encode($result));

            return response()->json([
                'status'       => $result['success'] ? 'transferred' : 'transfer_failed',
                'agent_number' => $agentNumber,
                'calm_script'  => $service->escalation_calm_script ?? "আপনাকে আমাদের agent এর সাথে কানেক্ট করছি।",
                'hold_script'  => $service->escalation_hold_script ?? "একটু অপেক্ষা করুন।",
                'message'      => $result['message'],
            ]);

        } catch (\Throwable $e) {
            Log::error("Escalation error: " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * AI Prompt থেকে escalation trigger হলে — agent কে সরাসরি call দাও
     */
    public function callAgent(Request $request)
    {
        try {
            $ivrKey          = $request->input('ivr_key', '1');
            $customerChannel = $request->input('customer_channel');

            $service     = IvrService::where('key_press', $ivrKey)->where('is_active', true)->first();
            $agentNumber = $service?->escalation_agent_number;

            if (!$agentNumber || !$customerChannel) {
                return response()->json(['status' => 'missing_data', 'message' => 'Agent নম্বর বা channel নেই']);
            }

            $ami    = new AsteriskAmiService();
            $result = $ami->originateToAgent($agentNumber, $customerChannel);

            return response()->json([
                'status'  => $result['success'] ? 'calling' : 'failed',
                'message' => $result['message'],
            ]);

        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
