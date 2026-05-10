<?php

namespace App\Services;

use App\Models\ClientApiIntegration;
use App\Models\ClientDataCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ClientApiPushService
 *
 * SR/QM ticket push করার আগে:
 * 1. client_data_cache চেক করো — এই mobile/barcode দিয়ে আগের SR/QM আছে কিনা
 * 2. আছে → PUT (update + share আমাদের new ticket info)
 * 3. নেই → POST (নতুন তৈরি করো)
 */
class ClientApiPushService
{
    public static function getIntegration(?int $companyProfileId): ?ClientApiIntegration
    {
        if (!$companyProfileId) return null;
        return ClientApiIntegration::where('company_profile_id', $companyProfileId)
            ->where('is_active', true)
            ->first();
    }

    // ─────────────────────────────────────────────────────────
    // SR
    // ─────────────────────────────────────────────────────────
    public static function pushSrTicket(\App\Models\SrTicket $ticket): void
    {
        $integration = self::getIntegrationFromTicket($ticket);
        if (!$integration) {
            Log::info("[ClientApiPush] SR #{$ticket->id} — no active integration found, skipping push");
            return;
        }

        $endpoints = $integration->outbound_endpoints ?? [];
        $createEndpoint = $endpoints['sr_create'] ?? null;
        $updateEndpoint = $endpoints['ticket_update'] ?? $endpoints['sr_update'] ?? null;
        if (!$createEndpoint) {
            Log::warning("[ClientApiPush] SR #{$ticket->id} — sr_create endpoint not configured in outbound_endpoints");
            return;
        }

        $payload = self::mapFields($integration, 'sr', [
            'ticket_type'         => 'SR',
            'customer_name'       => $ticket->customer_name,
            'mobile_number'       => $ticket->mobile_number,
            'alt_mobile_number'   => $ticket->alt_mobile_number,
            'address'             => $ticket->address,
            'district'            => $ticket->district,
            'product_name'        => $ticket->product_name,
            'product_model'       => $ticket->product_model,
            'barcode'             => $ticket->barcode,
            'serial_number'       => $ticket->serial_number,
            'problem_description' => $ticket->problem_description,
            'service_center'      => $ticket->service_center,
            'brand'               => $ticket->brand,
            'status'              => $ticket->status,
            'our_ticket_id'       => $ticket->id,
            'created_at'          => $ticket->created_at?->toIso8601String(),
        ]);

        // Emergency SR — Walton API তে CALL_TYPE=EMERGENCY পাঠানো হবে
        if ($ticket->is_emergency) {
            $payload['CALL_TYPE'] = 'EMERGENCY';
        }

        // ── Check: client এ এই mobile/barcode দিয়ে SR আগে থেকে আছে?
        $existing = self::findExistingInCache(
            $integration, 'sr_history',
            phone: $ticket->mobile_number,
            barcode: $ticket->barcode
        );

        if ($existing && $updateEndpoint) {
            // ✅ পুরানো SR আছে — UPDATE করো + আমাদের নতুন তথ্য share করো
            $updateUrl = str_replace('{id}', $existing->external_id, $updateEndpoint);
            $payload['existing_client_sr_id'] = $existing->external_id;
            $payload['update_reason']         = 'new_call_received';
            self::push($integration, $updateUrl, $payload, "SR #{$ticket->id} UPDATE existing={$existing->external_id}", 'PATCH', $ticket);
        } else {
            // ✅ নতুন SR — CREATE করো (Walton insert.php = PATCH + JSON)
            self::push($integration, $createEndpoint, $payload, "SR #{$ticket->id} CREATE", 'PATCH', $ticket);
        }
    }

