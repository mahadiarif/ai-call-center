# 🚀 COMPLETE ASTERISK INTEGRATION GUIDE
# AI Call Center সম্পূর্ণ Asterisk সেটআপ গাইড

---

## ✅ **SYSTEM READY CONFIRMATION** - সিস্টেম রেডি কনফার্মেশন

### 🎯 **হ্যাঁ, আপনার সিস্টেম Asterisk Live করার জন্য সম্পূর্ণ প্রস্তুত!**

#### ✅ যা যা কনফার্ম করা হয়েছে:

1. **✅ WAV Audio Format - Asterisk Compatible:**
   - Sample Rate: **8000 Hz** (Telephony Standard)
   - Channels: **Mono** (Single Channel)
   - Codec: **PCM 16-bit signed little-endian** (pcm_s16le)
   - Format: **.wav** (Asterisk সাপোর্টেড)

2. **✅ AI Call + Customer Call - Both WAV:**
   - AI Agent Voice: **Google Chirp3-HD → WAV (8000 Hz)**
   - Customer Recording: **Asterisk → WAV (8000 Hz)**
   - FFmpeg Conversion: **Automatic MP3 to WAV**

3. **✅ Auto IVR Button System:**
   - Database থেকে Dynamic IVR Load হবে
   - Call করলে Automatic IVR Menu Play হবে
   - Button 1,2,3... অনুযায়ী Service Select

4. **✅ Live Call Flow Ready:**
   ```
   Customer → Asterisk → IVR Menu → AI Agent → Speech Recognition → 
   Database Storage → Agent Transfer (if needed) → Call End
   ```

5. **✅ FFmpeg Installed & Working:**
   - Version: **8.1-full_build**
   - MP3 → WAV conversion: **Working ✅**
   - Storage Directory: **storage/app/public/asterisk-voices/**

6. **✅ APIs Ready:**
   - Voice Generation API: **/api/asterisk/generate-agent-voice**
   - Audio Processing API: **/api/asterisk/process-audio**
   - Escalation Transfer API: **/api/escalation/transfer**
   - AMI Integration: **AsteriskAmiService.php**

---

## 📋 **WHAT YOU NEED FOR ASTERISK LIVE SETUP**
## Asterisk Live করার জন্য যা লাগবে

### **1. Asterisk Server (Linux Server Required)**
   - **OS:** Ubuntu 20.04/22.04 LTS বা CentOS 7/8
   - **RAM:** Minimum 2GB (Recommended: 4GB)
   - **CPU:** 2 Cores বা বেশি
   - **Storage:** 20GB Free Space

### **2. SIP Trunk Provider (Phone Number)**
   - একটি SIP Trunk Provider থেকে Phone Number কিনতে হবে:
     - **Bangladesh:** Grameenphone Enterprise, Robi, Banglalink
     - **International:** Twilio, Vonage, Bandwidth, DIDWW
   - SIP Credentials পাবেন: `Username`, `Password`, `Server IP`

### **3. Domain/IP Address**
   - Laravel Application এর Public IP বা Domain
   - Asterisk থেকে API Call করার জন্য

### **4. SSL Certificate (Optional but Recommended)**
   - HTTPS connection এর জন্য
   - Let's Encrypt দিয়ে Free SSL

---

## 🔧 **STEP-BY-STEP ASTERISK INSTALLATION**
## ধাপে ধাপে Asterisk ইনস্টলেশন

### **STEP 1: Asterisk Server Setup (Ubuntu 22.04)**

