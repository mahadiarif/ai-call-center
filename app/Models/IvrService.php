<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IvrService extends Model
{
    use HasFactory;

    protected $guarded = [];

    // JSON ডাটাকে অ্যারে হিসেবে কাজ করানোর জন্য
    protected $casts = [
        'required_fields' => 'array',
        'secondary_convince_scripts' => 'array', // ✅ এটা যোগ করা হলো
        'is_active' => 'boolean',
        'voice_speed' => 'decimal:2',
        'voice_gender' => 'string',
        'escalation_collect_before_transfer' => 'boolean',
        'secondary_option_enabled' => 'boolean',
        'secondary_collect_name_mobile' => 'boolean',
    ];

    // Ensure defaults are applied
    protected $attributes = [
        'voice_gender' => 'Charon',
        'voice_speed' => 1.0,
    ];
}