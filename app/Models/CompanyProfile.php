<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyProfile extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    // 🚀 JSON ডাটাকে অ্যারে হিসেবে কাজ করানোর জন্য
    protected $casts = [
        'dynamic_instructions' => 'array',
        'contact_info' => 'array',
    ];
}