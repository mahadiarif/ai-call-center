# AI Field Collection - Prompt Templates
## প্রতিটা Field এর জন্য Short & Long Format

---

## 1️⃣ CUSTOMER NAME (নাম)

### Short Question (সংক্ষিপ্ত):
```
"Sir, আপনার নামটা?"
```

### Long Question (বিস্তারিত):
```
"Sir, আপনার পুরো নামটা একবার বলবেন?"
```

### Convincing Logic (না দিলে):
```
"Sir, নামটা থাকলে পরে যোগাযোগ করতে সুবিধা হয়।"
```

### Confirmation (confirm):
```
"ধন্যবাদ sir, [নাম] ঠিক আছে।"
```

### Skip Message:
```
"ঠিক আছে sir।"
```

---

## 2️⃣ MOBILE NUMBER (মোবাইল নম্বর)

### ⚠️ PRIMARY NUMBER - জিজ্ঞেস করবে না!
```
Caller number already আছে - automatically use হবে
```

### ALTERNATE NUMBER ONLY:

#### Short Question:
```
"Sir, আপনার এই number ছাড়া আরও কোনো number আছে?"
```

#### Long Question:
```
"Sir, এই number ছাড়া কি আরেকটা alternate number দিতে পারবেন?"
```

#### Convincing Logic:
```
"Sir, যদি এই number এ পাওয়া না যায়, তাহলে alternate number এ যোগাযোগ করতে পারব।"
```

#### Confirmation:
```
"ধন্যবাদ sir, [number]।"
```

#### Skip Message:
```
"ঠিক আছে sir।"
```

---

## 3️⃣ ADDRESS (ঠিকানা)

### Short Question:
```
"Sir, আপনার ঠিকানাটা?"
```

### Long Question:
```
"Sir, আপনার সম্পূর্ণ ঠিকানাটা বলবেন?"
```

### Convincing Logic:
```
"Sir, ঠিকানা থাকলে service দিতে সুবিধা হবে।"
```

### Confirmation:
```
"ধন্যবাদ sir, [ঠিকানা] ঠিক আছে।"
```

### Skip Message:
```
"ঠিক আছে sir।"
```

---

## 4️⃣ DISTRICT (জেলা)

### ⚠️ Smart Rule:
```
Address এ জেলার নাম থাকলে আর জিজ্ঞেস করবে না!
Example: "মিরপুর, ঢাকা" → district = "ঢাকা"
```

### Short Question:
```
"Sir, কোন জেলা?"
```

### Long Question:
```
"Sir, আপনি কোন জেলায় থাকেন?"
```

### Confirmation:
```
"ধন্যবাদ sir, [জেলা]।"
```

### Skip Message:
```
"ঠিক আছে sir।"
```

---

## 5️⃣ PRODUCT NAME (পণ্যের নাম)

### Short Question:
```
"Sir, কোন পণ্যের সমস্যা?"
```

### Long Question:
```
"Sir, আপনার কোন পণ্যে সমস্যা হচ্ছে?"
```

### Follow-up (যদি শুধু category বলে):
```
Customer: "ফ্রিজ"
AI: "আচ্ছা, কোন মডেলের ফ্রিজ?"
```

### Confirmation:
```
"ধন্যবাদ sir, [পণ্য/মডেল] ঠিক আছে।"
```

### Skip Message:
```
"ঠিক আছে sir।"
```

---

## 6️⃣ BARCODE / SERIAL NUMBER (বারকোড/সিরিয়াল)

### Short Question:
```
"Sir, বারকোড নম্বরটা আছে?"
```

### Long Question:
```
"Sir, পণ্যের বারকোড বা সিরিয়াল নম্বর দেখতে পাচ্ছেন?"
```

### Convincing Logic:
```
"Sir, বারকোড থাকলে সার্ভিস দিতে সহজ হয়।"
```

### Confirmation:
```
"ধন্যবাদ sir, [barcode]।"
```

