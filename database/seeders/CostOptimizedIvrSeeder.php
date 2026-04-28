<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IvrService;

class CostOptimizedIvrSeeder extends Seeder
{
    /**
     * Run: php artisan db:seed --class=CostOptimizedIvrSeeder
     * 
     * দুটো IVR তৈরি করবে:
     * Button 1: Normal (লম্বা prompts - বেশি খরচ)
     * Button 3: Cost-Optimized (ছোট prompts - কম খরচ)
     */
    public function run(): void
    {
        // ═══════════════════════════════════════════════════════════
        // 🔴 IVR #1: NORMAL (Long Prompts) - বর্তমান স্টাইল
        // ═══════════════════════════════════════════════════════════
        
        $normalIvr = IvrService::updateOrCreate(
            ['key_press' => '1'],
            [
                'service_name' => 'Walton TV সাপোর্ট (Normal - লম্বা Prompt)',
                'is_active' => true,
                'system_prompt' => "আপনি ওয়ালটন কোম্পানির একজন অত্যন্ত দক্ষ এবং অভিজ্ঞ কাস্টমার সাপোর্ট এজেন্ট। আপনার প্রধান কাজ হলো কাস্টমারদের সমস্যা শুনে তাদের সাহায্য করা এবং প্রয়োজনীয় তথ্য সংগ্রহ করা। আপনাকে অবশ্যই ভদ্র এবং সুন্দর ব্যবহার করতে হবে এবং কাস্টমারদের সাথে ধৈর্য ধরে কথা বলতে হবে। প্রতিটি প্রশ্ন সুন্দরভাবে করুন এবং কাস্টমার যা বলছে তা মনোযোগ দিয়ে শুনুন।",
                'greeting_message' => "আসসালামু আলাইকুম। আমি ওয়ালটনের একজন এআই সাপোর্ট এজেন্ট। আপনাকে ওয়ালটন কল সেন্টারে স্বাগতম। আপনার সমস্যা সমাধানে আমি এখানে আছি। আপনি কেমন আছেন? আপনার কি কোনো সমস্যা হয়েছে?",
                'ai_name' => 'রহিমা',
                'required_fields' => [
                    [
                        'field_name' => 'customer_name',
                        'ai_instruction' => 'কাস্টমারের কাছে খুব ভদ্রভাবে এবং সুন্দরভাবে তার পুরো নাম জিজ্ঞেস করুন। যদি কাস্টমার শুধু প্রথম নাম দেয় তাহলে পুরো নাম চান। নাম পেলে অবশ্যই রিপিট করে কনফার্ম করুন।',
                        'is_mandatory' => true,
                        'is_blocking' => false,
                        'needs_confirmation' => true,
                        'max_retries' => 3,
                        'convincing_logic' => 'স্যার/ম্যাডাম, আপনার নামটি আমাদের অত্যন্ত প্রয়োজন কারণ এটি ছাড়া আমরা আপনার জন্য সঠিকভাবে সার্ভিস রেজিস্টার করতে পারব না। দয়া করে আপনার পুরো নামটি বলুন।',
                        'error_message' => 'দুঃখিত, আপনার নামটি সঠিকভাবে শুনতে পারিনি। অনুগ্রহ করে আরেকবার বলুন।',
                    ],
                    [
                        'field_name' => 'mobile_number',
                        'ai_instruction' => 'কাস্টমারের মোবাইল নম্বর জিজ্ঞেস করুন এবং ব্যাখ্যা করুন যে এটি যোগাযোগের জন্য অত্যন্ত প্রয়োজনীয়। নম্বর অবশ্যই 11 ডিজিটের হতে হবে এবং 01 দিয়ে শুরু হতে হবে। নম্বর পেলে রিপিট করে কনফার্ম করুন।',
                        'is_mandatory' => true,
                        'is_blocking' => true,
                        'needs_confirmation' => true,
                        'max_retries' => 3,
                        'min_length' => 11,
                        'max_length' => 11,
                        'expected_format' => '01XXXXXXXXX',
                        'convincing_logic' => 'স্যার/ম্যাডাম, আপনার মোবাইল নম্বরটি অত্যন্ত জরুরি কারণ আমাদের টেকনিশিয়ান আপনার সাথে যোগাযোগ করতে এই নম্বর ব্যবহার করবেন। এটি ছাড়া সার্ভিস প্রদান সম্ভব নয়। অনুগ্রহ করে আপনার 11 ডিজিটের মোবাইল নম্বর দিন।',
                        'error_message' => 'দুঃখিত, এই নম্বরটি সঠিক ফরম্যাটে নেই। বাংলাদেশি মোবাইল নম্বর অবশ্যই 11 ডিজিটের হতে হবে এবং 01 দিয়ে শুরু হতে হবে। অনুগ্রহ করে সঠিক নম্বর দিন।',
                    ],
                    [
                        'field_name' => 'address',
                        'ai_instruction' => 'কাস্টমারের এলাকা বা ঠিকানা জিজ্ঞেস করুন। বিস্তারিত ঠিকানা না চেয়ে শুধু এলাকা বা জেলার নাম নিন। ঠিকানা পেলে কনফার্ম করুন।',
                        'is_mandatory' => true,
                        'is_blocking' => false,
                        'needs_confirmation' => true,
                        'max_retries' => 2,
                        'convincing_logic' => 'আপনার এলাকা জানা দরকার যাতে আমরা সঠিক টেকনিশিয়ান পাঠাতে পারি। অনুগ্রহ করে আপনার এলাকার নাম বলুন।',
                    ],
                    [
                        'field_name' => 'product_name',
                        'ai_instruction' => 'কাস্টমারের কোন প্রোডাক্টে সমস্যা হয়েছে তা জিজ্ঞেস করুন। টিভি, ফ্রিজ, এসি ইত্যাদি কোনটি সেটা জানতে চান। প্রোডাক্ট নাম পেলে কনফার্ম করুন।',
                        'is_mandatory' => true,
                        'is_blocking' => false,
                        'max_retries' => 2,
                    ],
                    [
                        'field_name' => 'problem_description',
                        'ai_instruction' => 'কাস্টমারকে তার প্রোডাক্টের সমস্যা বিস্তারিত বলতে বলুন। সমস্যা শুনে তাকে আশ্বস্ত করুন যে আমরা শীঘ্রই সমাধান করব। যদি কাস্টমার সঠিকভাবে বুঝাতে না পারে তাহলে তাকে সাহায্য করুন এবং ধৈর্য ধরুন।',
                        'is_mandatory' => true,
                        'is_blocking' => false,
                        'max_retries' => 2,
                        'convincing_logic' => 'আপনার সমস্যা বিস্তারিত জানলে আমরা আরও ভালোভাবে সাহায্য করতে পারব। অনুগ্রহ করে সমস্যাটি একটু খুলে বলুন।',
                    ],
                ],
            ]
        );

        $this->command->info("✅ IVR #1 (Button 1): Normal Long Prompts - তৈরি হয়েছে");

        // ═══════════════════════════════════════════════════════════
        // 🟢 IVR #3: COST-OPTIMIZED (Short Prompts) - নতুন স্টাইল
        // ═══════════════════════════════════════════════════════════
        
        $optimizedIvr = IvrService::updateOrCreate(
            ['key_press' => '3'],
            [
                'service_name' => 'Walton TV সাপোর্ট (Optimized - ছোট Prompt) 💰',
                'is_active' => true,
                'system_prompt' => "তুমি ওয়ালটন এআই এজেন্ট। নিয়ম: সংক্ষিপ্ত (max 15 শব্দ), দ্রুত তথ্য নাও, ভদ্র থাকো।",
                'greeting_message' => "আসসালামু আলাইকুম। ওয়ালটন সাপোর্ট। বলুন।",
                'ai_name' => 'রহিমা',
                'required_fields' => [
                    [
                        'field_name' => 'customer_name',
                        'ai_instruction' => 'নাম জিজ্ঞেস করো।',
                        'is_mandatory' => true,
                        'is_blocking' => false,
                        'needs_confirmation' => true,
                        'max_retries' => 2,
                        'convincing_logic' => 'নাম লাগবে সেবার জন্য।',
                        'error_message' => 'নাম ভুল, আবার দাও।',
                    ],
                    [
                        'field_name' => 'mobile_number',
                        'ai_instruction' => 'মোবাইল চাও (11 ডিজিট)।',
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
                        'field_name' => 'address',
                        'ai_instruction' => 'এলাকা কোথায়?',
                        'is_mandatory' => true,
                        'is_blocking' => false,
                        'needs_confirmation' => true,
                        'max_retries' => 2,
                        'convincing_logic' => 'এলাকা দরকার টেকনিশিয়ান পাঠাতে।',
                    ],
                    [
                        'field_name' => 'product_name',
                        'ai_instruction' => 'কোন পণ্য?',
                        'is_mandatory' => true,
                        'is_blocking' => false,
                        'max_retries' => 2,
                    ],
                    [
                        'field_name' => 'problem_description',
                        'ai_instruction' => 'কী সমস্যা? সংক্ষেপে।',
                        'is_mandatory' => true,
                        'is_blocking' => false,
                        'max_retries' => 2,
                        'convincing_logic' => 'সমস্যা জানলে সমাধান দিতে পারব।',
                    ],
                ],
            ]
        );

        $this->command->info("✅ IVR #3 (Button 3): Cost-Optimized Short Prompts - তৈরি হয়েছে");
        
        $this->command->line("");
        $this->command->line("═══════════════════════════════════════════");
        $this->command->line("🎯 A/B Testing Setup সম্পন্ন!");
        $this->command->line("═══════════════════════════════════════════");
        $this->command->line("");
        $this->command->line("📞 Button 1: Normal IVR (লম্বা prompts)");
        $this->command->line("   - বেশি খরচ হবে (বেশি tokens)");
        $this->command->line("   - Natural sounding");
        $this->command->line("");
        $this->command->line("💰 Button 3: Optimized IVR (ছোট prompts)");
        $this->command->line("   - কম খরচ (70-80% কম tokens)");
        $this->command->line("   - সংক্ষিপ্ত কিন্তু কার্যকর");
        $this->command->line("");
        $this->command->line("🧪 Test করুন:");
        $this->command->line("   1. mic-test.blade.php এ Button 1 select করে test করুন");
        $this->command->line("   2. তারপর Button 3 select করে test করুন");
        $this->command->line("   3. দুটোর response এবং billing compare করুন");
        $this->command->line("");
    }
}