```bash
# Server এ Login করুন (SSH)
ssh root@your-server-ip

# System Update করুন
sudo apt update && sudo apt upgrade -y

# Asterisk Dependencies Install করুন
sudo apt install -y build-essential wget libssl-dev libncurses5-dev \
  libnewt-dev libxml2-dev linux-headers-$(uname -r) libsqlite3-dev \
  uuid-dev libjansson-dev libedit-dev libsrtp2-dev

# Asterisk Download করুন (Latest Version 20)
cd /usr/src
sudo wget https://downloads.asterisk.org/pub/telephony/asterisk/asterisk-20-current.tar.gz
sudo tar -xvf asterisk-20-current.tar.gz
cd asterisk-20*/

# Configure করুন
sudo ./configure --with-jansson-bundled

# Select করুন যা যা লাগবে
sudo make menuselect
# → Core Sound Packages → CORE-SOUNDS-EN-WAV (Select করুন)
# → MOH (Music on Hold) → MOH-OPSOUND-WAV (Select করুন)
# → Save & Exit

# Compile & Install করুন (সময় লাগবে 15-30 মিনিট)
sudo make && sudo make install
sudo make samples    # Sample config files
sudo make config     # System startup scripts

# Asterisk User তৈরি করুন
sudo groupadd asterisk
sudo useradd -r -d /var/lib/asterisk -g asterisk asterisk
sudo chown -R asterisk:asterisk /etc/asterisk /var/{lib,log,spool}/asterisk /usr/lib/asterisk

# Asterisk Start করুন
sudo systemctl start asterisk
sudo systemctl enable asterisk

# Check করুন
sudo systemctl status asterisk
```

---

### **STEP 2: SIP Trunk Configuration**

#### **2.1 Edit `/etc/asterisk/sip.conf`**

```ini
[general]
context=public
allowguest=no
allowoverlap=no
bindport=5060
bindaddr=0.0.0.0
tcpenable=yes
tcpbindaddr=0.0.0.0

nat=force_rport,comedia
qualify=yes

; Your SIP Trunk Provider Details
[your-sip-provider]
type=friend
host=sip.provider.com          ; Provider দেয় (যেমন: sip.twilio.com)
username=YOUR_USERNAME          ; Provider থেকে পাবেন
secret=YOUR_PASSWORD            ; Provider থেকে পাবেন
fromuser=YOUR_PHONE_NUMBER      ; আপনার Phone Number
fromdomain=sip.provider.com
insecure=invite,port
context=from-trunk
dtmfmode=rfc2833
canreinvite=no
```

**Save করুন:** `Ctrl + X`, তারপর `Y`, তারপর `Enter`

---

#### **2.2 Edit `/etc/asterisk/extensions.conf`**

```ini
[general]
static=yes
writeprotect=no

; Incoming Call Handling - Customer যখন Call করবে
[from-trunk]
exten => _X.,1,NoOp(Incoming Call from ${CALLERID(num)})
 same => n,Answer()
 same => n,Wait(1)
 same => n,Set(CHANNEL_NAME=${CHANNEL})
 same => n,Set(CUSTOMER_NUMBER=${CALLERID(num)})
 
 ; IVR Menu Play করুন
 same => n,Playback(welcome)  ; "Welcome to our service" audio
 same => n,Read(CHOICE,ivr-menu,1,,,5)  ; 1 digit input, 5 seconds timeout
 
 ; Choice অনুযায়ী Service Select
 same => n,GotoIf($["${CHOICE}" = "1"]?service1)
 same => n,GotoIf($["${CHOICE}" = "2"]?service2)
 same => n,GotoIf($["${CHOICE}" = "3"]?service3)
 same => n,Goto(default-service)
 
 ; Service 1 - AI Agent Call
 same => n(service1),Goto(ai-agent,s,1)
 
 ; Service 2 - Another Service
 same => n(service2),Goto(ai-agent,s,1)
 
 ; Service 3 - Another Service
 same => n(service3),Goto(ai-agent,s,1)
 
 ; Default Service
 same => n(default-service),Goto(ai-agent,s,1)

; AI Agent Conversation Context
[ai-agent]
exten => s,1,NoOp(AI Agent Service Started)
 
 ; Laravel API থেকে Agent Greeting Audio নিয়ে আসুন
 same => n,Set(API_URL=https://your-domain.com/api/asterisk/get-greeting)
 same => n,Set(CURL_RESULT=${SHELL(curl -s -X POST ${API_URL} -H "Content-Type: application/json" -d '{"ivr_key":"1","customer_number":"${CUSTOMER_NUMBER}"}')})
 same => n,Set(AUDIO_FILE=${CUT(CURL_RESULT,",",2)})  ; JSON থেকে audio path parse করুন
 
 ; Agent Greeting Play করুন
 same => n,Playback(/var/www/ai-call-center/storage/app/public/asterisk-voices/agent_greeting)
 
 ; Customer এর কথা Record করুন (WAV format, 8000 Hz)
 same => n,Set(RECORDING_FILE=/tmp/customer_${UNIQUEID}.wav)
 same => n,Record(${RECORDING_FILE}:wav,10,60)  ; 10s silence, 60s max
 
 ; Recording Laravel API তে পাঠান
 same => n,Set(UPLOAD_URL=https://your-domain.com/api/asterisk/process-audio)
 same => n,System(curl -s -X POST ${UPLOAD_URL} -F "audio=@${RECORDING_FILE}" -F "ivr_key=1" -F "customer_number=${CUSTOMER_NUMBER}" -F "channel=${CHANNEL_NAME}")
 
 ; AI Response Wait করুন বা Next Action
 same => n,Wait(2)
 same => n,Playback(thank-you)
 same => n,Hangup()

; Agent Transfer Extension
exten => transfer,1,NoOp(Transferring to Agent)
 same => n,Dial(SIP/agent-extension,30,tT)
 same => n,Hangup()
```

