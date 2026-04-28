# IVR Dashboard - Field Setup Guide
## ✅ আপনার Dashboard Form অনুযায়ী সঠিক Format

---

## 📝 Form Fields যেভাবে Fill করবেন:

### 🔹 Example: Mobile Number (Alternate) Setup

---

#### 1️⃣ **ফিল্ডের নাম** (Field Name)
```
alt_mobile_number
```
*(English এ, lowercase, underscore ব্যবহার করুন)*

---

#### 2️⃣ **ডিসপ্লে নাম** (Display Label) - যদি থাকে
```
বিকল্প মোবাইল নম্বর
```
বা
```
Alternate Mobile Number
```

---

#### 3️⃣ **AI জিজ্ঞাসা করবে** (AI Question/Instruction)
```
Sir, আপনার এই number ছাড়া আরও কোনো number আছে?
```

---

#### 4️⃣ **পরোক্ষ প্রশ্ন/বিকল্প** (Indirect Question) - যদি থাকে
```
Sir, এই number ছাড়া কি আরেকটা alternate number দিতে পারবেন?
```

---

#### 5️⃣ **বোঝানোর লজিক** (Convincing Logic) - যদি suggestion চান
```
Sir, যদি এই number এ পাওয়া না যায়, alternate number এ যোগাযোগ করব।
```
*(Optional - ছোট রাখুন)*

---

#### 6️⃣ **Toggle Options:**

##### ✅ তথ্য জরুরি (Is Mandatory)
- **চালু করুন (ON):** যদি এই field অবশ্যই লাগবে
- **বন্ধ রাখুন (OFF):** যদি optional

**Alternate number এর জন্য:** ❌ OFF (optional)

---

##### ✅ অন্তর্ভুক্ত যাচাইকরণ (Needs Confirmation)
- **চালু করুন (ON):** AI confirm করবে
- **বন্ধ রাখুন (OFF):** confirm ছাড়া next field

**Alternate number এর জন্য:** ✅ ON (confirm করবে)

---

#### 7️⃣ **ডাটা যাচাইয়াতি ও Sample (AI validation এর জন্য)**

এই section expand করুন এবং fill করুন:

##### **Minimum Length:**
```
11
```
*(Bangladesh mobile: 11 digit)*

##### **Maximum Length:**
```
11
```

##### **Expected Format:**
```
01XXXXXXXXX (Bangladesh mobile number)
```

##### **Sample Values** (একাধিক লাইনে):
```
01712345678
01812345678
01912345678
01512345678
```

##### **Custom Q&A** - যদি customer প্রশ্ন করে:
*(Optional - খালি রাখতে পারেন বা add করুন)*

**প্রশ্ন:**
```
Alternate number কেন লাগবে?
```
**উত্তর:**
```
Sir, যদি primary number এ পাওয়া না যায়, alternate এ যোগাযোগ করতে পারব।
```

---

#### 8️⃣ **Advanced Options (যদি থাকে)**

এই section expand করলে দেখতে পারবেন:

##### **Max Retries (কতবার চেষ্টা):**
```
2
```
*(2 বার চেষ্টা করবে, তারপর skip)*

##### **Is Blocking:**
- **চালু করুন (ON):** না দিলে এগোবে না
- **বন্ধ রাখুন (OFF):** skip করে যাবে

**Alternate number এর জন্য:** ❌ OFF (skip করবে)

##### **Depends on Field:**
*(খালি রাখুন - কোনো dependency নেই)*

---

#### 9️⃣ **FAQ Section (যদি থাকে)**

এখানে customer এর common questions add করতে পারেন।

---

---

# 📋 সব Field এর Quick Reference

## 1️⃣ CUSTOMER NAME

| Field | Value |
|-------|-------|
| ফিল্ডের নাম | `customer_name` |
| AI প্রশ্ন | `Sir, আপনার নামটা?` |
| পরোক্ষ প্রশ্ন | `Sir, আপনার পুরো নামটা একবার বলবেন?` |
| বোঝানোর লজিক | `Sir, নামটা থাকলে পরে যোগাযোগ করতে সুবিধা হয়।` |
| তথ্য জরুরি | ✅ ON |
| যাচাইকরণ | ✅ ON |
| Min Length | `2` |
| Max Length | `100` |
| Expected Format | `Text (বাংলা/English)` |
| Sample Values | `করিম`<br>`রহিম`<br>`Fatima` |
| Max Retries | `2` |
| Is Blocking | ❌ OFF |

---

## 2️⃣ ALTERNATE MOBILE NUMBER

