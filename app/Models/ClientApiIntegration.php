<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientApiIntegration extends Model
{
    protected $fillable = [
        'company_profile_id', 'name', 'description',
        'api_base_url', 'api_key', 'api_secret',
        'auth_type', 'auth_header_name',
        'sync_endpoints', 'outbound_endpoints', 'field_mapping',
        'webhook_secret', 'sync_interval_minutes', 'last_synced_at',
        'sync_status', 'last_error', 'is_active',
    ];

    protected $casts = [
        'sync_endpoints'      => 'array',
        'outbound_endpoints'  => 'array',
        'field_mapping'       => 'array',
        'discovered_schema'   => 'array',
        'last_synced_at'      => 'datetime',
        'last_discovered_at'  => 'datetime',
        'is_active'           => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    public function cacheRecords(): HasMany
    {
        return $this->hasMany(ClientDataCache::class, 'integration_id');
    }

    // Build Authorization header value
    public function getAuthHeader(): array
    {
        return match ($this->auth_type) {
            'bearer'  => ['Authorization' => "Bearer {$this->api_key}"],
            'basic'   => ['Authorization' => 'Basic ' . base64_encode("{$this->api_key}:{$this->api_secret}")],
            'api_key' => [($this->auth_header_name ?: 'X-API-Key') => $this->api_key],
            default   => [],
        };
    }

    // ─────────────────────────────────────────────────────────
    // Discovered schema থেকে AI conversation instruction তৈরি করো
    // AI এই instruction পড়ে client এর নিজস্ব field গুলো collect করবে
    // ─────────────────────────────────────────────────────────
    public function buildSchemaPrompt(): string
    {
        $schema = $this->discovered_schema;
        if (empty($schema)) return '';

        $prompt = "\n[🏢 Client System Schema — এই rules মেনে কাজ করবে]\n";
        $prompt .= "Client এর নিজস্ব system আছে। তাদের database এ যেভাবে SR/QM থাকে সেভাবেই collect করবে।\n\n";

        // SR Fields
        if (!empty($schema['sample_sr_fields'])) {
            $fields = array_diff($schema['sample_sr_fields'], ['id', 'created_at', 'updated_at', 'deleted_at']);
            $prompt .= "📋 SR Ticket এ Client যে fields রাখে:\n";
            foreach ($fields as $f) {
                $bn = self::translateField($f);
                $prompt .= "  • {$f} → {$bn}\n";
            }
            $prompt .= "\n";
        }

        // QM Fields
        if (!empty($schema['sample_qm_fields'])) {
            $fields = array_diff($schema['sample_qm_fields'], ['id', 'created_at', 'updated_at', 'deleted_at']);
            $prompt .= "📋 QM Complaint এ Client যে fields রাখে:\n";
            foreach ($fields as $f) {
                $bn = self::translateField($f);
                $prompt .= "  • {$f} → {$bn}\n";
            }
            $prompt .= "\n";
        }

        // Status values discovered
        if (!empty($schema['sr_endpoints'][0]['status_field'])) {
            $prompt .= "📊 Client এর SR status field: `{$schema['sr_endpoints'][0]['status_field']}`\n";
        }

        $prompt .= "\n⚠️ RULE: উপরের fields এর data collect করো কথোপকথনের মাধ্যমে। Extra field চাইবে না, কম ও নয়।\n";
        $prompt .= "Client এর system এর মতো একই ধরনের data রেখো — তাদের format follow করো।\n\n";

        return $prompt;
    }

    // Common field name → বাংলা label mapping — public so AIFormController can call it
    public static function translateField(string $field): string
    {
        $map = [
            'name' => 'নাম', 'customer_name' => 'কাস্টমারের নাম', 'full_name' => 'পুরো নাম',
            'mobile' => 'মোবাইল নম্বর', 'phone' => 'ফোন নম্বর', 'mobile_number' => 'মোবাইল নম্বর', 'contact' => 'যোগাযোগ নম্বর',
            'address' => 'ঠিকানা', 'district' => 'জেলা', 'area' => 'এলাকা', 'city' => 'শহর', 'division' => 'বিভাগ',
            'product' => 'পণ্যের নাম', 'product_name' => 'পণ্যের নাম', 'model' => 'মডেল', 'model_number' => 'মডেল নম্বর',
            'barcode' => 'বারকোড', 'serial' => 'সিরিয়াল নম্বর', 'serial_number' => 'সিরিয়াল নম্বর',
            'problem' => 'সমস্যার বিবরণ', 'complaint' => 'অভিযোগ', 'issue' => 'সমস্যা', 'description' => 'বিস্তারিত', 'details' => 'বিস্তারিত',
            'status' => 'স্ট্যাটাস', 'date' => 'তারিখ', 'purchase_date' => 'ক্রয়ের তারিখ',
            'warranty' => 'ওয়ারেন্টি', 'service_center' => 'সার্ভিস সেন্টার', 'technician' => 'টেকনিশিয়ান',
            'brand' => 'ব্র্যান্ড', 'category' => 'ক্যাটাগরি', 'quantity' => 'পরিমাণ', 'price' => 'মূল্য',
            'remarks' => 'মন্তব্য', 'comment' => 'মন্তব্য', 'note' => 'নোট',
            'sr_number' => 'SR নম্বর', 'ticket_id' => 'টিকেট নম্বর', 'qm_id' => 'QM নম্বর',
        ];
        return $map[strtolower($field)] ?? $field;
    }

    public function needsSync(): bool
    {
        if (!$this->last_synced_at) return true;
        return $this->last_synced_at->addMinutes($this->sync_interval_minutes ?? 60)->isPast();
    }
}