    // ─────────────────────────────────────────────────────────
    // QM Complaint
    // ─────────────────────────────────────────────────────────
    public static function pushQmComplaint(\App\Models\QmComplaint $ticket): void
    {
        $integration = self::getIntegrationFromTicket($ticket);
        if (!$integration) {
            Log::info("[ClientApiPush] QM_COMPLAINT #{$ticket->id} — no active integration found, skipping push");
            return;
        }

        $endpoints      = $integration->outbound_endpoints ?? [];
        $createEndpoint = $endpoints['qm_complaint_create'] ?? $endpoints['qm_create'] ?? null;
        $updateEndpoint = $endpoints['ticket_update'] ?? $endpoints['qm_update'] ?? null;
        if (!$createEndpoint) {
            Log::warning("[ClientApiPush] QM_COMPLAINT #{$ticket->id} — qm_complaint_create endpoint not configured");
            return;
        }

        $payload = self::mapFields($integration, 'qm_complaint', [
            'ticket_type'        => 'QM_COMPLAINT',
            'customer_name'      => $ticket->customer_name,
            'mobile_number'      => $ticket->mobile_number,
            'address'            => $ticket->address,
            'district'           => $ticket->district,
            'sr_reference'       => $ticket->sr_reference,
            'complaint_category' => $ticket->complaint_category,
            'person_name'        => $ticket->person_name,
            'showroom_address'   => $ticket->showroom_address,
            'incident_date'      => $ticket->incident_date,
            'complaint_details'  => $ticket->complaint_details,
            'status'             => $ticket->status,
            'our_ticket_id'      => $ticket->id,
            'created_at'         => $ticket->created_at?->toIso8601String(),
        ]);

        $existing = self::findExistingInCache($integration, 'qm_complaint', phone: $ticket->mobile_number);

        if ($existing && $updateEndpoint) {
            $updateUrl = str_replace('{id}', $existing->external_id, $updateEndpoint);
            $payload['existing_client_qm_id'] = $existing->external_id;
            $payload['update_reason']         = 'new_complaint_received';
            self::push($integration, $updateUrl, $payload, "QM_COMPLAINT #{$ticket->id} UPDATE existing={$existing->external_id}", 'PUT', $ticket);
        } else {
            self::push($integration, $createEndpoint, $payload, "QM_COMPLAINT #{$ticket->id} CREATE", 'POST', $ticket);
        }
    }

    // ─────────────────────────────────────────────────────────
    // QM Parts Query
    // ─────────────────────────────────────────────────────────
    public static function pushQmPartsQuery(\App\Models\QmPartsQuery $ticket): void
    {
        $integration = self::getIntegrationFromTicket($ticket);
        if (!$integration) {
            Log::info("[ClientApiPush] QM_PARTS #{$ticket->id} — no active integration found, skipping push");
            return;
        }

        $endpoints      = $integration->outbound_endpoints ?? [];
        $createEndpoint = $endpoints['qm_parts_create'] ?? $endpoints['qm_create'] ?? null;
        $updateEndpoint = $endpoints['ticket_update'] ?? $endpoints['qm_update'] ?? null;
        if (!$createEndpoint) {
            Log::warning("[ClientApiPush] QM_PARTS #{$ticket->id} — qm_parts_create endpoint not configured");
            return;
        }

        $payload = self::mapFields($integration, 'qm_parts', [
            'ticket_type'             => 'QM_PARTS',
            'customer_name'           => $ticket->customer_name,
            'mobile_number'           => $ticket->mobile_number,
            'address'                 => $ticket->address,
            'district'                => $ticket->district,
            'product_name'            => $ticket->product_name,
            'product_model'           => $ticket->product_model,
            'parts_name'              => $ticket->parts_name,
            'preferred_service_point' => $ticket->preferred_service_point,
            'status'                  => $ticket->status,
            'our_ticket_id'           => $ticket->id,
            'created_at'              => $ticket->created_at?->toIso8601String(),
        ]);

        $existing = self::findExistingInCache($integration, 'qm_parts', phone: $ticket->mobile_number);

        if ($existing && $updateEndpoint) {
            $updateUrl = str_replace('{id}', $existing->external_id, $updateEndpoint);
            self::push($integration, $updateUrl, $payload, "QM_PARTS #{$ticket->id} UPDATE", 'PUT', $ticket);
        } else {
            self::push($integration, $createEndpoint, $payload, "QM_PARTS #{$ticket->id} CREATE", 'POST', $ticket);
        }
    }

