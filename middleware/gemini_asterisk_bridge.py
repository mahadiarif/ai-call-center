"""
Asterisk AudioSocket <-> Vertex AI (Gemini Live 2.5 Native Audio) Bridge
=========================================================================
Receives raw PCM audio (8 kHz, 16-bit, mono) from Asterisk via AudioSocket TCP,
resamples to 16 kHz, streams to Vertex AI BidiGenerateContent WebSocket, and
returns the AI audio response (24 kHz → 8 kHz) back to Asterisk in real-time.

Model  : gemini-live-2.5-flash-native-audio  (Vertex AI, v1beta1)
Auth   : Service Account JSON key  (KEY_PATH below)
Port   : 9092  (AudioSocket)

Requirements:
    pip install websockets numpy google-auth

Usage:
    python gemini_asterisk_bridge.py
"""

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
KEY_PATH   = "/root/vertex_bridge/service-account.json"

# ── Model ────────────────────────────────────────────────────────────────────
#  gemini-live-2.5-flash-native-audio
#    → Gemini 2.5 Native Audio model
#    → Input:  16 000 Hz PCM-16 mono   (must specify rate in mime_type)
#    → Output: 24 000 Hz PCM-16 mono   (always, regardless of request)
#    → API:    v1beta1  (LlmBidiService)
# ─────────────────────────────────────────────────────────────────────────────
MODEL   = f"projects/{PROJECT_ID}/locations/{LOCATION}/publishers/google/models/gemini-live-2.5-flash-native-audio"
API_VER = "v1beta1"

# Vertex AI BidiGenerateContent WebSocket endpoint
BASE_WSS = (
    f"wss://{LOCATION}-aiplatform.googleapis.com/ws/"
    f"google.cloud.aiplatform.{API_VER}.LlmBidiService/BidiGenerateContent"
)

# Fallback prompt — used ONLY if Laravel API unreachable
FALLBACK_SYSTEM_PROMPT = (
    "তুমি একজন প্রফেশনাল বাংলা কাস্টমার সাপোর্ট এজেন্ট। "
    "সংক্ষিপ্ত ও স্পষ্টভাবে কথা বলো।"
)

# ══════════════════════════════════════════════════════════════════════════════
#  LARAVEL INTEGRATION  —  call log + data save (same as mic-test.blade.php)
# ══════════════════════════════════════════════════════════════════════════════
LARAVEL_BASE_URL = "http://127.0.0.1"   # Laravel app URL (same server)
DEFAULT_IVR_KEY  = "1"                  # IVR key_press value to use for Asterisk calls

# AudioSocket packet types
PKT_HANGUP  = 0x00
PKT_UUID    = 0x01
PKT_AUDIO   = 0x10

# Asterisk slin frame size: 160 samples × 2 bytes = 320 bytes = 20ms @ 8 kHz
AST_FRAME_BYTES = 320


# ══════════════════════════════════════════════════════════════════════════════
#  AUTH  –  Service Account JSON → short-lived access token
#  NOTE: creds.refresh() is a blocking HTTP call — must run in thread executor
#        so it does NOT block the asyncio event loop.
# ══════════════════════════════════════════════════════════════════════════════
def _get_vertex_wss_url_sync() -> str:
    """Blocking version — call via run_in_executor only."""
    creds = service_account.Credentials.from_service_account_file(
        KEY_PATH,
        scopes=["https://www.googleapis.com/auth/cloud-platform"],
    )
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
        with urllib.request.urlopen(req, timeout=15) as resp:
            return json.loads(resp.read())
    except Exception as e:
        print(f"[HTTP] Error → {url}: {e}")
        return {}


