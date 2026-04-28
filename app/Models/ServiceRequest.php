<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    // ১. সব কলামে ডাটা সেভ করার পারমিশন
    protected $guarded = []; 

    // 🚀 ২. মাস্টারমাইন্ড ফিক্স: JSON ডাটাকে ডাটাবেসে সেভ ও পড়ার পারমিশন!
    protected $casts = [
        'extracted_data' => 'array',
    ];
    
    // ক্যাটাগরির সাথে রিলেশন (যদি আগে না দিয়ে থাকেন)
    public function ivrService()
    {
        return $this->belongsTo(IvrService::class, 'ivr_service_id');
    }
    // AiTicket এর সাথে রিলেশন
    public function aiTicket()
    {
        return $this->hasOne(AiTicket::class);
    }}