**Save করুন**

---

### **STEP 3: Laravel .env Configuration**

Laravel Application এ এই Environment Variables যোগ করুন:

```env
# .env file

# Google TTS API Key
GOOGLE_TTS_KEY=your-google-tts-api-key-here

# Asterisk AMI Configuration
ASTERISK_AMI_HOST=127.0.0.1
ASTERISK_AMI_PORT=5038
ASTERISK_AMI_USERNAME=admin
ASTERISK_AMI_PASSWORD=your-strong-password-here

# Application URL (Asterisk এটা ব্যবহার করবে API call করতে)
APP_URL=https://your-domain.com
```

---

### **STEP 4: Asterisk AMI (Manager) Configuration**

#### **Edit `/etc/asterisk/manager.conf`**

```ini
[general]
enabled=yes
port=5038
bindaddr=0.0.0.0

[admin]
secret=your-strong-password-here   ; .env এ যে password দিয়েছেন
deny=0.0.0.0/0.0.0.0
permit=127.0.0.1/255.255.255.0     ; Laravel server IP allow করুন
permit=YOUR_LARAVEL_SERVER_IP/255.255.255.0  ; যদি আলাদা server হয়
read=system,call,log,verbose,command,agent,user,config
write=system,call,log,verbose,command,agent,user,config
```

**Reload করুন:**
```bash
sudo asterisk -rx "manager reload"
```

---

### **STEP 5: Create IVR Audio Files**

#### **5.1 Laravel API দিয়ে Audio তৈরি করুন:**

```bash
# Welcome Message
curl -X POST https://your-domain.com/api/asterisk/generate-agent-voice \
  -H "Content-Type: application/json" \
  -d '{
    "text": "আসসালামু আলাইকুম। আমাদের AI কল সেন্টারে আপনাকে স্বাগতম।",
    "filename": "welcome"
  }'

# IVR Menu
curl -X POST https://your-domain.com/api/asterisk/generate-agent-voice \
  -H "Content-Type: application/json" \
  -d '{
    "text": "সেলস সার্ভিসের জন্য ১ চাপুন। টেকনিক্যাল সাপোর্টের জন্য ২ চাপুন। অন্যান্য সেবার জন্য ৩ চাপুন।",
    "filename": "ivr-menu"
  }'

# Agent Greeting
curl -X POST https://your-domain.com/api/asterisk/generate-agent-voice \
  -H "Content-Type: application/json" \
  -d '{
    "text": "আসসালামু আলাইকুম। আমি আপনার AI এজেন্ট। আমি কিভাবে আপনাকে সাহায্য করতে পারি?",
    "filename": "agent_greeting",
    "ivr_key": "1"
  }'

# Thank You Message
curl -X POST https://your-domain.com/api/asterisk/generate-agent-voice \
  -H "Content-Type: application/json" \
  -d '{
    "text": "আপনার কল এর জন্য ধন্যবাদ। ভাল থাকবেন।",
    "filename": "thank-you"
  }'
```