| Field | Value |
|-------|-------|
| ফিল্ডের নাম | `alt_mobile_number` |
| AI প্রশ্ন | `Sir, আপনার এই number ছাড়া আরও কোনো number আছে?` |
| পরোক্ষ প্রশ্ন | `Sir, এই number ছাড়া কি আরেকটা alternate number দিতে পারবেন?` |
| বোঝানোর লজিক | *(খালি রাখুন বা short রাখুন)* |
| তথ্য জরুরি | ❌ OFF |
| যাচাইকরণ | ✅ ON |
| Min Length | `11` |
| Max Length | `11` |
| Expected Format | `01XXXXXXXXX` |
| Sample Values | `01712345678`<br>`01812345678` |
| Max Retries | `2` |
| Is Blocking | ❌ OFF |

---

## 3️⃣ ADDRESS

| Field | Value |
|-------|-------|
| ফিল্ডের নাম | `address` |
| AI প্রশ্ন | `Sir, আপনার ঠিকানাটা?` |
| পরোক্ষ প্রশ্ন | `Sir, আপনার সম্পূর্ণ ঠিকানাটা বলবেন?` |
| বোঝানোর লজিক | `Sir, ঠিকানা থাকলে service দিতে সুবিধা হবে।` |
| তথ্য জরুরি | ❌ OFF |
| যাচাইকরণ | ✅ ON |
| Min Length | `10` |
| Max Length | `500` |
| Expected Format | `Full address with area/district` |
| Sample Values | `মিরপুর-১, ঢাকা`<br>`উত্তরা, সেক্টর ৩` |
| Max Retries | `2` |
| Is Blocking | ❌ OFF |

---

## 4️⃣ DISTRICT

| Field | Value |
|-------|-------|
| ফিল্ডের নাম | `district` |
| AI প্রশ্ন | `Sir, কোন জেলা?` |
| পরোক্ষ প্রশ্ন | `Sir, আপনি কোন জেলায় থাকেন?` |
| তথ্য জরুরি | ❌ OFF |
| যাচাইকরণ | ✅ ON |
| Expected Format | `Bangladesh district name` |
| Sample Values | `ঢাকা`<br>`চট্টগ্রাম`<br>`সিলেট` |
| Max Retries | `1` |
| Is Blocking | ❌ OFF |
| **Special Note** | Address এ district থাকলে জিজ্ঞেস করবে না |

---

## 5️⃣ PRODUCT NAME

| Field | Value |
|-------|-------|
| ফিল্ডের নাম | `product_name` |
| AI প্রশ্ন | `Sir, কোন পণ্যের সমস্যা?` |
| পরোক্ষ প্রশ্ন | `Sir, আপনার কোন পণ্যে সমস্যা হচ্ছে?` |
| তথ্য জরুরি | ✅ ON |
| যাচাইকরণ | ✅ ON |
| Min Length | `2` |
| Max Length | `200` |
| Expected Format | `Product name/model` |
| Sample Values | `ফ্রিজ`<br>`WFE-2B4-RXXX`<br>`AC` |
| Max Retries | `3` |
| Is Blocking | ✅ ON (must have) |

---

## 6️⃣ BARCODE / SERIAL NUMBER

| Field | Value |
|-------|-------|
| ফিল্ডের নাম | `barcode` |
| AI প্রশ্ন | `Sir, বারকোড নম্বরটা আছে?` |
| পরোক্ষ প্রশ্ন | `Sir, পণ্যের বারকোড বা সিরিয়াল নম্বর দেখতে পাচ্ছেন?` |
| বোঝানোর লজিক | `Sir, বারকোড থাকলে সার্ভিস দিতে সহজ হয়।` |
| তথ্য জরুরি | ❌ OFF |
| যাচাইকরণ | ✅ ON |
| Min Length | `4` |
| Max Length | `50` |
| Expected Format | `Alphanumeric barcode/serial` |
| Sample Values | `WFE123456789`<br>`SN-2024-001` |
| Max Retries | `2` |
| Is Blocking | ❌ OFF |

---

## 7️⃣ PROBLEM DESCRIPTION

| Field | Value |
|-------|-------|
| ফিল্ডের নাম | `problem_description` |
| AI প্রশ্ন | `Sir, কী সমস্যা হচ্ছে?` |
| পরোক্ষ প্রশ্ন | `Sir, ঠিক কোন সমস্যাটা হচ্ছে বলবেন?` |
| তথ্য জরুরি | ✅ ON |
| যাচাইকরণ | ✅ ON |
| Min Length | `5` |
| Max Length | `1000` |
| Expected Format | `Description of problem` |
| Sample Values | `ফ্রিজ ঠান্ডা হয় না`<br>`এসি চলে না` |
| Max Retries | `3` |
| Is Blocking | ✅ ON (must have) |

---

