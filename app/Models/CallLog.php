<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    // IVR সার্ভিসের সাথে রিলেশন
    public function ivrService()
    {
        return $this->belongsTo(IvrService::class);
    }

    // এই call এর caller number এর সবচেয়ে recent SR ticket
    public function latestSrTicket()
    {
        return $this->hasOne(\App\Models\SrTicket::class, 'mobile_number', 'caller_number')
                    ->latest('id');
    }

    // Duration কে মিনিট:সেকেন্ড ফরম্যাটে দেখানো
    public function getDurationFormattedAttribute(): string
    {
        $minutes = intdiv($this->duration, 60);
        $seconds = $this->duration % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
