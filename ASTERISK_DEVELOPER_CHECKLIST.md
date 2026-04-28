# 📋 ASTERISK SETUP CHECKLIST - Developer Quick Guide
# Asterisk Setup করার জন্য Quick Checklist

---

## 🎯 **PRE-REQUISITES (যা আগে থেকেই লাগবে)**

### 1. Server Requirements
- [ ] Linux Server (Ubuntu 20.04/22.04 বা CentOS 7/8)
- [ ] Minimum 2GB RAM (Recommended: 4GB)
- [ ] 2 CPU Cores
- [ ] 20GB Storage
- [ ] Root/Sudo Access

### 2. Network Requirements
- [ ] Static IP Address
- [ ] Open Ports:
  - [ ] Port 5060 (UDP) - SIP Signaling
  - [ ] Port 10000-20000 (UDP) - RTP Media
  - [ ] Port 5038 (TCP) - AMI Manager

### 3. SIP Trunk Provider
- [ ] Phone Number কিনেছেন (Grameenphone/Twilio/Vonage)
- [ ] SIP Credentials আছে:
  - [ ] Username
  - [ ] Password
  - [ ] Server IP/Domain
  - [ ] Phone Number

### 4. Laravel Application
- [ ] Laravel Running (Production Server এ)
- [ ] Public Domain/IP আছে
- [ ] HTTPS SSL Certificate (Optional)

---

## 🚀 **INSTALLATION STEPS (ইনস্টলেশন স্টেপ)**

### Step 1: Server Setup ✅
```bash
# Commands to Run:
sudo apt update && sudo apt upgrade -y
sudo apt install -y build-essential wget libssl-dev libncurses5-dev
cd /usr/src
sudo wget https://downloads.asterisk.org/pub/telephony/asterisk/asterisk-20-current.tar.gz
sudo tar -xvf asterisk-20-current.tar.gz
cd asterisk-20*/
sudo ./configure --with-jansson-bundled
sudo make menuselect  # Select CORE-SOUNDS-EN-WAV
sudo make && sudo make install
sudo make samples
sudo make config
```

- [ ] Asterisk Downloaded
- [ ] Dependencies Installed
- [ ] Asterisk Compiled
- [ ] Asterisk Installed
- [ ] Sample Configs Created

### Step 2: Asterisk Service ✅
```bash
sudo groupadd asterisk
sudo useradd -r -d /var/lib/asterisk -g asterisk asterisk
sudo chown -R asterisk:asterisk /etc/asterisk /var/{lib,log,spool}/asterisk /usr/lib/asterisk
sudo systemctl start asterisk
sudo systemctl enable asterisk
```

- [ ] Asterisk User Created
- [ ] Permissions Set
- [ ] Service Started
- [ ] Service Enabled on Boot

### Step 3: SIP Trunk Configuration ✅
**Edit:** `/etc/asterisk/sip.conf`

- [ ] File Opened
- [ ] [your-sip-provider] Section Added
- [ ] Provider Details Filled:
  - [ ] host = ___________________
  - [ ] username = _______________
  - [ ] secret = _________________
  - [ ] fromuser = _______________
- [ ] File Saved

**Reload SIP:**
```bash
sudo asterisk -rx "sip reload"
sudo asterisk -rx "sip show peers"  # Check connection
```

- [ ] SIP Reloaded
- [ ] Trunk Connected (Status: OK)

### Step 4: Dialplan Configuration ✅
**Edit:** `/etc/asterisk/extensions.conf`

- [ ] File Opened
- [ ] [from-trunk] Context Added
- [ ] [ai-agent] Context Added
- [ ] Laravel API URLs Updated:
  - [ ] `API_URL=https://your-domain.com/api/asterisk/get-greeting`
  - [ ] `UPLOAD_URL=https://your-domain.com/api/asterisk/process-audio`
- [ ] File Saved

**Reload Dialplan:**
```bash
sudo asterisk -rx "dialplan reload"
sudo asterisk -rx "dialplan show from-trunk"  # Verify
```

- [ ] Dialplan Reloaded
- [ ] Context Visible

### Step 5: AMI Manager Configuration ✅
**Edit:** `/etc/asterisk/manager.conf`

- [ ] File Opened
- [ ] [admin] User Added
- [ ] Password Set: ___________________
- [ ] Laravel Server IP Permitted: ___________________
- [ ] File Saved