## 8️⃣ SERVICE CENTER

| Field | Value |
|-------|-------|
| ফিল্ডের নাম | `service_center` |
| AI প্রশ্ন | `Sir, কোন সেন্টার থেকে কিনেছিলেন?` |
| পরোক্ষ প্রশ্ন | `Sir, আপনি কোন সার্ভিস সেন্টার থেকে পণ্যটি কিনেছিলেন বলতে পারবেন?` |
| তথ্য জরুরি | ❌ OFF |
| যাচাইকরণ | ✅ ON |
| Expected Format | `Service center name/location` |
| Sample Values | `মিরপুর সেন্টার`<br>`উত্তরা শাখা` |
| Max Retries | `1` |
| Is Blocking | ❌ OFF |
| **Special Responses** | মনে না থাকলে: "কোনো সমস্যা নেই sir। আমরা ব্যবস্থা নিচ্ছি।" |

---

## 9️⃣ BRAND

| Field | Value |
|-------|-------|
| ফিল্ডের নাম | `brand` |
| AI প্রশ্ন | `Sir, কোন ব্র্যান্ডের?` |
| পরোক্ষ প্রশ্ন | `Sir, পণ্যটি কোন ব্র্যান্ডের?` |
| তথ্য জরুরি | ❌ OFF |
| যাচাইকরণ | ✅ ON |
| Expected Format | `Brand name` |
| Sample Values | `WALTON`<br>`Singer`<br>`Samsung` |
| Max Retries | `1` |
| Is Blocking | ❌ OFF |
| **Special Note** | Product name এ brand থাকলে skip করবে |

---

## 🔟 COMMENTS

| Field | Value |
|-------|-------|
| ফিল্ডের নাম | `comments` |
| AI প্রশ্ন | `Sir, আর কিছু বলার আছে?` |
| পরোক্ষ প্রশ্ন | `Sir, আর কোনো বিশেষ কিছু জানানোর আছে?` |
| তথ্য জরুরি | ❌ OFF |
| যাচাইকরণ | ❌ OFF |
| Max Retries | `1` |
| Is Blocking | ❌ OFF |

---

---

# 🎯 Step-by-Step Dashboard Setup

## একটা Field Add করার পূর্ণ Process:

### Step 1: Field Section এ যান
- IVR Services → Edit → নিচে scroll করুন
- "Required Fields" বা similar section খুঁজুন
- "Add New Field" বা "+" button click করুন

### Step 2: Basic Information Fill করুন
```
ফিল্ডের নাম: alt_mobile_number
AI প্রশ্ন: Sir, আপনার এই number ছাড়া আরও কোনো number আছে?
```

### Step 3: Toggle Options Set করুন
- তথ্য জরুরি: OFF
- যাচাইকরণ: ON

### Step 4: Validation Section Expand করুন
```
Min Length: 11
Max Length: 11
Expected Format: 01XXXXXXXXX
Sample Values: 
  01712345678
  01812345678
```

### Step 5: Advanced Options (যদি থাকে)
```
Max Retries: 2
Is Blocking: OFF
```

### Step 6: Save করুন
- "Save" বা "Submit" button click করুন
- Success message দেখুন

### Step 7: Test করুন
- Test call করুন
- AI question শুনুন
- Field collect হচ্ছে কিনা check করুন

---

# 💡 Important Tips

## ✅ DO's:
1. **Short questions ব্যবহার করুন**
   - ✅ "নামটা?"
   - ❌ "আপনার পুরো নাম কি বলতে পারবেন দয়া করে?"

2. **Sample values অবশ্যই দিন**
   - AI validation এর জন্য জরুরি
   - Real example দিন

3. **Max retries সঠিক রাখুন**
   - Important field = 2-3
   - Optional = 1

4. **Blocking শুধু জরুরি field এ**
   - product_name, problem_description = ON
   - বাকি সব = OFF

## ❌ DON'Ts:
1. লম্বা convincing logic লিখবেন না
2. একসাথে অনেক field add করবেন না (প্রথমে 2-3 টা)
3. সব field mandatory করবেন না
4. Sample values ছাড়া validation দিবেন না

---

# 🚀 Priority Order (কোনটা আগে add করবেন)

1. **customer_name** - নাম (mandatory)
2. **product_name** - পণ্য (mandatory + blocking)
3. **problem_description** - সমস্যা (mandatory + blocking)
4. **alt_mobile_number** - alternate number (optional)
5. **address** - ঠিকানা (optional)
6. **barcode** - বারকোড (optional)
7. বাকি fields

এভাবে একটা একটা করে add করুন এবং test করুন!

---

✅ এখন আপনার Dashboard form এর সাথে পুরোপুরি মিলবে!
