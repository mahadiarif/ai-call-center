<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * 💰 AI API খরচ কমানোর জন্য Response Caching
 * একই প্রশ্নের উত্তর বার বার API থেকে না এনে Cache থেকে দেয়
 */
class AiCacheService
{
    /**
     * Cache থেকে response চেক করো, না থাকলে API call করো
     * 
     * @param string $type - 'voice' | 'form' | 'general'
     * @param string $input - User input বা prompt
     * @param callable $apiCallback - API call করার function
     * @param int $ttl - Cache রাখার সময় (সেকেন্ড), ডিফল্ট ৩০ মিনিট
     * @return mixed
     */
    public static function remember(string $type, string $input, callable $apiCallback, int $ttl = 1800)
    {
        // Input থেকে unique cache key তৈরি
        $cacheKey = self::generateCacheKey($type, $input);
        
        // Cache আছে কিনা চেক করো
        if (Cache::has($cacheKey)) {
            \Log::info("💰 Cache Hit - API call বাঁচল: {$type}");
            return Cache::get($cacheKey);
        }
        
        // Cache নেই, তাহলে API call করো
        \Log::info("📞 API Call করছি: {$type}");
        $response = $apiCallback();
        
        // Response cache করে রাখো
        Cache::put($cacheKey, $response, $ttl);
        
        return $response;
    }
    
    /**
     * Unique cache key তৈরি
     */
    private static function generateCacheKey(string $type, string $input): string
    {
        // Input কে ছোট হাতের অক্ষর করে hash তৈরি করো
        $hash = md5(strtolower(trim($input)));
        return "ai_cache_{$type}_{$hash}";
    }
    
    /**
     * নির্দিষ্ট type এর সব cache মুছে ফেলো
     */
    public static function clearType(string $type): void
    {
        // এটা Laravel টিন্কার বা Artisan command থেকে চালাতে পারবেন
        Cache::flush(); // সাবধান! সব cache মুছে ফেলবে
    }
    
    /**
     * কোনো নির্দিষ্ট input এর cache মুছো
     */
    public static function forget(string $type, string $input): void
    {
        $cacheKey = self::generateCacheKey($type, $input);
        Cache::forget($cacheKey);
    }
}
