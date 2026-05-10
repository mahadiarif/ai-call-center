<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SrTicket extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'extracted_data' => 'array',
        'synced_at'      => 'datetime',
        'is_emergency'   => 'boolean',
    ];

    // Auto-generate QM number — SR এর জন্য নেই, কিন্তু relationship আছে
    public function ivrService()
    {
        return $this->belongsTo(IvrService::class);
    }

    // একই mobile-এর আগের SR tickets
    public function previousTickets()
    {
        if (empty($this->mobile_number)) return collect();
        return SrTicket::where('mobile_number', $this->mobile_number)
            ->where('id', '!=', $this->id)
            ->orderByDesc('created_at');
    }

    // Status badge color
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'Resolved'              => 'success',
            'Pending', 'In Progress'=> 'warning',
            'Drop Call', 'Rejected' => 'danger',
            'Escalation Requested'  => 'purple',
            default                 => 'gray',
        };
    }
}