**Reload Manager:**
```bash
sudo asterisk -rx "manager reload"
sudo asterisk -rx "manager show users"
```

- [ ] AMI Reloaded
- [ ] Admin User Visible

### Step 6: Laravel .env Configuration ✅
**Edit:** Laravel `.env` file

```env
GOOGLE_TTS_KEY=_____________________________
ASTERISK_AMI_HOST=127.0.0.1
ASTERISK_AMI_PORT=5038
ASTERISK_AMI_USERNAME=admin
ASTERISK_AMI_PASSWORD=_____________________
APP_URL=https://your-domain.com
```

- [ ] .env File Updated
- [ ] Google TTS Key Added
- [ ] AMI Credentials Added
- [ ] APP_URL Updated

**Clear Laravel Cache:**
```bash
php artisan config:clear
php artisan cache:clear
```

- [ ] Cache Cleared

### Step 7: Generate Audio Files ✅
```bash
# Welcome Message
curl -X POST https://your-domain.com/api/asterisk/generate-agent-voice \
  -H "Content-Type: application/json" \
  -d '{"text":"আসসালামু আলাইকুম। আমাদের AI কল সেন্টারে আপনাকে স্বাগতম।","filename":"welcome"}'

# IVR Menu
curl -X POST https://your-domain.com/api/asterisk/generate-agent-voice \
  -H "Content-Type: application/json" \
  -d '{"text":"সেলস সার্ভিসের জন্য ১ চাপুন। টেকনিক্যাল সাপোর্টের জন্য ২ চাপুন।","filename":"ivr-menu"}'

# Agent Greeting
curl -X POST https://your-domain.com/api/asterisk/generate-agent-voice \
  -H "Content-Type: application/json" \
  -d '{"text":"আসসালামু আলাইকুম। আমি আপনার AI এজেন্ট। কিভাবে সাহায্য করতে পারি?","filename":"agent_greeting"}'

# Thank You
curl -X POST https://your-domain.com/api/asterisk/generate-agent-voice \
  -H "Content-Type: application/json" \
  -d '{"text":"আপনার কল এর জন্য ধন্যবাদ। ভাল থাকবেন।","filename":"thank-you"}'
```

- [ ] welcome.wav Generated
- [ ] ivr-menu.wav Generated
- [ ] agent_greeting.wav Generated
- [ ] thank-you.wav Generated

**Copy to Asterisk Sounds:**
```bash
sudo cp /var/www/ai-call-center/storage/app/public/asterisk-voices/*.wav /var/lib/asterisk/sounds/en/
sudo chown asterisk:asterisk /var/lib/asterisk/sounds/en/*.wav
sudo chmod 644 /var/lib/asterisk/sounds/en/*.wav
```

- [ ] Files Copied
- [ ] Permissions Set
- [ ] Files Verified: `ls -la /var/lib/asterisk/sounds/en/`

### Step 8: Firewall Configuration ✅
```bash
sudo ufw allow 5060/udp    # SIP
sudo ufw allow 10000:20000/udp  # RTP
sudo ufw allow 5038/tcp    # AMI
sudo ufw reload
```

- [ ] Firewall Rules Added
- [ ] Ports Open

---

## 🧪 **TESTING (টেস্টিং)**

### Test 1: Asterisk Service ✅
```bash
sudo systemctl status asterisk
sudo asterisk -rvvv
*CLI> core show version
*CLI> sip show peers
*CLI> manager show users
```

- [ ] Asterisk Running
- [ ] SIP Trunk Connected
- [ ] AMI User Active

### Test 2: SIP Trunk Test Call ✅
```bash
# Asterisk CLI থেকে Test Call করুন
*CLI> console dial XXXXXXXXXX@your-sip-provider
```

- [ ] Test Call Successful
- [ ] Audio Heard

### Test 3: Incoming Call Test ✅
```
1. আপনার Mobile থেকে SIP Number এ Call করুন
2. Welcome Message শুনুন
3. IVR Menu শুনুন (1/2/3 Press করুন)
4. AI Agent এর সাথে কথা বলুন
5. Thank You Message শুনে Call End করুন
```

- [ ] Call Connected
- [ ] Welcome Audio Played
- [ ] IVR Menu Played
- [ ] DTMF (1/2/3) Detected
- [ ] AI Agent Spoke
- [ ] Recording Saved

