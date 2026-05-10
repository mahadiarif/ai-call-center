import os
import json
import time
import asyncio
import aiomysql
import redis.asyncio as redis
from fastapi import FastAPI, Request, HTTPException, Query
from pydantic import BaseModel
from typing import Optional, List, Dict, Any
from dotenv import load_dotenv

# Load Laravel .env
# Assuming bridge_api.py is in /middleware/ and .env is in root
ENV_PATH = os.path.join(os.path.dirname(__file__), "..", ".env")
load_dotenv(ENV_PATH)

app = FastAPI(title="AI Call Center Bridge API", version="2.0.0")

# DB Config
DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", 3306))
DB_USER = os.getenv("DB_USERNAME", "root")
DB_PASS = os.getenv("DB_PASSWORD", "")
DB_NAME = os.getenv("DB_DATABASE", "laravel")

# Redis Config
REDIS_HOST = os.getenv("REDIS_HOST", "127.0.0.1")
REDIS_PORT = int(os.getenv("REDIS_PORT", 6379))
REDIS_PASS = os.getenv("REDIS_PASSWORD", None)

# Connection Pools
db_pool = None
redis_client = None

@app.on_event("startup")
async def startup():
    global db_pool, redis_client
    # Database Pool
    db_pool = await aiomysql.create_pool(
        host=DB_HOST, port=DB_PORT,
        user=DB_USER, password=DB_PASS,
        db=DB_NAME, autocommit=True
    )
    # Redis Client
    redis_client = redis.Redis(
        host=REDIS_HOST, port=REDIS_PORT, 
        password=REDIS_PASS, decode_responses=True
    )
    print(f"[*] Bridge API started. Connected to DB: {DB_NAME} and Redis")

@app.on_event("shutdown")
async def shutdown():
    db_pool.close()
    await db_pool.wait_closed()
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
    service_request_id: Optional[int] = None

class HangupRequest(BaseModel):
    service_request_id: Optional[int] = None
    call_log_id: Optional[int] = None

class TicketRequest(BaseModel):
    transcript: str
    service_request_id: Optional[int] = None
    caller_number: Optional[str] = None
    ivr_key: str
    existing_walton_sr: Optional[str] = None
    existing_walton_product: Optional[str] = None
    existing_walton_status: Optional[str] = None

class TransferRequest(BaseModel):
    ivr_key: str
    service_request_id: Optional[int] = None
    caller_number: Optional[str] = None
    reason: str

class FinalTextRequest(BaseModel):
    text: str
    ivr_key: str
    caller_number: Optional[str] = None
    service_request_id: Optional[int] = None
    call_type: Optional[str] = "inbound"
    survey_id: Optional[int] = None

# ── Endpoints ─────────────────────────────────────────────────────────────────

@app.get("/api/bridge/register-caller")
@app.post("/api/bridge/register-caller")
async def register_caller(
    uuid: str, 
    caller: str, 
    ivr_key: str = "1", 
    call_type: str = "inbound", 
    survey_id: Optional[int] = None
):
    """
    Registers a caller UUID mapping in Redis. 
    Called by Asterisk System(curl ...) before AudioSocket starts.
    """
    data = {
        "caller_number": caller,
        "ivr_key": ivr_key,
        "call_type": call_type,
        "survey_id": str(survey_id) if survey_id else ""
    }
    await redis_client.setex(f"call:{uuid}", 3600, json.dumps(data))
    return {"status": "success", "message": "Caller registered", "uuid": uuid}

@app.get("/api/bridge/caller-by-uuid")
async def get_caller_by_uuid(uuid: str):
    """Retrieves caller info from Redis."""
    data_raw = await redis_client.get(f"call:{uuid}")
    if not data_raw:
        return {"status": "error", "message": "UUID not found"}
    
    data = json.loads(data_raw)
    return {
        "status": "success", 
        "caller_number": data.get("caller_number"),
        "ivr_key": data.get("ivr_key"),
        "call_type": data.get("call_type", "inbound"),
        "survey_id": int(data["survey_id"]) if data.get("survey_id") else None
    }