def _get_json_sync(url: str) -> dict:
    """Blocking HTTP GET to Laravel API — call via run_in_executor only."""
    req = urllib.request.Request(url, headers={"Accept": "application/json"}, method="GET")
    try:
        with urllib.request.urlopen(req, timeout=15) as resp:
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

    # ── Audio buffer queue ────────────────────────────────────────────────────
    audio_queue: asyncio.Queue = asyncio.Queue(maxsize=500)
    stop_event = asyncio.Event()

    async def drain_asterisk():
        """
        Read ALL packets from Asterisk continuously and put audio into queue.
        Runs from the very beginning so the TCP buffer never fills up.
        """
        nonlocal call_uuid, call_log_id, service_request_id, caller_number, ivr_key
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
                        print(f"[DB] Caller resolved: {caller_number}, IVR key: {ivr_key}")
                    else:
                        # Fallback: retry once after 300ms (handles slight timing issues)
                        await asyncio.sleep(0.3)
                        cr2 = await get_json(
                            f"{LARAVEL_BASE_URL}/api/bridge/caller-by-uuid?uuid={call_uuid}"
                        )
                        if cr2.get("status") == "success" and cr2.get("caller_number"):
                            caller_number = cr2["caller_number"]
                            ivr_key       = cr2.get("ivr_key", DEFAULT_IVR_KEY)
                            print(f"[DB] Caller resolved (retry): {caller_number}, IVR key: {ivr_key}")
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

    # Start draining Asterisk immediately — before connecting to Vertex AI
    drain_task = asyncio.create_task(drain_asterisk())

    # ── Wait for caller number to be resolved (max 3s) before fetching IVR ───
    # drain_asterisk() sets caller_resolved once UUID lookup completes.
    # Without this wait, IVR fetch uses caller_number=None (race condition).
    try:
        await asyncio.wait_for(caller_resolved.wait(), timeout=3.0)
    except asyncio.TimeoutError:
        print("[DB] Caller resolve timeout — proceeding without number")

    # ── Fetch dynamic IVR prompt + voice from Laravel ─────────────────────────
    ivr_setup = await get_json(
        f"{LARAVEL_BASE_URL}/api/bridge/get-ivr-setup"
        f"?ivr_key={ivr_key}&caller_number={caller_number or ''}"
    )
    if ivr_setup.get("status") == "error" or not ivr_setup.get("prompt"):
        print("[DB] Could not fetch IVR prompt — using fallback prompt.")
        system_prompt = FALLBACK_SYSTEM_PROMPT
        voice_name    = "Charon"
    else:
        system_prompt = ivr_setup["prompt"]
        voice_name    = ivr_setup.get("voice_gender", "Charon")
        print(f"[DB] IVR prompt fetched ({len(system_prompt)} chars), voice={voice_name}")
    # ── Connect to Vertex AI ──────────────────────────────────────────────────
    try:
        wss_url   = await get_vertex_wss_url()   # runs in thread pool
        vertex_ws = await websockets.connect(
            wss_url,
            additional_headers={"Content-Type": "application/json"},
            ping_interval=20,
            ping_timeout=20,
        )
    except Exception as e:
        print(f"[Critical] Cannot connect to Vertex AI: {e}")
        stop_event.set()
        writer.close()
        await drain_task
        return

    print("[Gemini] Connected to Vertex AI WebSocket")

    # ── Send setup message (camelCase JSON as Vertex AI proto requires) ────────
    setup_msg = {
        "setup": {
            "model": MODEL,
            "generationConfig": {
                "responseModalities": ["AUDIO"],
                "speechConfig": {
                    "voiceConfig": {
                        "prebuiltVoiceConfig": {
                            "voiceName": voice_name
                        }
                    }
                },
            },
            # ━━ Enable transcription — mic-test.blade.php এর মতো data পাবো ━━
            "inputAudioTranscription":  {},   # customer এর কথা text এ → ai_text_log
            "outputAudioTranscription": {},   # AI এর কথা text এ → ai_text_log
            "systemInstruction": {
                "parts": [{"text": system_prompt}]
            },
        }
    }
    await vertex_ws.send(json.dumps(setup_msg))
    print("[Gemini] Setup message sent")

    # ── Wait for setupComplete ────────────────────────────────────────────────
    try:
        raw  = await asyncio.wait_for(vertex_ws.recv(), timeout=15.0)
        resp = json.loads(raw)
        if "setupComplete" in resp:
            print("[Gemini] Setup Complete. Ready for audio.")
        else:
            print(f"[Gemini] Unexpected setup response: {resp}")
    except asyncio.TimeoutError:
        print("[Gemini] Timeout waiting for setupComplete, continuing anyway...")

    # ── Send greeting trigger ─────────────────────────────────────────────────
    # Tells Gemini to speak first. Without this the native audio model waits
    # for the caller to speak first → caller hears silence → hangs up.
    try:
        await vertex_ws.send(json.dumps({
            "clientContent": {
                "turns": [{
                    "role": "user",
                    "parts": [{"text": "Start the conversation with your greeting message now."}]
                }],
                "turnComplete": True
            }
        }))
        print("[Gemini] Greeting trigger sent.")
    except Exception as e:
        print(f"[Gemini] Greeting trigger failed: {e}")

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
                    customer_buf.append(input_tr["text"])

                # ── On turnComplete → flush buffers as complete sentences ─────────
                # This mirrors mic-test agentChatHistory — complete turns only
                if server_content.get("turnComplete"):
                    if agent_buf:
                        full = "".join(agent_buf).strip()
                        if full:
                            ai_text_log.append(f"এজেন্ট: {full}")
                            print(f"[Agent] {full}")
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
        save_resp = await post_json(
            f"{LARAVEL_BASE_URL}/api/bridge/process-final-text",
            {
                "text":          transcript,
                "ivr_key":       ivr_key,
                "caller_number": caller_number,
            },
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
