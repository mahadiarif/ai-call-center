<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClientApiIntegration;
use App\Models\ClientDataCache;

/**
 * ClientWebhookController
 *
 * Client → আমরা (Push):
 *   POST /api/client-webhook/{integration_id}
 *
 * দুটো কাজ:
 * 1. local client_data_cache আপডেট করো
 * 2. যদি এটা SR/QM ticket এর update হয়, local ticket এর status ও update করো
 */
class ClientWebhookController extends Controller
{
    public function receive(Request $request, int $integrationId)
    {
        $integration = ClientApiIntegration::where('id', $integrationId)
            ->where('is_active', true)
            ->first();

        if (!$integration) {
            return response()->json(['status' => 'not_found'], 404);
        }

        // ── Signature verification
        if ($integration->webhook_secret) {
            $signature = $request->header('X-Webhook-Signature')
                      ?? $request->header('X-Hub-Signature-256')
                      ?? '';
            $expected = 'sha256=' . hash_hmac('sha256', $request->getContent(), $integration->webhook_secret);
            if (!hash_equals($expected, $signature)) {
                return response()->json(['status' => 'invalid_signature'], 401);
            }
        }

        $payload  = $request->json()->all();
        $event    = $payload['event']     ?? 'updated'; // sr.updated, qm.created, customer.updated ...
        $dataType = $payload['data_type'] ?? self::guessDataType($event);
        $record   = $payload['record']    ?? $payload['data'] ?? $payload;

        if (empty($record) || !is_array($record)) {
            return response()->json(['status' => 'empty_payload'], 422);
        }

        // ── 1. client_data_cache আপডেট করো
        $externalId = (string)($record['id'] ?? $record['sr_number'] ?? $record['ticket_id'] ?? uniqid('wh_'));
        $phone = $record['mobile'] ?? $record['phone'] ?? $record['mobile_number'] ?? null;
        if ($phone) {
            $phone = preg_replace('/\D/', '', (string)$phone);
            if (strlen($phone) > 11) $phone = substr($phone, -11);
        }

        ClientDataCache::updateOrCreate(
            ['integration_id' => $integration->id, 'data_type' => $dataType, 'external_id' => $externalId],
            ['company_profile_id' => $integration->company_profile_id, 'search_key' => $phone, 'data' => $record, 'synced_at' => now()]
        );

        // ── 2. যদি এটা ticket update হয়, local ticket ও update করো
        $newStatus = $record['status'] ?? $record['ticket_status'] ?? null;
        $comment   = $record['comment'] ?? $record['remarks'] ?? $record['note'] ?? null;

        if ($newStatus || $comment) {
            self::syncToLocalTicket($dataType, $externalId, $newStatus, $comment, $record);
        }

        \Illuminate\Support\Facades\Log::info("ClientWebhook [{$integration->name}] {$dataType} | event={$event}", [
            'external_id' => $externalId,
            'status'      => $newStatus,
        ]);

        return response()->json(['status' => 'ok', 'event' => $event]);
    }

    /**
     * Event name থেকে data_type বের করো
     */
    private static function guessDataType(string $event): string
    {
        if (str_contains($event, 'sr') || str_contains($event, 'service')) return 'sr_history';
        if (str_contains($event, 'qm') || str_contains($event, 'complaint')) return 'qm_complaint';
        if (str_contains($event, 'part')) return 'qm_parts';
        if (str_contains($event, 'bill')) return 'qm_bill';
        if (str_contains($event, 'customer') || str_contains($event, 'user')) return 'customer';
        return 'other';
    }

    /**
     * client_ticket_id দিয়ে local ticket খুঁজে status + comment update করো
     */
    private static function syncToLocalTicket(
        string  $dataType,
        string  $externalId,
        ?string $newStatus,
        ?string $comment,
        array   $record
    ): void {
        $models = [
            'sr_history'   => \App\Models\SrTicket::class,
            'qm_complaint' => \App\Models\QmComplaint::class,
            'qm_parts'     => \App\Models\QmPartsQuery::class,
            'qm_bill'      => \App\Models\QmBillQuery::class,
        ];

        $modelClass = $models[$dataType] ?? null;
        if (!$modelClass) return;

        // client_ticket_id দিয়ে খোঁজো
        $ticket = $modelClass::where('client_ticket_id', $externalId)->first();
        if (!$ticket) return;

        $updates = ['client_synced_at' => now()];

        if ($newStatus) {
            // Client এর status → আমাদের status mapping
            $mappedStatus = self::mapClientStatus($newStatus);
            $updates['status']                = $mappedStatus;
            $updates['client_ticket_status']  = $newStatus; // original client status রাখো
        }

        // comment থাকলে existing comments এ append করো
        if ($comment) {
            $existing = $ticket->comments ?? '';
            $ts = now('Asia/Dhaka')->format('d/m H:i');
            $updates['comments'] = $existing . "\n[Client Update {$ts}]: {$comment}";
        }

        $ticket->update($updates);
    }

    /**
     * Client এর status string → আমাদের status এ map করো
     */
    private static function mapClientStatus(string $clientStatus): string
    {
        $s = strtolower($clientStatus);
        if (in_array($s, ['resolved', 'closed', 'completed', 'done', 'fixed'])) return 'Resolved';
        if (in_array($s, ['rejected', 'cancelled', 'denied']))                  return 'Rejected';
        if (in_array($s, ['in_progress', 'processing', 'working', 'open']))     return 'In Progress';
        if (in_array($s, ['escalated', 'escalation']))                           return 'Escalation Requested';
        return 'Pending';
    }
}

