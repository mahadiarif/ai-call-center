<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ApiKeyController extends Controller
{
    /**
     * API Keys দেখান (Admin শুধু)
     */
    public function show()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $envFile = base_path('.env');
        $googleTtsKey = env('GOOGLE_TTS_KEY', 'Not Set');
        $geminiKey = env('GEMINI_API_KEY', 'Not Set');

        return response()->json([
            'google_tts_key' => substr($googleTtsKey, 0, 10) . '***' . substr($googleTtsKey, -5),
            'gemini_api_key' => substr($geminiKey, 0, 10) . '***' . substr($geminiKey, -5),
            'message' => 'API Keys are partially shown for security'
        ]);
    }

    /**
     * API Keys আপডেট করুন
     */
    public function update(Request $request)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'google_tts_key' => 'nullable|string',
            'gemini_api_key' => 'nullable|string',
        ]);

        try {
            $envFile = base_path('.env');
            $contents = File::get($envFile);

            // GOOGLE_TTS_KEY আপডেট করো
            if ($request->has('google_tts_key') && !empty($validated['google_tts_key'])) {
                $contents = $this->updateEnvValue($contents, 'GOOGLE_TTS_KEY', $validated['google_tts_key']);
            }

            // GEMINI_API_KEY আপডেট করো
            if ($request->has('gemini_api_key') && !empty($validated['gemini_api_key'])) {
                $contents = $this->updateEnvValue($contents, 'GEMINI_API_KEY', $validated['gemini_api_key']);
            }

            // ফাইলে লেখো
            File::put($envFile, $contents);

            // ক্যাশ ক্লিয়ার করো
            \Artisan::call('config:clear');

            return response()->json([
                'success' => true,
                'message' => 'API Keys আপডেট হয়েছে সফলভাবে',
                'keys_updated' => array_keys(array_filter($validated))
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Key আপডেট ব্যর্থ: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * .env ফাইলে ভ্যালু আপডেট করো
     */
    private function updateEnvValue($contents, $key, $value)
    {
        $pattern = "/^{$key}=.*/m";

        if (preg_match($pattern, $contents)) {
            // Key পরিবর্তন করো
            $contents = preg_replace($pattern, "{$key}={$value}", $contents);
        } else {
            // নতুন Key যোগ করো (শেষে)
            $contents .= "\n{$key}={$value}";
        }

        return $contents;
    }

    /**
     * API Key ট্যাস্ট করো
     */
    public function test(Request $request)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $testType = $request->input('test', 'both');
        $results = [];

        // Google TTS টেস্ট করো
        if ($testType === 'google' || $testType === 'both') {
            $ttsKey = env('GOOGLE_TTS_KEY');
            if (empty($ttsKey)) {
                $results['google_tts'] = ['status' => 'error', 'message' => 'API Key নির্ধারিত নয়'];
            } else {
                try {
                    $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                        ->post("https://texttospeech.googleapis.com/v1/text:synthesize?key={$ttsKey}", [
                            'input' => ['text' => 'পরীক্ষা'],
                            'voice' => ['languageCode' => 'bn-IN', 'name' => 'bn-IN-Chirp3-HD-Charon'],
                            'audioConfig' => ['audioEncoding' => 'MP3']
                        ]);

                    if ($response->successful()) {
                        $results['google_tts'] = ['status' => 'success', 'message' => 'Google TTS API সংযোগ সফল'];
                    } else {
                        $results['google_tts'] = ['status' => 'error', 'message' => $response->json('error.message') ?? 'Connection Failed'];
                    }
                } catch (\Exception $e) {
                    $results['google_tts'] = ['status' => 'error', 'message' => $e->getMessage()];
                }
            }
        }

        // Gemini API টেস্ট করো
        if ($testType === 'gemini' || $testType === 'both') {
            $geminiKey = env('GEMINI_API_KEY');
            if (empty($geminiKey)) {
                $results['gemini'] = ['status' => 'error', 'message' => 'API Key নির্ধারিত নয়'];
            } else {
                try {
                    $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                        ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$geminiKey}", [
                            'contents' => [['role' => 'user', 'parts' => [['text' => 'হ্যালো']]]]
                        ]);

                    if ($response->successful()) {
                        $results['gemini'] = ['status' => 'success', 'message' => 'Gemini API সংযোগ সফল'];
                    } else {
                        $results['gemini'] = ['status' => 'error', 'message' => $response->json('error.message') ?? 'Connection Failed'];
                    }
                } catch (\Exception $e) {
                    $results['gemini'] = ['status' => 'error', 'message' => $e->getMessage()];
                }
            }
        }

        return response()->json($results);
    }
}
