<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'gateway_response' => 'array',
    ];

    public function srTicket()
    {
        return $this->belongsTo(SrTicket::class, 'reference_id')
                    ->when($this->reference_type === 'sr_ticket', fn($q) => $q);
    }

    public function qmTicket()
    {
        return $this->belongsTo(QmTicket::class, 'reference_id')
                    ->when($this->reference_type === 'qm_ticket', fn($q) => $q);
    }
}
