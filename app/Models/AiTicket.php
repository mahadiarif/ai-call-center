<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiTicket extends Model
{
    use HasFactory;

    // 🚀 আমাদের নতুন ডায়নামিক টেবিলের কলামগুলো
    protected $fillable = [
        'service_request_id',
        'ivr_service_id', 
        'customer_number', 
        'extracted_data', 
        'status'
    ];

    // JSON ডাটাকে অ্যারে হিসেবে হ্যান্ডেল করার জন্য
    protected $casts = [
        'extracted_data' => 'array',
    ];

    // IVR সার্ভিসের সাথে রিলেশন
    public function ivrService()
    {
        return $this->belongsTo(IvrService::class);
    }

    // ServiceRequest এর সাথে রিলেশন
    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    // Comments এর সাথে রিলেশন
    public function comments()
    {
        return $this->hasMany(AiTicketComment::class);
    }
}