    // ─────────────────────────────────────────────────────────
    // QM Bill Query
    // ─────────────────────────────────────────────────────────
    public static function pushQmBillQuery(\App\Models\QmBillQuery $ticket): void
    {
        $integration = self::getIntegrationFromTicket($ticket);
        if (!$integration) {
            Log::info("[ClientApiPush] QM_BILL #{$ticket->id} — no active integration found, skipping push");
            return;
        }

        $endpoints      = $integration->outbound_endpoints ?? [];
        $createEndpoint = $endpoints['qm_bill_create'] ?? $endpoints['qm_create'] ?? null;
        $updateEndpoint = $endpoints['ticket_update'] ?? $endpoints['qm_update'] ?? null;
        if (!$createEndpoint) {
            Log::warning("[ClientApiPush] QM_BILL #{$ticket->id} — qm_bill_create endpoint not configured");
            return;
        }

        $payload = self::mapFields($integration, 'qm_bill', [
            'ticket_type'        => 'QM_BILL',
            'customer_name'      => $ticket->customer_name,
            'mobile_number'      => $ticket->mobile_number,
            'address'            => $ticket->address,
            'district'           => $ticket->district,
            'sr_reference'       => $ticket->sr_reference,
            'product_name'       => $ticket->product_name,
            'bill_query_details' => $ticket->bill_query_details,
            'status'             => $ticket->status,
            'our_ticket_id'      => $ticket->id,
            'created_at'         => $ticket->created_at?->toIso8601String(),
        ]);

        $existing = self::findExistingInCache($integration, 'qm_bill', phone: $ticket->mobile_number);

        if ($existing && $updateEndpoint) {
            $updateUrl = str_replace('{id}', $existing->external_id, $updateEndpoint);
            self::push($integration, $updateUrl, $payload, "QM_BILL #{$ticket->id} UPDATE", 'PUT', $ticket);
        } else {
            self::push($integration, $createEndpoint, $payload, "QM_BILL #{$ticket->id} CREATE", 'POST', $ticket);
        }
    }

    // ─────────────────────────────────────────────────────────
    // Ticket status update (manual trigger from admin)
    // ─────────────────────────────────────────────────────────
    public static function pushStatusUpdate(
        string  $ticketType,
        int     $ticketId,
        ?string $clientId,
        string  $newStatus,
        int     $companyId
    ): void {
        $integration = self::getIntegration($companyId);
        if (!$integration) return;

        $endpoints = $integration->outbound_endpoints ?? [];
        $endpoint  = $endpoints['ticket_update'] ?? null;
        if (!$endpoint || !$clientId) return;

        $url = str_replace('{id}', $clientId, $endpoint);

        self::push($integration, $url, [
            'ticket_type'   => $ticketType,
            'our_ticket_id' => $ticketId,
            'client_id'     => $clientId,
            'status'        => $newStatus,
            'updated_at'    => now()->toIso8601String(),
        ], "{$ticketType} #{$ticketId} status→{$newStatus}", 'PUT');
    }

    // ─────────────────────────────────────────────────────────
    // Core: client_data_cache থেকে existing record খোঁজো
    // ─────────────────────────────────────────────────────────
    /**
     * @param string      $dataType  sr_history | qm_complaint | qm_parts | qm_bill | customer
     * @param string|null $phone     mobile number (normalized)
     * @param string|null $barcode   product barcode (optional 2nd key)
     */
    private static function findExistingInCache(
        ClientApiIntegration $integration,
        string  $dataType,
        ?string $phone = null,
        ?string $barcode = null
    ): ?\App\Models\ClientDataCache {
        if (!$phone && !$barcode) return null;

        $phone = $phone ? preg_replace('/\D/', '', $phone) : null;
        if ($phone && strlen($phone) > 11) $phone = substr($phone, -11);

        $query = ClientDataCache::where('integration_id', $integration->id)
            ->where('data_type', $dataType);

        if ($phone) {
            $query->where('search_key', $phone);
        }

        if ($barcode && !$phone) {
            $query->orWhere('search_key2', $barcode);
        }

        return $query->latest('synced_at')->first();
    }

