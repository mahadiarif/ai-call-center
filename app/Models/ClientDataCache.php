<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientDataCache extends Model
{
    protected $table = 'client_data_cache';

    protected $fillable = [
        'company_profile_id', 'integration_id', 'data_type',
        'external_id', 'search_key', 'search_key2',
        'data', 'synced_at', 'expires_at',
    ];

    protected $casts = [
        'data'       => 'array',
        'synced_at'  => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(ClientApiIntegration::class, 'integration_id');
    }

    /**
     * Look up customer data by phone number.
     * Returns all records for that customer across all integrations of a company.
     */
    public static function findCustomerByPhone(string $phone, int $companyProfileId): array
    {
        $clean = preg_replace('/\D/', '', $phone);
        // try last 11 digits (Bangladesh mobile)
        $short = strlen($clean) > 11 ? substr($clean, -11) : $clean;

        $records = static::where('company_profile_id', $companyProfileId)
            ->where('data_type', 'customer')
            ->where(function ($q) use ($clean, $short) {
                $q->where('search_key', $clean)
                  ->orWhere('search_key', $short)
                  ->orWhere('search_key', $phone);
            })
            ->get();

        return $records->map(fn($r) => $r->data)->toArray();
    }

    /**
     * Get SR history for a customer phone number.
     */
    public static function findSrHistoryByPhone(string $phone, int $companyProfileId): array
    {
        $clean = preg_replace('/\D/', '', $phone);
        $short = strlen($clean) > 11 ? substr($clean, -11) : $clean;

        $records = static::where('company_profile_id', $companyProfileId)
            ->where('data_type', 'sr_history')
            ->where(function ($q) use ($clean, $short) {
                $q->where('search_key', $clean)
                  ->orWhere('search_key', $short);
            })
            ->orderByDesc('synced_at')
            ->limit(5)
            ->get();

        return $records->map(fn($r) => $r->data)->toArray();
    }

    /**
     * Get all records of a given type for a company.
     */
    public static function getByType(string $dataType, int $companyProfileId): array
    {
        return static::where('company_profile_id', $companyProfileId)
            ->where('data_type', $dataType)
            ->get()
            ->map(fn($r) => $r->data)
            ->toArray();
    }

    /**
     * Format client data as AI-readable context text.
     */
    public static function buildAiContext(string $phone, int $companyProfileId): string
    {
        $context = '';

        $customers = static::findCustomerByPhone($phone, $companyProfileId);
        if (!empty($customers)) {
            $c = $customers[0];
            $context .= "\n[📋 Client Database থেকে কাস্টমার তথ্য]\n";
            foreach ($c as $key => $val) {
                if ($val && $key !== 'id') {
                    $context .= "• {$key}: {$val}\n";
                }
            }
        }

        $srHistory = static::findSrHistoryByPhone($phone, $companyProfileId);
        if (!empty($srHistory)) {
            $context .= "\n[🗂️ পুরানো SR / Service History (Client থেকে)]\n";
            foreach ($srHistory as $sr) {
                $line = [];
                foreach (['sr_number','date','product','status','complaint'] as $f) {
                    if (!empty($sr[$f])) $line[] = "{$f}: {$sr[$f]}";
                }
                $context .= '• ' . implode(' | ', $line) . "\n";
            }
        }

        return $context;
    }
}
