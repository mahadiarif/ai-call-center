<?php

namespace App\Services;

use App\Models\ClientApiIntegration;
use App\Models\ClientDataCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ClientApiDiscoveryService
 *
 * Client এর API URL দিলে এই service:
 * 1. সব common endpoint probe করে SR/QM খুঁজে বের করে
 * 2. Field structure analyze করে
 * 3. discovered_schema তে save করে
 * 4. sync_endpoints ও outbound_endpoints auto-populate করে
 * 5. সব data clone করে client_data_cache তে রাখে
 */
class ClientApiDiscoveryService
{
    // Common SR endpoint patterns — সব ERP/CRM এ এরকম থাকে
    private static array $SR_PROBE_PATHS = [
        '/api/sr', '/api/service-requests', '/api/service_requests',
        '/api/v1/sr', '/api/v1/service-requests',
        '/api/tickets', '/api/service-ticket',
        '/api/repair', '/api/repairs',
        '/api/sr/list', '/api/service-requests/list',
        '/service-requests', '/sr/list',
    ];

    // Common QM/Complaint endpoint patterns
    private static array $QM_PROBE_PATHS = [
        '/api/qm', '/api/complaints', '/api/qm-complaints',
        '/api/v1/qm', '/api/v1/complaints',
        '/api/quality', '/api/quality-management',
        '/api/complaint', '/api/issues',
        '/api/parts', '/api/qm/parts', '/api/spare-parts',
        '/api/bill', '/api/billing', '/api/bill-queries',
    ];

    // Common customer data patterns
    private static array $CUSTOMER_PROBE_PATHS = [
        '/api/customers', '/api/users', '/api/clients',
        '/api/v1/customers', '/api/customer/list',
    ];

    // Common status update patterns
    private static array $UPDATE_PROBE_PATHS = [
        '/api/sr/{id}', '/api/service-requests/{id}',
        '/api/tickets/{id}', '/api/ticket/{id}/status',
        '/api/sr/{id}/status', '/api/sr/{id}/update',
    ];

