<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI API খরচ কমানোর কনফিগারেশন
    |--------------------------------------------------------------------------
    | এই সেটিংস দিয়ে Gemini API এর খরচ নিয়ন্ত্রণ করুন
    */

    // ✅ Cache সক্রিয় করুন (একই প্রশ্নের উত্তর cache থেকে নেয়)
    'enable_cache' => env('AI_ENABLE_CACHE', true),
    
    // Cache TTL (সেকেন্ডে) - ডিফল্ট 30 মিনিট
    'cache_ttl' => env('AI_CACHE_TTL', 1800),
    
    // ✅ ব্যবহৃত Model (দামি মডেল এড়িয়ে যান)
    'models' => [
        'voice' => 'gemini-1.5-flash',      // 💰 সস্তা
        'form'  => 'gemini-1.5-flash',      // 💰 সস্তা
        'chat'  => 'gemini-1.5-flash',      // 💰 সস্তা
        // ❌ 'gemini-2.5-flash' আগে ছিল - এখন সরানো হয়েছে
        // ❌ 'gemini-live-2.5-flash-native-audio' - খুব দামি, শুধু ডেমোতে ব্যবহার করুন
    ],
    
    // ✅ প্রম্পট অপটিমাইজেশন (ছোট প্রম্পট = কম টোকেন = কম খরচ)
    'optimize_prompts' => env('AI_OPTIMIZE_PROMPTS', true),
    
    // ✅ Response timeout (সেকেন্ডে) - দীর্ঘ রেসপন্স এড়ান
    'timeout' => env('AI_TIMEOUT', 10),
    
    // ✅ প্রতিদিন সর্বোচ্চ API call সীমা (0 = সীমা নেই)
    'daily_limit' => env('AI_DAILY_LIMIT', 0),
    
    // ⚠️ Gemini Live সতর্কীকরণ
    'gemini_live_warning' => [
        'enabled' => true,
        'message' => '⚠️ সতর্কতা: Gemini Live Native Audio খুব দামি! শুধুমাত্র গুরুত্বপূর্ণ ডেমোতে ব্যবহার করুন।',
        'estimated_cost_per_hour' => '$X.XX', // আপনার actual rate দিন
    ],
    
    /*
    | 💡 খরচ কমানোর টিপস:
    | 1. Cache সবসময় সক্রিয় রাখুন
    | 2. gemini-1.5-flash ব্যবহার করুন (সবচেয়ে সস্তা)
    | 3. প্রম্পট যতটা সম্ভব ছোট রাখুন
    | 4. একই প্রশ্ন বার বার করবেন না
    | 5. Gemini Live শুধু প্রয়োজনে ব্যবহার করুন
    | 6. Daily limit সেট করুন billing surprise এড়াতে
    */
];
