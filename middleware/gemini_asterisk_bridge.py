"""
Asterisk AudioSocket <-> Vertex AI (Gemini Live 2.5) Bridge
==========================================================
Real-time bi-directional audio streaming between Asterisk and Gemini Live.
"""

import os
import asyncio
import json
import base64
import uuid
import time
import urllib.request
import numpy as np
import websockets
import google.auth
import google.auth.transport.requests
from google.oauth2 import service_account
from urllib.parse import urlencode

# ══════════════════════════════════════════════════════════════════════════════
#  CONFIGURATION
# ══════════════════════════════════════════════════════════════════════════════
AST_PORT   = 9092
PROJECT_ID = "ai-calls-center"
LOCATION   = "us-central1"
KEY_PATH   = os.path.join(os.path.dirname(__file__), "service-account.json")
MODEL      = f"projects/{PROJECT_ID}/locations/{LOCATION}/publishers/google/models/gemini-live-2.5-flash-native-audio"
API_VER    = "v1beta1"

BASE_WSS = (
    f"wss://{LOCATION}-aiplatform.googleapis.com/ws/"
    f"google.cloud.aiplatform.{API_VER}.LlmBidiService/BidiGenerateContent"
)

# ── ★ LANGUAGE LOCK — prevent Hindi mis-transcription ★ ────────────
LANGUAGE_LOCK = (
    "🔒 ABSOLUTE LANGUAGE RULE — কাস্টমার শুধুমাত্র বাংলা ভাষায় কথা বলবেন।\n"
    "interpret all audio as Bengali. Always respond in Bengali (বাংলা script).\n\n"
)

LARAVEL_BASE_URL = "http://127.0.0.1:8001"
DEFAULT_IVR_KEY  = "1"

# AudioSocket constants
PKT_HANGUP, PKT_UUID, PKT_AUDIO = 0x00, 0x01, 0x10
AST_FRAME_BYTES = 320

def _get_vertex_wss_url_sync() -> str:
    creds = service_account.Credentials.from_service_account_file(KEY_PATH, scopes=["https://www.googleapis.com/auth/cloud-platform"])
    creds.refresh(google.auth.transport.requests.Request())
    return f"{BASE_WSS}?{urlencode({'access_token': creds.token})}"


async def get_vertex_wss_url() -> str:
    """Async wrapper — runs blocking auth in thread pool so event loop stays free."""
    loop = asyncio.get_running_loop()
    return await loop.run_in_executor(None, _get_vertex_wss_url_sync)


# ══════════════════════════════════════════════════════════════════════════════
#  AUDIO RESAMPLING  (linear interpolation via numpy)
#
#  Asterisk slin  →  8 000 Hz, 16-bit, mono
#  Vertex AI in   → 16 000 Hz, 16-bit, mono
#  Vertex AI out  → 24 000 Hz, 16-bit, mono  (Gemini Live always outputs 24 kHz)
# ══════════════════════════════════════════════════════════════════════════════
def resample(audio_bytes: bytes, from_hz: int, to_hz: int) -> bytes:
    """Generic linear-interpolation PCM resampler."""
    samples = np.frombuffer(audio_bytes, dtype=np.int16).astype(np.float32)
    if len(samples) == 0:
        return audio_bytes
    x_old  = np.arange(len(samples))
    x_new  = np.linspace(0, len(samples) - 1, num=int(len(samples) * to_hz / from_hz))
    result = np.interp(x_new, x_old, samples)
    return result.astype(np.int16).tobytes()