@app.get("/api/bridge/get-ivr-setup")
async def get_ivr_setup(
    ivr_key: Optional[str] = None, 
    call_type: str = "inbound", 
    survey_id: Optional[int] = None,
    caller_number: Optional[str] = None
):
    """Fetches IVR prompt and settings from MySQL."""
    async with db_pool.acquire() as conn:
        async with conn.cursor(aiomysql.DictCursor) as cur:
            if call_type == "outbound_survey" and survey_id:
                # Survey specific prompt logic could go here
                await cur.execute("SELECT * FROM ivr_services WHERE key_press = %s", ("1",))
            else:
                await cur.execute("SELECT * FROM ivr_services WHERE key_press = %s", (ivr_key or "1",))
            
            row = await cur.fetchone()
            if not row:
                return {"status": "error", "message": "IVR key not found"}
            
            return {
                "status": "success",
                "prompt": row.get("system_prompt"),
                "voice_gender": "Charon" if "male" in row.get("service_name", "").lower() else "Kore"
            }

@app.post("/api/bridge/call-log/start")
async def start_call_log(req: CallStartRequest):
    """Creates a new call log and service request record."""
    async with db_pool.acquire() as conn:
        async with conn.cursor() as cur:
            # 1. Create Service Request
            await cur.execute(
                "INSERT INTO service_requests (mobile_number, status, created_at, updated_at) VALUES (%s, %s, NOW(), NOW())",
                (req.caller_number, "Pending")
            )
            sr_id = cur.lastrowid
            
            # 2. Create Call Log
            await cur.execute(
                "INSERT INTO call_logs (caller_number, session_id, status, created_at, updated_at) VALUES (%s, %s, %s, NOW(), NOW())",
                (req.caller_number, req.session_id, "in-progress")
            )
            log_id = cur.lastrowid
            
            return {
                "status": "success", 
                "log_id": log_id, 
                "service_request_id": sr_id
            }

@app.post("/api/bridge/call-log/end")
async def end_call_log(req: CallEndRequest):
    """Updates the call log with duration and final status."""
    async with db_pool.acquire() as conn:
        async with conn.cursor() as cur:
            await cur.execute(
                "UPDATE call_logs SET duration = %s, status = %s, updated_at = NOW() WHERE id = %s",
                (req.duration, req.status, req.log_id)
            )
            return {"status": "success"}

@app.post("/api/bridge/call-hangup")
async def call_hangup(req: HangupRequest):
    """Marks a service request as ended."""
    async with db_pool.acquire() as conn:
        async with conn.cursor() as cur:
            if req.service_request_id:
                # We might want to keep it as 'Pending' but mark call as ended
                await cur.execute(
                    "UPDATE service_requests SET status = 'AI Finished', updated_at = NOW() WHERE id = %s",
                    (req.service_request_id,)
                )
            return {"status": "success"}

@app.post("/api/bridge/pre-register-ticket")
async def pre_register_ticket(req: TicketRequest):
    """Creates a ticket record based on AI conversation so far."""
    # This is where you would normally push to Walton API or similar
    # For now, we'll just return a mock SR number
    mock_walton_sr = f"SR{int(time.time())}"
    return {
        "status": "success",
        "walton_sr": mock_walton_sr,
        "our_sr": f"WLT-{req.service_request_id}",
        "ticket_id": req.service_request_id
    }

@app.post("/api/bridge/check-walton-sr")
async def check_walton_sr(ticket_id: int):
    """Polls for Walton SR number."""
    return {"status": "success", "walton_sr": f"SR{int(time.time())}"}

@app.post("/api/bridge/transfer-to-agent")
async def transfer_to_agent(req: TransferRequest):
    """Handles escalation to a human agent."""
    print(f"[*] Escalation requested for {req.caller_number} (SR: {req.service_request_id})")
    async with db_pool.acquire() as conn:
        async with conn.cursor() as cur:
            await cur.execute(
                "UPDATE service_requests SET status = 'Escalated', problem_description = %s WHERE id = %s",
                (f"Escalation Reason: {req.reason}", req.service_request_id)
            )
            return {"status": "success"}

@app.post("/api/bridge/process-final-text")
async def process_final_text(req: FinalTextRequest):
    """Processes the final transcript and saves to DB."""
    async with db_pool.acquire() as conn:
        async with conn.cursor() as cur:
            if req.service_request_id:
                await cur.execute(
                    "UPDATE service_requests SET call_transcript = %s, updated_at = NOW() WHERE id = %s",
                    (req.text, req.service_request_id)
                )
            return {"status": "success"}

if __name__ == "__main__":
    import uvicorn
    # 4 workers, uvloop for high performance
    uvicorn.run("bridge_api:app", host="0.0.0.0", port=8001, workers=4, loop="uvloop")