### Test 4: Database Check ✅
```bash
# Laravel Database চেক করুন
mysql -u root -p ai_call_center
SELECT * FROM service_requests ORDER BY id DESC LIMIT 5;
SELECT * FROM call_logs ORDER BY id DESC LIMIT 5;
```

- [ ] Call Log Saved
- [ ] Service Request Created
- [ ] Customer Data Stored

### Test 5: Laravel API Test ✅
```bash
# Laravel Logs Monitor করুন
tail -f /var/www/ai-call-center/storage/logs/laravel.log

# Call করুন এবং Log দেখুন
```

- [ ] API Hit Logged
- [ ] Voice Generation Working
- [ ] Audio Processing Working

---

## 🚨 **TROUBLESHOOTING (সমস্যা সমাধান)**

### যদি Call Connect না হয়:
```bash
# Asterisk Full Log দেখুন
sudo tail -f /var/log/asterisk/full

# CLI তে Monitor করুন
sudo asterisk -rvvv
*CLI> core set verbose 5
*CLI> core set debug 5
```

**Common Issues:**
- [ ] SIP Trunk Credentials ভুল → sip.conf চেক করুন
- [ ] Firewall Blocked → Ports 5060, 10000-20000 খুলুন
- [ ] NAT Issue → sip.conf এ `nat=force_rport,comedia` add করুন

### যদি Audio Play না হয়:
```bash
# Audio Files আছে কিনা চেক করুন
ls -la /var/lib/asterisk/sounds/en/

# File Format চেক করুন
file /var/lib/asterisk/sounds/en/welcome.wav
# Output: RIFF (little-endian) data, WAVE audio, Microsoft PCM, 16 bit, mono 8000 Hz
```

**Common Issues:**
- [ ] Audio File Missing → আবার Generate করুন
- [ ] Wrong Format → FFmpeg দিয়ে Convert করুন: `ffmpeg -i input.mp3 -ar 8000 -ac 1 output.wav`
- [ ] Permission Issue → `sudo chown asterisk:asterisk *.wav`

### যদি AMI Connect না হয়:
```bash
# AMI Port Listen করছে কিনা
sudo netstat -tuln | grep 5038

# Laravel Server থেকে Test করুন
telnet asterisk-server-ip 5038
```

**Common Issues:**
- [ ] AMI Disabled → manager.conf এ `enabled=yes` করুন
- [ ] Permission Denied → Laravel Server IP permit করুন
- [ ] Wrong Password → .env এ password match করুন

---

## ✅ **FINAL VERIFICATION (ফাইনাল যাচাই)**

### System Health Check:
```bash
sudo asterisk -rx "core show uptime"
sudo asterisk -rx "core show channels"
sudo asterisk -rx "sip show peers"
sudo asterisk -rx "manager show connected"
```

**Expected Output:**
- [ ] Uptime: > 0 hours
- [ ] Channels: 0 active (idle state)
- [ ] SIP Peers: 1 peer OK
- [ ] AMI: 0 connected (idle state)

### Complete Call Flow Test:
1. [ ] Customer Mobile → Call করলো
2. [ ] Asterisk → Call Received
3. [ ] Welcome Audio → Played
4. [ ] IVR Menu → Played
5. [ ] DTMF 1 → Pressed
6. [ ] AI Agent → Spoke
7. [ ] Customer → Spoke (Recorded)
8. [ ] Laravel API → Called
9. [ ] Database → Data Saved
10. [ ] Thank You → Played
11. [ ] Call → Ended

---

## 📞 **CONTACT & SUPPORT**

যদি কোনো সমস্যা হয়:
1. Asterisk Logs: `/var/log/asterisk/full`
2. Laravel Logs: `storage/logs/laravel.log`
3. Asterisk CLI: `sudo asterisk -rvvv`

**Common Commands:**
```bash
# Restart Asterisk
sudo systemctl restart asterisk

# Reload All Configs
sudo asterisk -rx "core reload"

# Check Active Calls
sudo asterisk -rx "core show channels"
```

---

**✅ Setup Complete! আপনার AI Call Center এখন Live!**

**Phone Number:** ___________________________  
**SIP Provider:** ___________________________  
**Asterisk IP:** ___________________________  
**Laravel URL:** ___________________________
