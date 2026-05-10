import os
import json
import time
import asyncio
import aiomysql
import redis.asyncio as redis
from fastapi import FastAPI, Request, HTTPException
from pydantic import BaseModel
from typing import Optional, List, Dict, Any
from dotenv import load_dotenv

# Load Environment
ENV_PATH = os.path.join(os.path.dirname(__file__), "..", ".env")
load_dotenv(ENV_PATH)

app = FastAPI(title="AI Telephony Bridge API", version="2.1.0")

# DB & Redis Config
DB_CONFIG = {
    "host": os.getenv("DB_HOST", "127.0.0.1"),
    "port": int(os.getenv("DB_PORT", 3306)),
    "user": os.getenv("DB_USERNAME", "root"),
    "password": os.getenv("DB_PASSWORD", ""),
    "db": os.getenv("DB_DATABASE", "laravel"),
    "autocommit": True
}

REDIS_CONFIG = {
    "host": os.getenv("REDIS_HOST", "127.0.0.1"),
    "port": int(os.getenv("REDIS_PORT", 6379)),
    "password": os.getenv("REDIS_PASSWORD", None),
    "decode_responses": True
}

db_pool = None
redis_client = None

@app.on_event("startup")
async def startup():
    global db_pool, redis_client
    db_pool = await aiomysql.create_pool(**DB_CONFIG)
    redis_client = redis.Redis(**REDIS_CONFIG)
    print(f"[*] Bridge API Ready. Database: {DB_CONFIG['db']}")

@app.on_event("shutdown")
async def shutdown():
    if db_pool:
        db_pool.close()
        await db_pool.wait_closed()
    if redis_client:
        await redis_client.close()

# ── Models ────────────────────────────────────────────────────────────────────

class CallStartRequest(BaseModel):
    ivr_key: str
    session_id: str
    caller_number: Optional[str] = None

class CallEndRequest(BaseModel):
    log_id: int
    duration: int
    status: str

class HangupRequest(BaseModel):
    service_request_id: Optional[int] = None
    call_log_id: Optional[int] = None

class FinalTextRequest(BaseModel):
    text: str
    caller_number: Optional[str] = None
    service_request_id: Optional[int] = None

# ── Endpoints ─────────────────────────────────────────────────────────────────

@app.post("/api/bridge/register-caller")
@app.get("/api/bridge/register-caller")
async def register_caller(uuid: str, caller: str, ivr_key: str = "1", call_type: str = "inbound", survey_id: Optional[int] = None):
    """Registers caller metadata in Redis for AudioSocket lookup."""
    data = {"caller_number": caller, "ivr_key": ivr_key, "call_type": call_type, "survey_id": str(survey_id or "")}
    await redis_client.setex(f"call:{uuid}", 3600, json.dumps(data))
    return {"status": "success", "uuid": uuid}

@app.get("/api/bridge/caller-by-uuid")
async def get_caller_by_uuid(uuid: str):
    """Retrieves caller metadata from Redis."""
    data_raw = await redis_client.get(f"call:{uuid}")
    if not data_raw:
        return {"status": "error", "message": "Session not found"}
    data = json.loads(data_raw)
    return {
        "status": "success", 
        "caller_number": data.get("caller_number"),
        "ivr_key": data.get("ivr_key"),
        "call_type": data.get("call_type", "inbound"),
        "survey_id": int(data["survey_id"]) if data.get("survey_id") else None
    }

@app.get("/api/bridge/get-ivr-setup")
async def get_ivr_setup(ivr_key: Optional[str] = None, caller_number: Optional[str] = None):
    """Fetches personalized IVR settings and prompts."""
    async with db_pool.acquire() as conn:
        async with conn.cursor(aiomysql.DictCursor) as cur:
            # 1. Recognize Caller
            customer_name = None
            if caller_number:
                await cur.execute(
                    "SELECT customer_name FROM service_requests WHERE mobile_number = %s AND customer_name IS NOT NULL ORDER BY created_at DESC LIMIT 1",
                    (caller_number.lstrip("+"),)
                )
                res = await cur.fetchone()
                customer_name = res.get("customer_name") if res else None

            # 2. Fetch Service Rules
            await cur.execute("SELECT * FROM ivr_services WHERE key_press = %s AND is_active = 1", (ivr_key or "1",))
            row = await cur.fetchone() or (await cur.execute("SELECT * FROM ivr_services WHERE is_active = 1 LIMIT 1") or await cur.fetchone())
            
            if not row:
                return {"status": "error", "message": "No active service found"}
            
            # 3. Format Fields
            fields_instruction = ""
            if row.get("required_fields"):
                try:
                    fields = json.loads(row["required_fields"]) if isinstance(row["required_fields"], str) else row["required_fields"]
                    if isinstance(fields, list):
                        fields_instruction = "\n\nসংগ্রহযোগ্য তথ্য:\n" + "\n".join([f"👉 {f['field_name']} {'(জরুরি)' if f.get('is_mandatory') else '(ঐচ্ছিক)'}: {f.get('ai_instruction','')}" for f in fields])
                except: pass

            # 4. Personalized Greeting
            greeting = row.get("greeting_message") or "স্বাগতম।"
            hour = time.localtime().tm_hour
            time_text = "শুভ সকাল" if 5 <= hour < 12 else "শুভ দুপুর" if 12 <= hour < 17 else "শুভ সন্ধ্যা" if 17 <= hour < 20 else "শুভ রাত"
            greeting = greeting.replace("{time_greeting}", time_text)
            if customer_name:
                greeting = f"{time_text} {customer_name} সাহেব! " + greeting.replace(time_text, "").strip()

            return {
                "status": "success",
                "prompt": (row.get("system_prompt") or "") + fields_instruction,
                "voice_gender": row.get("voice_gender") or "Charon",
                "voice_speed": float(row.get("voice_speed") or 1.0),
                "greeting": greeting
            }