### Skip Message:
```
"ঠিক আছে sir, সমস্যা নেই।"
```

---

## 7️⃣ PROBLEM DESCRIPTION (সমস্যার বিবরণ)

### Short Question:
```
"Sir, কী সমস্যা হচ্ছে?"
```

### Long Question:
```
"Sir, ঠিক কোন সমস্যাটা হচ্ছে বলবেন?"
```

### Follow-up (যদি short বলে):
```
Customer: "ঠান্ডা হয় না"
AI: "আচ্ছা, কতদিন ধরে এই সমস্যা?"
```

### Confirmation:
```
"বুঝলাম sir, [সমস্যা]।"
```

### Skip Message:
```
"ঠিক আছে sir।"
```

---

## 8️⃣ SERVICE CENTER (সার্ভিস সেন্টার)

### Short Question:
```
"Sir, কোন সেন্টার থেকে কিনেছিলেন?"
```

### Long Question:
```
"Sir, আপনি কোন সার্ভিস সেন্টার থেকে পণ্যটি কিনেছিলেন বলতে পারবেন?"
```

### যদি বলে:
```
AI: "ধন্যবাদ sir। [center name] এ যোগাযোগ করতে পারেন।"
```

### যদি না বলে/মনে না থাকে:
```
"কোনো সমস্যা নেই sir। আমি দেখছি কি করা যায়। আমরা ব্যবস্থা নিচ্ছি।"
```

### যদি বলে "আপনারা manage করেন":
```
"অবশ্যই sir। আমরা নিকটস্থ সার্ভিস সেন্টার থেকে আপনার সাথে যোগাযোগ করব।"
```

### Skip Message:
```
"ঠিক আছে sir।"
```

---

## 9️⃣ BRAND (ব্র্যান্ড)

### ⚠️ Smart Rule:
```
Product name এ brand থাকলে আর জিজ্ঞেস করবে না!
Example: "Walton ফ্রিজ" → brand = "WALTON"
```

### Short Question:
```
"Sir, কোন ব্র্যান্ডের?"
```

### Long Question:
```
"Sir, পণ্যটি কোন ব্র্যান্ডের?"
```

### Confirmation:
```
"ধন্যবাদ sir, [brand]।"
```

### Skip Message:
```
"ঠিক আছে sir।"
```

---

## 🔟 COMMENTS (অতিরিক্ত মন্তব্য)

### Short Question:
```
"Sir, আর কিছু বলার আছে?"
```

### Long Question:
```
"Sir, আর কোনো বিশেষ কিছু জানানোর আছে?"
```

### Skip Message:
```
"ঠিক আছে sir, ধন্যবাদ।"
```

---

---

# 📋 DATABASE/IVR CONFIG FORMAT

## 🎯 কোথায় এবং কিভাবে Use করবেন?

### ✅ Location 1: **IVR Services Dashboard** (সবচেয়ে সহজ)

**Path:** `Dashboard → IVR Services → Edit IVR → Required Fields Section`

**Steps:**
1. Admin panel এ login করুন
2. `IVR Services` menu তে যান
3. যে IVR edit করতে চান তাতে click করুন
4. নিচে scroll করে `Required Fields` section খুঁজুন
5. "Add New Field" বা similar button এ click করুন
6. নিচের JSON format টা copy করে paste করুন
7. Save করুন

---

### ✅ Location 2: **Database Direct Entry** (Advanced)

**Table:** `ivr_services`  
**Column:** `required_fields` (JSON type)

**Query Example:**
```sql
UPDATE ivr_services 
SET required_fields = '[
  {
    "field_name": "customer_name",
    "is_mandatory": true,
    "ai_instruction": "Sir, আপনার নামটা?",
    ...
  },
  {
    "field_name": "address",
    "is_mandatory": false,
    ...
  }
]'
WHERE id = 1;
```

---

## 📝 JSON Format (Single Field):