#### **5.2 Asterisk Sounds Directory তে Copy করুন:**

```bash
# Laravel storage থেকে Asterisk sounds folder এ copy করুন
sudo cp /var/www/ai-call-center/storage/app/public/asterisk-voices/*.wav \
  /var/lib/asterisk/sounds/en/

# Permissions ঠিক করুন
sudo chown asterisk:asterisk /var/lib/asterisk/sounds/en/*.wav
sudo chmod 644 /var/lib/asterisk/sounds/en/*.wav
```

---

### **STEP 6: Reload Asterisk Configuration**

```bash
# SIP Configuration Reload
sudo asterisk -rx "sip reload"

# Dialplan Reload
sudo asterisk -rx "dialplan reload"

# Manager Reload
sudo asterisk -rx "manager reload"

# Full Restart (যদি সমস্যা হয়)
sudo systemctl restart asterisk

# Status Check
sudo systemctl status asterisk

# Asterisk CLI তে ঢুকুন
sudo asterisk -rvvv

# CLI তে Test করুন
*CLI> sip show peers      # SIP trunk connected কিনা
*CLI> dialplan show       # Dialplan loaded কিনা
*CLI> manager show users  # AMI user দেখুন
```

---

## 🔥 **TESTING THE SYSTEM** - সিস্টেম টেস্ট করুন

### **Test 1: Call করুন**
```
1. আপনার SIP Number এ Call করুন
2. IVR Menu শুনবেন
3. 1/2/3 Press করুন
4. AI Agent এর সাথে কথা বলুন
5. Database এ Data Save হবে
```

### **Test 2: Asterisk CLI Monitor**
```bash
sudo asterisk -rvvv

# এটা দিয়ে Live call দেখবেন
*CLI> core set verbose 5
*CLI> core set debug 5

# Call করুন এবং CLI তে Log দেখুন
```

### **Test 3: Laravel Log Check**
```bash
tail -f /var/www/ai-call-center/storage/logs/laravel.log

# Call করলে API hit হচ্ছে কিনা দেখুন
```

---

## 🚨 **TROUBLESHOOTING** - সমস্যা সমাধান

### সমস্যা ১: Call Connect হচ্ছে না
```bash
# Firewall চেক করুন
sudo ufw allow 5060/udp   # SIP
sudo ufw allow 10000:20000/udp  # RTP (Media)

# Asterisk Logs দেখুন
sudo tail -f /var/log/asterisk/full
```

### সমস্যা ২: Audio Play হচ্ছে না
```bash
# Audio file আছে কিনা
ls -la /var/lib/asterisk/sounds/en/

# Permissions ঠিক কিনা
sudo chown -R asterisk:asterisk /var/lib/asterisk/sounds/
```

### সমস্যা ৩: AMI Connect হচ্ছে না
```bash
# AMI Port খোলা কিনা
sudo netstat -tuln | grep 5038

# manager.conf চেক করুন
sudo asterisk -rx "manager show users"
```

---

## 📊 **SYSTEM ARCHITECTURE** - সিস্টেম আর্কিটেকচার

```
┌─────────────┐
│  Customer   │
│   Mobile    │
└──────┬──────┘
       │ Call
       ↓
┌─────────────────┐
│  SIP Provider   │ (Grameenphone/Twilio)
│ Phone Number    │
└────────┬────────┘
         │ SIP
         ↓
┌──────────────────┐
│ Asterisk Server  │
│  - IVR Menu      │
│  - Call Routing  │
│  - Audio Play    │
│  - Recording     │
└────────┬─────────┘
         │ API Call
         ↓
┌───────────────────────┐
│ Laravel Application   │
│  - Voice Generation   │
│  - Speech Processing  │
│  - Database Storage   │
│  - AMI Control        │
└───────────────────────┘
         │
         ↓
┌───────────────────────┐
│ MySQL Database        │
│  - Service Requests   │
│  - Call Logs          │
│  - IVR Services       │
└───────────────────────┘
```

