<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\ClientApiIntegration;
use App\Models\ClientDataCache;

class ClientApiSyncCommand extends Command
{
    protected $signature   = 'client:sync {--company= : Sync specific company ID} {--force : Force sync even if not due}';
    protected $description = 'Sync client database data into local cache for AI context';

    public function handle(): int
    {
        $query = ClientApiIntegration::where('is_active', true);
        if ($companyId = $this->option('company')) {
            $query->where('company_profile_id', $companyId);
        }

        $integrations = $query->get();

        if ($integrations->isEmpty()) {
            $this->info('No active client API integrations found.');
            return self::SUCCESS;
        }

        foreach ($integrations as $integration) {
            if (!$this->option('force') && !$integration->isDueForSync()) {
                $this->line("⏭  [{$integration->name}] — Not due yet (next sync in " .
                    $integration->last_synced_at->addMinutes($integration->sync_interval_minutes)->diffForHumans() . ")");
                continue;
            }

            $this->syncIntegration($integration);
        }

        return self::SUCCESS;
    }

    private function syncIntegration(ClientApiIntegration $integration): void
    {
        $this->info("🔄 Syncing: [{$integration->name}] (company_id: {$integration->company_profile_id})");

        $integration->update(['sync_status' => 'running', 'last_error' => null]);

        $endpoints = $integration->sync_endpoints ?? [];
        if (empty($endpoints)) {
            $this->warn("  ⚠  No endpoints configured for [{$integration->name}]");
            $integration->update(['sync_status' => 'idle']);
            return;
        }

        $totalSynced = 0;
        $errors      = [];

        foreach ($endpoints as $ep) {
            $path      = $ep['path']      ?? '/';
            $dataType  = $ep['data_type'] ?? 'other';
            $idField   = $ep['id_field']  ?? 'id';
            $phoneField = $ep['phone_field'] ?? 'mobile';
            $barcodeField = $ep['barcode_field'] ?? null;
            $paginate  = $ep['paginate']  ?? false;
            $pageParam = $ep['page_param'] ?? 'page';
            $perPage   = $ep['per_page']  ?? 100;
            $dataPath  = $ep['data_path'] ?? null; // e.g., "data.records"

            $this->line("  📡 [{$dataType}] → {$path}");

            try {
                $page   = 1;
                $synced = 0;
                do {
                    $params = $paginate ? [$pageParam => $page, 'per_page' => $perPage] : [];
                    $url    = rtrim($integration->api_base_url, '/') . '/' . ltrim($path, '/');

                    $response = Http::withHeaders($integration->getAuthHeader())
                        ->timeout(30)
                        ->get($url, $params);

                    if (!$response->successful()) {
                        $errors[] = "[{$dataType}] HTTP {$response->status()}: {$response->body()}";
                        break;
                    }

                    $body    = $response->json();
                    $records = $this->extractRecords($body, $dataPath);

                    if (empty($records)) break;

                    foreach ($records as $record) {
                        $externalId  = $record[$idField]     ?? null;
                        $searchKey   = $record[$phoneField]  ?? null;
                        $searchKey2  = $barcodeField ? ($record[$barcodeField] ?? null) : null;

                        // Normalize phone
                        if ($searchKey) {
                            $searchKey = preg_replace('/\D/', '', $searchKey);
                            if (strlen($searchKey) > 11) $searchKey = substr($searchKey, -11);
                        }

                        ClientDataCache::updateOrCreate(
                            [
                                'integration_id' => $integration->id,
                                'data_type'      => $dataType,
                                'external_id'    => (string)($externalId ?? uniqid()),
                            ],
                            [
                                'company_profile_id' => $integration->company_profile_id,
                                'search_key'         => $searchKey,
                                'search_key2'        => $searchKey2,
                                'data'               => $record,
                                'synced_at'          => now(),
                            ]
                        );
                        $synced++;
                    }

                    $totalSynced += $synced;
                    $page++;
                    // Stop paginating if fewer records than per_page (last page)
                } while ($paginate && count($records) >= $perPage);

                $this->line("     ✅ {$synced} records synced for [{$dataType}]");

            } catch (\Throwable $e) {
                $errors[] = "[{$dataType}] " . $e->getMessage();
                $this->error("     ❌ {$e->getMessage()}");
            }
        }

        $status = empty($errors) ? 'success' : 'error';
        $integration->update([
            'sync_status'    => $status,
            'last_synced_at' => now(),
            'last_error'     => empty($errors) ? null : implode("\n", $errors),
        ]);

        $this->info("  ✅ Done [{$integration->name}]: {$totalSynced} total records | Status: {$status}");
    }

    private function extractRecords(array $body, ?string $dataPath): array
    {
        if (!$dataPath) {
            return is_array($body) && isset($body[0]) ? $body : ($body['data'] ?? $body['results'] ?? $body['items'] ?? []);
        }

        $parts = explode('.', $dataPath);
        $node  = $body;
        foreach ($parts as $part) {
            $node = $node[$part] ?? null;
            if ($node === null) return [];
        }
        return is_array($node) ? $node : [];
    }
}