```json
{
  "field_name": "customer_name",
  "is_mandatory": true,
  "is_blocking": false,
  "max_retries": 2,
  "needs_confirmation": true,
  "ai_instruction": "Sir, আপনার নামটা?",
  "indirect_question": "Sir, আপনার পুরো নামটা একবার বলবেন?",
  "convincing_logic": "Sir, নামটা থাকলে পরে যোগাযোগ করতে সুবিধা হয়।",
  "error_message": "নামটা সঠিক শুনতে পাইনি sir, আবার বলবেন?",
  "min_length": 2,
  "max_length": 100,
  "expected_format": "Text (বাংলা/English)",
  "sample_values": "করিম\nরহিম\nFatima\nAhmed"
}
```

---

## 📚 Multiple Fields Example (একসাথে সব fields):

```json
[
  {
    "field_name": "customer_name",
    "is_mandatory": true,
    "is_blocking": false,
    "max_retries": 2,
    "needs_confirmation": true,
    "ai_instruction": "Sir, আপনার নামটা?",
    "indirect_question": "Sir, আপনার পুরো নামটা একবার বলবেন?",
    "convincing_logic": "Sir, নামটা থাকলে পরে যোগাযোগ করতে সুবিধা হয়।",
    "min_length": 2,
    "max_length": 100
  },
  {
    "field_name": "address",
    "is_mandatory": false,
    "is_blocking": false,
    "max_retries": 2,
    "needs_confirmation": true,
    "ai_instruction": "Sir, আপনার ঠিকানাটা?",
    "indirect_question": "Sir, আপনার সম্পূর্ণ ঠিকানাটা বলবেন?",
    "convincing_logic": "Sir, ঠিকানা থাকলে service দিতে সুবিধা হবে।"
  },
  {
    "field_name": "product_name",
    "is_mandatory": true,
    "is_blocking": true,
    "max_retries": 3,
    "needs_confirmation": true,
    "ai_instruction": "Sir, কোন পণ্যের সমস্যা?",
    "indirect_question": "Sir, আপনার কোন পণ্যে সমস্যা হচ্ছে?"
  },
  {
    "field_name": "problem_description",
    "is_mandatory": true,
    "is_blocking": true,
    "max_retries": 2,
    "needs_confirmation": true,
    "ai_instruction": "Sir, কী সমস্যা হচ্ছে?",
    "indirect_question": "Sir, ঠিক কোন সমস্যাটা হচ্ছে বলবেন?"
  }
]
```

---

## 🔧 Field Properties বুঝুন:

| Property | Type | কাজ কী? | Example |
|----------|------|---------|---------|
| `field_name` | string | Field এর নাম (database column) | `"customer_name"` |
| `is_mandatory` | boolean | জরুরি কিনা? | `true` বা `false` |
| `is_blocking` | boolean | না দিলে এগোবে না? | `true` = আটকে যাবে |
| `max_retries` | number | কতবার চেষ্টা করবে? | `2` = দুইবার |
| `needs_confirmation` | boolean | Confirm করবে? | `true` = করবে |
| `ai_instruction` | string | 1st try question | `"Sir, নামটা?"` |
| `indirect_question` | string | 2nd try question | `"Sir, পুরো নামটা?"` |
| `convincing_logic` | string | না দিলে কী বলবে | `"নামটা থাকলে সুবিধা"` |
| `error_message` | string | ভুল হলে বলবে | `"শুনতে পাইনি"` |
| `min_length` | number | Minimum length | `2` |
| `max_length` | number | Maximum length | `100` |
| `expected_format` | string | কোন type data | `"Text"` বা `"Number"` |
| `sample_values` | string | উদাহরণ values | `"করিম\nরহিম"` |

---

## 🚀 Quick Start Guide:

### Step 1: একটা Field Configure করুন
```json
{
  "field_name": "customer_name",
  "is_mandatory": true,
  "max_retries": 2,
  "ai_instruction": "Sir, আপনার নামটা?"
}
```

