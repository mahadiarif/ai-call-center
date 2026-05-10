<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIFormController;
use App\Http\Controllers\AsteriskVoiceController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\CallLogController;

// Asterisk সার্ভার কল শুরু হলে এই এপিআই-তে হিট করবে
Route::post('/asterisk/get-greeting', [AIFormController::class, 'getGreetingAudio']);

// Asterisk কাস্টমারের কথা রেকর্ড করে অডিও ফাইল এই এপিআই-তে পাঠাবে
Route::post('/asterisk/process-audio', [AIFormController::class, 'processAudio']);

// ওয়েভ ফাইল জেনারেশন এপিআই
Route::post('/asterisk/generate-agent-voice', [AsteriskVoiceController::class, 'generateAgentVoice']);
Route::post('/asterisk/generate-customer-voice', [AsteriskVoiceController::class, 'generateCustomerVoice']);
Route::get('/asterisk/config', [AsteriskVoiceController::class, 'getAsteriskConfig']);
Route::get('/asterisk/voices', [AsteriskVoiceController::class, 'listVoices']);

// API Key ম্যানেজমেন্ট (Admin Only)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/api-keys', [ApiKeyController::class, 'show']);
    Route::post('/api-keys', [ApiKeyController::class, 'update']);
    Route::post('/api-keys/test', [ApiKeyController::class, 'test']);
});

// ══════════════════════════════════════════════════════════
//  Asterisk AudioSocket Bridge — server-to-server (no CSRF)
//  Python bridge (gemini_asterisk_bridge.py) এই routes use করে
// ══════════════════════════════════════════════════════════
Route::post('/bridge/call-log/start',     [CallLogController::class, 'startCall']);
Route::post('/bridge/call-log/end',       [CallLogController::class, 'endCall']);
Route::post('/bridge/call-hangup',        [CallLogController::class, 'immediateHangup']);
Route::post('/bridge/process-final-text', [AIFormController::class,  'processFinalText']);
Route::post('/bridge/pre-register-ticket', [AIFormController::class, 'preRegisterTicket']);
Route::post('/bridge/check-walton-sr',     [AIFormController::class, 'checkWaltonSr']);
Route::post('/bridge/transfer-to-agent',   [\App\Http\Controllers\EscalationController::class, 'transferToAgent']);
Route::get('/bridge/get-ivr-setup',       [AIFormController::class,  'getLiveSetup']);
// Asterisk dialplan → call করে caller number register করে (AudioSocket আগে)
Route::post('/bridge/register-caller',    [CallLogController::class, 'registerCaller']);
// Bridge → UUID দিয়ে caller number নাও (IP mismatch এড়াতে UUID use করা হয়)
Route::get('/bridge/caller-by-uuid',      [CallLogController::class, 'getCallerByUuid']);
// Legacy IP-based lookup (fallback)
Route::get('/bridge/caller-by-ip',        [CallLogController::class, 'getCallerByIp']);
Route::get('/bridge/customer-profile',     [CallLogController::class, 'getCustomerProfile']);

// ══════════════════════════════════════════════════════════
//  Client API Webhook — client system pushes real-time updates
//  POST /api/client-webhook/{integration_id}
// ══════════════════════════════════════════════════════════
Route::post('/client-webhook/{integrationId}', [\App\Http\Controllers\ClientWebhookController::class, 'receive']);