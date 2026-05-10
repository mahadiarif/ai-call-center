<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QmTicket extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'extracted_data' => 'array',
        'synced_at'      => 'datetime',
        'send_to_mail'   => 'boolean',
        'send_via_sms'   => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->qm_number)) {
                $lastId = static::max('id') ?? 0;
                $model->qm_number = 'QM-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function ivrService()
    {
        return $this->belongsTo(IvrService::class);
    }

    // Map old QM types to query_type values
    public static function queryTypeFromIvrType(string $ivrType): string
    {
        return match ($ivrType) {
            'QM_COMPLAINT' => 'Complain',
            'QM_PARTS'     => 'Parts Inquiry',
            'QM_BILL'      => 'Bill Inquiry',
            default        => 'General Inquiry',
        };
    }

    // Auto-set send_to_group from query_type
    public static function sendToGroupFromQueryType(string $queryType): ?string
    {
        return match ($queryType) {
            'Complain'          => 'Complain',
            'Parts Inquiry'     => 'Parts Query',
            'Bill Inquiry'      => 'Bill Query',
            'Technical Support' => 'Tech Support',
            default             => null,
        };
    }
}
