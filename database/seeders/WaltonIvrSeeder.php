<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IvrService;
use Illuminate\Support\Facades\DB;

class WaltonIvrSeeder extends Seeder
{
    /**
     * Walton এর actual service request form অনুযায়ী IVR তৈরি
     * Run: php artisan db:seed --class=WaltonIvrSeeder
     */
    public function run(): void
    {
        // আগের সব IVR মুছে ফেলা (foreign key constraint এর কারণে delete ব্যবহার)
        IvrService::query()->delete();
        
        $this->command->warn("🗑️  আগের সব IVR মুছে ফেলা হচ্ছে...");
        
        // ═══════════════════════════════════════════════════════════
        // 🔴 IVR #1: NORMAL (Long Prompts) - Natural & Detailed
        // ═══════════════════════════════════════════════════════════
        
        $normalIvr = IvrService::create([
            'key_press' => '1',
            'service_name' => '🔴 Walton সাপোর্ট (Normal - বিস্তারিত)',
            'is_active' => true,
            'serial_order' => 1,
            
            // Voice Settings - Database এ store হবে
            'voice_gender' => 'Charon', // Male voice
            'voice_speed' => 1.0,
            
            'ai_name' => 'করিম',
            'greeting_message' => "আসসালামু আলাইকুম। আমি ওয়ালটন কাস্টমার সাপোর্টের এআই এজেন্ট করিম। আপনার সমস্যা সমাধানে আমি এখানে আছি। আপনার প্রোডাক্টে কী সমস্যা হয়েছে আমাকে জানান।",
            
            'system_prompt' => "আপনি ওয়ালটন বাংলাদেশের একজন অভিজ্ঞ কাস্টমার সাপোর্ট এজেন্ট। আপনার কাজ হলো কাস্টমারদের সমস্যা শুনে প্রয়োজনীয় তথ্য সংগ্রহ করা এবং সার্ভিস রিকোয়েস্ট তৈরি করা। আপনাকে অবশ্যই ভদ্র এবং সহায়ক হতে হবে। প্রতিটি তথ্য সুন্দরভাবে জিজ্ঞেস করুন এবং কাস্টমার যা বলছে তা মনোযোগ দিয়ে শুনুন। তথ্য পেলে রিপিট করে কনফার্ম করুন।",
            
            'required_fields' => [
                [
                    'field_name' => 'customer_name',
                    'ai_instruction' => 'কাস্টমারের কাছে তার পুরো নাম জিজ্ঞেস করুন। নাম পেলে অবশ্যই রিপিট করে কনফার্ম করুন যে সঠিকভাবে শুনতে পেরেছেন কিনা।',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'needs_confirmation' => true,
                    'max_retries' => 3,
                    'convincing_logic' => 'স্যার/ম্যাডাম, সার্ভিস রেজিস্ট্রেশনের জন্য আপনার নামটি অত্যন্ত প্রয়োজন। দয়া করে আপনার পুরো নাম বলুন।',
                    'error_message' => 'দুঃখিত, আপনার নামটি সঠিকভাবে শুনতে পারিনি। অনুগ্রহ করে আরেকবার বলুন।',
                ],
                [
                    'field_name' => 'mobile_number',
                    'ai_instruction' => 'কাস্টমারের মোবাইল নম্বর জিজ্ঞেস করুন এবং বলুন যে এটি যোগাযোগের জন্য অত্যন্ত জরুরি। নম্বর অবশ্যই ১১ ডিজিটের হতে হবে এবং ০১ দিয়ে শুরু হতে হবে। নম্বর পেলে রিপিট করে কনফার্ম করুন।',
                    'is_mandatory' => true,
                    'is_blocking' => true,
                    'needs_confirmation' => true,
                    'max_retries' => 3,
                    'min_length' => 11,
                    'max_length' => 11,
                    'expected_format' => '01XXXXXXXXX',
                    'convincing_logic' => 'স্যার/ম্যাডাম, আমাদের টেকনিশিয়ান আপনার সাথে যোগাযোগ করতে এই নম্বর ব্যবহার করবেন। এটি ছাড়া সার্ভিস প্রদান সম্ভব নয়। অনুগ্রহ করে আপনার ১১ ডিজিটের মোবাইল নম্বর দিন।',
                    'error_message' => 'দুঃখিত, এই নম্বরটি সঠিক নয়। বাংলাদেশি মোবাইল নম্বর অবশ্যই ১১ ডিজিটের হতে হবে এবং ০১ দিয়ে শুরু হতে হবে। অনুগ্রহ করে সঠিক নম্বর দিন।',
                ],
                [
                    'field_name' => 'alt_mobile_number',
                    'ai_instruction' => 'কাস্টমারকে জিজ্ঞেস করুন তার কোনো বিকল্প মোবাইল নম্বর আছে কিনা। যদি থাকে তাহলে সেটা নিন, না থাকলে সমস্যা নেই।',
                    'is_mandatory' => false,
                    'is_blocking' => false,
                    'max_retries' => 1,
                    'convincing_logic' => 'বিকল্প নম্বর থাকলে দিতে পারেন, না থাকলে কোনো সমস্যা নেই।',
                ],
                [
                    'field_name' => 'address',
                    'ai_instruction' => 'কাস্টমারের পুরো ঠিকানা জিজ্ঞেস করুন। বাসা/বাড়ির নম্বর, রোড, এলাকা সহ বিস্তারিত ঠিকানা নিন। ঠিকানা পেলে কনফার্ম করুন।',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'needs_confirmation' => true,
                    'max_retries' => 2,
                    'convincing_logic' => 'টেকনিশিয়ান আপনার কাছে যাওয়ার জন্য পুরো ঠিকানা জানা অত্যন্ত জরুরি। অনুগ্রহ করে আপনার সম্পূর্ণ ঠিকানা বলুন।',
                ],
                [
                    'field_name' => 'district',
                    'ai_instruction' => 'কাস্টমারের জেলার নাম জিজ্ঞেস করুন। যেমন: ঢাকা, চট্টগ্রাম, সিলেট ইত্যাদি।',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'max_retries' => 2,
                    'convincing_logic' => 'সঠিক সার্ভিস সেন্টার নির্ধারণের জন্য আপনার জেলার নাম জানা দরকার।',
                ],
                [
                    'field_name' => 'product_name',
                    'ai_instruction' => 'কাস্টমারের কোন ওয়ালটন প্রোডাক্টে সমস্যা হয়েছে তা জিজ্ঞেস করুন। যেমন: টিভি, ফ্রিজ, এসি, ওয়াশিং মেশিন ইত্যাদি। প্রোডাক্ট নাম পেলে কনফার্ম করুন।',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'needs_confirmation' => true,
                    'max_retries' => 2,
                ],
                [
                    'field_name' => 'barcode',
                    'ai_instruction' => 'প্রোডাক্টের বারকোড বা সিরিয়াল নম্বর জিজ্ঞেস করুন। যদি কাস্টমার না জানে তাহলে বলুন যে প্রোডাক্টের পিছনে বা নিচে একটা স্টিকার আছে যেখানে নম্বর লেখা আছে। না থাকলেও চলবে।',
                    'is_mandatory' => false,
                    'is_blocking' => false,
                    'max_retries' => 1,
                    'convincing_logic' => 'বারকোড থাকলে আমরা দ্রুত সার্ভিস দিতে পারব। প্রোডাক্টের পিছনে বা নিচে দেখুন।',
                ],
                [
                    'field_name' => 'problem_description',
                    'ai_instruction' => 'কাস্টমারকে তার প্রোডাক্টের সমস্যা বিস্তারিত বলতে বলুন। সমস্যা শুনে তাকে আশ্বস্ত করুন যে আমরা শীঘ্রই সমাধান করব। যদি সঠিকভাবে বুঝাতে না পারে তাহলে ধৈর্য ধরে শুনুন এবং সাহায্য করুন।',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'max_retries' => 2,
                    'convincing_logic' => 'আপনার সমস্যা বিস্তারিত জানলে আমরা আরও ভালোভাবে সাহায্য করতে পারব। অনুগ্রহ করে সমস্যাটি খুলে বলুন।',
                ],
                [
                    'field_name' => 'service_center',
                    'ai_instruction' => 'কাস্টমারকে জিজ্ঞেস করুন তিনি কোন সার্ভিস সেন্টারে প্রোডাক্ট আনতে চান বা কোন সার্ভিস সেন্টার তার কাছাকাছি। যদি না জানেন তাহলে তার জেলার নাম অনুযায়ী সাজেশন দিন।',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'max_retries' => 2,
                    'convincing_logic' => 'আপনার নিকটস্থ সার্ভিস সেন্টার জানলে আমরা দ্রুত সার্ভিস দিতে পারব। যদি না জানেন তাহলে আমরা আপনার জেলা অনুযায়ী ঠিক করে দেব।',
                ],
                [
                    'field_name' => 'brand',
                    'ai_instruction' => 'Brand হিসেবে WALTON ব্যবহার করো। কাস্টমারকে জিজ্ঞেস করার দরকার নেই, automatically WALTON set হবে।',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'max_retries' => 0,
                ],
                [
                    'field_name' => 'comments',
                    'ai_instruction' => 'কাস্টমারকে জিজ্ঞেস করুন তার আর কোনো কিছু বলার আছে কিনা বা কোনো বিশেষ নির্দেশনা দিতে চান কিনা। এটি ঐচ্ছিক।',
                    'is_mandatory' => false,
                    'is_blocking' => false,
                    'max_retries' => 1,
                    'convincing_logic' => 'আর কিছু বলার থাকলে বলতে পারেন, না থাকলে সমস্যা নেই।',
                ],
            ],
        ]);

        $this->command->info("✅ IVR #1 (Button 1): Normal - বিস্তারিত prompts তৈরি হয়েছে");

        // ═══════════════════════════════════════════════════════════
        // 💰 IVR #2: COST-OPTIMIZED (Short Prompts) - সংক্ষিপ্ত
        // ═══════════════════════════════════════════════════════════
        
        $optimizedIvr = IvrService::create([
            'key_press' => '2',
            'service_name' => '💰 Walton সাপোর্ট (Optimized - সংক্ষিপ্ত)',
            'is_active' => true,
            'serial_order' => 2,
            
            // Voice Settings
            'voice_gender' => 'Radha', // Female voice
            'voice_speed' => 1.1,
            
            'ai_name' => 'সামিয়া',
            'greeting_message' => "আসসালামু আলাইকুম। ওয়ালটন সাপোর্ট। বলুন।",
            
            'system_prompt' => "তুমি ওয়ালটন এআই এজেন্ট। নিয়ম: সংক্ষিপ্ত (max 12 শব্দ), দ্রুত তথ্য নাও, ভদ্র থাকো।",
            
            'required_fields' => [
                [
                    'field_name' => 'customer_name',
                    'ai_instruction' => 'নাম জিজ্ঞেস করো।',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'needs_confirmation' => true,
                    'max_retries' => 2,
                    'convincing_logic' => 'নাম লাগবে।',
                    'error_message' => 'নাম আবার বলুন।',
                ],
                [
                    'field_name' => 'mobile_number',
                    'ai_instruction' => 'মোবাইল নম্বর চাও (11 ডিজিট)।',
                    'is_mandatory' => true,
                    'is_blocking' => true,
                    'needs_confirmation' => true,
                    'max_retries' => 2,
                    'min_length' => 11,
                    'max_length' => 11,
                    'expected_format' => '01XXXXXXXXX',
                    'convincing_logic' => 'যোগাযোগের জন্য দরকার।',
                    'error_message' => '01 দিয়ে 11 ডিজিট দাও।',
                ],
                [
                    'field_name' => 'alt_mobile_number',
                    'ai_instruction' => 'বিকল্প নম্বর আছে?',
                    'is_mandatory' => false,
                    'is_blocking' => false,
                    'max_retries' => 1,
                ],
                [
                    'field_name' => 'address',
                    'ai_instruction' => 'ঠিকানা বলুন।',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'needs_confirmation' => true,
                    'max_retries' => 2,
                    'convincing_logic' => 'ঠিকানা লাগবে টেকনিশিয়ানের জন্য।',
                ],
                [
                    'field_name' => 'district',
                    'ai_instruction' => 'জেলা কোনটা?',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'max_retries' => 2,
                ],
                [
                    'field_name' => 'product_name',
                    'ai_instruction' => 'কোন পণ্য?',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'needs_confirmation' => true,
                    'max_retries' => 2,
                ],
                [
                    'field_name' => 'barcode',
                    'ai_instruction' => 'বারকোড আছে? (ঐচ্ছিক)',
                    'is_mandatory' => false,
                    'is_blocking' => false,
                    'max_retries' => 1,
                ],
                [
                    'field_name' => 'problem_description',
                    'ai_instruction' => 'কী সমস্যা? সংক্ষেপে।',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'max_retries' => 2,
                    'convincing_logic' => 'সমস্যা জানলে সমাধান দিতে পারব।',
                ],
                [
                    'field_name' => 'service_center',
                    'ai_instruction' => 'কোন সেন্টারে আনবেন?',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'max_retries' => 1,
                    'convincing_logic' => 'সেন্টার লাগবে।',
                ],
                [
                    'field_name' => 'brand',
                    'ai_instruction' => 'Brand: WALTON (auto-set)।',
                    'is_mandatory' => true,
                    'is_blocking' => false,
                    'max_retries' => 0,
                ],
                [
                    'field_name' => 'comments',
                    'ai_instruction' => 'আর কিছু?',
                    'is_mandatory' => false,
                    'is_blocking' => false,
                    'max_retries' => 1,
                ],
            ],
        ]);

        $this->command->info("✅ IVR #2 (Button 2): Optimized - সংক্ষিপ্ত prompts তৈরি হয়েছে");
        
        $this->command->line("");
        $this->command->line("═══════════════════════════════════════════");
        $this->command->line("🎯 Walton IVR Setup সম্পন্ন!");
        $this->command->line("═══════════════════════════════════════════");
        $this->command->line("");
        $this->command->line("📞 Button 1: Normal IVR (বিস্তারিত)");
        $this->command->line("   - Voice: Charon (Male)");
        $this->command->line("   - বেশি খরচ কিন্তু Natural");
        $this->command->line("");
        $this->command->line("💰 Button 2: Optimized IVR (সংক্ষিপ্ত)");
        $this->command->line("   - Voice: Radha (Female)");
        $this->command->line("   - কম খরচ (70-80% সাশ্রয়)");
        $this->command->line("");
        $this->command->line("🎙️ Voice Settings:");
        $this->command->line("   - Database এ store হয়েছে");
        $this->command->line("   - Admin Panel থেকে change করতে পারবেন");
        $this->command->line("");
        $this->command->line("📋 Fields (Walton Service Request অনুযায়ী):");
        $this->command->line("   1. Customer Name");
        $this->command->line("   2. Mobile Number (11 digit)");
        $this->command->line("   3. Alternative Mobile (optional)");
        $this->command->line("   4. Address (full)");
        $this->command->line("   5. District");
        $this->command->line("   6. Product Name");
        $this->command->line("   7. Barcode (optional)");
        $this->command->line("   8. Problem Description");
        $this->command->line("   9. Service Center");
        $this->command->line("   10. Brand (WALTON - auto-set)");
        $this->command->line("   11. Comments (optional)");
        $this->command->line("");
    }
}
