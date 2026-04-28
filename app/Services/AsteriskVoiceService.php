<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AsteriskVoiceService
{
    private $ttsKey;
    private $voicePath = 'public/asterisk-voices';

    public function __construct()
    {
        $this->ttsKey = env('GOOGLE_TTS_KEY');
    }

    /**
     * AI Agent এর জন্য WAV ফাইল তৈরি করো (ডিফল্ট)
     */
    public function generateAgentVoice(string $text, string $fileName = 'agent_voice'): ?string
    {
        return $this->generateWavFile($text, $fileName, 'bn-IN-Chirp3-HD-Charon', 1.0);
    }

    /**
     * কাস্টম সেটিংস সহ AI Agent ভয়েস তৈরি করো
     */
    public function generateAgentVoiceCustom(string $text, string $voiceGender = 'Charon', float $voiceSpeed = 1.0, string $fileName = 'agent_voice'): ?string
    {
        // Voice name তৈরি করো: bn-IN-Chirp3-HD-{Gender}
        $voiceName = "bn-IN-Chirp3-HD-{$voiceGender}";
        return $this->generateWavFile($text, $fileName, $voiceName, $voiceSpeed);
    }

    /**
     * কাস্টমার ভয়েসের জন্য WAV ফাইল তৈরি করো (ভিন্ন টোন - ডিফল্ট)
     */
    public function generateCustomerVoice(string $text, string $fileName = 'customer_voice'): ?string
    {
        return $this->generateWavFile($text, $fileName, 'bn-IN-Chirp3-HD-Radha', 0.9);
    }

    /**
     * Google TTS থেকে MP3 নিয়ে আসো এবং WAV তে কনভার্ট করো
     */
    private function generateWavFile(string $text, string $fileName, string $voiceName, float $speed): ?string
    {
        try {
            // ১. Google TTS থেকে MP3 ডাউনলোড করো
            $mp3Response = Http::withoutVerifying()->post("https://texttospeech.googleapis.com/v1/text:synthesize?key={$this->ttsKey}", [
                'input' => ['text' => $text],
                'voice' => ['languageCode' => 'bn-IN', 'name' => $voiceName],
                'audioConfig' => ['audioEncoding' => 'MP3', 'speakingRate' => $speed]
            ]);

            if (!$mp3Response->successful()) {
                return null;
            }

            // ২. Base64 থেকে MP3 ফাইল তৈরি করো
            $audioBase64 = $mp3Response->json('audioContent');
            $audioData = base64_decode($audioBase64);
            
            $tempMp3Path = storage_path('temp/' . uniqid() . '.mp3');
            file_put_contents($tempMp3Path, $audioData);

            // ৩. FFmpeg দিয়ে MP3 কে WAV তে কনভার্ট করো
            $wavFileName = $fileName . '_' . time() . '.wav';
            $wavPath = storage_path($this->voicePath . '/' . $wavFileName);
            
            // WAV ফরম্যাট (৮০০০ Hz, mono - Asterisk এর জন্য সেরা)
            $command = "ffmpeg -i {$tempMp3Path} -acodec pcm_s16le -ar 8000 -ac 1 {$wavPath} -y 2>&1";
            exec($command, $output, $returnCode);

            // ৪. Temp MP3 ফাইল ডিলিট করো
            if (file_exists($tempMp3Path)) {
                unlink($tempMp3Path);
            }

            if ($returnCode === 0 && file_exists($wavPath)) {
                return $wavPath;
            }

            return null;

        } catch (\Exception $e) {
            \Log::error('Voice generation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Asterisk এর জন্য সমস্ত কফিগারেশন তথ্য রিটার্ন করো
     */
    public function getAsteriskConfig(): array
    {
        $baseUrl = asset('asterisk-voices');
        
        return [
            'voice_directory' => storage_path($this->voicePath),
            'public_url' => $baseUrl,
            'supported_formats' => ['wav'],
            'required_sample_rate' => '8000 Hz',
            'channels' => 'mono',
            'codec' => 'pcm_s16le',
            'asterisk_dial_plan' => [
                'play_agent_greeting' => 'Playback(/{$baseUrl}/agent_voice_xxx.wav)',
                'record_customer' => 'Record(/var/spool/asterisk/monitor/customer_${UNIQUEID})',
            ],
        ];
    }

    /**
     * সমস্ত WAV ফাইল দেখাও
     */
    public function listAvailableVoices(): array
    {
        $voicePath = storage_path($this->voicePath);
        
        if (!file_exists($voicePath)) {
            mkdir($voicePath, 0755, true);
        }

        $files = [];
        foreach (glob($voicePath . '/*.wav') as $file) {
            $files[] = [
                'name' => basename($file),
                'path' => $file,
                'url' => asset('asterisk-voices/' . basename($file)),
                'size' => filesize($file) . ' bytes',
            ];
        }

        return $files;
    }
}