# ══════════════════════════════════════════════════════════════════════════════
#  LARAVEL HTTP HELPERS  (urllib — no extra dependencies)
# ══════════════════════════════════════════════════════════════════════════════
def _post_json_sync(url: str, data: dict) -> dict:
    """Blocking HTTP POST to Laravel API — call via run_in_executor only."""
    body = json.dumps(data).encode()
    req  = urllib.request.Request(
        url, data=body,
        headers={"Content-Type": "application/json"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=10) as resp:
            return json.loads(resp.read())
    except Exception as e:
        print(f"[HTTP] Error → {url}: {e}")
        return {}


def _post_form_sync(url: str, data: dict, timeout: int = 5):
    """Blocking HTTP POST x-www-form-urlencoded — for Walton API."""
    body = urlencode(data).encode()
    req  = urllib.request.Request(
        url, data=body,
        headers={"Content-Type": "application/x-www-form-urlencoded"},
        method="POST",
    )
    try:
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            raw = resp.read()
            return json.loads(raw)
    except Exception as e:
        print(f"[HTTP Form] Error → {url}: {e}")
        return None


async def post_form(url: str, data: dict, timeout: int = 10):
    """Async wrapper for form POST — runs in thread pool."""
    loop = asyncio.get_running_loop()
    return await loop.run_in_executor(None, lambda: _post_form_sync(url, data, timeout))


def _get_json_sync(url: str) -> dict:
    """Blocking HTTP GET to Laravel API — call via run_in_executor only."""
    req = urllib.request.Request(url, headers={"Accept": "application/json"}, method="GET")
    try:
        with urllib.request.urlopen(req, timeout=5) as resp:
            return json.loads(resp.read())
    except Exception as e:
        print(f"[HTTP] Error → {url}: {e}")
        return {}


async def post_json(url: str, data: dict) -> dict:
    """Async wrapper — runs blocking HTTP in thread pool."""
    loop = asyncio.get_running_loop()
    return await loop.run_in_executor(None, _post_json_sync, url, data)


async def get_json(url: str) -> dict:
    """Async wrapper — runs blocking HTTP GET in thread pool."""
    loop = asyncio.get_running_loop()
    return await loop.run_in_executor(None, _get_json_sync, url)


# ══════════════════════════════════════════════════════════════════════════════
#  READ ONE AudioSocket PACKET  (3-byte header: type + 2-byte length)
# ══════════════════════════════════════════════════════════════════════════════
async def read_packet(reader: asyncio.StreamReader):
    header      = await reader.readexactly(3)
    pkt_type    = header[0]
    payload_len = int.from_bytes(header[1:3], "big")
    payload     = await reader.readexactly(payload_len) if payload_len else b""
    return pkt_type, payload


# ══════════════════════════════════════════════════════════════════════════════
#  CALL HANDLER
# ══════════════════════════════════════════════════════════════════════════════
async def handle_call(reader: asyncio.StreamReader, writer: asyncio.StreamWriter):
    peer     = writer.get_extra_info("peername")
    peer_ip  = peer[0] if peer else "unknown"
    print(f"\n[Asterisk] New call connected via AudioSocket — {peer}")
    call_uuid = "unknown"

    # ── Database tracking (same as mic-test.blade.php) ───────────────────────
    call_log_id        = None
    service_request_id = None
    ai_text_log        = []    # complete turns — エジেন্ট:/কাস্টমার: prefixed
    call_start_time    = time.time()

    # ── Transcription turn-buffers ────────────────────────────────────────────
    # Gemini Live streams text in small chunks — we accumulate until turnComplete
    agent_buf    = []   # AI output transcript chunks for current turn
    customer_buf = []   # Customer input transcript chunks for current turn

    # ── caller number/ivr_key — resolved after UUID packet received ──────────
    caller_number = None
    ivr_key       = DEFAULT_IVR_KEY

    # ── Synchronization: main coroutine waits until UUID+caller resolved ─────
    caller_resolved = asyncio.Event()

    # ── Call type — set after UUID lookup ────────────────────────────────────
    call_type = "inbound"   # inbound | outbound_survey
    survey_id = None

    # ── Audio buffer queue ────────────────────────────────────────────────────
    audio_queue: asyncio.Queue = asyncio.Queue(maxsize=500)
    stop_event = asyncio.Event()

    # ── Gemini Pre-warm tracking ──────────────────────────────────────────────
    vertex_ws      = None
    vertex_ready   = asyncio.Event()
    prewarm_failed = False
    final_prompt   = ""
    final_voice    = "Charon"

    # ── Walton SR tracking ────────────────────────────────────────────────────
    existing_open_walton_sr  = None
    existing_open_sr_product = None
    existing_open_sr_status  = None
    OPEN_STATUSES = {"pending", "in process", "in-process", "processing", "escalation requested", "new"}

    async def prewarm_gemini():
        nonlocal vertex_ws, prewarm_failed, final_prompt, final_voice
        nonlocal existing_open_walton_sr, existing_open_sr_product, existing_open_sr_status

        try:
            await asyncio.wait_for(caller_resolved.wait(), timeout=2.0)
            print(f"[PreWarm] Caller={caller_number} | Firing parallel tasks...")
            t_start = time.time()

            # ── Parallel Tasks ──
            t0 = time.time()
            
            # ১. IVR Setup Fetch
            if call_type == "outbound_survey" and survey_id:
                ivr_coro = get_json(f"{LARAVEL_BASE_URL}/api/bridge/get-ivr-setup?call_type=outbound_survey&survey_id={survey_id}&caller_number={caller_number or ''}")
            else:
                ivr_coro = get_json(f"{LARAVEL_BASE_URL}/api/bridge/get-ivr-setup?ivr_key={ivr_key}&caller_number={caller_number or ''}")

            # ২. Customer Profile Fetch (Personalization)
            profile_coro = get_json(f"{LARAVEL_BASE_URL}/api/bridge/customer-profile?mobile={caller_number or ''}")

            # ৩. Walton SR Search (Existing Logic)
            walton_coro = post_form(
                "http://192.168.117.135:8080/webApiProduction/local_116_228/webCrmSrSearch.php",
                {"CUSTOMER_MOBILE": (caller_number or "").lstrip("+"), "username": "walton", "key": "xHj0LoH!9%4VVWYWQilrti"},
                timeout=3
            ) if (caller_number and call_type != "outbound_survey") else asyncio.sleep(0, result=None)

            # ৪. Auth URL
            wss_coro = get_vertex_wss_url()

            ivr_result, profile_result, walton_result, wss_url = await asyncio.gather(
                ivr_coro, profile_coro, walton_coro, wss_coro,
                return_exceptions=True
            )
            print(f"[PreWarm] Parallel fetch done in {(time.time()-t0)*1000:.0f}ms")

            # --- Process Results ---
            
            # IVR Setup
            if isinstance(ivr_result, Exception) or not ivr_result or not ivr_result.get("prompt"):
                final_prompt = "You are a helpful customer service assistant for Walton. Respond in Bengali."
                final_voice  = "Charon"
            else:
                final_prompt = ivr_result["prompt"]
                final_voice  = ivr_result.get("voice_gender", "Charon")

            # Customer Profile (Personalization)
            customer_context = ""
            if profile_result and not isinstance(profile_result, Exception) and profile_result.get("status") == "success":
                profile = profile_result.get("data", {})
                name = profile.get("name", "সম্মানিত গ্রাহক")
                honorific = profile.get("honorific", "স্যার/ম্যাডাম")
                last_interaction = profile.get("last_interaction", "নাই")
                customer_context = f"\n\n[CUSTOMER PROFILE]\nনাম: {name}\nসম্বোধন: {honorific}\nমোবাইল: {caller_number}\nসর্বশেষ যোগাযোগ: {last_interaction}\n[AI: কাস্টমারকে '{honorific}' বলে সম্বোধন করো।]\n"
                print(f"[PreWarm] Personalized for: {name} ({honorific})")
            else:
                customer_context = f"\n\n[CUSTOMER PROFILE]\nনাম: নতুন গ্রাহক\nসম্বোধন: স্যার/ম্যাডাম\nমোবাইল: {caller_number}\n"

            # Walton SR process
            walton_sr_context = ""
            if walton_result and not isinstance(walton_result, Exception):
                sr_list = (
                    walton_result if isinstance(walton_result, list)
                    else ([walton_result] if isinstance(walton_result, dict)
                          and walton_result.get("SERVICE_NO") else [])
                )
                if sr_list:
                    latest    = sr_list[0]
                    sr_no     = latest.get("SERVICE_NO", "—")
                    sr_status = latest.get("SERVICE_STATUS", "—")
                    product   = latest.get("PRODUCT", latest.get("ITEM_NAME", "—"))
                    problem   = latest.get("PROBLEMS", "—")
                    created   = latest.get("CREATED_DATE", "—")
                    is_open   = sr_status.lower().strip() in OPEN_STATUSES

                    if is_open:
                        existing_open_walton_sr  = sr_no
                        existing_open_sr_product = product
                        existing_open_sr_status  = sr_status
                        walton_sr_context = (
                            f"\n\n[WALTON SR — OPEN: {caller_number}]\n"
                            f"SR: {sr_no} | পণ্য: {product} | সমস্যা: {problem}\n"
                            f"তারিখ: {created} | Status: {sr_status}\n"
                            f"[AI: কথা শুরুতেই বলো SR {sr_no} চলমান। "
                            f"একই সমস্যা হলে নতুন SR নয়।]\n"
                        )
                        print(f"[PreWarm] OPEN SR: {sr_no} ({sr_status})")
                    else:
                        walton_sr_context = (
                            f"\n\n[WALTON SR HISTORY: {caller_number}]\n"
                            f"Last SR: {sr_no} (CLOSED) | পণ্য: {product}\n"
                            f"[AI: নতুন সমস্যায় নতুন SR করা যাবে।]\n"
                        )
                        print(f"[PreWarm] SR {sr_no} CLOSED")
            elif isinstance(walton_result, Exception):
                print(f"[PreWarm] Walton failed: {walton_result}")

            # Vertex connect
            if isinstance(wss_url, Exception):
                raise wss_url

            t1 = time.time()
            vertex_ws = await websockets.connect(
                wss_url,
                additional_headers={"Content-Type": "application/json"},
                ping_interval=20,
                ping_timeout=20,
            )
            print(f"[PreWarm] Vertex connected: {(time.time()-t1)*1000:.0f}ms")

            # Setup message
            full_prompt = LANGUAGE_LOCK + final_prompt + customer_context + walton_sr_context
            await vertex_ws.send(json.dumps({
                "setup": {
                    "model": MODEL,
                    "generationConfig": {
                        "responseModalities": ["AUDIO"],
                        "speechConfig": {
                            "voiceConfig": {
                                "prebuiltVoiceConfig": {"voiceName": final_voice}
                            }
                        }
                    },
                    "inputAudioTranscription":  {},
                    "outputAudioTranscription": {},
                    "systemInstruction": {
                        "parts": [{"text": full_prompt}]
                    },
                }
            }))

            # setupComplete
            t2 = time.time()
            raw  = await asyncio.wait_for(vertex_ws.recv(), timeout=10.0)
            resp = json.loads(raw)
            if "setupComplete" in resp:
                print(f"[PreWarm] setupComplete: {(time.time()-t2)*1000:.0f}ms")

            # Greeting trigger
            greeting_text = ivr_result.get("greeting") if (ivr_result and isinstance(ivr_result, dict)) else None
            if not greeting_text:
                greeting_text = "Start the conversation with your greeting message now."
            
            await vertex_ws.send(json.dumps({
                "clientContent": {
                    "turns": [{
                        "role":  "user",
                        "parts": [{"text": greeting_text}]
                    }],
                    "turnComplete": True
                }
            }))

            total = (time.time() - t_start) * 1000
            print(f"[PreWarm] 🚀 FULLY READY in {total:.0f}ms!")
            
            # Log Performance Metric (Suggestion #5)
            asyncio.create_task(post_json(
                f"{LARAVEL_BASE_URL}/api/bridge/log-performance",
                {
                    "session_id": call_uuid,
                    "latency_ms": total,
                    "provider": "Gemini Live 2.5",
                    "status": "ready"
                }
            ))
            
            vertex_ready.set()

        except asyncio.TimeoutError:
            print("[PreWarm] ❌ Timeout")
            prewarm_failed = True
            vertex_ready.set()
        except Exception as e:
            print(f"[PreWarm] ❌ Error: {e}")
            prewarm_failed = True
            vertex_ready.set()

    async def drain_asterisk():
        """
        Read ALL packets from Asterisk continuously and put audio into queue.
        Runs from the very beginning so the TCP buffer never fills up.
        """
        nonlocal call_uuid, call_log_id, service_request_id, caller_number, ivr_key, call_type, survey_id
        first_packet = True
        try:
            while not stop_event.is_set():
                pkt_type, payload = await read_packet(reader)

                # First packet is always UUID
                if first_packet and pkt_type == PKT_UUID and len(payload) == 16:
                    call_uuid = str(uuid.UUID(bytes=payload))
                    print(f"[Asterisk] Call UUID: {call_uuid}")
                    first_packet = False

                    # ── Resolve caller number by UUID ──────────────────────────
                    # Asterisk System(curl register-caller ...) runs BEFORE AudioSocket
                    # so the cache entry should already exist when we get here.
                    cr = await get_json(
                        f"{LARAVEL_BASE_URL}/api/bridge/caller-by-uuid?uuid={call_uuid}"
                    )
                    if cr.get("status") == "success" and cr.get("caller_number"):
                        caller_number = cr["caller_number"]
                        ivr_key       = cr.get("ivr_key", DEFAULT_IVR_KEY)
                        call_type     = cr.get("call_type", "inbound")
                        survey_id     = cr.get("survey_id")
                        print(f"[DB] Caller resolved: {caller_number}, IVR key: {ivr_key}, type: {call_type}")
                    else:
                        # Fallback: retry once after 100ms (handles slight timing issues)
                        await asyncio.sleep(0.1)
                        cr2 = await get_json(
                            f"{LARAVEL_BASE_URL}/api/bridge/caller-by-uuid?uuid={call_uuid}"
                        )
                        if cr2.get("status") == "success" and cr2.get("caller_number"):
                            caller_number = cr2["caller_number"]
                            ivr_key       = cr2.get("ivr_key", DEFAULT_IVR_KEY)
                            call_type     = cr2.get("call_type", "inbound")
                            survey_id     = cr2.get("survey_id")
                            print(f"[DB] Caller resolved (retry): {caller_number}, IVR key: {ivr_key}, type: {call_type}")
                        else:
                            print(f"[DB] UUID lookup failed (response: {cr}) — saving without number")

                    # ── Signal main coroutine that caller is known ─────────────
                    caller_resolved.set()

                    # 📞 Start call log
                    log_data = await post_json(
                        f"{LARAVEL_BASE_URL}/api/bridge/call-log/start",
                        {
                            "ivr_key":       ivr_key,
                            "session_id":    call_uuid,
                            "caller_number": caller_number,
                        },
                    )
                    call_log_id        = log_data.get("log_id")
                    service_request_id = log_data.get("service_request_id")
                    print(f"[DB] Call log started — log_id={call_log_id}, sr_id={service_request_id}")
                    continue

                first_packet = False

                if pkt_type == PKT_HANGUP:
                    print("[Asterisk] Hangup packet received.")
                    stop_event.set()
                    await audio_queue.put(None)  # sentinel to unblock sender

                    # ── Immediately mark SR as ended — don't wait for AI processing ──
                    # This makes Live Monitor update within 2 seconds of hangup
                    if service_request_id:
                        asyncio.create_task(post_json(
                            f"{LARAVEL_BASE_URL}/api/bridge/call-hangup",
                            {"service_request_id": service_request_id, "call_log_id": call_log_id},
                        ))
                    break

                if pkt_type == PKT_AUDIO and payload:
                    try:
                        audio_queue.put_nowait(payload)
                    except asyncio.QueueFull:
                        pass  # drop oldest data under backpressure

        except asyncio.IncompleteReadError:
            print("[Asterisk] Disconnected.")
        except Exception as e:
            print(f"[Error] Asterisk Read Error: {e}")
        finally:
            stop_event.set()
            await audio_queue.put(None)  # ensure sender task can exit
            # ── Always fire hangup on disconnect (clean or crash) ──────────
            if service_request_id:
                try:
                    loop = asyncio.get_running_loop()
                    loop.create_task(post_json(
                        f"{LARAVEL_BASE_URL}/api/bridge/call-hangup",
                        {"service_request_id": service_request_id, "call_log_id": call_log_id},
                    ))
                except Exception:
                    pass

    # Start draining Asterisk immediately — before connecting to Vertex AI
    drain_task = asyncio.create_task(drain_asterisk())

    # ── Start Pre-warm process ───────────────────────────────────────────────
    prewarm_task = asyncio.create_task(prewarm_gemini())

    # ── Wait for Gemini to be ready (or fail) ─────────────────────────────────
    await vertex_ready.wait()
    if prewarm_failed or not vertex_ws:
        print("[System] Gemini connection failed — closing call")
        writer.close()
        return

    # ── Bidirectional audio bridge ────────────────────────────────────────────

    async def queue_to_gemini():
        """
        Forward buffered + live Asterisk audio → resample 8k→16k → Vertex AI.
        Reads from the queue that drain_asterisk() is filling.
        """
        try:
            while not stop_event.is_set():
                payload = await audio_queue.get()
                if payload is None:
                    break  # sentinel → call ended

                audio_16k = resample(payload, from_hz=8000, to_hz=16000)
                msg = {
                    "realtime_input": {
                        "media_chunks": [{
                            "data": base64.b64encode(audio_16k).decode(),
                            "mime_type": "audio/pcm;rate=16000",
                        }]
                    }
                }
                await vertex_ws.send(json.dumps(msg))

        except Exception as e:
            print(f"[Error] Gemini Send Error: {e}")
        finally:
            stop_event.set()
            # ── Close Vertex AI WebSocket so gemini_to_asterisk()'s async-for exits ──
            # Without this, gemini_to_asterisk blocks forever waiting for more messages
            # and asyncio.gather() never completes → DB save never runs.
            try:
                await vertex_ws.close()
            except Exception:
                pass

    async def gemini_to_asterisk():
        """
        Vertex AI audio → resample 24k→8k → pace at 20ms/frame → Asterisk.

        We send ONLY real audio frames (no silence padding).
        Asterisk AudioSocket handles silence natively between AI turns.
        Sending all-zero 'silence' frames resets Asterisk's translate context
        (detected as CNG), causing 'lost frame' warnings.
        """
        INTERVAL = 0.020  # 20ms per 320-byte slin frame
        loop = asyncio.get_running_loop()
        total_frames_sent = 0
        sr_pre_saved = False  # prevent duplicate pre-register calls

        # ── Pre-register SR during live call ─────────────────────────────────
        # Trigger phrase — must match what AI says in prompt
        SAVE_TRIGGERS = ['রেজিস্ট্রেশন প্রক্রিয়াধীন', 'রেজিস্ট্রেশন সম্পন্ন হচ্ছে', 'প্রক্রিয়া চলছে', 'একটু অপেক্ষা করুন']

        async def _pre_register_sr():
            """Call Laravel to create SR ticket + Walton push, inject srNo back to Gemini."""
            sr_display = 'PENDING'
            ticket_id  = None
            try:
                transcript_text = "\n".join(ai_text_log)
                resp = await post_json(
                    f"{LARAVEL_BASE_URL}/api/bridge/pre-register-ticket",
                    {
                        "transcript":              transcript_text,
                        "service_request_id":      service_request_id,
                        "caller_number":           caller_number,
                        "ivr_key":                 ivr_key,
                        "existing_walton_sr":      existing_open_walton_sr,   # open SR থাকলে পাঠাও
                        "existing_walton_product": existing_open_sr_product,
                        "existing_walton_status":  existing_open_sr_status,
                    }
                )
                walton_sr = resp.get('walton_sr') or ''
                our_sr    = resp.get('our_sr', '')
                ticket_id = resp.get('ticket_id')
                print(f"[PreSave] ✅ SR created: walton={walton_sr} our={our_sr} ticket={ticket_id}")
                if walton_sr:
                    sr_display = walton_sr
                elif ticket_id:
                    # Walton push may be slow — poll up to 5 times (every 3s = 15s max)
                    print(f"[PreSave] No walton_sr yet, polling for ticket_id={ticket_id}...")
                    for attempt in range(5):
                        await asyncio.sleep(3)
                        try:
                            poll_resp = await post_json(
                                f"{LARAVEL_BASE_URL}/api/bridge/check-walton-sr",
                                {"ticket_id": ticket_id}
                            )
                            polled_sr = poll_resp.get('walton_sr') or ''
                            print(f"[PreSave] Poll #{attempt+1}: walton_sr={polled_sr}")
                            if polled_sr:
                                sr_display = polled_sr
                                break
                        except Exception as pe:
                            print(f"[PreSave] Poll #{attempt+1} failed: {pe}")
                    if sr_display == 'PENDING':
                        print(f"[PreSave] All polls done, walton_sr still not available. our_sr={our_sr}")
                        # our_sr (WLT-xxx) is internal only — never speak to customer
                        # Keep sr_display as 'PENDING' so AI says SMS will arrive
                        # (do NOT set sr_display = our_sr here)
            except Exception as e:
                sr_display = 'PENDING'
                print(f"[PreSave] ❌ Error: {e}")
            print(f"[PreSave] Final sr_display={sr_display}")

            # Inject SR number back to Gemini so AI can announce it
            try:
                await vertex_ws.send(json.dumps({
                    "client_content": {
                        "turns": [{"role": "user", "parts": [{"text": f"SYSTEM_SR_READY:{sr_display}"}]}],
                        "turnComplete": True
                    }
                }))
                print(f"[PreSave] Injected SYSTEM_SR_READY:{sr_display} to Gemini")
            except Exception as e:
                print(f"[PreSave] Failed to inject SR number: {e}")

        try:
            async for raw_msg in vertex_ws:
                if stop_event.is_set():
                    break

                data = json.loads(raw_msg)

                if "setupComplete" in data:
                    continue

                server_content = data.get("serverContent")
                if not server_content:
                    top_keys = list(data.keys())
                    if top_keys not in (["serverContent"], ["setupComplete"], ["usageMetadata"]):
                        print(f"[DEBUG] Gemini msg keys: {top_keys}")
                    continue

                # ── DEBUG: show serverContent keys to verify transcript field names ──
                sc_keys = list(server_content.keys())
                if any(k in sc_keys for k in ("inputTranscription", "outputTranscription",
                                               "inputAudioTranscription", "outputAudioTranscription")):
                    print(f"[DEBUG] transcript keys: {sc_keys}")

                # ── Accumulate AI output transcript chunks ──────────────────────
                output_tr = server_content.get("outputTranscription")
                if output_tr and output_tr.get("text"):
                    agent_buf.append(output_tr["text"])

                # ── Accumulate customer input transcript chunks ──────────────────
                input_tr = server_content.get("inputTranscription")
                if input_tr and input_tr.get("text"):
                    raw_text = input_tr["text"]
                    # 🌐 Bengali-only filter: Devanagari/Hindi script সরাও
                    # Bengali: \u0980-\u09FF | ASCII | space — এগুলো রাখো
                    # Devanagari: \u0900-\u097F — সরাও
                    import re as _re
                    clean_text = _re.sub(
                        r'[\u0900-\u097F\u0600-\u06FF\u0C00-\u0C7F\uAC00-\uD7AF\u4E00-\u9FFF\u3040-\u30FF\u0400-\u04FF]+',
                        '', raw_text
                    ).strip()
                    # যদি clean text অর্থপূর্ণ হয় তাহলে রাখো, না হলে raw রাখো
                    final_text = clean_text if len(clean_text) >= 2 else raw_text
                    customer_buf.append(final_text)

                # ── On turnComplete → flush buffers as complete sentences ─────────
                # This mirrors mic-test agentChatHistory — complete turns only
                if server_content.get("turnComplete"):
                    if agent_buf:
                        full = "".join(agent_buf).strip()
                        if full:
                            ai_text_log.append(f"এজেন্ট: {full}")
                            print(f"[Agent] {full}")

                            # 🔔 SR Pre-register trigger — detect when AI says the save phrase
                            if not sr_pre_saved and any(t in full for t in SAVE_TRIGGERS):
                                sr_pre_saved = True
                                print(f"[PreSave] Trigger detected — launching pre-register task")
                                asyncio.create_task(_pre_register_sr())

                            # 🚨 ESCALATION trigger — AI যেকোনো forward/transfer phrase বললেই trigger
                            ESCALATION_TRIGGERS = [
                                # Tag-based (AI prompt এ এই tags দেওয়া আছে)
                                '[escalation]', '[transfer]', '[agent_transfer]',
                                # বাংলা phrases — AI এগুলো বলতে পারে
                                'এজেন্ট এর সাথে কানেক্ট', 'এজেন্টের সাথে কানেক্ট',
                                'মানুষের সাথে কথা', 'মানুষের সাথে বলতে',
                                'আমাদের agent', 'আমাদের এজেন্ট',
                                'agent এর সাথে', 'এজেন্ট এর সাথে',
                                'transfer করছি', 'ট্রান্সফার করছি',
                                'forward করছি', 'ফরওয়ার্ড করছি',
                                'connect করছি', 'কানেক্ট করছি',
                                'লাইনে দিচ্ছি', 'সংযুক্ত করছি',
                                'বিশেষজ্ঞের সাথে', 'বিশেষজ্ঞ agent',
                                'human agent', 'Human Agent',
                                'কল ট্রান্সফার', 'call transfer',
                                'এখনই agent', 'এখনই এজেন্ট',
                            ]
                            if any(t.lower() in full.lower() for t in ESCALATION_TRIGGERS):
                                print(f"[Escalation] Trigger detected — calling transfer API")
                                async def _trigger_escalation():
                                    try:
                                        await post_json(
                                            f"{LARAVEL_BASE_URL}/api/bridge/transfer-to-agent",
                                            {
                                                "ivr_key":            ivr_key,
                                                "service_request_id": service_request_id,
                                                "caller_number":      caller_number,
                                                "reason":             "ai_triggered",
                                            }
                                        )
                                        print(f"[Escalation] Transfer API called")
                                    except Exception as ex:
                                        print(f"[Escalation] Transfer API error: {ex}")
                                asyncio.create_task(_trigger_escalation())

                        agent_buf.clear()
                    if customer_buf:
                        full = "".join(customer_buf).strip()
                        if full:
                            ai_text_log.append(f"কাস্টমার: {full}")
                            print(f"[Customer] {full}")
                        customer_buf.clear()

                # ── Audio parts → pace to Asterisk ─────────────────────────────
                parts = (
                    server_content
                        .get("modelTurn", {})
                        .get("parts", [])
                )
                for part in parts:
                    inline = part.get("inlineData")
                    if not inline:
                        continue

                    audio_bytes = base64.b64decode(inline["data"])
                    if not audio_bytes:
                        continue

                    audio_8k = resample(audio_bytes, from_hz=24000, to_hz=8000)

                    frames = []
                    for i in range(0, len(audio_8k), AST_FRAME_BYTES):
                        chunk = audio_8k[i : i + AST_FRAME_BYTES]
                        if len(chunk) < AST_FRAME_BYTES:
                            chunk = chunk + b'\x00' * (AST_FRAME_BYTES - len(chunk))
                        frames.append(chunk)

                    if not frames:
                        continue

                    next_send = loop.time()
                    for frame in frames:
                        if stop_event.is_set():
                            break
                        writer.write(
                            bytes([PKT_AUDIO]) + len(frame).to_bytes(2, "big") + frame
                        )
                        await writer.drain()
                        total_frames_sent += 1
                        next_send += INTERVAL
                        sleep_for = next_send - loop.time()
                        if sleep_for > 0:
                            await asyncio.sleep(sleep_for)

                    print(
                        f"[Gemini] Sent {len(frames)} frames "
                        f"({len(audio_8k)} bytes, {len(frames) * 20}ms audio) | "
                        f"total={total_frames_sent * 20 / 1000:.1f}s"
                    )

        except Exception as e:
            print(f"[Error] Gemini Read Error: {e}")
        finally:
            stop_event.set()
            print(f"[Gemini→Asterisk] Done. {total_frames_sent} frames sent "
                  f"({total_frames_sent * 20 / 1000:.1f}s audio)")

    await asyncio.gather(drain_task, queue_to_gemini(), gemini_to_asterisk())

    # ── Flush any remaining transcript buffers (call ended mid-turn) ──────────
    if agent_buf:
        full = "".join(agent_buf).strip()
        if full:
            ai_text_log.append(f"এজেন্ট: {full}")
    if customer_buf:
        full = "".join(customer_buf).strip()
        if full:
            ai_text_log.append(f"কাস্টমার: {full}")

    # ── Save to database — exact same flow as mic-test.blade.php endBtn ───────
    call_duration = int(time.time() - call_start_time)
    save_status   = "dropped"

    if ai_text_log:
        # Build transcript exactly like mic-test finalTranscript
        # agentChatHistory lines + data section
        transcript = (
            "[Asterisk Phone Call — Conversation Transcript:]\n"
            + "\n".join(ai_text_log)
            + "\n\n[আসল কথোপকথন থেকে এআইয়ের বের করা সম্পূর্ণ ডাটা]:\n"
            + "\n".join(
                f"• {line.replace('কাস্টমার: ', '')}"
                for line in ai_text_log
                if line.startswith("কাস্টমার:")
            )
        )

        print("[DB] Sending transcript to Laravel for data extraction...")
        final_text_payload = {
            "text":               transcript,
            "ivr_key":            ivr_key,
            "caller_number":      caller_number,
            "service_request_id": service_request_id,
            "call_type":          call_type,     # outbound_survey → survey result saving
        }
        if survey_id:
            final_text_payload["survey_id"] = survey_id
        save_resp = await post_json(
            f"{LARAVEL_BASE_URL}/api/bridge/process-final-text",
            final_text_payload,
        )
        save_status = save_resp.get("status", "dropped")
        print(f"[DB] process-final-text → status={save_status}")
    else:
        print("[DB] No transcript collected — marking as drop call.")

    # 📞 End call log — same as mic-test.blade.php /call-log/end
    if call_log_id:
        end_resp = await post_json(
            f"{LARAVEL_BASE_URL}/api/bridge/call-log/end",
            {
                "log_id":             call_log_id,
                "duration":           call_duration,
                "status":             "completed" if save_status == "success" else "dropped",
                "service_request_id": service_request_id,
            },
        )
        print(f"[DB] Call log ended — {end_resp.get('status')}")

    # ── Cleanup ───────────────────────────────────────────────────────────────
    try:
        await vertex_ws.close()
    except Exception:
        pass
    try:
        writer.close()
    except Exception:
        pass
    print(f"[System] Call Finished / Connection Closed — {call_uuid}\n")


# ══════════════════════════════════════════════════════════════════════════════
#  ENTRY POINT
# ══════════════════════════════════════════════════════════════════════════════
async def main():
    server = await asyncio.start_server(handle_call, "0.0.0.0", AST_PORT)
    print(f"[*] Vertex AI Bridge listening on port {AST_PORT}")
    print(f"[*] Model   : gemini-live-2.5-flash-native-audio")
    print(f"[*] Project : {PROJECT_ID}  /  {LOCATION}")
    print("[*] Press Ctrl-C to stop.\n")
    try:
        async with server:
            await server.serve_forever()
    except KeyboardInterrupt:
        print("\n[System] Shutting down...")
    finally:
        tasks = [t for t in asyncio.all_tasks() if t is not asyncio.current_task()]
        [task.cancel() for task in tasks]
        await asyncio.gather(*tasks, return_exceptions=True)


if __name__ == "__main__":
    asyncio.run(main())