    // ─────────────────────────────────────────────────────────
    // Walton Product Name Normalizer
    // AI বাংলা/English product name → Walton API accepted values
    // ─────────────────────────────────────────────────────────
    public static function normalizeWaltonProduct(?string $product): ?string
    {
        if (empty($product)) return $product;

        $p = mb_strtolower(trim($product));

        // ফ্রিজ / refrigerator
        if (preg_match('/ফ্রিজ|fridge|refri|রেফ্রি/u', $p))         return 'REFRIGERATOR';
        // ফ্রিজার
        if (preg_match('/ফ্রিজার|freezer/u', $p))                    return 'FREEZER';
        // এসি / air conditioner
        if (preg_match('/এসি|এ\.সি|air.?con|aircond|a\.c\b|^ac$/u', $p)) return 'AIRCONDITIONER';
        // টেলিভিশন
        if (preg_match('/led.smart|smart.tv|স্মার্ট.টিভি/u', $p))   return 'LED SMART TELEVISION';
        if (preg_match('/3d.tv|3d.tele/u', $p))                      return '3D TELEVISION';
        if (preg_match('/led.tv|led.tele/u', $p))                    return 'LED TELEVISION';
        if (preg_match('/color.tv|colour.tv/u', $p))                 return 'COLOR TELEVISION';
        if (preg_match('/টিভি|টেলিভিশন|television|tele|^tv$/u', $p)) return 'LED TELEVISION';
        // চিলার / বেভারেজ
        if (preg_match('/chiller|চিলার/u', $p))                      return 'CHILLER';
        if (preg_match('/beverage|বেভারেজ/u', $p))                   return 'BEVERAGE COOLER';
        // ফ্যান রেগুলেটর
        if (preg_match('/fan.reg|ফ্যান.রেগ/u', $p))                  return 'Fan Regulator';
        // TV Tuner
        if (preg_match('/tuner|টিউনার/u', $p))                       return 'TV TUNER';

        // Already valid Walton product value — return as-is
        $waltonProducts = [
            'LCD TELEVISION','TELEVISION','AIRCONDITIONER','3D TELEVISION',
            'BEVERAGE COOLER','LED TELEVISION','CHILLER','REFRIGERATOR',
            'LED SMART TELEVISION','FREEZER','TV TUNER','COLOR TELEVISION',
            'AIRCONDITIONER-INDOOR','AIRCONDITIONER-OUTDOOR','VRF AIR CONDITIONER',
            'Fan Regulator',
        ];
        foreach ($waltonProducts as $wp) {
            if (mb_strtolower($wp) === $p) return $wp;
        }

        // Unknown — return original (client API will handle/reject)
        return strtoupper($product);
    }

    // ─────────────────────────────────────────────────────────
    // Walton WARRANTY_STATUS normalizer
    // "হ্যাঁ"/"yes"/true → "1", "না"/"no"/false → "0"
    // ─────────────────────────────────────────────────────────
    private static function normalizeWarranty(mixed $warranty): string
    {
        if (is_null($warranty)) return '0';
        $v = mb_strtolower(trim((string)$warranty));
        if (in_array($v, ['1','yes','true','হ্যাঁ','আছে','warranty','within warranty','in warranty'], true)) return '1';
        return '0';
    }

