<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Models\KnowledgeBase;
use App\Models\CustomerMemory;
use Illuminate\Support\Facades\Cache;
use Google\Client;

class AIFormController extends Controller
{
    // ১. গুগল থেকে লাইভ টোকেন নেওয়া
    private function getGoogleToken() {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/ai-calls-center-70b747d09902.json'));
        $client->addScope('https://www.googleapis.com/auth/cloud-platform');
        $token = $client->fetchAccessTokenWithAssertion();
        return $token['access_token'];
    }

    // ২. ফ্রন্টএন্ডে টোকেন এবং প্রম্পট পাঠানো (মাস্টারমাইন্ড আপডেটেড)
    public function getLiveSetup()
    {
        try {
            $token = $this->getGoogleToken();
            
            // ১. IVR Key ধরা 
            $keyPress = request()->input('ivr_key', '1'); 
            $service = \App\Models\IvrService::where('key_press', $keyPress)->where('is_active', true)->first();
            if (!$service) {
                $service = \App\Models\IvrService::where('is_active', true)->first();
            }

            // 🚀 ২. ড্যাশবোর্ড থেকে কোম্পানির গ্লোবাল প্রোফাইল টেনে আনা
            $company = \App\Models\CompanyProfile::where('is_active', true)->first();
            
            $companyName = $company ? $company->company_name : "আমাদের কোম্পানি";
            $aboutCompany = $company ? "কোম্পানির বিস্তারিত: " . $company->about_company . "\n" : "";
            $globalPersona = $company ? $company->global_persona : "তুমি একজন প্রফেশনাল কাস্টমার সাপোর্ট এজেন্ট।";
            
            // 🚀 ৩. কন্টাক্ট ইনফো (আনলিমিটেড ফিল্ডস) রেডি করা
            $contactInfoText = "";
            if ($company && $company->contact_info) {
                $contactInfoText = "কোম্পানির অন্যান্য তথ্য (প্রয়োজনে কাস্টমারকে দেবে):\n";
                foreach ($company->contact_info as $key => $value) {
                    $contactInfoText .= "- {$key}: {$value}\n";
                }
            }

            // 🚀 ৪. ডাইনামিক রুলস ফিল্টার করা (যেগুলো Global অথবা এই নির্দিষ্ট IVR এর জন্য)
            $dynamicRulesText = "";
            if ($company && $company->dynamic_instructions) {
                $dynamicRulesText = "\nবিশেষ নিয়মাবলি (অবশ্যই মানতে হবে):\n";
                foreach ($company->dynamic_instructions as $rule) {
                    if ($rule['is_active']) {
                        // যদি রুলসটা গ্লোবাল হয়, অথবা বর্তমান সার্ভিসের সাথে মিলে যায়
                        if (empty($rule['ivr_service_id']) || $rule['ivr_service_id'] == ($service ? $service->id : null)) {
                            $dynamicRulesText .= "👉 {$rule['rule_title']}: {$rule['rule_details']}\n";
                        }
                    }
                }
            }

            $aiName = ($service && $service->ai_name) ? "তোমার নাম {$service->ai_name}। " : "";
            
            // 🎙️ ভয়েস জেন্ডার ইনফো
            $voiceGender = $service && $service->voice_gender ? $service->voice_gender : 'Charon';
            $voiceSpeed = $service && $service->voice_speed ? $service->voice_speed : 1.0;
            
            $voiceGenderText = "";
            if ($voiceGender === 'Radha') {
                // Female voice - কাস্টমার নারী হলে "Madam" বলবে, পুরুষ হলে "Sister" বলবে
                $voiceGenderText = "আপনি একজন নারী এআই এজেন্ট। আপনার ভয়েস নারীর মতো (Radha voice)।\n";
                $voiceGenderText .= "- পুরুষ কাস্টমার হলে: 'Sir' দিয়ে address করুন\n";
                $voiceGenderText .= "- নারী কাস্টমার হলে: 'Madam' বা 'Sister' দিয়ে address করুন\n";
                $voiceGenderText .= "- অনিশ্চিত হলে 'আপনি' বা নাম ব্যবহার করুন\n";
            } else {
                // Male voice - কাস্টমার পুরুষ হলে "Sir" বলবে, নারী হলে "Madam" বলবে
                $voiceGenderText = "আপনি একজন পুরুষ এআই এজেন্ট। আপনার ভয়েস পুরুষের মতো (Charon voice)।\n";
                $voiceGenderText .= "- পুরুষ কাস্টমার হলে: 'Sir' দিয়ে address করুন\n";
                $voiceGenderText .= "- নারী কাস্টমার হলে: 'Madam' বা 'Sister' দিয়ে address করুন\n";
                $voiceGenderText .= "- অনিশ্চিত হলে 'আপনি' বা নাম ব্যবহার করুন\n";
            }
            $voiceGenderText .= "Voice Speed: {$voiceSpeed}x (এই গতিতে কথা বলতে হবে)\n\n";
            
            // 🚀 ৫. গ্রিটিংস কন্ট্রোল (Company Profile থেকে)
            $aiGreeting = "";
            $greetingBehavior = $company ? $company->greeting_behavior : 'ai_first';

            if ($service && $service->greeting_message) {
                if ($greetingBehavior == 'ai_first') {
                    $aiGreeting = "কল আসার পর প্রথমে আপনি এক্সাক্টলি এটা বলবেন: '{$service->greeting_message}'\n\n";
                } else {
                    $aiGreeting = "কাস্টমার 'হ্যালো' বা কোনো কথা বললে, আপনি প্রথমে এক্সাক্টলি এটা বলবেন: '{$service->greeting_message}'\n\n";
                }
            }

            // 🚀 Caller Number Auto-Detection
            $callerNumber = request()->input('caller_number');
            $callerNumberText = "";
            if ($callerNumber) {
                $callerNumberText = "\n[📞 Caller Number - PRIMARY MOBILE]\n";
                $callerNumberText .= "• কাস্টমার এই নম্বর থেকে কল করেছে: {$callerNumber}\n";
                $callerNumberText .= "• এটাই তার primary mobile number - JSON এ mobile_number = {$callerNumber}\n";
                $callerNumberText .= "• 🚫 Customer কে primary number জিজ্ঞেস করবে না (already আছে)\n";
                $callerNumberText .= "• ✅ শুধু alternate number জিজ্ঞেস করবে (if field আছে)\n";
                $callerNumberText .= "• Customer জানে তুমি তার running number জানো (যেটা দিয়ে call করেছে)\n\n";
            }

            // 🇧🇩 Bangladesh Districts (64 জেলা)
            $districtsList = "\n[🇧🇩 Bangladesh Districts - ৬৪টি জেলা মনে রাখো]\n";
            $districtsList .= "ঢাকা বিভাগ: ঢাকা, গাজীপুর, নারায়ণগঞ্জ, টাঙ্গাইল, মানিকগঞ্জ, মুন্সিগঞ্জ, নরসিংদী, কিশোরগঞ্জ, গোপালগঞ্জ, ফরিদপুর, মাদারীপুর, রাজবাড়ী, শরীয়তপুর\n";
            $districtsList .= "চট্টগ্রাম বিভাগ: চট্টগ্রাম, কক্সবাজার, রাঙামাটি, বান্দরবান, খাগড়াছড়ি, ফেনী, লক্ষ্মীপুর, কুমিল্লা, ব্রাহ্মণবাড়িয়া, চাঁদপুর, নোয়াখালী\n";
            $districtsList .= "রাজশাহী বিভাগ: রাজশাহী, নাটোর, নওগাঁ, চাঁপাইনবাবগঞ্জ, বগুড়া, জয়পুরহাট, পাবনা, সিরাজগঞ্জ\n";
            $districtsList .= "খুলনা বিভাগ: খুলনা, বাগেরহাট, সাতক্ষীরা, যশোর, ঝিনাইদহ, মাগুরা, নড়াইল, চুয়াডাঙ্গা, কুষ্টিয়া, মেহেরপুর\n";
            $districtsList .= "বরিশাল বিভাগ: বরিশাল, ঝালকাঠি, পটুয়াখালী, পিরোজপুর, ভোলা, বরগুনা\n";
            $districtsList .= "সিলেট বিভাগ: সিলেট, মৌলভীবাজার, হবিগঞ্জ, সুনামগঞ্জ\n";
            $districtsList .= "রংপুর বিভাগ: রংপুর, দিনাজপুর, ঠাকুরগাঁও, পঞ্চগড়, নীলফামারী, লালমনিরহাট, গাইবান্ধা, কুড়িগ্রাম\n";
            $districtsList .= "ময়মনসিংহ বিভাগ: ময়মনসিংহ, জামালপুর, শেরপুর, নেত্রকোনা\n\n";
            $districtsList .= "🎯 SMART RULE: কাস্টমার address এ কোনো জেলার নাম বললে, district field এ সেটা automatically রাখবে এবং আলাদা করে district জিজ্ঞেস করবে না।\n";
            $districtsList .= "উদাহরণ: কাস্টমার বললো 'মিরপুর, ঢাকা' → address: 'মিরপুর, ঢাকা', district: 'ঢাকা' (আলাদা করে district জিজ্ঞেস করবে না)\n\n";

            // ৬. কাস্টমার ডাটা কালেকশন লজিক (৭ কৌশল সহ স্মার্ট আপডেট)
            $fields_instruction = "";
            $mandatory_fields = [];

            // 🚨 Escalation — per-IVR রাগী কাস্টমার হ্যান্ডেলিং
            $escalationPrompt = "";
            if ($service) {
                $agentNumber    = $service->escalation_agent_number ?? null;
                $trigger        = $service->escalation_trigger ?? 'any';
                $calmScript     = $service->escalation_calm_script ?? "আমি সত্যিই দুঃখিত। আপনাকে আমাদের একজন বিশেষজ্ঞ এজেন্টের সাথে কানেক্ট করছি।";
                $holdScript     = $service->escalation_hold_script ?? "একটু ধৈর্য ধরুন, লাইনে থাকুন।";
                $extraInstr     = $service->escalation_instructions ?? "";
                $collectBefore  = $service->escalation_collect_before_transfer ?? true;

                $triggerLabel = match($trigger) {
                    'angry'      => 'কাস্টমার রাগী/গালিগালাজ করলে',
                    'repeated'   => 'একই কথা ৩বারের বেশি বললে',
                    'requested'  => 'কাস্টমার নিজে Agent চাইলে',
                    'frustrated' => 'কাস্টমার বিরক্ত/হতাশ মনে হলে',
                    default      => 'কাস্টমার রাগী, বিরক্ত, বারবার একই কথা বললে, অথবা নিজে agent চাইলে',
                };

                $escalationPrompt = "\n[🚨 Escalation — Human Agent এ Transfer নিয়ম]\n";
                $escalationPrompt .= "• কখন escalate করবে: {$triggerLabel}\n";
                $escalationPrompt .= "• Escalate করার আগে অবশ্যই বলবে:\n  \"{$calmScript}\"\n";
                $escalationPrompt .= "• Hold এ রাখার সময় বলবে:\n  \"{$holdScript}\"\n";
                $escalationPrompt .= "• 🔴 IMPORTANT: Escalate করার সিদ্ধান্ত নিলে তোমার response এর শেষে অবশ্যই [ESCALATE] tag লিখবে\n";
                $escalationPrompt .= "  উদাহরণ: \"...আপনাকে এখনই আমাদের agent এর সাথে কানেক্ট করছি। [ESCALATE]\"\n";

                if ($agentNumber) {
                    $escalationPrompt .= "• Human Agent নম্বর: {$agentNumber} — কাস্টমারকে এই নম্বর দাও অথবা transfer করো\n";
                } else {
                    $escalationPrompt .= "• কাস্টমারকে বলো আমাদের টিম শীঘ্রই call back করবে\n";
                }

                if ($collectBefore) {
                    $escalationPrompt .= "• Transfer এর আগে নাম ও মোবাইল নম্বর collect করো যদি এখনো না থাকে\n";
                }

                if ($extraInstr) {
                    $escalationPrompt .= "• বিশেষ নির্দেশনা: {$extraInstr}\n";
                }
                $escalationPrompt .= "\n";

                // 🎯 Secondary Option — সরাসরি agent চাইলে
                if ($service->secondary_option_enabled) {
                    $agentNum2      = $service->secondary_agent_number ?: $agentNumber;
                    $attempts       = (int)($service->secondary_convince_attempts ?? 2);
                    $forwardScript  = $service->secondary_forward_script ?? "ঠিক আছে, আপনাকে agent এর সাথে কানেক্ট করছি।";
                    $collectInfo    = $service->secondary_collect_name_mobile ?? true;
                    $extraInstr2    = $service->secondary_ai_instructions ?? "";
                    
                    // ✅ Fix: Ensure $convinceScripts is always an array
                    $convinceScripts = $service->secondary_convince_scripts ?? [];
                    if (!is_array($convinceScripts)) {
                        $convinceScripts = [];
                    }

                    $escalationPrompt .= "\n[🎯 Secondary Option — কাস্টমার সরাসরি Agent চাইলে]\n";
                    $escalationPrompt .= "• কাস্টমার যদি বলে: 'agent চাই', 'মানুষের সাথে কথা বলব', 'আপনার সাথে কথা বলব না', 'সরাসরি কথা বলতে চাই'\n";

                    if ($attempts > 0 && count($convinceScripts) > 0) {
                        $escalationPrompt .= "• প্রথমে {$attempts}বার convince করার চেষ্টা করো:\n";
                        foreach (array_slice($convinceScripts, 0, $attempts) as $i => $cs) {
                            $n = $i + 1;
                            $script = $cs['script'] ?? '';
                            if ($script) $escalationPrompt .= "  {$n}. \"{$script}\"\n";
                        }
                        $escalationPrompt .= "• তারপরেও চাইলে forward করো\n";
                    } else {
                        $escalationPrompt .= "• সাথে সাথে forward করো\n";
                    }

                    if ($collectInfo) {
                        $escalationPrompt .= "• Forward এর আগে নাম ও মোবাইল নম্বর collect করো (না থাকলে)\n";
                        $escalationPrompt .= "  বলো: 'আপনার নাম ও মোবাইল নম্বরটা দিন, agent সাথে সাথে আপনাকে call করবেন'\n";
                    }

                    if ($agentNum2) {
                        $escalationPrompt .= "• Forward script: \"{$forwardScript}\" তারপর [ESCALATE] tag দাও\n";
                        $escalationPrompt .= "• Agent নম্বর: {$agentNum2}\n";
                    }

                    if ($extraInstr2) {
                        $escalationPrompt .= "• বিশেষ নির্দেশনা: {$extraInstr2}\n";
                    }
                    $escalationPrompt .= "\n";
                }
            }
            if ($service && $service->required_fields) {
                $fields_instruction = "\n\n[কাস্টমার ডাটা কালেকশন — স্মার্ট নিয়মাবলি]\n";
                $fields_instruction .= "⚡ সাধারণ নিয়ম:\n";
                $fields_instruction .= "• প্রতিটা তথ্য আলাদাভাবে জিজ্ঞেস করো, সব একসাথে নয়\n";
                $fields_instruction .= "• 🚫 কখনো একসাথে দুটো field জিজ্ঞেস করবে না (যেমন: নাম এবং ঠিকানা)\n";
                $fields_instruction .= "• কাস্টমার তথ্য দিলে confirm করো (রিপিট করে বলো) এবং পরের ফিল্ডে যাও\n";
                $fields_instruction .= "• 🚫 কোনো ফিল্ড একবার জিজ্ঞেস করার পর আর সেই ফিল্ডে ফিরে যাবে না\n";
                $fields_instruction .= "• তথ্য না দিলে শুধু একবার ভিন্নভাবে চেষ্টা করো, তারপর পরের ফিল্ডে যাও\n";
                $fields_instruction .= "• প্রতিটা ফিল্ড নির্দিষ্ট সংখ্যক বার জিজ্ঞেস করবে (max_retries), তারপর skip করবে\n";
                $fields_instruction .= "• কাস্টমার কোন তথ্য দিতে না চাইলে জোর করবে না - পরের ফিল্ডে চলে যাও\n";
                $fields_instruction .= "• সব তথ্য না পেলেও যা পেয়েছ তাই JSON এ দাও\n";
                $fields_instruction .= "• একই প্রশ্ন বারবার করবে না - এটা কাস্টমারকে বিরক্ত করে\n\n";
                
                $fields_instruction .= "🎯 Conversation Flow (এই ক্রমে প্রশ্ন করবে):\n";
                $fields_instruction .= "1. প্রথম ফিল্ড জিজ্ঞেস করো → উত্তর পেলে confirm করো → পরের ফিল্ডে যাও\n";
                $fields_instruction .= "2. উত্তর না পেলে → ভিন্নভাবে ১বার চেষ্টা করো → তারপরও না পেলে skip করো\n";
                $fields_instruction .= "3. Skip করার পর সেই ফিল্ডে আর কখনো ফিরে আসবে না\n";
                $fields_instruction .= "4. সব ফিল্ড শেষ হলে বা কাস্টমার 'এত হবে' বললে কল শেষ করো\n\n";
                $fields_instruction .= "⚠️ CRITICAL: প্রতিটা ফিল্ড আলাদা করে জিজ্ঞেস করবে, কখনো একসাথে দুটো প্রশ্ন করবে না!\n";
                $fields_instruction .= "❌ WRONG: \"আপনার নাম এবং ঠিকানা দিবেন\"\n";
                $fields_instruction .= "✅ CORRECT: \"আপনার নাম?\" → confirm → তারপর \"আপনার ঠিকানা?\"\n\n";
                
                $fields_instruction .= "📱 MOBILE NUMBER SPECIAL RULES (CRITICAL):\n";
                $fields_instruction .= "• Caller number already আছে - এটাই primary mobile number\n";
                $fields_instruction .= "• mobile_number field এ জিজ্ঞেস করবে না - caller number automatically use হবে\n\n";
                
                $fields_instruction .= "📞 ONLY ASK FOR ALTERNATE NUMBER:\n";
                $fields_instruction .= "  \"Sir, আপনার এই number ছাড়া আরও কোনো number আছে?\"\n";
                $fields_instruction .= "  ⚠️ সংক্ষিপ্ত এবং direct - কোনো লম্বা explanation নয়!\n\n";
                
                $fields_instruction .= "✅ Customer alternate number দিলে:\n";
                $fields_instruction .= "  \"ধন্যবাদ sir।\" (confirm করে পরের field এ যাও)\n\n";
                
                $fields_instruction .= "❌ Customer না দিলে বা 'না' বললে:\n";
                $fields_instruction .= "  \"ঠিক আছে sir।\" (পরের field এ যাও - কোনো explanation নয়)\n\n";
                
                $fields_instruction .= "🚫 FORBIDDEN:\n";
                $fields_instruction .= "  ❌ Primary mobile number জিজ্ঞেস করা (already আছে)\n";
                $fields_instruction .= "  ❌ Alternate number এর কারণ ব্যাখ্যা করা (যেমন: যদি পাওয়া না যায়...)\n";
                $fields_instruction .= "  ❌ লম্বা কথা বলা - short and direct থাকো\n\n";
                
                $fields_instruction .= "�📋 যে তথ্যগুলো সংগ্রহ করতে হবে:\n";

                foreach ($service->required_fields as $field) {
                    $mandatory   = ($field['is_mandatory'] ?? false) ? '🔴 জরুরি' : '🟡 ঐচ্ছিক';
                    $blocking    = ($field['is_blocking'] ?? false) ? ' | 🔒 না দিলে এগোবে না' : '';
                    $maxRetries  = !empty($field['max_retries']) ? (int)$field['max_retries'] : 2;
                    $retries     = " | 🔁 সর্বোচ্চ {$maxRetries}বার চেষ্টা করবে, তারপর skip করে পরের ফিল্ডে যাবে";
                    $confirm     = ($field['needs_confirmation'] ?? true) ? ' | ✅ confirm নেবে' : '';
                    $depends     = !empty($field['depends_on_field']) ? " | ⛓️ শুধু {$field['depends_on_field']} পাওয়ার পরে জিজ্ঞেস করবে" : '';

                    $fields_instruction .= "\n• **{$field['field_name']}** [{$mandatory}{$blocking}{$retries}{$confirm}{$depends}]\n";
                    $fields_instruction .= "  ⚠️ IMPORTANT: এই ফিল্ড সর্বোচ্চ {$maxRetries}বার জিজ্ঞেস করবে। তারপর উত্তর না পেলে skip করে পরের ফিল্ডে যাবে।\n";
                    $fields_instruction .= "  🚫 একবার skip করলে আর কখনো এই ফিল্ডে ফিরে আসবে না।\n";

                    if (!empty($field['ai_instruction'])) {
                        $fields_instruction .= "  → ১ম চেষ্টা: {$field['ai_instruction']}\n";
                    }
                    if (!empty($field['indirect_question'])) {
                        $fields_instruction .= "  → ২য় চেষ্টা (উত্তর না পেলে): {$field['indirect_question']}\n";
                    }
                    if (!empty($field['convincing_logic'])) {
                        $fields_instruction .= "  → তথ্য না দিতে চাইলে: {$field['convincing_logic']}, তারপর skip করো\n";
                    }
                    if (!empty($field['error_message'])) {
                        $fields_instruction .= "  → ভুল ডাটা দিলে বলবে: {$field['error_message']}\n";
                    }
                    // Sample values ও length validation
                    if (!empty($field['min_length']) || !empty($field['max_length'])) {
                        $min = $field['min_length'] ?? '';
                        $max = $field['max_length'] ?? '';
                        $fields_instruction .= "  → সঠিক দৈর্ঘ্য: ";
                        if ($min && $max) $fields_instruction .= "{$min} থেকে {$max} digit/character\n";
                        elseif ($min)     $fields_instruction .= "কমপক্ষে {$min} digit/character\n";
                        else              $fields_instruction .= "সর্বোচ্চ {$max} digit/character\n";
                        $fields_instruction .= "  ⚠️ কাস্টমার কম বা বেশি দিলে ভুল ধরো এবং আবার চাও\n";
                    }
                    if (!empty($field['expected_format'])) {
                        $fields_instruction .= "  → সঠিক ফরম্যাট: {$field['expected_format']}\n";
                    }
                    if (!empty($field['sample_values'])) {
                        $samples = array_filter(array_map('trim', explode("\n", $field['sample_values'])));
                        if (count($samples)) {
                            $fields_instruction .= "  → সঠিক ডাটার উদাহরণ: " . implode(', ', array_slice($samples, 0, 3)) . "\n";
                            $fields_instruction .= "  ⚠️ এই ফরম্যাটের মতো না হলে কাস্টমারকে জানাও ও আবার চাও\n";
                        }
                    }
                    // FAQ / QnA
                    if (!empty($field['custom_qna'])) {
                        $fields_instruction .= "  → কাস্টমার পাল্টা প্রশ্ন করলে:\n";
                        foreach ($field['custom_qna'] as $qna) {
                            if (!empty($qna['customer_question']) && !empty($qna['ai_answer'])) {
                                $fields_instruction .= "    ❓ \"{$qna['customer_question']}\" → \"{$qna['ai_answer']}\"\n";
                            }
                        }
                    }

                    if ($field['is_mandatory'] ?? false) {
                        $mandatory_fields[] = $field['field_name'];
                    }
                }

                $fields_instruction .= "\n[🏢 SERVICE CENTER INTELLIGENT CONVERSATION]\n";
                $fields_instruction .= "• যদি service center field থাকে, smart করে জিজ্ঞেস করবে:\n";
                $fields_instruction .= "  \"Sir, আপনি কোন সার্ভিস সেন্টার থেকে পণ্যটি কিনেছিলেন বলতে পারবেন?\"\n";
                $fields_instruction .= "• Customer center নাম বললে:\n";
                $fields_instruction .= "  \"ধন্যবাদ sir। আপনি [center name] এ যোগাযোগ করতে পারেন, তারা আপনাকে সাহায্য করবে।\"\n";
                $fields_instruction .= "• Customer না বললে বা মনে না থাকলে:\n";
                $fields_instruction .= "  \"কোনো সমস্যা নেই sir। আমি দেখছি কি করা যায়। আমরা ব্যবস্থা নিচ্ছি।\"\n";
                $fields_instruction .= "• Customer বললে \"আপনারাই manage করেন\":\n";
                $fields_instruction .= "  \"অবশ্যই sir। আমরা নিকটস্থ সার্ভিস সেন্টার থেকে আপনার সাথে যোগাযোগ করব।\"\n\n";
                
                $fields_instruction .= "\n[Fallback নিয়ম]\n";
                $fields_instruction .= "• সব জরুরি তথ্য না পেলেও কল শেষে যা পেয়েছ তা JSON এ দাও\n";
                $fields_instruction .= "• কাস্টমার বিরক্ত হলে অতিরিক্ত চাপ দিও না, যা আছে তাই নাও\n";
                $fields_instruction .= "• যে ফিল্ড ইতিমধ্যে জিজ্ঞেস করেছ, সেটা আর জিজ্ঞেস করবে না\n";
                $fields_instruction .= "• প্রতিটা ফিল্ড linear sequence এ collect করো - আগের ফিল্ডে ফিরে যাবে না\n";
                $fields_instruction .= "• কাস্টমার যদি বলে 'জানি না' বা 'এখন নেই', তাহলে সেই ফিল্ড skip করে পরেরটা নাও\n";
                $fields_instruction .= "\n🎯 DATA COLLECTION GOAL: প্রতিটা field এ maximum data collect করতে হবে smart conversation এর মাধ্যমে\n";
            }
            $mandatory_fields_string = implode(', ', $mandatory_fields);

            // 🚀 ৭. নলেজবেস — সব ধরনের ডাটা AI prompt এ পাঠানো
            // প্রোডাক্ট ইনফো (এই IVR বা global)
            $products = KnowledgeBase::where('is_active', true)
                ->whereNotIn('category', ['Instruction', 'persona', 'এআই ব্রেইন (Persona/Rules)', 'Persona/Rules', 'Greeting'])
                ->where(function($q) use ($service) {
                    $q->whereNull('ivr_service_id')
                      ->orWhere('ivr_service_id', $service ? $service->id : null);
                })
                ->get();

            $kbText = "";
            foreach ($products as $p) {
                $kbText .= "\n📦 [{$p->category}" . ($p->brand_name ? " - {$p->brand_name}" : "") . "]\n";

                if (!empty($p->answer)) {
                    $answers = is_array($p->answer) ? implode(' | ', $p->answer) : $p->answer;
                    $kbText .= "  তথ্য: {$answers}\n";
                }
                if (!empty($p->product_models)) {
                    $kbText .= "  মডেলসমূহ: " . implode(', ', $p->product_models) . "\n";
                }
                if (!empty($p->warranty_info)) {
                    $kbText .= "  ওয়ারেন্টি: {$p->warranty_info}\n";
                }
                if (!empty($p->service_charge)) {
                    $kbText .= "  সার্ভিস চার্জ: {$p->service_charge}\n";
                }
                if (!empty($p->common_issues)) {
                    $kbText .= "  সাধারণ সমস্যা ও সমাধান:\n";
                    foreach ($p->common_issues as $issue) {
                        $kbText .= "    - {$issue}\n";
                    }
                }
                if (!empty($p->sample_question)) {
                    $kbText .= "  কাস্টমার এসব প্রশ্ন করতে পারে: " . implode(' | ', $p->sample_question) . "\n";
                }
            }

            // AI Behavior Rules (global + এই IVR)
            $behaviorRules = KnowledgeBase::where('is_active', true)
                ->whereIn('category', ['Instruction', 'এআই ব্রেইন (Persona/Rules)'])
                ->where(function($q) use ($service) {
                    $q->whereNull('ivr_service_id')
                      ->orWhere('ivr_service_id', $service ? $service->id : null);
                })
                ->get();

            $behaviorText = "";
            $negativeText = "";
            $escalationText = "";
            $greetingKbText = "";

            foreach ($behaviorRules as $rule) {
                if (!empty($rule->behavior_rules)) {
                    $behaviorText .= implode("\n", $rule->behavior_rules) . "\n";
                }
                if (!empty($rule->negative_rules)) {
                    $negativeText .= implode("\n", $rule->negative_rules) . "\n";
                }
                if (!empty($rule->escalation_rules)) {
                    $escalationText .= implode("\n", $rule->escalation_rules) . "\n";
                }
                if (!empty($rule->closing_rules)) {
                    $greetingKbText .= implode("\n", $rule->closing_rules) . "\n";
                }
                if (!empty($rule->strict_validation)) {
                    $fields_instruction .= "\n[ডাটা যাচাই নিয়ম]\n" . implode("\n", $rule->strict_validation) . "\n";
                }
            }

            // 🧠 Conversation Memory — আগের কল থেকে শেখা তথ্য
            $callerNumber = request()->input('caller_number');
            $memoryText = "";
            if ($callerNumber) {
                $memory = CustomerMemory::findOrCreateByPhone($callerNumber);
                $memoryText = $memory->toPromptText();
            }

            // 🌐 ৮. মাল্টি-ল্যাঙ্গুয়েজ + BNCC ইনস্ট্রাকশন
            $multiLangText = "\n[ভাষা সংক্রান্ত নিয়ম — অবশ্যই মানতে হবে]\n" .
                "• কাস্টমার বাংলায় কথা বললে: সম্পূর্ণ বাংলায় উত্তর দাও\n" .
                "• কাস্টমার ইংরেজিতে কথা বললে: সম্পূর্ণ ইংরেজিতে উত্তর দাও\n" .
                "• কাস্টমার Banglish (মিশিয়ে) কথা বললে: সহজ বাংলায় উত্তর দাও\n" .
                "• BNCC (Bengali Numeric Code Convention) — কাস্টমার বাংলায় সংখ্যা বললে সেটা বুঝে নাও:\n" .
                "  - 'এক শূন্য সাত' = 107, 'পনের' = 15, 'আট হাজার' = 8000\n" .
                "  - বাংলায় বলা নম্বর সংখ্যায় convert করে JSON এ রাখো\n" .
                "• ভাষা মাঝপথে পরিবর্তন হলে: কাস্টমারের নতুন ভাষায় সাথে সাথে মিলিয়ে নাও\n\n";

            // 😊 Sentiment Analysis — কাস্টমারের আবেগ বোঝা
            $sentimentText = "\n[Sentiment Analysis — কাস্টমারের আবেগ বুঝে সাড়া দাও]\n" .
                "• কাস্টমার রাগী/বিরক্ত মনে হলে: আগে ক্ষমা চাও, শান্ত করো, তারপর তথ্য নাও\n" .
                "  → বলো: 'আমি সত্যিই দুঃখিত, আপনার সমস্যাটা আমি বুঝতে পারছি...'\n" .
                "• কাস্টমার দুশ্চিন্তিত/উদ্বিগ্ন মনে হলে: আশ্বস্ত করো\n" .
                "  → বলো: 'চিন্তা করবেন না, আমরা দ্রুত সমাধান করে দেব'\n" .
                "• কাস্টমার খুশি/সহযোগী মনে হলে: স্বাভাবিকভাবে এগিয়ে যাও\n" .
                "• কাস্টমার তাড়াহুড়োয় থাকলে: দ্রুত এবং সংক্ষেপে কথা বলো\n" .
                "• কাস্টমার বারবার একই কথা বললে: ধৈর্য ধরো, ভিন্নভাবে বোঝাও\n" .
                "• কাস্টমার কান্না/অসুস্থ মনে হলে: সহানুভূতি দেখাও, প্রয়োজনে হিউম্যান এজেন্টে দাও\n\n";

            // � Conversational Data Collection Strategy
            $conversationalStrategy = "\n[🎭 CONVERSATIONAL DATA COLLECTION - স্মার্ট কথোপকথনে তথ্য সংগ্রহ]\n";
            $conversationalStrategy .= "• প্রতিটা field এ data collect করার জন্য natural conversation করবে\n";
            $conversationalStrategy .= "• কখনো mechanical বা robotic মনে হবে না - human এর মত কথা বলবে\n";
            $conversationalStrategy .= "• একটা field শেষে smooth transition এ পরেরটা জিজ্ঞেস করবে\n";
            $conversationalStrategy .= "• Customer যদি partial তথ্য দেয়, politely পুরো তথ্য নিবে:\n";
            $conversationalStrategy .= "  Example: Customer বললো 'ফ্রিজ', তুমি বলবে 'আচ্ছা, ফ্রিজের কোন মডেল?'\n";
            $conversationalStrategy .= "• Maximum data collect করতে হবে but বিরক্ত না করে\n";
            $conversationalStrategy .= "• Smart পদ্ধতিতে missing fields fill করবে:\n";
            $conversationalStrategy .= "  - Address থেকে district extract করবে\n";
            $conversationalStrategy .= "  - Product name থেকে brand guess করতে পারো (confirm করে)\n";
            $conversationalStrategy .= "  - Problem থেকে related তথ্য বের করবে\n\n";

            // �🏋️ Company AI Training — Global Persona, Forbidden Topics, Tone, Special Knowledge
            $forbiddenText = "";
            if ($company && !empty($company->ai_forbidden_topics)) {
                $forbiddenLines = array_filter(array_map('trim', explode("\n", $company->ai_forbidden_topics)));
                if (count($forbiddenLines)) {
                    $forbiddenText = "\n[🚫 যেসব বিষয়ে কথা বলবে না — Forbidden]\n";
                    foreach ($forbiddenLines as $topic) {
                        $forbiddenText .= "• {$topic}\n";
                    }
                    $forbiddenText .= "\n";
                }
            }

            $toneText = "";
            if ($company && !empty($company->ai_tone_guidelines)) {
                $toneText = "\n[🎙️ কথা বলার ধরন (Tone Guidelines)]\n" . $company->ai_tone_guidelines . "\n\n";
            }

            $specialKnowledgeText = "";
            if ($company && !empty($company->ai_special_knowledge)) {
                $specialKnowledgeText = "\n[📚 বিশেষ জ্ঞান ও তথ্য (Company Training)]\n" . $company->ai_special_knowledge . "\n\n";
            }

            // 🔴 STRICT: MANDATORY CONVERSATION PROTOCOL
            $mandatoryConversation = "\n\n[🔴 MANDATORY: CONVERSATION-FIRST PROTOCOL]\n";
            $mandatoryConversation .= "⛔ ZERO TOLERANCE FOR DATA EXTRACTION WITHOUT CONVERSATION:\n\n";
            $mandatoryConversation .= "🚨 YOU MUST FOLLOW THIS SEQUENCE - NO EXCEPTIONS:\n";
            $mandatoryConversation .= "1. Give greeting message first (MANDATORY)\n";
            $mandatoryConversation .= "2. Ask for FIRST field\n";
            $mandatoryConversation .= "3. Wait for customer response\n";
            $mandatoryConversation .= "4. Confirm customer's answer\n";
            $mandatoryConversation .= "5. Ask for NEXT field\n";
            $mandatoryConversation .= "6. Repeat steps 3-5 for ALL fields\n";
            $mandatoryConversation .= "7. Only fill JSON with data customer EXPLICITLY told you\n\n";
            
            $mandatoryConversation .= "📞 IF CUSTOMER DOESN'T RESPOND:\n";
            $mandatoryConversation .= "• প্রথমে ২-৩বার ভদ্রভাবে জিজ্ঞেস করো: 'Sir, আমি কি আপনার কথা শুনতে পাচ্ছি?'\n";
            $mandatoryConversation .= "• তারপরও response না পেলে: 'Sir, আপনার কোনো সমস্যার জন্য কল করেছেন?'\n";
            $mandatoryConversation .= "• একদমই কথা না বললে: শুধু caller_number দিয়ে minimal entry করো\n";
            $mandatoryConversation .= "• ❌ NEVER fill other fields with fake/assumed data\n";
            $mandatoryConversation .= "• ❌ NEVER write 'customer কথা বলেনি' in any field\n";
            $mandatoryConversation .= "• ✅ Call center এ প্রতিটা call important - caller number save করতে হবে\n\n";
            
            $mandatoryConversation .= "🎯 SILENT/NON-RESPONSIVE CUSTOMER PROTOCOL:\n";
            $mandatoryConversation .= "Step 1: Greeting দাও\n";
            $mandatoryConversation .= "Step 2: কথা বলতে বলো: 'Sir, আমি শুনতে পাচ্ছি, বলুন'\n";
            $mandatoryConversation .= "Step 3: তারপরও না বললে: 'Sir, কোনো সমস্যা আছে?'\n";
            $mandatoryConversation .= "Step 4: Silent থাকলে: 'Sir, আপনি যদি পরে কথা বলতে চান, আমরা এই number এ call back করব'\n";
            $mandatoryConversation .= "Step 5: JSON এ শুধু caller_number দিবে, বাকি fields EMPTY\n";
            $mandatoryConversation .= "Step 6: Status: 'Drop Call' or 'No Response'\n\n";
            
            $mandatoryConversation .= "❌ ABSOLUTELY FORBIDDEN:\n";
            $mandatoryConversation .= "• Collecting data without asking questions\n";
            $mandatoryConversation .= "• Filling fields based on assumptions\n";
            $mandatoryConversation .= "• Skipping conversation and going straight to JSON\n";
            $mandatoryConversation .= "• Using text like 'নেই', 'বলেনি' as field values\n";
            $mandatoryConversation .= "• Guessing district from area name (কল্যাণপুর ≠ district!)\n";
            $mandatoryConversation .= "• Making up customer name, address, product details\n\n";
            
            $mandatoryConversation .= "✅ MANDATORY BEHAVIORS:\n";
            $mandatoryConversation .= "• Start with greeting EVERY TIME\n";
            $mandatoryConversation .= "• Ask questions ONE BY ONE\n";
            $mandatoryConversation .= "• Have real back-and-forth dialogue\n";
            $mandatoryConversation .= "• Only record what customer SAID in conversation\n";
            $mandatoryConversation .= "• Empty fields if not mentioned = EMPTY (not 'নেই')\n";
            $mandatoryConversation .= "• Silent customer = Only caller_number, rest EMPTY\n";
            $mandatoryConversation .= "• Every call is IMPORTANT in call center - always save caller_number\n\n";

            $antiHallucinationRules = "\n\n[🚨 CRITICAL: ANTI-HALLUCINATION RULES - অবশ্যই মানতে হবে]\n";
            $antiHallucinationRules .= "❌ NEVER invent, guess, or fabricate ANY information\n";
            $antiHallucinationRules .= "❌ NEVER create fake addresses, product names, or problem descriptions\n";
            $antiHallucinationRules .= "❌ NEVER assume information that customer didn't explicitly say\n";
            $antiHallucinationRules .= "❌ If customer is SILENT = Do NOT make up data!\n";
            $antiHallucinationRules .= "✅ ONLY record exactly what customer verbally tells you\n";
            $antiHallucinationRules .= "✅ If customer doesn't mention a field, leave it EMPTY in JSON\n";
            $antiHallucinationRules .= "✅ If customer says 'জানি না' or 'মনে নেই', mark field as null\n";
            $antiHallucinationRules .= "✅ If customer is SILENT/non-responsive = Only caller_number in JSON, rest EMPTY\n";
            $antiHallucinationRules .= "✅ Never use placeholder values like 'N/A', 'Unknown', 'বলেনি'\n";
            $antiHallucinationRules .= "✅ Problem description MUST be customer's EXACT words only\n";
            $antiHallucinationRules .= "✅ Never expand or paraphrase customer's problem description\n";
            $antiHallucinationRules .= "✅ If customer gives short problem, keep it short in JSON\n\n";
            
            $antiHallucinationRules .= "🔄 FIELD TRACKING RULES - Conversation Memory:\n";
            $antiHallucinationRules .= "🚫 NEVER ask for the same field twice in one conversation\n";
            $antiHallucinationRules .= "� NEVER ask for multiple fields in ONE sentence\n";
            $antiHallucinationRules .= "📝 Keep mental track: Which fields have you already asked?\n";
            $antiHallucinationRules .= "➡️ Always move FORWARD in the field sequence - never go back\n";
            $antiHallucinationRules .= "⏭️ If customer doesn't answer after max_retries, SKIP and move to next field\n";
            $antiHallucinationRules .= "✅ Customer already gave a field? Don't ask again, just use it\n";
            $antiHallucinationRules .= "❌ Example of WRONG behavior: 'আচ্ছা আপনার নাম কী?' [customer answers] ... [5 minutes later] 'আপনার নাম কী?'\n";
            $antiHallucinationRules .= "❌ Example of WRONG behavior: 'আপনার নাম এবং মোবাইল নম্বর দিবেন' (দুটো একসাথে)\n";
            $antiHallucinationRules .= "✅ Example of CORRECT behavior: Ask each field once → confirm → move to next → never return\n";
            $antiHallucinationRules .= "✅ Example of CORRECT behavior: Ask one field → confirm → then ask next field separately\n\n";
            
            $antiHallucinationRules .= "🎯 Example 1 - CORRECT behavior (Responsive Customer):\n";
            $antiHallucinationRules .= "Customer বললো: 'আমার নাম করিম, নম্বর 01712345678, ফ্রিজ ঠান্ডা হয় না'\n";
            $antiHallucinationRules .= "JSON: {customer_name: 'করিম', mobile_number: '01712345678', problem_description: 'ফ্রিজ ঠান্ডা হয় না'}\n";
            $antiHallucinationRules .= "❌ Address, district, barcode → EMPTY (customer বলেনি)\n\n";
            
            $antiHallucinationRules .= "🎯 Example 2 - CORRECT behavior (SILENT/Non-responsive Customer):\n";
            $antiHallucinationRules .= "AI: 'স্যার, আমি কি আপনার কথা শুনতে পাচ্ছি?'\n";
            $antiHallucinationRules .= "Customer: [no response]\n";
            $antiHallucinationRules .= "AI: 'স্যার, আপনার কোনো সমস্যার জন্য কল করেছেন?'\n";
            $antiHallucinationRules .= "Customer: [still no response]\n";
            $antiHallucinationRules .= "JSON: {mobile_number: '01712345678'}\n";
            $antiHallucinationRules .= "✅ All other fields → EMPTY (customer বলেনি)\n";
            $antiHallucinationRules .= "✅ Caller number saved (call center এ প্রতিটা call important)\n";
            $antiHallucinationRules .= "❌ NEVER fill name, district, address based on guesses!\n\n";
            
            $antiHallucinationRules .= "❌ Example - WRONG behavior (NEVER do this):\n";
            $antiHallucinationRules .= "Customer বললো: 'ফ্রিজ ঠান্ডা হয় না'\n";
            $antiHallucinationRules .= "JSON: {problem_description: 'ফ্রিজটি ১০ তারিখ থেকে সমস্যা করছে...'} ← HALLUCINATION!\n";
            $antiHallucinationRules .= "Never add details customer didn't say!\n\n";
            
            $antiHallucinationRules .= "❌ Example - ABSOLUTELY FORBIDDEN:\n";
            $antiHallucinationRules .= "Customer: 'Walton থেকে করিম বলছি' [then silent]\n";
            $antiHallucinationRules .= "AI: [কোন conversation ছাড়া data fill]\n";
            $antiHallucinationRules .= "JSON: {customer_name: 'করিম আক্তার', district: 'কল্যাণপুর', barcode: 'নেই'} ← THIS IS FORBIDDEN!\n";
            $antiHallucinationRules .= "✅ CORRECT: শুধু caller_number save করো, বাকি EMPTY রাখো\n\n";

            // 🚀 ৯. ফাইনাল সুপার প্রম্পট (১০০% ডাইনামিক + মাল্টি-ল্যাঙ্গুয়েজ + Sentiment)
            $systemPrompt = "কোম্পানির নাম: {$companyName}\n" .
            $aboutCompany . 
            $contactInfoText . "\n\n" .
            $aiName . 
            $voiceGenderText .
            $aiGreeting . 
            "আচরণ ও গাইডলাইন: " . $globalPersona . "\n" .
            $toneText .
            $forbiddenText .
            $specialKnowledgeText .
            $dynamicRulesText .
            $multiLangText .
            $sentimentText .
            $conversationalStrategy .
            $mandatoryConversation .
            $callerNumberText .
            $districtsList .
            $escalationPrompt .
            ($behaviorText ? "\n[আচরণের নিয়ম]\n{$behaviorText}" : "") .
            ($negativeText ? "\n[যা বলা একদম নিষেধ]\n{$negativeText}" : "") .
            ($escalationText ? "\n[কখন হিউম্যান এজেন্টে ট্রান্সফার করবে]\n{$escalationText}" : "") .
            ($greetingKbText ? "\n[কল শেষ করার নিয়ম]\n{$greetingKbText}" : "") .
            $memoryText .
            $fields_instruction .
            $antiHallucinationRules .
            ($kbText ? "\n\n[প্রোডাক্ট ও সার্ভিস নলেজবেস]\n{$kbText}" : "");

            return response()->json([
                'status' => 'success',
                'token' => $token,
                'prompt' => $systemPrompt,
                'project_id' => 'ai-calls-center',
                'voice_gender' => $voiceGender, // Database থেকে
                'voice_speed' => $voiceSpeed,   // Database থেকে
            ]);
            
        } catch (\Throwable $e) { 
            return response()->json([
                'status' => 'error', 
                'message' => 'Line ' . $e->getLine() . ': ' . $e->getMessage()
            ]);
        }
    }
    
