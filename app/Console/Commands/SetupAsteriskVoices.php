<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AsteriskVoiceService;

class SetupAsteriskVoices extends Command
{
    protected $signature = 'asterisk:setup-voices';
    protected $description = 'সমস্ত ডিফল্ট Asterisk ভয়েস সেটআপ করুন';

    public function handle()
    {
        $voiceService = new AsteriskVoiceService();

        $this->info('🔊 Asterisk ভয়েস সেটআপ শুরু হচ্ছে...');

        // এআই এজেন্ট ভয়েস
        $this->info('📱 এআই এজেন্ট ভয়েস তৈরি করছি...');
        $agentPath = $voiceService->generateAgentVoice(
            'আসসালামু আলাইকুম। ওয়ালটন কাস্টমার কেয়ার থেকে আমি আহসান কবির বলছি। আপনার সমস্যা নিয়ে কথা বলতে প্রস্তুত।',
            'agent_greeting'
        );

        if ($agentPath) {
            $this->line('✅ এআই ভয়েস সফল: ' . basename($agentPath));
        } else {
            $this->error('❌ এআই ভয়েস ব্যর্থ - FFmpeg চেক করুন');
        }

        // কাস্টমার ভয়েস
        $this->info('📱 কাস্টমার ভয়েস তৈরি করছি...');
        $customerPath = $voiceService->generateCustomerVoice(
            'হ্যালো, আমার একটি সমস্যা আছে।',
            'customer_test'
        );

        if ($customerPath) {
            $this->line('✅ কাস্টমার ভয়েস সফল: ' . basename($customerPath));
        } else {
            $this->error('❌ কাস্টমার ভয়েস ব্যর্থ');
        }

        // কনফিগ দেখান
        $config = $voiceService->getAsteriskConfig();
        $this->info('📋 Asterisk কনফিগারেশন:');
        $this->line('ভয়েস ডিরেক্টরি: ' . $config['voice_directory']);
        $this->line('সাপোর্টেড ফরম্যাট: ' . implode(', ', $config['supported_formats']));
        $this->line('সেম্পল রেট: ' . $config['required_sample_rate']);

        // উপলব্ধ ভয়েস দেখান
        $voices = $voiceService->listAvailableVoices();
        if (!empty($voices)) {
            $this->info('🎵 উপলব্ধ ভয়েস:');
            foreach ($voices as $voice) {
                $this->line('  - ' . $voice['name'] . ' (' . $voice['size'] . ')');
            }
        }

        $this->info('✨ সেটআপ সম্পন্ন!');
    }
}