    // ─────────────────────────────────────────────────────────
    // Field mapping
    // Supports: __static:VALUE for hardcoded values
    // ─────────────────────────────────────────────────────────
    private static function mapFields(ClientApiIntegration $integration, string $type, array $payload): array
    {
        $fm = $integration->field_mapping ?? [];
        if (is_string($fm)) {
            $fm = json_decode($fm, true) ?? [];
        }
        // Support both wrapped {"sr":{...}} and flat {"customer_name":"CUSTOMER_NAME"} formats
        $mapping = $fm[$type] ?? $fm['global'] ?? $fm;
        if (!is_array($mapping)) $mapping = [];
        if (empty($mapping)) return $payload;

        $mapped = [];

        // Step 1: our fields → client field names (skip __static keys from payload side)
        foreach ($payload as $ourKey => $value) {
            $clientKey = $mapping[$ourKey] ?? $ourKey;
            if (is_string($clientKey) && str_starts_with($clientKey, '__static:')) continue;
            $mapped[$clientKey] = $value;
        }

        // Step 2: inject __static fields — mapping এ যেগুলোর value "__static:VALUE"
        foreach ($mapping as $fieldName => $directive) {
            if (is_string($directive) && str_starts_with($directive, '__static:')) {
                $mapped[$fieldName] = substr($directive, 9); // "walton", API key, "" etc
            }
        }

        // Step 3: PRODUCT normalize
        if (isset($mapped['PRODUCT'])) {
            $mapped['PRODUCT'] = self::normalizeWaltonProduct($mapped['PRODUCT']);
        }
        foreach ($mapping as $ourKey => $clientKey) {
            if ($ourKey === 'product_name' && is_string($clientKey) && isset($mapped[$clientKey])) {
                $mapped[$clientKey] = self::normalizeWaltonProduct($mapped[$clientKey]);
            }
        }

        // Step 4: WARRANTY_STATUS normalize → "1" or "0"
        if (isset($mapped['WARRANTY_STATUS'])) {
            $mapped['WARRANTY_STATUS'] = self::normalizeWarranty($mapped['WARRANTY_STATUS']);
        }
        foreach ($mapping as $ourKey => $clientKey) {
            if ($ourKey === 'warranty' && is_string($clientKey) && isset($mapped[$clientKey])) {
                $mapped[$clientKey] = self::normalizeWarranty($mapped[$clientKey]);
            }
        }

        // Step 5: SERVICE_CENTER — name হলে cached code তে convert করো
        if (isset($mapped['SERVICE_CENTER'])) {
            $mapped['SERVICE_CENTER'] = self::resolveServiceCenterCode($mapped['SERVICE_CENTER'], $integration);
        }

        // Step 6: null/empty string fields — Walton expects "" not null
        foreach ($mapped as $k => $v) {
            if (is_null($v)) $mapped[$k] = '';
        }

        return $mapped;
    }

    // ─────────────────────────────────────────────────────────
    // Service Center name → Walton code
    // ─────────────────────────────────────────────────────────
    private static function resolveServiceCenterCode(?string $value, ClientApiIntegration $integration): string
    {
        if (empty($value)) return '';
        // Already numeric code
        if (is_numeric($value)) return $value;

        // Cache থেকে match করো
        $centers = ClientDataCache::where('integration_id', $integration->id)
            ->where('data_type', 'service_center')
            ->get();

        $valueLower = mb_strtolower(trim($value));
        foreach ($centers as $sc) {
            $d    = $sc->raw_data ?? [];
            $code = $d['SERVICE_CENTER_ID'] ?? $d['code'] ?? $d['id'] ?? $sc->external_id ?? '';
            $name = mb_strtolower($d['SERVICE_CENTER_NAME'] ?? $d['name'] ?? '');
            $area = mb_strtolower($d['district'] ?? $d['area'] ?? $d['ADDRESS'] ?? '');

            if ($code && (
                $name === $valueLower ||
                $area === $valueLower ||
                str_contains($name, $valueLower) ||
                str_contains($area, $valueLower)
            )) {
                Log::info("[ServiceCenter] '{$value}' → '{$code}'");
                return (string)$code;
            }
        }

        // Match না হলে original দাও
        Log::info("[ServiceCenter] No match for '{$value}' — sending as-is");
        return $value;
    }

