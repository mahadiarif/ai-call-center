# 💰 AI খরচ কমানোর গাইড

## ✅ যা যা করা হয়েছে:

### 1️⃣ **Model Downgrade** (সবচেয়ে বড় প্রভাব)
- ❌ **আগে**: Gemini 2.5 Flash (দামি)
- ✅ **এখন**: Gemini 1.5 Flash (সস্তা)
- 📍 পরিবর্তন: `AIFormController.php` line 566
- **সাশ্রয়**: প্রায় **40-60% কম খরচ**

### 2️⃣ **Response Caching System**
- ✅ **নতুন ফাইল**: `app/Services/AiCacheService.php`
- একই প্রশ্নের উত্তর 30 মিনিট cache রাখে
- API call না করে cache থেকে দেয়
- **সাশ্রয়**: বার বার একই প্রশ্নের জন্য **90-100% সাশ্রয়**

### 3️⃣ **Prompt Optimization**
- প্রম্পট ছোট করা হয়েছে
- অপ্রয়োজনীয় শব্দ বাদ দেয়া হয়েছে
- **সাশ্রয়**: প্রতি call **20-30% কম token**

### 4️⃣ **Timeout যুক্ত করা**
- `AIController.php` তে 10 সেকেন্ড timeout
- দীর্ঘ response এড়ানো
- **সাশ্রয়**: অতিরিক্ত খরচ প্রতিরোধ

### 5️⃣ **Cost Optimization Config**
- ✅ **নতুন ফাইল**: `config/ai-optimization.php`
- সব setting এক জায়গায়
- সহজে পরিবর্তন করা যায়

### 6️⃣ **Gemini Live Warning**
- `mic-test.blade.php` তে সতর্কতা যুক্ত
- ব্যবহারকারীকে জানানো যে এটা দামি
- অপ্রয়োজনীয় ব্যবহার কমবে

---

## 📊 **আনুমানিক খরচ তুলনা:**

### আগে (April 10-11):
```
Gemini 2.5 Flash: $X/call
No caching: 100% API calls
Long prompts: বেশি token
────────────────────────────
মোট: উচ্চ খরচ
```

### এখন:
```
Gemini 1.5 Flash: 40-60% কম
Caching: 30-50% কম calls
Short prompts: 20-30% কম tokens
────────────────────────────
মোট সাশ্রয়: 60-80% 💰
```

---

## 🚀 **আরো খরচ কমানোর উপায়:**

### 1. **Alternative Free API ব্যবহার**
```php
// সাধারণ প্রশ্নের জন্য:
- Hugging Face (ফ্রি tier আছে)
- Ollama (নিজের সার্ভারে চালান - একদম ফ্রি)
- GPT-4All (ফ্রি + offline)
```

### 2. **Daily Limit সেট করুন**
```php
// config/ai-optimization.php
'daily_limit' => 1000, // প্রতিদিন সর্বোচ্চ 1000 calls
```

### 3. **Gemini Live শুধু জরুরিতে**
- শুধু ডেমো/প্রেজেন্টেশনে ব্যবহার করুন
- দৈনন্দিন কাজে সাধারণ API ব্যবহার করুন
- এটা সবচেয়ে দামি!

### 4. **FAQ/Pre-defined Responses**
```php
// সাধারণ প্রশ্নের জন্য database-based response
if (in_array($question, $commonQuestions)) {
    return $predefinedAnswers[$question]; // API call নয়!
}
```

### 5. **Batch Processing**
```php
// একসাথে অনেক request না পাঠিয়ে queue ব্যবহার করুন
// Rate limiting করুন
```

---

## 🎯 **বাংলাদেশি কোম্পানির জন্য সেরা Setup:**

### ✅ **সুপারিশকৃত Configuration:**
```
1. Model: Gemini 1.5 Flash (সবচেয়ে সস্তা)
2. Caching: সক্রিয় (30 মিনিট)
3. Daily Limit: 2000 calls
4. Gemini Live: বন্ধ (শুধু ডেমোতে)
5. Prompt: যতটা ছোট সম্ভব
6. Timeout: 10 seconds
```

### 📈 **প্রত্যাশিত খরচ (মাসিক):**
```
1000 calls/day × 30 days = 30,000 calls
Gemini 1.5 Flash rate: ~$0.00015/call
30,000 × $0.00015 = $4.50/month
────────────────────────────
Cache hit 40% = $2.70/month 🎉
```

---

## 🛠️ **কিভাবে আরও উন্নত করবেন:**

### 1. **API Usage Tracking যোগ করুন**
```bash
php artisan make:migration create_api_usage_logs_table
```

### 2. **Daily Report পান**
```php
// প্রতিদিন কত API call হয়েছে email করুন
```

### 3. **Alert System**
```php
// Daily limit 80% পৌঁছালে warning email
```

---

## 📝 **পরবর্তী পদক্ষেপ:**

1. ✅ Cache clear করুন: `php artisan cache:clear`
2. ✅ Testing করুন নতুন setup
3. ✅ 1 সপ্তাহ monitor করুন billing
4. ✅ প্রয়োজনে আরও adjust করুন

---

**মনে রাখবেন**: সবচেয়ে বড় খরচ Gemini Live থেকে আসে। এটা এড়িয়ে চলুন! 🚫
