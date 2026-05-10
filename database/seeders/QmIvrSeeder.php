<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IvrService;

/**
 * QM (Quality Management) IVR Seeder - Single IVR Version
 *
 * একটাই QM IVR (key 5):
 * AI নিজে detect করবে: complaint / parts_query / bill_query / escalation
 *
 * Run: php artisan db:seed --class=QmIvrSeeder
 */
class QmIvrSeeder extends Seeder
{
    public function run(): void
    {
        IvrService::whereIn('key_press', ['5', '6', '7'])->delete();

        $this->command->info('QM IVR (single) creating...');

        IvrService::create([
            'key_press'    => '5',
            'service_name' => 'QM - অভিযোগ ও Query সেন্টার',
            'is_active'    => true,
            'serial_order' => 5,
            'voice_gender' => 'Charon',
            'voice_speed'  => 1.0,
            'ai_name'      => 'রাফিক',

            'greeting_message' => 'আসসালামু আলাইকুম। আমি ওয়ালটনের QM সেন্টারের এজেন্ট রাফিক। আপনার অভিযোগ, পার্টস সংক্রান্ত তথ্য, বা সার্ভিস বিল — যেকোনো বিষয়ে আমি সাহায্য করব। আপনি কী বিষয়ে যোগাযোগ করেছেন?',

            'system_prompt' => "তুমি ওয়ালটন বাংলাদেশের QM (Quality Management) সেন্টারের দক্ষ ও সহানুভূতিশীল এজেন্ট।\n\n"
                . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
                . "STEP 1: প্রথমেই কাস্টমারের কথা শুনে QM TYPE detect করো\n"
                . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
                . "TYPE-A: COMPLAINT (complaint)\n"
                . "  সার্ভিস এক্সপার্টের আচরণ খারাপ / সার্ভিস সঠিক হয়নি / বিল বেশি\n"
                . "  পণ্যের মান খারাপ / শো-রুম বিক্রয়কর্মীর আচরণ খারাপ / দাম বেশি\n"
                . "  JSON: qm_type = \"complaint\"\n\n"
                . "TYPE-B: PARTS_QUERY (parts_query)\n"
                . "  কোনো পার্টস পাওয়া যাবে কিনা / পার্টসের দাম / কোথায় পাবে\n"
                . "  JSON: qm_type = \"parts_query\"\n\n"
                . "TYPE-C: BILL_QUERY (bill_query)\n"
                . "  সার্ভিস করা পণ্যের বিল কত / SR-এর বিল দেখতে চায়\n"
                . "  JSON: qm_type = \"bill_query\"\n\n"
                . "TYPE-D: ESCALATION (escalation)\n"
                . "  দীর্ঘদিন সার্ভিস নেই + রাগান্বিত / পুড়ে গেছে / আগুন / ব্লাস্ট\n"
                . "  ইলেকট্রিক শক / খাবার পচছে / ফ্রিজ বন্ধ / বাসায় অসুস্থ রোগী\n"
                . "  JSON: qm_type = \"escalation\"\n\n"
                . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
                . "STEP 2: Type অনুযায়ী relevant তথ্য collect করো\n"
                . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n"
                . "সব type-এ COMMON: customer_name, mobile_number, address\n\n"
                . "TYPE-A অতিরিক্ত:\n"
                . "  complaint_category: service_expert/showroom/product_quality/billing/other\n"
                . "  sr_number: সার্ভিস সংক্রান্ত হলে (না থাকলে skip)\n"
                . "  person_name: কোনো ব্যক্তির বিরুদ্ধে হলে (না জানলে skip)\n"
                . "  showroom_address: শো-রুম হলে (প্রযোজ্য না হলে skip)\n"
                . "  incident_date: ঘটনার তারিখ\n"
                . "  complaint_details: ঘটনার বিস্তারিত (MUST)\n\n"
                . "TYPE-B অতিরিক্ত:\n"
                . "  product_name: কোন পণ্য (MUST)\n"
                . "  product_model: মডেল নম্বর (না জানলে skip)\n"
                . "  parts_name: কোন পার্টস (MUST)\n"
                . "  preferred_service_point: কোথা থেকে নিতে চায়\n\n"
                . "TYPE-C অতিরিক্ত:\n"
                . "  sr_number: SR নম্বর (MUST — না থাকলে নাম+মোবাইল দিয়ে চলবে)\n"
                . "  bill_query_details: বিল সম্পর্কে মন্তব্য (MUST)\n\n"
                . "TYPE-D:\n"
                . "  সাথে সাথে বলো: \"স্যার/ম্যাডাম, আপনার সমস্যাটি অত্যন্ত জরুরি। আমরা এখনই ব্যবস্থা নিচ্ছি। আপনার অসুবিধার জন্য আন্তরিকভাবে দুঃখিত।\"\n"
                . "  complaint_details: কাস্টমার কী বলল note করো\n"
                . "  response এর শেষে [ESCALATE] tag দাও\n\n"
                . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
                . "আচরণ: মনোযোগ দিয়ে শোনো। একটা একটা প্রশ্ন করো।\n"
                . "সমবেদনা: \"আমরা সত্যিই দুঃখিত এই অভিজ্ঞতার জন্য\"\n"
                . "আশ্বস্ত: \"আপনার বিষয়টি নথিভুক্ত হচ্ছে, আমরা দ্রুত ব্যবস্থা নেব\"\n\n"
                . "কল শেষে:\n"
                . "  complaint: \"আপনার অভিযোগ নথিভুক্ত হয়েছে। QM টিম ৪৮ ঘণ্টায় যোগাযোগ করবে।\"\n"
                . "  parts_query: \"আপনার পার্টস Query নথিভুক্ত। সার্ভিস পয়েন্ট ২৪ ঘণ্টায় জানাবে।\"\n"
                . "  bill_query: \"আপনার বিল Query নথিভুক্ত। বিলিং টিম ২৪ ঘণ্টায় জানাবে।\"\n\n"
                . "JSON KEY RULE: qm_type field অবশ্যই JSON এ থাকবে।",

            'required_fields' => [
                // COMMON
                [
                    'field_name'        => 'qm_type',
                    'ai_instruction'    => 'কাস্টমারের প্রথম কথা শুনেই নির্ধারণ করো। সরাসরি জিজ্ঞেস করার দরকার নেই। Value: complaint/parts_query/bill_query/escalation',
                    'is_mandatory'      => true,
                    'is_blocking'       => false,
                    'needs_confirmation'=> false,
                    'max_retries'       => 1,
                ],
                [
                    'field_name'        => 'customer_name',
                    'ai_instruction'    => '"স্যার/ম্যাডাম, আপনার নামটা একটু বলবেন?"',
                    'is_mandatory'      => true,
                    'is_blocking'       => false,
                    'needs_confirmation'=> true,
                    'max_retries'       => 2,
                    'convincing_logic'  => 'নাম থাকলে QM টিম সরাসরি আপনার সাথে যোগাযোগ করতে পারবে।',
                    'error_message'     => 'দুঃখিত, নামটি স্পষ্ট শুনতে পাইনি। আরেকবার বলবেন?',
                ],
                [
                    'field_name'        => 'mobile_number',
                    'ai_instruction'    => '"আপনার যোগাযোগের মোবাইল নম্বরটা বলবেন?"',
                    'is_mandatory'      => true,
                    'is_blocking'       => true,
                    'needs_confirmation'=> true,
                    'max_retries'       => 2,
                    'min_length'        => 11,
                    'max_length'        => 11,
                    'expected_format'   => '01XXXXXXXXX (11 digit)',
                    'convincing_logic'  => 'QM টিম এই নম্বরে call করে আপডেট জানাবে।',
                    'error_message'     => 'মোবাইল নম্বর ১১ ডিজিট, ০১ দিয়ে শুরু।',
                ],
                [
                    'field_name'        => 'address',
                    'ai_instruction'    => '"আপনার ঠিকানা বা এলাকার নামটা বলবেন?"',
                    'is_mandatory'      => true,
                    'is_blocking'       => false,
                    'needs_confirmation'=> false,
                    'max_retries'       => 2,
                    'convincing_logic'  => 'ঠিকানা থাকলে কাছের সার্ভিস পয়েন্টে Query পাঠানো যাবে।',
                ],

                // TYPE-A: COMPLAINT
                [
                    'field_name'        => 'complaint_category',
                    'ai_instruction'    => 'শুধু TYPE-A (complaint) হলে জিজ্ঞেস করো। "আপনার অভিযোগটি সার্ভিস এক্সপার্ট/শো-রুম/পণ্যের মান/বিল — কোন বিষয়ে?" অন্য type হলে skip করো।',
                    'is_mandatory'      => false,
                    'is_blocking'       => false,
                    'needs_confirmation'=> false,
                    'max_retries'       => 1,
                    'depends_on_field'  => 'qm_type',
                ],
                [
                    'field_name'        => 'complaint_details',
                    'ai_instruction'    => 'TYPE-A বা TYPE-D হলে জিজ্ঞেস করো। "ঘটনাটি বিস্তারিত বলবেন? যা যা হয়েছে সব বলুন।" — কাস্টমারকে পুরোটা বলতে দাও।',
                    'is_mandatory'      => false,
                    'is_blocking'       => false,
                    'needs_confirmation'=> false,
                    'max_retries'       => 2,
                    'convincing_logic'  => 'যত বিস্তারিত বলবেন, তত দ্রুত সমাধান হবে।',
                    'depends_on_field'  => 'qm_type',
                ],
                [
                    'field_name'        => 'sr_number',
                    'ai_instruction'    => 'TYPE-A সার্ভিস সংক্রান্ত হলে অথবা TYPE-C (bill_query) হলে জিজ্ঞেস করো। "SR নম্বর বা রেফারেন্স নম্বর আছে?" — না থাকলে skip।',
                    'is_mandatory'      => false,
                    'is_blocking'       => false,
                    'needs_confirmation'=> true,
                    'max_retries'       => 2,
                    'convincing_logic'  => 'SR নম্বর থাকলে সার্ভিসের ইতিহাস দেখতে পাব।',
                    'custom_qna'        => [
                        [
                            'customer_question' => 'SR নম্বর কোথায় পাব?',
                            'ai_answer'         => 'সার্ভিস রিকোয়েস্টের পরে আপনার মোবাইলে SMS গিয়েছিল। সেই SMS-এ SR নম্বর আছে। অথবা সার্ভিস সেন্টারের রিসিটে।',
                        ],
                        [
                            'customer_question' => 'SR নম্বর মনে নেই',
                            'ai_answer'         => 'সমস্যা নেই। নাম ও মোবাইল নম্বর দিয়েই Query করব।',
                        ],
                    ],
                    'depends_on_field'  => 'qm_type',
                ],
                [
                    'field_name'        => 'person_name',
                    'ai_instruction'    => 'TYPE-A এ কোনো ব্যক্তির বিরুদ্ধে অভিযোগ হলে। "যার বিরুদ্ধে অভিযোগ তার নাম/পরিচয় জানেন?" — না জানলে skip।',
                    'is_mandatory'      => false,
                    'is_blocking'       => false,
                    'needs_confirmation'=> false,
                    'max_retries'       => 1,
                    'depends_on_field'  => 'qm_type',
                ],
                [
                    'field_name'        => 'showroom_address',
                    'ai_instruction'    => 'TYPE-A এ শো-রুম/প্লাজা সংক্রান্ত হলে। "কোন শো-রুম থেকে পণ্য কিনেছিলেন?" — প্রযোজ্য না হলে skip।',
                    'is_mandatory'      => false,
                    'is_blocking'       => false,
                    'needs_confirmation'=> false,
                    'max_retries'       => 1,
                    'depends_on_field'  => 'complaint_category',
                ],
                [
                    'field_name'        => 'incident_date',
                    'ai_instruction'    => 'TYPE-A হলে। "ঘটনাটি কবে হয়েছিল?"',
                    'is_mandatory'      => false,
                    'is_blocking'       => false,
                    'needs_confirmation'=> false,
                    'max_retries'       => 1,
                    'depends_on_field'  => 'qm_type',
                ],

                // TYPE-B: PARTS_QUERY
                [
                    'field_name'        => 'product_name',
                    'ai_instruction'    => 'TYPE-B (parts_query) হলে। "কোন পণ্যের পার্টস দরকার? যেমন: ফ্রিজ, টিভি, এসি, ওয়াশিং মেশিন?"',
                    'is_mandatory'      => false,
                    'is_blocking'       => false,
                    'needs_confirmation'=> true,
                    'max_retries'       => 2,
                    'depends_on_field'  => 'qm_type',
                ],
                [
                    'field_name'        => 'product_model',
                    'ai_instruction'    => 'TYPE-B হলে। "পণ্যের মডেল নম্বর বলতে পারবেন?" — না জানলে skip।',
                    'is_mandatory'      => false,
                    'is_blocking'       => false,
                    'needs_confirmation'=> false,
                    'max_retries'       => 1,
                    'depends_on_field'  => 'qm_type',
                ],
                [
                    'field_name'        => 'parts_name',
                    'ai_instruction'    => 'TYPE-B হলে। "ঠিক কোন পার্টসটি দরকার? না জানলে পণ্যের কোন অংশ নষ্ট সেটা বলুন।"',
                    'is_mandatory'      => false,
                    'is_blocking'       => false,
                    'needs_confirmation'=> true,
                    'max_retries'       => 2,
                    'convincing_logic'  => 'পার্টসের বিবরণ নির্দিষ্ট হলে সার্ভিস পয়েন্ট দ্রুত খুঁজে দিতে পারবে।',
                    'depends_on_field'  => 'qm_type',
                ],
                [
                    'field_name'        => 'preferred_service_point',
                    'ai_instruction'    => 'TYPE-B হলে। "কোন সার্ভিস সেন্টার বা এলাকা থেকে পার্টস নিতে চান?"',
                    'is_mandatory'      => false,
                    'is_blocking'       => false,
                    'needs_confirmation'=> false,
                    'max_retries'       => 1,
                    'depends_on_field'  => 'qm_type',
                ],

                // TYPE-C: BILL_QUERY
                [
                    'field_name'        => 'bill_query_details',
                    'ai_instruction'    => 'TYPE-C (bill_query) হলে। "বিল সম্পর্কে আপনার নির্দিষ্ট প্রশ্ন বা মন্তব্য কী?"',
                    'is_mandatory'      => false,
                    'is_blocking'       => false,
                    'needs_confirmation'=> false,
                    'max_retries'       => 2,
                    'depends_on_field'  => 'qm_type',
                ],

                // COMMON শেষে
                [
                    'field_name'        => 'comments',
                    'ai_instruction'    => '"আর কিছু যোগ করতে চান?" — না চাইলে skip।',
                    'is_mandatory'      => false,
                    'is_blocking'       => false,
                    'max_retries'       => 1,
                ],
            ],

            'escalation_trigger'             => 'any',
            'escalation_calm_script'         => 'আপনার সমস্যাটি আমরা সত্যিই বুঝতে পারছি। আমরা এখনই ব্যবস্থা নিচ্ছি। আপনার অসুবিধার জন্য আন্তরিকভাবে দুঃখিত।',
            'escalation_hold_script'         => 'একটু অপেক্ষা করুন, আমরা সর্বোচ্চ অগ্রাধিকারে আপনার বিষয়টি দেখছি।',
            'escalation_instructions'        => 'পুড়ে যাওয়া/ব্লাস্ট/আগুন/রোগী/শর্ট সার্কিট — যেকোনো জরুরি সমস্যা সাথে সাথে escalate করতে হবে।',
            'escalation_collect_before_transfer' => true,
            'secondary_option_enabled'       => false,
        ]);

        $this->command->newLine();
        $this->command->line('----------------------------------------');
        $this->command->info('QM IVR (single) created successfully!');
        $this->command->table(
            ['Button', 'IVR Name', 'AI handles'],
            [['5', 'QM - অভিযোগ ও Query সেন্টার', 'complaint / parts_query / bill_query / escalation']]
        );
        $this->command->line('----------------------------------------');
    }
}