    // ─────────────────────────────────────────────────────────
    // HTTP push
    // Walton API = x-www-form-urlencoded (not JSON)
    // ─────────────────────────────────────────────────────────
    private static function push(
        ClientApiIntegration $integration,
        string  $endpoint,
        array   $payload,
        string  $label,
        string  $method = 'POST',
        $ticketModel = null
    ): void {
        $url = str_starts_with($endpoint, 'http') ? $endpoint
             : rtrim($integration->api_base_url, '/') . '/' . ltrim($endpoint, '/');

        try {
            // Walton insert.php expects raw JSON body with PATCH method
            $request = Http::asJson()
                ->timeout(15);

            $response = $request->$method($url, $payload);

            Log::info("[ClientApiPush] {$label} → {$url}", [
                'status' => $response->status(),
                'body'   => substr($response->body(), 0, 500),
            ]);

            if ($response->successful()) {
                if ($ticketModel) {
                    $json = $response->json() ?? [];

                    // Walton insert.php returns array: [{"message":"Success","srNo":"...","code":201}]
                    $data = is_array($json) && isset($json[0]) ? $json[0] : $json;

                    // Walton response fields: srNO (capital O) | srNo | SERVICE_NO
                    $clientId = $data['srNO']        // ← Walton actual key (capital O)
                             ?? $data['srNo']
                             ?? $data['SERVICE_NO']
                             ?? $data['SR_NO']
                             ?? $data['sr_no']
                             ?? $data['id']
                             ?? $data['ticket_id']
                             ?? $data['data']['SERVICE_NO']
                             ?? null;

                    $clientStatus = $data['SERVICE_STATUS']
                                 ?? $data['status']
                                 ?? $data['STATUS']
                                 ?? null;

                    if ($clientId) {
                        $ticketModel->update([
                            'client_ticket_id'     => (string)$clientId,
                            'client_ticket_status' => $clientStatus,
                            'client_synced_at'     => now(),
                        ]);
                        Log::info("[ClientApiPush] ✅ Saved client SR: {$clientId}");
                    } else {
                        // Response এ SR number না থাকলে full response log করো
                        Log::warning("[ClientApiPush] ⚠️ No SR number in response", ['body' => $json]);
                    }
                }
            } else {
                Log::warning("[ClientApiPush] ⚠️ {$label} HTTP {$response->status()}", ['body' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error("[ClientApiPush] ❌ {$label}: " . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────
    // Walton SR Lookup — webCrmSrSearch.php দিয়ে SR details আনো
    // POST x-www-form-urlencoded: srNo=xxxx অথবা CUSTOMER_MOBILE=xxxx
    // ─────────────────────────────────────────────────────────
    public static function lookupWaltonSr(\App\Models\SrTicket $ticket): ?array
    {
        $integration = self::getIntegrationFromTicket($ticket);
        if (!$integration) return null;

        $endpoints  = $integration->outbound_endpoints ?? [];
        $searchPath = $endpoints['sr_search'] ?? 'webCrmSrSearch.php';
        $url = str_starts_with($searchPath, 'http') ? $searchPath
             : rtrim($integration->api_base_url, '/') . '/' . ltrim($searchPath, '/');

        $searchPayload = [
            'username' => 'walton',
            'key'      => 'xHj0LoH!9%4VVWYWQilrti',
        ];

        // SR number থাকলে সেটা দিয়ে, না হলে mobile দিয়ে search
        if (!empty($ticket->client_ticket_id)) {
            $searchPayload['srNo'] = $ticket->client_ticket_id;
        } elseif (!empty($ticket->mobile_number)) {
            $searchPayload['CUSTOMER_MOBILE'] = $ticket->mobile_number;
        } else {
            return null;
        }

        try {
            $response = Http::asForm()->timeout(10)->post($url, $searchPayload);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && isset($data[0])) return $data[0];
                if (is_array($data) && !empty($data['SERVICE_NO'])) return $data;
            }
            Log::warning("[WaltonLookup] HTTP {$response->status()}", ['body' => $response->body()]);
        } catch (\Throwable $e) {
            Log::error("[WaltonLookup] ❌ " . $e->getMessage());
        }
        return null;
    }

    // ─────────────────────────────────────────────────────────
    // Integration lookup from ticket model
    // ─────────────────────────────────────────────────────────
    private static function getIntegrationFromTicket($ticket): ?ClientApiIntegration
    {
        $ivrServiceId = $ticket->ivr_service_id ?? null;
        if ($ivrServiceId) {
            $ivrService = \App\Models\IvrService::find($ivrServiceId);
            if ($ivrService) {
                $integration = self::getIntegration($ivrService->company_profile_id ?? null);
                if ($integration) return $integration;
            }
        }

        // Fallback: first active integration (single-company setup)
        return ClientApiIntegration::where('is_active', true)->first();
    }
}