@app.post("/api/bridge/log-performance")
async def log_performance(req: Request):
    """Logs AI latency and performance metrics."""
    data = await req.json()
    async with db_pool.acquire() as conn:
        async with conn.cursor() as cur:
            await cur.execute(
                "INSERT INTO ai_performance_logs (session_id, provider, latency_ms, status, created_at, updated_at) VALUES (%s, %s, %s, %s, NOW(), NOW())",
                (data.get("session_id"), data.get("provider"), data.get("latency_ms"), data.get("status"))
            )
            return {"status": "success"}

@app.post("/api/bridge/call-log/start")
async def start_call_log(req: CallStartRequest):
    async with db_pool.acquire() as conn:
        async with conn.cursor() as cur:
            await cur.execute("INSERT INTO service_requests (mobile_number, status, created_at, updated_at) VALUES (%s, 'Pending', NOW(), NOW())", (req.caller_number,))
            sr_id = cur.lastrowid
            await cur.execute("INSERT INTO call_logs (caller_number, session_id, status, created_at, updated_at) VALUES (%s, %s, 'in-progress', NOW(), NOW())", (req.caller_number, req.session_id))
            return {"status": "success", "log_id": cur.lastrowid, "service_request_id": sr_id}

@app.post("/api/bridge/call-log/end")
async def end_call_log(req: CallEndRequest):
    async with db_pool.acquire() as conn:
        async with conn.cursor() as cur:
            await cur.execute("UPDATE call_logs SET duration = %s, status = %s, updated_at = NOW() WHERE id = %s", (req.duration, req.status, req.log_id))
            return {"status": "success"}

@app.post("/api/bridge/call-hangup")
async def call_hangup(req: HangupRequest):
    if req.service_request_id:
        async with db_pool.acquire() as conn:
            async with conn.cursor() as cur:
                await cur.execute("UPDATE service_requests SET status = 'AI Finished', updated_at = NOW() WHERE id = %s", (req.service_request_id,))
    return {"status": "success"}

@app.post("/api/bridge/process-final-text")
async def process_final_text(req: FinalTextRequest):
    async with db_pool.acquire() as conn:
        async with conn.cursor() as cur:
            if req.service_request_id:
                await cur.execute("UPDATE service_requests SET call_transcript = %s, updated_at = NOW() WHERE id = %s", (req.text, req.service_request_id))
                sms_msg = f"প্রিয় গ্রাহক, আপনার কলটি রেকর্ড করা হয়েছে। আইডি: SR-{req.service_request_id}। ধন্যবাদ।"
                await cur.execute("INSERT INTO sms_logs (mobile_number, message, status, created_at, updated_at) VALUES (%s, %s, 'pending', NOW(), NOW())", (req.caller_number, sms_msg))
            return {"status": "success"}

# ── Walton Integration Placeholders ──
@app.post("/api/bridge/pre-register-ticket")
async def pre_register_ticket(req: Request):
    return {"status": "success", "walton_sr": f"SR{int(time.time())}", "ticket_id": 0}

@app.post("/api/bridge/check-walton-sr")
async def check_walton_sr(req: Request):
    return {"status": "success", "walton_sr": f"SR{int(time.time())}"}

@app.post("/api/bridge/transfer-to-agent")
async def transfer_to_agent(req: Request):
    return {"status": "success"}

if __name__ == "__main__":
    import uvicorn
    uvicorn.run("bridge_api:app", host="127.0.0.1", port=8001, workers=1, loop="uvloop")
