<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\IvrService;
use App\Services\AiCacheService;

class AIController extends Controller
{
    public function processVoice(Request $request)
    {
        // ১. কাস্টমার যা বলেছে সেটা রিসিভ করা
        $userText = $request->input('text');

        // 🚀 ২. Asterisk থেকে আসা বাটন প্রেস রিসিভ করা (যদি Asterisk বাটন না পাঠায়, ডিফল্ট '1' ধরবে)
        $keyPress = $request->input('ivr_key', '1');

        // 🚀 ৩. ডাটাবেস থেকে সার্ভিস বের করা
        $service = IvrService::where('key_press', $keyPress)->where('is_active', true)->first();

        // যদি ওই বাটনের কোনো সার্ভিস না থাকে, তাহলে ডিফল্টভাবে অ্যাকটিভ থাকা প্রথম সার্ভিসটা নেবে
        if (!$service) {
            $service = IvrService::where('is_active', true)->first(); 
        }

        // ৪. এআইয়ের সিস্টেম প্রম্পট রেডি করা (💰 ছোট প্রম্পট = কম খরচ)
        $systemInstruction = "তুমি সাপোর্ট এজেন্ট।";

        if ($service) {
            $fields = [];
            if($service->required_fields) {
                foreach ($service->required_fields as $field) {
                    $fields[] = $field['field_name'] . ($field['is_mandatory'] ? '*' : '');
                }
            }
            // সংক্ষিপ্ত প্রম্পট
            $systemInstruction = $service->system_prompt;
            if (!empty($fields)) {
                $systemInstruction .= "\nতথ্য: " . implode(', ', $fields);
            }
        }

        // ৩. API Key .env ফাইল থেকে নেওয়া
        $apiKey = env('AIzaSyC3GA5D6NRsNFVPvOrCzdAzJxSFC0SnmvA');
        
        // জেমিনি ১.৫ ফ্ল্যাশ মডেলের ইউআরএল
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

        // ৪. জেমিনির কাছে ডাটা পাঠানো
        $response = Http::post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemInstruction . "\n\nকাস্টমারের কথা: " . $userText]
                    ]
                ]
            ]
        ]);

        // ৫. জেমিনির উত্তর ধরে ফ্রন্টএন্ডে পাঠানো
        if ($response->successful()) {
            $aiReply = $response->json()['candidates'][0]['content']['parts'][0]['text'] ?? 'দুঃখিত, আমি কথাটি ঠিক বুঝতে পারিনি।';
            return response()->json(['reply' => $aiReply]);
        }

        return response()->json(['reply' => 'সার্ভারে একটু সমস্যা হচ্ছে, দয়া করে আবার চেষ্টা করুন।'], 500);
    }
}