    /**
     * Main: API auto-discover করো ও schema save করো
     */
    public static function discover(ClientApiIntegration $integration): array
    {
        $base    = rtrim($integration->api_base_url, '/');
        $headers = array_merge(
            ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
            $integration->getAuthHeader()
        );

        $schema = [
            'base_url'          => $base,
            'discovered_at'     => now()->toIso8601String(),
            'sr_endpoints'      => [],
            'qm_endpoints'      => [],
            'customer_endpoints'=> [],
            'update_endpoints'  => [],
            'sample_sr_fields'  => [],
            'sample_qm_fields'  => [],
            'auth_works'        => false,
        ];

        // ── Step 1: Auth test
        try {
            $root = Http::withHeaders($headers)->timeout(8)->get($base . '/api');
            $schema['auth_works'] = $root->status() !== 401;
        } catch (\Throwable $e) {
            Log::info("[Discovery] Base probe failed: " . $e->getMessage());
        }

        // ── Step 2: Probe SR endpoints
        foreach (self::$SR_PROBE_PATHS as $path) {
            $found = self::probe($base . $path, $headers);
            if ($found) {
                $schema['sr_endpoints'][] = [
                    'path'        => $path,
                    'sample'      => $found,
                    'fields'      => array_keys($found[0] ?? []),
                    'id_field'    => self::guessField($found[0] ?? [], ['id', 'sr_id', 'ticket_id', 'service_id']),
                    'phone_field' => self::guessField($found[0] ?? [], ['mobile', 'phone', 'mobile_number', 'customer_mobile', 'contact']),
                    'status_field'=> self::guessField($found[0] ?? [], ['status', 'ticket_status', 'state']),
                    'paginated'   => count($found) >= 10,
                ];
                if (empty($schema['sample_sr_fields'])) {
                    $schema['sample_sr_fields'] = array_keys($found[0] ?? []);
                }
            }
        }

        // ── Step 3: Probe QM endpoints
        foreach (self::$QM_PROBE_PATHS as $path) {
            $found = self::probe($base . $path, $headers);
            if ($found) {
                $schema['qm_endpoints'][] = [
                    'path'        => $path,
                    'sample'      => $found,
                    'fields'      => array_keys($found[0] ?? []),
                    'id_field'    => self::guessField($found[0] ?? [], ['id', 'complaint_id', 'qm_id']),
                    'phone_field' => self::guessField($found[0] ?? [], ['mobile', 'phone', 'mobile_number', 'contact']),
                    'status_field'=> self::guessField($found[0] ?? [], ['status', 'state']),
                    'paginated'   => count($found) >= 10,
                ];
                if (empty($schema['sample_qm_fields'])) {
                    $schema['sample_qm_fields'] = array_keys($found[0] ?? []);
                }
            }
        }

        // ── Step 4: Probe Customer endpoints
        foreach (self::$CUSTOMER_PROBE_PATHS as $path) {
            $found = self::probe($base . $path, $headers);
            if ($found) {
                $schema['customer_endpoints'][] = [
                    'path'        => $path,
                    'fields'      => array_keys($found[0] ?? []),
                    'id_field'    => self::guessField($found[0] ?? [], ['id', 'customer_id', 'user_id']),
                    'phone_field' => self::guessField($found[0] ?? [], ['mobile', 'phone', 'mobile_number', 'contact']),
                    'paginated'   => count($found) >= 10,
                ];
            }
        }

        // ── Step 5: Auto-populate sync_endpoints ও outbound_endpoints
        $syncEndpoints    = $integration->sync_endpoints ?? [];
        $outboundEndpoints = $integration->outbound_endpoints ?? [];

        // SR sync endpoint
        if (!empty($schema['sr_endpoints'])) {
            $best = $schema['sr_endpoints'][0];
            $syncEndpoints[] = [
                'path'        => $best['path'],
                'data_type'   => 'sr_history',
                'id_field'    => $best['id_field'],
                'phone_field' => $best['phone_field'],
                'paginate'    => $best['paginated'],
                'page_param'  => 'page',
                'per_page'    => 100,
                'auto_discovered' => true,
            ];
            // Outbound — POST to same path to create SR
            $outboundEndpoints['sr_create'] = $best['path'];
            // Update — PUT/PATCH to path with ID
            $outboundEndpoints['ticket_update'] = rtrim($best['path'], '/') . '/{id}';
        }

        // QM sync endpoint
        if (!empty($schema['qm_endpoints'])) {
            $best = $schema['qm_endpoints'][0];
            $syncEndpoints[] = [
                'path'        => $best['path'],
                'data_type'   => 'qm_complaint',
                'id_field'    => $best['id_field'],
                'phone_field' => $best['phone_field'],
                'paginate'    => $best['paginated'],
                'auto_discovered' => true,
            ];
            $outboundEndpoints['qm_complaint_create'] = $best['path'];
        }

        // Customer endpoint
        if (!empty($schema['customer_endpoints'])) {
            $best = $schema['customer_endpoints'][0];
            $existing = collect($syncEndpoints)->firstWhere('data_type', 'customer');
            if (!$existing) {
                $syncEndpoints[] = [
                    'path'        => $best['path'],
                    'data_type'   => 'customer',
                    'id_field'    => $best['id_field'],
                    'phone_field' => $best['phone_field'],
                    'paginate'    => $best['paginated'],
                    'auto_discovered' => true,
                ];
            }
        }

        // Save everything
        $integration->update([
            'discovered_schema'   => $schema,
            'last_discovered_at'  => now(),
            'sync_endpoints'      => $syncEndpoints,
            'outbound_endpoints'  => $outboundEndpoints,
        ]);

        Log::info("[Discovery] Completed for [{$integration->name}]", [
            'sr_found'       => count($schema['sr_endpoints']),
            'qm_found'       => count($schema['qm_endpoints']),
            'customer_found' => count($schema['customer_endpoints']),
        ]);

        return $schema;
    }

