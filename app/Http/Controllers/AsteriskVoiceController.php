<?php

namespace App\Http\Controllers;

use App\Services\AsteriskVoiceService;
use Illuminate\Http\Request;

class AsteriskVoiceController extends Controller
{
    protected $voiceService;

    public function __construct(AsteriskVoiceService $voiceService)
    {
        $this->voiceService = $voiceService;
    }

    /**
     * AI Agent ভয়েস তৈরি করো (কাস্টম সেটিংস সহ)
     */
    public function generateAgentVoice(Request $request)
    {
        $text = $request->input('text', 'আসসালামু আলাইকুম। ওয়ালটন কাস্টমার কেয়ার থেকে আমি আহসান কবির বলছি।');
        $fileName = $request->input('filename', 'agent_voice');
        
        // IVR Service থেকে ভয়েস সেটিংস নিন
        $ivrKey = $request->input('ivr_key', '1');
        $voiceGender = 'Charon';
        $voiceSpeed = 1.0;
        
        if ($ivrKey) {
            $service = \App\Models\IvrService::where('key_press', $ivrKey)->first();
            if ($service) {
                $voiceGender = $service->voice_gender ?? 'Charon';
                $voiceSpeed = $service->voice_speed ?? 1.0;
            }
        }

        $wavPath = $this->voiceService->generateAgentVoiceCustom($text, $voiceGender, $voiceSpeed, $fileName);

        if ($wavPath) {
            return response()->json([
                'success' => true,
                'message' => "এআই এজেন্ট ভয়েস তৈরি হয়েছে ({$voiceGender}, Speed: {$voiceSpeed}x)",
                'file' => basename($wavPath),
                'path' => $wavPath,
                'url' => asset('asterisk-voices/' . basename($wavPath)),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'ভয়েস তৈরিতে ব্যর্থ - FFmpeg ইনস্টল করুন',
        ], 400);
    }

    /**
     * কমান্ড থেকে ভয়েস তৈরি করো
     */
    public function generateCustomerVoice(Request $request)
    {
        $text = $request->input('text', 'হ্যালো, আমার একটি সমস্যা আছে।');
        $fileName = $request->input('filename', 'customer_voice');

        $wavPath = $this->voiceService->generateCustomerVoice($text, $fileName);

        if ($wavPath) {
            return response()->json([
                'success' => true,
                'message' => 'কাস্টমার ভয়েস তৈরি হয়েছে',
                'file' => basename($wavPath),
                'path' => $wavPath,
                'url' => asset('asterisk-voices/' . basename($wavPath)),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'ভয়েস তৈরিতে ব্যর্থ',
        ], 400);
    }

    /**
     * Asterisk কনফিগারেশন ডিটেইলস দেখাও
     */
    public function getAsteriskConfig()
    {
        return response()->json([
            'config' => $this->voiceService->getAsteriskConfig(),
            'voices' => $this->voiceService->listAvailableVoices(),
        ]);
    }

    /**
     * সমস্ত উপলব্ধ ভয়েস দেখাও
     */
    public function listVoices()
    {
        return response()->json([
            'voices' => $this->voiceService->listAvailableVoices(),
        ]);
    }
}
