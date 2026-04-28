<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ওয়ালটন এআই লাইভ (Universal Data Extractor)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .chat-container { background: white; border: 1px solid #ddd; border-radius: 10px; padding: 20px; text-align: center;}
        .call-animation { font-size: 24px; color: #198754; font-weight: bold; animation: pulse 1.5s infinite; display: none; margin-bottom: 10px;}
        @keyframes pulse { 0% { opacity: 0.5; } 50% { opacity: 1; transform: scale(1.05);} 100% { opacity: 0.5; } }
        #chatBox { height: 250px; overflow-y: auto; text-align: left; background: #f1f3f5; padding: 15px; border-radius: 8px; display: none; border: 1px solid #dee2e6; margin-bottom: 20px; }
        .msg-agent { color: #198754; font-weight: bold; margin-bottom: 8px;}
    </style>
    <!-- Warning icon SVG -->
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </symbol>
    </svg>
</head>
<body>

<div class="container py-5" style="max-width: 700px;">
    
    <!-- ⚠️ খরচ সতর্কতা -->
    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
        <svg class="bi flex-shrink-0 me-2" style="width:24px;height:24px;fill:currentColor" role="img" aria-label="Warning:"><use xlink:href="#exclamation-triangle-fill"/></svg>
        <div>
            <strong>💰 সতর্কতা:</strong> এই পেজটি <strong>Gemini Live Native Audio</strong> ব্যবহার করে যা <strong>খুবই দামি</strong>। 
            শুধুমাত্র গুরুত্বপূর্ণ ডেমো বা টেস্টিংয়ে ব্যবহার করুন। প্রতিদিনের কাজে সাধারণ voice API ব্যবহার করুন।
        </div>
    </div>
    
    <div class="text-center">
        <h2 class="mb-2 text-primary fw-bold">ওয়ালটন লাইভ এজেন্ট</h2>
        <p class="text-muted mb-4">(Universal Audio Data Extraction)</p>
    </div>
    
    <div class="chat-container mb-4 shadow-sm">
        <div id="waitingMsg" class="text-muted fs-5 mb-3">কল শুরু করতে নিচের বাটনে চাপ দিন...</div>
        <div id="activeCallMsg" class="call-animation">📞 লাইভ কল চলছে (আপনার কথা রেকর্ড হচ্ছে)...</div>
        <h5 id="status" class="mb-3 text-muted">বন্ধ আছে</h5>
        
        <div id="chatBox"></div>
        
        <select id="ivrKeySelect" class="form-select w-75 mx-auto mb-3 text-center shadow-sm border-primary fw-bold">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $ivrServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ivr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <option value="<?php echo e($ivr->id); ?>">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ivr->key_press == 1): ?> 🔴 <?php else: ?> 💰 <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?> 
                    Button <?php echo e($ivr->key_press); ?>: <?php echo e($ivr->service_name); ?> 
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($ivr->voice_gender == 'Charon'): ?> (Male - <?php echo e($ivr->voice_speed); ?>) <?php else: ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </option>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <option value="">No IVR Service Available</option>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </select>
        <small class="text-muted d-block text-center mb-3">
            <strong>📋 IVR Services:</strong> All active IVR buttons loaded from database
        </small>

        <input type="text" id="callerNumberInput" class="form-control w-75 mx-auto mb-4 text-center"
            placeholder="📱 কলারের নম্বর লিখুন (01XXXXXXXXX)" style="background:#fff;font-weight:bold;font-size:1.1rem;" />
        
        <div class="d-flex justify-content-center gap-3">
            <button id="startBtn" class="btn btn-success btn-lg px-5 shadow rounded-pill">📞 কল শুরু করুন</button>
            <button id="endBtn" class="btn btn-danger btn-lg px-5 shadow rounded-pill d-none">❌ কল কাটুন (সেভ করুন)</button>
        </div>
    </div>
</div>

<script>
    let ws, audioCtx, processor, stream;
    let nextPlayTime = 0;
    let isCallActive = false;
    let agentChatHistory = []; 
    let callRecorder;
    let callAudioChunks = [];
    let globalConfig = null; 

    // 📞 Call Log tracking
    let callLogId = null;
    let serviceRequestId = null; // Asterisk caller number থেকে তৈরি ServiceRequest ID
    let callStartTime = null;

    // ☎️ URL থেকে Asterisk caller number auto-fill
    (function() {
        const params = new URLSearchParams(window.location.search);
        const callerNum = params.get('caller') || params.get('CallerID') || params.get('from') || '';
        if (callerNum) {
            document.getElementById('callerNumberInput').value = callerNum;
        }
    })();

    const playAudioCtx = new (window.AudioContext || window.webkitAudioContext)({ sampleRate: 24000 });

    function updateChatBox(text) {
        const chatBox = document.getElementById('chatBox');
        chatBox.style.display = 'block';
        chatBox.innerHTML += `<div class="msg-agent">এজেন্ট: <span class="text-dark fw-normal">${text}</span></div>`;
        chatBox.scrollTop = chatBox.scrollHeight; 
        agentChatHistory.push(`এজেন্ট: ${text}`);
    }

    document.getElementById('startBtn').addEventListener('click', async () => {
        document.getElementById('status').innerText = "সার্ভারের সাথে কানেক্ট হচ্ছে... ⏳";
        document.getElementById('startBtn').classList.add('d-none');
        document.getElementById('endBtn').classList.remove('d-none');
        document.getElementById('waitingMsg').style.display = 'none';
        document.getElementById('chatBox').innerHTML = ''; 
        
        const selectedIvrKey = document.getElementById('ivrKeySelect').value;
        isCallActive = true;
        agentChatHistory = []; 
        callAudioChunks = [];
        callStartTime = Date.now(); // ⏱️ কল শুরুর সময়

        // 📞 Call Log শুরু করো
        try {
            const logRes = await fetch('/call-log/start', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
                body: JSON.stringify({
                    ivr_key: selectedIvrKey,
                    session_id: Date.now().toString(),
                    caller_number: document.getElementById('callerNumberInput').value || null
                })
            });
            const logData = await logRes.json();
            if (logData.status === 'success') {
                callLogId = logData.log_id;
                serviceRequestId = logData.service_request_id; // Asterisk caller number entry
            }
        } catch(e) {} // log fail হলেও কল চলবে

        try {
            const res = await fetch('/get-live-setup?ivr_key=' + selectedIvrKey + '&caller_number=' + (document.getElementById('callerNumberInput').value || ''));
            globalConfig = await res.json(); 

            if(globalConfig.status === 'error') { alert("সার্ভার এরর: " + globalConfig.message); return; }

            const wsUrl = `wss://us-central1-aiplatform.googleapis.com/ws/google.cloud.aiplatform.v1beta1.LlmBidiService/BidiGenerateContent?access_token=${globalConfig.token}`;
            ws = new WebSocket(wsUrl);

            ws.onopen = () => {
                document.getElementById('status').innerText = "কানেক্টেড 🟢 (কথা বলুন)";
                document.getElementById('activeCallMsg').style.display = 'block';
                
                let selectedVoice = globalConfig.voice_gender || 'Charon';
                
                ws.send(JSON.stringify({
                    setup: {
                        model: `projects/${globalConfig.project_id}/locations/us-central1/publishers/google/models/gemini-live-2.5-flash-native-audio`,
                        systemInstruction: { parts: [{ text: globalConfig.prompt }] },
                        generationConfig: {
                            speechConfig: {
                                voiceConfig: {
                                    prebuiltVoiceConfig: {
                                        voiceName: selectedVoice
                                    }
                                }
                            }
                        }
                    }
                }));

                setTimeout(() => {
                    if (ws.readyState === WebSocket.OPEN) {
                        ws.send(JSON.stringify({
                            clientContent: { turns: [{ role: "user", parts: [{ text: "Start the conversation with your greeting message now." }] }], turnComplete: true }
                        }));
                    }
                }, 800); 

                startMic();
            }; 

            ws.onmessage = async (event) => {
                try {
                    let dataText = event.data;
                    if (event.data instanceof Blob) { dataText = await event.data.text(); }
                    const msg = JSON.parse(dataText);

                    if (msg.serverContent && msg.serverContent.modelTurn) {
                        let aiTextSnippet = ""; 
                        msg.serverContent.modelTurn.parts.forEach(part => {
                            if (part.inlineData && part.inlineData.mimeType.startsWith('audio/pcm')) {
                                playPcmAudio(part.inlineData.data);
                            }
                            if (part.text) {
                                aiTextSnippet += part.text;
                            }
                        });

                        if (aiTextSnippet.trim() !== "") {
                            updateChatBox(aiTextSnippet.trim());

                            // 🚨 Escalation detect — AI যদি ESCALATE বলে তাহলে transfer করো
                            const escalateKeywords = ['[ESCALATE]', '[TRANSFER]', '[AGENT_TRANSFER]'];
                            const shouldEscalate = escalateKeywords.some(k => aiTextSnippet.includes(k));
                            if (shouldEscalate && !window._escalating) {
                                window._escalating = true;
                                triggerEscalation('ai_detected');
                            }
                        }
                    }
                } catch (err) {}
            };

            ws.onerror = () => {
                document.getElementById('status').innerText = "কানেকশন কেটে গেছে!";
                document.getElementById('status').className = "mb-4 text-danger fw-bold";
            };

        } catch (error) { alert("লারাভেল ব্যাকএন্ডে সমস্যা!"); }
    });

    async function startMic() {
        stream = await navigator.mediaDevices.getUserMedia({ audio: { sampleRate: 16000, channelCount: 1 } });
        audioCtx = new AudioContext({ sampleRate: 16000 });
        const source = audioCtx.createMediaStreamSource(stream);
        
        processor = audioCtx.createScriptProcessor(4096, 1, 1);
        processor.onaudioprocess = (e) => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                const inputData = e.inputBuffer.getChannelData(0);
                const pcm16 = new Int16Array(inputData.length);
                for (let i = 0; i < inputData.length; i++) {
                    let s = Math.max(-1, Math.min(1, inputData[i]));
                    pcm16[i] = s < 0 ? s * 0x8000 : s * 0x7FFF;
                }
                const base64Data = btoa(String.fromCharCode(...new Uint8Array(pcm16.buffer)));
                ws.send(JSON.stringify({ realtimeInput: { mediaChunks: [{ data: base64Data, mimeType: "audio/pcm;rate=16000" }] } }));
            }
        };
        source.connect(processor);
        processor.connect(audioCtx.destination);

        callRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
        callRecorder.ondataavailable = e => callAudioChunks.push(e.data);
        callRecorder.start();
    }

    function playPcmAudio(base64Data) {
        const binaryString = window.atob(base64Data);
        const len = binaryString.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) { bytes[i] = binaryString.charCodeAt(i); }
        const int16Array = new Int16Array(bytes.buffer);
        const float32Array = new Float32Array(int16Array.length);
        for (let i = 0; i < int16Array.length; i++) { float32Array[i] = int16Array[i] / 32768.0; }
        const audioBuffer = playAudioCtx.createBuffer(1, float32Array.length, 24000);
        audioBuffer.copyToChannel(float32Array, 0);
        const source = playAudioCtx.createBufferSource();
        source.buffer = audioBuffer;
        source.connect(playAudioCtx.destination);
        if (nextPlayTime < playAudioCtx.currentTime) nextPlayTime = playAudioCtx.currentTime;
        source.start(nextPlayTime);
        nextPlayTime += audioBuffer.duration;
    }

    // 🚨 Escalation — রাগী কাস্টমার detect হলে agent এ transfer
    async function triggerEscalation(reason) {
        const ivrKey = document.getElementById('ivrKeySelect').value;
        updateChatBox('⏳ আপনাকে আমাদের agent এর সাথে কানেক্ট করা হচ্ছে...');

        try {
            const res = await fetch('/escalation/transfer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
                body: JSON.stringify({
                    ivr_key: ivrKey,
                    reason: reason,
                    customer_channel: window._asteriskChannel ?? null
                })
            });
            const data = await res.json();

            if (data.status === 'transferred') {
                updateChatBox('✅ ' + (data.calm_script || 'আপনাকে agent এর সাথে কানেক্ট করা হয়েছে।'));
            } else if (data.status === 'number_only') {
                // Asterisk নেই — শুধু নম্বর দাও
                updateChatBox('📞 আমাদের agent নম্বর: ' + data.agent_number + ' — সরাসরি call করুন।');
            } else {
                updateChatBox(data.calm_script || 'দুঃখিত, এই মুহূর্তে agent available নেই।');
            }
        } catch(e) {
            updateChatBox('দুঃখিত, agent transfer এ সমস্যা হয়েছে।');
        }
        window._escalating = false;
    }

    document.getElementById('endBtn').addEventListener('click', async () => {
        isCallActive = false; 
        if (processor) processor.disconnect();
        if (ws) ws.close();
        
        document.getElementById('activeCallMsg').style.display = 'none';
        document.getElementById('status').innerText = "এআই আপনার কল থেকে সম্পূর্ণ ডাটা বের করছে... ⏳";
        document.getElementById('status').className = "mb-4 text-warning fw-bold fs-5";
        document.getElementById('endBtn').classList.add('disabled');

        if (callRecorder && callRecorder.state !== 'inactive') {
            callRecorder.onstop = async () => {
                let blob = new Blob(callAudioChunks, { type: 'audio/webm' });
                let reader = new FileReader();
                reader.readAsDataURL(blob);
                reader.onloadend = async () => {
                    let base64data = reader.result.split(',')[1];

                    try {
                        const aiUrl = `https://us-central1-aiplatform.googleapis.com/v1beta1/projects/${globalConfig.project_id}/locations/us-central1/publishers/google/models/gemini-2.5-flash:generateContent`;
                        
                        // 🚀 ম্যাজিক: এবার আর হার্ডকোড নেই! এআইকে সব বের করতে বলা হয়েছে!
                        const aiPrompt = "কাস্টমার সাপোর্ট অডিও থেকে ডাটা বের করুন। কাস্টমার যা যা বলেছে সব তথ্য বাংলায় লিখুন। খুঁজে বের করুন: নাম, মোবাইল নম্বর, ঠিকানা, জেলা, পণ্যের নাম/মডেল, বারকোড/সিরিয়াল নম্বর, সমস্যার বিবরণ। শুধুমাত্র বুলেট পয়েন্ট ফরম্যাটে লিখুন, কোনো অতিরিক্ত লেবেল (যেমন 'Customer Name', 'Primary Phone') ব্যবহার করবেন না।";

                        const aiRes = await fetch(aiUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Authorization': `Bearer ${globalConfig.token}`
                            },
                            body: JSON.stringify({
                                contents: [{
                                    role: 'user',
                                    parts: [
                                        { inlineData: { mimeType: 'audio/webm', data: base64data } },
                                        { text: aiPrompt }
                                    ]
                                }]
                            })
                        });

                        const aiData = await aiRes.json();
                        let extractedAudioData = "কাস্টমার কোনো কথা বলেননি।";

                        if (aiData.candidates && aiData.candidates[0].content.parts[0].text) {
                            extractedAudioData = aiData.candidates[0].content.parts[0].text; 
                        }

                        let finalTranscript = agentChatHistory.join("\n") + "\n\n[আসল অডিও থেকে এআইয়ের বের করা সম্পূর্ণ ডাটা]:\n" + extractedAudioData;

                        document.getElementById('status').innerText = "ডাটাবেসে সেভ করা হচ্ছে... ⏳";
                        const selectedIvrKey = document.getElementById('ivrKeySelect').value;

                        const saveRes = await fetch('/process-final-text', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
                            body: JSON.stringify({
                                text: finalTranscript,
                                ivr_key: selectedIvrKey,
                                caller_number: document.getElementById('callerNumberInput').value || null
                            })
                        });
                        const saveData = await saveRes.json();

                        if(saveData.status === 'success') {
                            alert("✅ ডাটা সফলভাবে সেভ হয়েছে!");
                        } else if (saveData.status === 'rejected') {
                            // Hard rejection — bypass করার কোনো উপায় নেই
                            alert("🚫 ডাটা সেভ হয়নি!\n\nকারণ: " + saveData.message + "\n\nসঠিক তথ্য ছাড়া কোনো রেকর্ড তৈরি হবে না।");
                        } else if (saveData.status === 'validation_error') {
                            // Validation error — bypass নেই, শুধু দেখাও
                            alert("⚠️ ডাটা সেভ হয়নি!\n\nসমস্যা:\n" + saveData.validation_errors.join("\n") + "\n\nসঠিক তথ্য ছাড়া ডাটা save করা যাবে না।");
                        } else if (saveData.status === 'skipped') {
                            alert("ℹ️ " + saveData.message);
                        } else {
                            alert("❌ ডাটাবেস এরর: " + saveData.message);
                        }

                        // 📞 Call Log শেষ করো
                        if (callLogId) {
                            const duration = Math.floor((Date.now() - callStartTime) / 1000);
                            await fetch('/call-log/end', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
                                body: JSON.stringify({
                                    log_id: callLogId,
                                    duration: duration,
                                    status: saveData.status === 'success' ? 'completed' : 'dropped',
                                    service_request_id: serviceRequestId
                                })
                            }).catch(() => {});
                        }

                        window.location.reload();

                    } catch (err) {
                        // 📞 error হলে dropped log করো
                        if (callLogId) {
                            const duration = Math.floor((Date.now() - callStartTime) / 1000);
                            await fetch('/call-log/end', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
                                body: JSON.stringify({ log_id: callLogId, duration: duration, status: 'dropped', service_request_id: serviceRequestId })
                            }).catch(() => {});
                        }
                        alert("❌ এআই অডিও প্রসেসিং এরর!");
                        window.location.reload();
                    }
                };
            };
            callRecorder.stop();
        } else {
            alert("অডিও রেকর্ড হয়নি!");
            window.location.reload();
        }
    });
</script>
</body>
</html><?php /**PATH C:\laragon\www\ai-call-center\resources\views/mic-test.blade.php ENDPATH**/ ?>