    /**
     * Discover করার পরে সব data clone করো local cache তে
     */
    public static function cloneAll(ClientApiIntegration $integration): int
    {
        // Refresh করো
        $integration->refresh();

        $endpoints = $integration->sync_endpoints ?? [];
        if (empty($endpoints)) return 0;

        $total = 0;
        $base    = rtrim($integration->api_base_url, '/');
        $headers = array_merge(
            ['Accept' => 'application/json'],
            $integration->getAuthHeader()
        );

        foreach ($endpoints as $ep) {
            if (empty($ep['path'])) continue;

            $path       = $ep['path'];
            $dataType   = $ep['data_type'] ?? 'other';
            $idField    = $ep['id_field'] ?? 'id';
            $phoneField = $ep['phone_field'] ?? 'mobile';
            $paginate   = $ep['paginate'] ?? false;
            $pageParam  = $ep['page_param'] ?? 'page';
            $perPage    = $ep['per_page'] ?? 100;
            $dataPath   = $ep['data_path'] ?? null;

            $page = 1;
            do {
                try {
                    $params   = $paginate ? [$pageParam => $page, 'per_page' => $perPage] : [];
                    $url      = $base . '/' . ltrim($path, '/');
                    $response = Http::withHeaders($headers)->timeout(30)->get($url, $params);

                    if (!$response->successful()) break;

                    $records = self::extractRecords($response->json(), $dataPath);
                    if (empty($records)) break;

                    foreach ($records as $record) {
                        $extId  = (string)($record[$idField] ?? uniqid());
                        $phone  = $record[$phoneField] ?? null;
                        if ($phone) {
                            $phone = preg_replace('/\D/', '', (string)$phone);
                            if (strlen($phone) > 11) $phone = substr($phone, -11);
                        }

                        ClientDataCache::updateOrCreate(
                            ['integration_id' => $integration->id, 'data_type' => $dataType, 'external_id' => $extId],
                            ['company_profile_id' => $integration->company_profile_id, 'search_key' => $phone, 'data' => $record, 'synced_at' => now()]
                        );
                        $total++;
                    }

                    $page++;
                } catch (\Throwable $e) {
                    Log::error("[Discovery::cloneAll] {$dataType}: " . $e->getMessage());
                    break;
                }
            } while ($paginate && count($records ?? []) >= $perPage);
        }

        $integration->update(['sync_status' => 'success', 'last_synced_at' => now()]);

        return $total;
    }

    // ── Helpers ──────────────────────────────────────────────

    private static function probe(string $url, array $headers): ?array
    {
        try {
            $response = Http::withHeaders($headers)->timeout(8)->get($url, ['per_page' => 5, 'limit' => 5]);
            if (!$response->successful()) return null;

            $body = $response->json();
            if (!is_array($body)) return null;

            // Try common data wrapper keys
            $records = self::extractRecords($body, null);
            if (!empty($records) && is_array($records[0])) {
                return array_slice($records, 0, 3); // return sample
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function guessField(array $record, array $candidates): string
    {
        foreach ($candidates as $c) {
            if (array_key_exists($c, $record)) return $c;
        }
        return $candidates[0]; // fallback to first candidate
    }

    private static function extractRecords(array $body, ?string $dataPath): array
    {
        if ($dataPath) {
            $node = $body;
            foreach (explode('.', $dataPath) as $key) {
                $node = $node[$key] ?? null;
                if (!is_array($node)) return [];
            }
            return $node;
        }
        if (isset($body[0])) return $body;
        foreach (['data', 'results', 'items', 'records', 'list', 'rows'] as $key) {
            if (!empty($body[$key]) && is_array($body[$key]) && isset($body[$key][0])) {
                return $body[$key];
            }
        }
        return [];
    }
}