    // Mobile number validation — Bangladesh: 01[3-9]XXXXXXXX
    private function isValidBDMobile(?string $number): bool
    {
        if (empty($number) || $number === 'N/A') return false;
        $cleaned = preg_replace('/\D/', '', $number);
        return (bool) preg_match('/^01[3-9]\d{8}$/', $cleaned);
    }

    private function isValidName(?string $name): bool
    {
        if (empty($name) || $name === 'N/A') return false;
        $name = trim($name);
        if (mb_strlen($name) < 2) return false;
        if (preg_match('/^[0-9\s\W]+$/u', $name)) return false;
        $garbage = ['অজানা', 'unknown', 'n/a', 'na', 'null', 'none', 'test', 'অক্ষর', 'নাম'];
        if (in_array(mb_strtolower($name), $garbage)) return false;
        return true;
    }

    private function cleanValue(?string $val): ?string
    {
        if (empty($val) || in_array(trim(strtolower($val ?? '')), ['n/a', 'na', 'null', 'none', 'unknown', ''])) return null;
        return trim($val);
    }

    // লাইভ কল থেকে ডাটা সেভ
    // Scenario 1: Drop call — caller_number শুধু, কোনো কথা নেই
    // Scenario 2: Normal call — AI data collected
    public function saveTicketLive(\Illuminate\Http\Request $request)
    {
        try {
            $ivrKey      = $request->input('ivr_key', '1');
            $service     = \App\Models\IvrService::where('key_press', $ivrKey)->where('is_active', true)->first();
            $callerNum   = $this->cleanValue($request->input('caller_number')); // Asterisk থেকে আসা
            $isDropCall  = (bool) $request->input('is_drop_call', false);

            // ═══ DROP CALL ═══
            // কথা হয়নি, শুধু caller number দিয়ে একটা missed entry করো
            if ($isDropCall) {
                // caller_number না থাকলে drop call save করার কিছু নেই
                if (!$this->isValidBDMobile($callerNum)) {
                    return response()->json(['status' => 'skipped', 'message' => 'Drop call — caller number নেই, skip।']);
                }

                // Duplicate drop call check — same number last 2 minutes (Incoming entry থাকলে Drop Call করো)
                $existing = \App\Models\ServiceRequest::where('mobile_number', preg_replace('/\D/', '', $callerNum))
                    ->where('status', 'Incoming')
                    ->where('created_at', '>=', now()->subMinutes(30))
                    ->first();
                if ($existing) {
                    $existing->update(['status' => 'Drop Call']);
                    return response()->json(['status' => 'success', 'type' => 'drop_call_updated', 'id' => $existing->id]);
                }

                $ticket = new \App\Models\ServiceRequest();
                $ticket->ivr_service_id = $service ? $service->id : null;
                $ticket->mobile_number  = preg_replace('/\D/', '', $callerNum);
                $ticket->status         = 'Drop Call';
                $ticket->extracted_data = ['caller_number' => $callerNum, 'type' => 'drop_call'];
                $ticket->save();

                return response()->json(['status' => 'success', 'type' => 'drop_call', 'id' => $ticket->id]);
            }

            // ═══ NORMAL CALL ═══
            // Mobile must be valid BD number (AI extracted থেকে)
            $mobile = $this->cleanValue($request->mobile_number);

            // যদি AI mobile না পায়, Asterisk caller number use করো
            if (!$this->isValidBDMobile($mobile) && $this->isValidBDMobile($callerNum)) {
                $mobile = $callerNum;
            }

            if (!$this->isValidBDMobile($mobile)) {
                return response()->json([
                    'status'  => 'rejected',
                    'reason'  => 'invalid_mobile',
                    'message' => '❌ সঠিক মোবাইল নম্বর পাওয়া যায়নি (01XXXXXXXXX ফরম্যাট দরকার)',
                ], 422);
            }

            // Name — কাস্টমার ভুল বলতে পারে, তাই খুব basic check
            $name = $this->cleanValue($request->customer_name);
            // Name না থাকলে reject করব না — শুধু null রাখব

            $ticket = new \App\Models\ServiceRequest();
            $ticket->ivr_service_id      = $service ? $service->id : null;
            $ticket->customer_name       = $name; // null হলেও ok
            $ticket->mobile_number       = preg_replace('/\D/', '', $mobile);
            $ticket->alt_mobile_number   = $this->isValidBDMobile($request->alt_mobile_number)
                                            ? preg_replace('/\D/', '', $request->alt_mobile_number) : null;
            $ticket->address             = $this->cleanValue($request->address);
            $ticket->district            = $this->cleanValue($request->district);
            $ticket->product_name        = $this->cleanValue($request->product_name);
            $ticket->barcode             = $this->cleanValue($request->barcode);
            $ticket->problem_description = $this->cleanValue($request->problem_description);
            $ticket->extracted_data      = $request->all();
            $ticket->status              = 'Pending';
            $ticket->save();

            return response()->json(['status' => 'success', 'id' => $ticket->id]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AI Ticket Save Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function viewAiTickets()
    {
        $tickets = \App\Models\AiTicket::orderBy('id', 'desc')->get();
        return view('ai_tickets_list', compact('tickets'));
    }

    public function recognizeSpeech(\Illuminate\Http\Request $request)
    {
        // ... (আপনার আগের অডিও ফাংশনগুলো এখানে থাকবে, সেগুলো বদলাতে হবে না) ...
    }

    public function processAudioData(\Illuminate\Http\Request $request)
    {
        // ... (আপনার আগের অডিও ফাংশনগুলো এখানে থাকবে, সেগুলো বদলাতে হবে না) ...
    }

    // 🚀 শুধু টেক্সট প্রসেস করে ডাটাবেস বানানোর ফাংশন (Smart JSON Cleaner সহ ফিক্সড)
    public function processFinalText(\Illuminate\Http\Request $request)
    {
        try {
            $transcript = $request->text;
            $token = $this->getGoogleToken(); 
            $projectId = 'ai-calls-center'; 

            $ivrKey = $request->input('ivr_key', '1'); 
            $service = \App\Models\IvrService::where('key_press', $ivrKey)->where('is_active', true)->first();
            if (!$service) {
                $service = \App\Models\IvrService::where('is_active', true)->first();
            }

            if (!$token || empty($transcript)) {
                return response()->json(['status' => 'error', 'message' => 'কোনো টেক্সট পাওয়া যায়নি।']);
            }

            // 🚨 Smart Detection: Check if it's a drop call or real conversation
            $callerNum = $this->cleanValue($request->input('caller_number'));
            
            // Count actual conversation (not just AI greeting)
            $conversationLines = explode("\n", $transcript);
            $customerLines = array_filter($conversationLines, function($line) {
                // Customer spoke if line doesn't start with "এজেন্ট:"
                return !str_starts_with(trim($line), 'এজেন্ট:') && !str_starts_with(trim($line), '[');
            });
            $hasRealConversation = count($customerLines) > 0 && strlen(implode(' ', $customerLines)) > 20;

            // 🚀 এআইকে কী কী ফিল্ড এক্সট্র্যাক্ট করতে হবে তার লিস্ট
            $expectedKeysArray = ['customer_name', 'mobile_number', 'address', 'product_name', 'product_problem', 'problem_description'];
            if ($service && $service->required_fields) {
                foreach ($service->required_fields as $field) {
                    $expectedKeysArray[] = $field['field_name']; 
                }
            }
            $expectedKeysString = implode(', ', $expectedKeysArray);

            // আগের working model ব্যবহার করছি (Gemini 2.5 Flash - 100% কাজ করে)
            $url = "https://us-central1-aiplatform.googleapis.com/v1beta1/projects/{$projectId}/locations/us-central1/publishers/google/models/gemini-2.5-flash:generateContent";
            
            // 🎯 Smart prompt based on conversation type
            if (!$hasRealConversation) {
                // Drop call or minimal conversation - don't try to extract complex data
                $prompt = "Transcript: \"{$transcript}\"\n\nThe customer didn't speak or spoke very little. Extract ONLY what was explicitly mentioned:\n- mobile_number (if mentioned)\n- customer_name (if mentioned)\n\nIf nothing was mentioned, return empty JSON: {}\n\nReturn JSON only.";
            } else {
                // Real conversation - extract what customer said
                $prompt = "Extract customer data from this REAL conversation transcript.\n\nTranscript: \"{$transcript}\"\n\n🚨 CRITICAL RULES:\n- Extract ONLY what the customer ACTUALLY said\n- DO NOT fabricate, guess, or assume ANY data\n- If customer didn't mention a field, DO NOT include it\n- DO NOT use placeholder values like 'N/A', 'Unknown', 'বলেনি'\n- Preserve exact customer words\n\nFields to look for: {$expectedKeysString}\n\nExamples:\n- Customer said 'আমার নাম কবির' → {\"customer_name\":\"কবির\"}\n- Customer said 'আমার নম্বর 01712345678' → {\"mobile_number\":\"01712345678\"}\n- Customer didn't mention address → DO NOT include 'address' in JSON\n- Customer said 'হ্যালো' only → {}\n\nReturn valid JSON only, no markdown.";
            }



            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->post($url, [
                    'contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => ['responseMimeType' => 'application/json']
                ]);

            if ($response->successful()) {
                $content = $response->json('candidates.0.content.parts.0.text');
                
                // 🚀 ম্যাজিক ফিক্স: জেমিনি মার্কডাউন (```json) দিলে সেটা পরিষ্কার করা
                $cleanContent = str_replace(['```json', '```'], '', $content);
                $cleanContent = trim($cleanContent);
                
                // যদি JSON না হয় তাহলে এরর
                if (empty($cleanContent) || !is_string($cleanContent)) {
                    throw new \Exception('Empty response from AI');
                }
                
                $data = json_decode($cleanContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('JSON Decode Error: ' . json_last_error_msg());
                }
                
                // ✅ Light validation - শুধু obvious fake data remove করো, customer এর data বাদ দিও না!
                if (is_array($data)) {
                    foreach ($data as $key => &$value) {
                        // যদি array বা object হয়, তাহলে এটা invalid format
                        if (is_array($value) || is_object($value)) {
                            unset($data[$key]);
                            continue;
                        }
                        
                        // convert to string
                        $value = (string)$value;
                        
                        // শুধু empty বা clear placeholder values remove করো
                        if (empty(trim($value)) || in_array(mb_strtolower(trim($value)), ['na', 'n/a', 'null', 'none', 'unknown'])) {
                            unset($data[$key]);
                            continue;
                        }
                        
                        // ✅ Customer যা বলেছে তাই রাখো - even if ভুল, even if বিস্তারিত!
                        // AI prompt এ already বলা আছে customer এর exact words নিতে
                        // তাই customer conversation থেকে যা এসেছে তাই valid data
                    }
                }

                $callerNum = $this->cleanValue($request->input('caller_number'));

                // AI মোবাইল না পেলে — caller_number fallback
                $mobile = $this->cleanValue($data['mobile_number'] ?? null);
                if (!$this->isValidBDMobile($mobile) && $this->isValidBDMobile($callerNum)) {
                    $mobile = $callerNum;
                    $data['mobile_number'] = $mobile;
                }

                // 🎯 Smart handling based on conversation type
                
                // 🚨 Check for ESCALATION REQUEST in conversation
                $transcriptLower = mb_strtolower($transcript);
                $escalationDetected = false;
                $escalationKeywords = [
                    '[escalate]', '[transfer]', '[agent_transfer]',
                    'agent চাই', 'agent লাগবে', 'এজেন্ট চাই', 'মানুষের সাথে কথা',
                    'তোমার সাথে কথা বলব না', 'রাগ', 'বিরক্ত', 'ম্যানেজার চাই'
                ];
                
                foreach ($escalationKeywords as $keyword) {
                    if (mb_strpos($transcriptLower, $keyword) !== false) {
                        $escalationDetected = true;
                        break;
                    }
                }
                
                if (!$hasRealConversation) {
                    // Drop call or minimal conversation
                    if ($this->isValidBDMobile($mobile)) {
                        // Check if it was an escalation request
                        $status = $escalationDetected ? 'Escalation Requested' : 'Drop Call';
                        $type = $escalationDetected ? 'escalation_drop' : 'minimal_conversation';
                        
                        $ticket = new \App\Models\ServiceRequest();
                        $ticket->ivr_service_id = $service ? $service->id : null;
                        $ticket->mobile_number = preg_replace('/\D/', '', $mobile);
                        $ticket->status = $status;
                        $ticket->extracted_data = [
                            'caller_number' => $mobile, 
                            'type' => $type,
                            'escalation_requested' => $escalationDetected
                        ];
                        
                        // 🚨 Important: Add behavior note in problem_description
                        if ($escalationDetected) {
                            $ticket->problem_description = "🚨 Customer requested human agent. Customer বলেছে agent এর সাথে কথা বলতে চায়। Call wait করে কেটে দিয়েছে।";
                        }
                        
                        $ticket->call_transcript = $transcript;
                        $ticket->save();
                        
                        return response()->json([
                            'status' => 'success', 
                            'type' => $type, 
                            'id' => $ticket->id,
                            'escalation' => $escalationDetected
                        ]);
                    } else {
                        // No mobile number at all - skip
                        return response()->json([
                            'status' => 'skipped', 
                            'message' => 'Drop call - কোনো মোবাইল নম্বর পাওয়া যায়নি।'
                        ]);
                    }
                }

                // Real conversation - use caller number if available
                // 📞 Caller number আছে - use করো, customer কে আবার বলতে হবে না!
                if (!$this->isValidBDMobile($mobile)) {
                    // No mobile extracted but maybe we have caller number
                    if (empty($data)) {
                        // No data at all and no number - skip
                        return response()->json([
                            'status' => 'skipped', 
                            'message' => 'কোনো তথ্য extract করা যায়নি এবং caller number ও নেই।'
                        ]);
                    }
                    // If we reach here, we have SOME data but no valid mobile
                    // This shouldn't happen because we already handled caller number fallback above
                }

                // Name optional — না থাকলে null রাখব
                $name = $this->cleanValue($data['customer_name'] ?? null);

                // Existing Incoming entry থাকলে update করো, নাহলে নতুন তৈরি করো
                $ticket = \App\Models\ServiceRequest::where('mobile_number', preg_replace('/\D/', '', $mobile))
                    ->where('status', 'Incoming')
                    ->where('created_at', '>=', now()->subMinutes(30))
                    ->first();

                if (!$ticket) {
                    $ticket = new \App\Models\ServiceRequest();
                }

                $ticket->ivr_service_id    = $service ? $service->id : null;
                $ticket->extracted_data    = $data;
                $ticket->customer_name     = $name;
                $ticket->mobile_number     = preg_replace('/\D/', '', $mobile);
                $ticket->alt_mobile_number = $this->isValidBDMobile($data['alt_mobile_number'] ?? null)
                                             ? preg_replace('/\D/', '', $data['alt_mobile_number']) : null;
                $ticket->address           = $this->cleanValue($data['address'] ?? null);
                $ticket->district          = $this->cleanValue($data['district'] ?? null);
                $ticket->product_name      = $this->cleanValue($data['product_name'] ?? null);
                $ticket->service_center    = $this->cleanValue($data['service_center'] ?? null);
                $ticket->brand             = $this->cleanValue($data['brand'] ?? 'WALTON'); // Default WALTON
                $ticket->barcode           = $this->cleanValue($data['barcode'] ?? null);
                $ticket->problem_description = $this->cleanValue($data['problem_description'] ?? null);
                $ticket->comments          = $this->cleanValue($data['comments'] ?? null);
                $ticket->call_transcript   = $transcript;
                
                // 🚨 Check if escalation was requested during conversation
                if ($escalationDetected) {
                    $ticket->status = 'Escalation Requested';
                    
                    // Add escalation note to problem description
                    $originalProblem = $ticket->problem_description;
                    $ticket->problem_description = "🚨 AGENT REQUESTED: Customer agent এর সাথে কথা বলতে চেয়েছে।\n\n" 
                        . ($originalProblem ?: "Customer তথ্য দিয়েছে কিন্তু AI এর সাথে সমস্যা solve করতে চায়নি।");
                } else {
                    $ticket->status = 'Pending';
                }
                
                $ticket->save();

                // 🧠 Conversation Memory আপডেট করো
                $callerNumber = $request->input('caller_number');
                if ($callerNumber && !empty($data)) {
                    $memory = CustomerMemory::findOrCreateByPhone($callerNumber);
                    $ivrName = $service ? $service->service_name : null;
                    $memory->updateAfterCall($data, $ivrName);
                }

                // 📊 AI Performance Log করো
                \App\Models\AiPerformanceLog::logFromServiceRequest($ticket, $service);

                return response()->json(['status' => 'success']);
            }

            return response()->json(['status' => 'error', 'message' => 'Google API Error: ' . $response->body()]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Text Save Error: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'সার্ভার এরর: ' . $e->getMessage()]);
        }
    }

    // 🆕 #4 Smart Data Validation — AI extract করা data যাচাই করা
    private function validateExtractedData(array $data): array
    {
        $errors = [];

        // মোবাইল নম্বর ১১ ডিজিট হতে হবে
        if (!empty($data['mobile_number'])) {
            $mobile = preg_replace('/\D/', '', $data['mobile_number']); // শুধু সংখ্যা রাখো
            if (strlen($mobile) !== 11) {
                $errors[] = "মোবাইল নম্বর ১১ ডিজিট হতে হবে (পেয়েছি: {$data['mobile_number']})";
            }
            if (!str_starts_with($mobile, '01')) {
                $errors[] = "মোবাইল নম্বর '01' দিয়ে শুরু হতে হবে";
            }
        }

        // বিকল্প মোবাইল নম্বর চেক
        if (!empty($data['alt_mobile_number'])) {
            $altMobile = preg_replace('/\D/', '', $data['alt_mobile_number']);
            if (strlen($altMobile) !== 11) {
                $errors[] = "বিকল্প মোবাইল নম্বর ১১ ডিজিট হতে হবে";
            }
        }

        // নাম অন্তত ২ অক্ষর হতে হবে
        if (!empty($data['customer_name']) && mb_strlen(trim($data['customer_name'])) < 2) {
            $errors[] = "কাস্টমারের নাম কমপক্ষে ২ অক্ষর হতে হবে";
        }

        // barcode/serial সাধারণত numeric বা alphanumeric
        if (!empty($data['barcode'])) {
            if (strlen($data['barcode']) < 4) {
                $errors[] = "বারকোড/সিরিয়াল নম্বর সঠিক মনে হচ্ছে না";
            }
        }

        return $errors;
    }
}