### Step 2: IVR Dashboard এ যান
- Menu: `IVR Services`
- Click: `Edit` button
- Section: `Required Fields`

### Step 3: JSON Paste করুন
- উপরের JSON copy করুন
- Field form এ paste করুন
- Save করুন

### Step 4: Test করুন
- Test call করুন
- AI question শুনুন
- সঠিক হচ্ছে কিনা check করুন

---

## 💡 Tips:

1. **শুরুতে সহজ fields দিয়ে শুরু করুন:**
   - customer_name
   - problem_description

2. **একসাথে অনেক fields add করবেন না:**
   - প্রথমে 2-3 টা field test করুন
   - ঠিক হলে বাকিগুলো add করুন

3. **max_retries সঠিক রাখুন:**
   - Important field = 3
   - Optional field = 1 বা 2

4. **Short questions ব্যবহার করুন:**
   - ✅ "নামটা?"
   - ❌ "আপনার পুরো নাম কি বলতে পারবেন?"

---

# 🎯 CONVERSATION FLOW RULES

## ✅ CORRECT Pattern:

```
AI: "Sir, আপনার নামটা?"
Customer: "করিম"
AI: "ধন্যবাদ sir, করিম ঠিক আছে।"

AI: "Sir, আপনার ঠিকানাটা?"
Customer: "মিরপুর, ঢাকা"
AI: "ধন্যবাদ sir, মিরপুর, ঢাকা।"

AI: "Sir, কোন পণ্যের সমস্যা?"
Customer: "ফ্রিজ"
AI: "আচ্ছা, কোন মডেলের ফ্রিজ?"
```

## ❌ FORBIDDEN Patterns:

```
❌ "Sir, আপনার নাম এবং ঠিকানা দিবেন" (দুটো একসাথে)
❌ "Sir, mobile number এবং alternate number দিবেন" (দুটো একসাথে)
❌ "যদি কোনো কারণে আমরা পাওয়া না যায়..." (লম্বা explanation)
```

---

# 🚀 AI BEHAVIOR SUMMARY

## 1. Sequential Collection:
- একটা field → confirm → পরেরটা
- কখনো একসাথে দুটো field না

## 2. Short & Direct:
- সংক্ষিপ্ত প্রশ্ন করবে
- লম্বা ব্যাখ্যা নয়

## 3. Smart Auto-Fill:
- Address থেকে District
- Product name থেকে Brand
- Caller number = Primary mobile

## 4. Retry Logic:
- 1st try: Short question
- 2nd try: Long question (if no answer)
- 3rd: Skip করবে

## 5. Response Style:
- ✅ "ঠিক আছে sir।" (সংক্ষিপ্ত)
- ❌ "ঠিক আছে sir, কোনো সমস্যা নেই..." (লম্বা)

---

# 💡 USAGE INSTRUCTIONS

## কিভাবে Use করবেন:

1. **IVR Dashboard থেকে:**
   - Required Fields section এ যান
   - উপরের JSON format copy করুন
   - আপনার field এর জন্য customize করুন
   - Save করুন

2. **Prompt এ সরাসরি:**
   - উপরের short/long questions copy করুন
   - আপনার field এর জন্য modify করুন
   - AI instruction এ paste করুন

3. **Testing:**
   - প্রতিটা field আলাদাভাবে test করুন
   - Short question আগে, long পরে
   - Retry logic check করুন

---

# 📊 FIELD PRIORITY ORDER

## Recommended Sequence:

1. customer_name (নাম)
2. mobile_number (caller number auto)
3. alt_mobile_number (alternate)
4. address (ঠিকানা)
5. district (জেলা - auto if in address)
6. product_name (পণ্য)
7. brand (ব্র্যান্ড - auto if in product)
8. barcode (বারকোড)
9. problem_description (সমস্যা)
10. service_center (সেন্টার)
11. comments (মন্তব্য)

---

এই template use করে সহজেই field configure করতে পারবেন! 🎯
