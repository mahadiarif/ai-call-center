<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIFormController;
use App\Models\IvrService;

Route::get('/', function () { 
    $ivrServices = IvrService::where('is_active', true)->orderBy('key_press')->get();
    return view('mic-test', ['ivrServices' => $ivrServices]); 
});
// ☎️ Asterisk AGI থেকে call আসলে এই URL খুলবে: /?caller=01XXXXXXXXX&ivr=1
Route::get('/ai-call', function () { 
    $ivrServices = IvrService::where('is_active', true)->orderBy('key_press')->get();
    return view('mic-test', ['ivrServices' => $ivrServices]); 
});
Route::post('/process-audio', [AIFormController::class, 'processAudio']);
Route::get('/reset-session', [App\Http\Controllers\AIFormController::class, 'resetSession']);
Route::post('/get-greeting-audio', [App\Http\Controllers\AIFormController::class, 'getGreetingAudio']);
Route::get('/get-live-setup', [AIFormController::class, 'getLiveSetup']);
Route::post('/save-ticket-live', [\App\Http\Controllers\AIFormController::class, 'saveTicketLive']);
Route::get('/ai-tickets-list', [App\Http\Controllers\AIFormController::class, 'viewAiTickets']);
Route::post('/stt-recognize', [App\Http\Controllers\AIFormController::class, 'recognizeSpeech']);
Route::post('/process-audio-data', [\App\Http\Controllers\AIFormController::class, 'processAudioData']);
Route::post('/process-final-text', [\App\Http\Controllers\AIFormController::class, 'processFinalText']);

// 📞 Call Log API — কল শুরু ও শেষে ফ্রন্টএন্ড থেকে call করবে
Route::post('/call-log/start', [\App\Http\Controllers\CallLogController::class, 'startCall']);
Route::post('/call-log/end',   [\App\Http\Controllers\CallLogController::class, 'endCall']);

// 🚨 Escalation API — রাগী কাস্টমার হলে agent এ transfer
Route::post('/escalation/transfer', [\App\Http\Controllers\EscalationController::class, 'transferToAgent']);
Route::post('/escalation/call-agent', [\App\Http\Controllers\EscalationController::class, 'callAgent']);

Route::get('/test-ivr/{key}', function ($key) {
    $service = IvrService::where('key_press', $key)->where('is_active', true)->first();

    if (!$service) {
        return response()->json(['message' => 'দুঃখিত, এই বাটনের জন্য কোনো সার্ভিস নেই!']);
    }

    $fields_instruction = "তোমার কাস্টমারের কাছ থেকে নিচের তথ্যগুলো সিরিয়াল অনুযায়ী সংগ্রহ করতে হবে:\n";
    
    if($service->required_fields) {
        foreach ($service->required_fields as $field) {
            $mandatory = $field['is_mandatory'] ? '(অবশ্যই নিতে হবে)' : '(ঐচ্ছিক)';
            $fields_instruction .= "👉 {$field['field_name']} {$mandatory}: {$field['ai_instruction']}\n";
        }
    }

    $final_ai_prompt = $service->system_prompt . "\n\n" . $fields_instruction;

    return response()->json([
        'status' => 'Success',
        'service_name' => $service->service_name,
        'final_ai_prompt' => $final_ai_prompt
    ]);
});