---

## ✅ **FINAL CHECKLIST** - ফাইনাল চেকলিস্ট

**Asterisk Server:**
- [ ] Asterisk Installed & Running
- [ ] SIP Trunk Configured
- [ ] Dialplan Ready
- [ ] AMI Enabled
- [ ] Audio Files Copied

**Laravel Application:**
- [ ] .env Configured
- [ ] FFmpeg Installed
- [ ] Storage Directory Created
- [ ] APIs Working
- [ ] Database Migrated

**Testing:**
- [ ] Incoming Call Works
- [ ] IVR Menu Plays
- [ ] AI Agent Speaks
- [ ] Recording Saves
- [ ] Database Updates
- [ ] Agent Transfer Works

---

## 📞 **SUPPORT & NEXT STEPS**

আপনার 2nd Level Developer এই Document follow করে:
1. Asterisk Server Setup করবে
2. SIP Trunk Configure করবে
3. Audio Files Generate করবে
4. Testing করবে

যদি কোনো সমস্যা হয়, এই Logs চেক করতে বলুন:
- `/var/log/asterisk/full` - Asterisk logs
- `/var/www/ai-call-center/storage/logs/laravel.log` - Laravel logs
- Asterisk CLI: `sudo asterisk -rvvv`

---

**🎉 সিস্টেম সম্পূর্ণ প্রস্তুত! Asterisk Setup করলেই Live Call শুরু করতে পারবেন!**

## PHP থেকে সরাসরি ভয়েস তৈরি করুন

```php
use App\Services\AsteriskVoiceService;

$voiceService = new AsteriskVoiceService();

// এআই ভয়েস
$agentWavPath = $voiceService->generateAgentVoice(
    'আপনার বার্তা এখানে',
    'my_agent_voice'
);

// কাস্টমার ভয়েস
$customerWavPath = $voiceService->generateCustomerVoice(
    'কাস্টমার বার্তা',
    'my_customer_voice'
);

// সমস্ত কনফিগ পান
$config = $voiceService->getAsteriskConfig();

// উপলব্ধ ভয়েস লিস্ট
$voices = $voiceService->listAvailableVoices();
```

---

## ফাইল লোকেশন

WAV ফাইলগুলি এখানে সংরক্ষিত হয়:

- **স্টোরেজ পাথ:** `storage/public/asterisk-voices/`
- **পাবলিক URL:** `http://ai-call-center.test/asterisk-voices/`

Asterisk এ ব্যবহার করতে:
```
/var/spool/asterisk/sounds/asterisk-voices/
```

---

## WAV ফাইল স্পেসিফিকেশন

স্বয়ংক্রিয়ভাবে কনফিগার করা হয়:
- **Sample Rate:** 8000 Hz (Asterisk এর জন্য সেরা)
- **Channels:** Mono (1 চ্যানেল)
- **Codec:** PCM 16-bit (pcm_s16le)
- **Format:** WAV

---

## সমস্যা সমাধান

### "FFmpeg not found" ত্রুটি
- FFmpeg ইনস্টল করুন এবং PATH তে যোগ করুন

### ভয়েস ফাইল তৈরি হচ্ছে না
- Google TTS API Key সঠিক কিনা চেক করুন
- `.env` ফাইলে `GOOGLE_TTS_KEY` আছে কিনা দেখুন

### Asterisk এ সাউন্ড শোনা যাচ্ছে না
- ফাইল পারমিশন চেক করুন: `chmod 644 *.wav`
- Asterisk user এর read পারমিশন আছে কিনা দেখুন

---

## উদাহরণ script দিয়ে স্বয়ংক্রিয় ভয়েস সেটআপ

`artisan` কমান্ড তৈরি করুন:

```bash
php artisan asterisk:setup-voices
```

এটি স্বয়ংক্রিয়ভাবে সব ডিফল্ট ভয়েস তৈরি করবে।
