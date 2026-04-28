<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\IvrService;

class KnowledgeBase extends Model
{
    protected $guarded = [];

    // 🔥 ডাটাবেসের JSON ডাটাকে Array হিসেবে পড়ার জন্য সব ফিল্ড যুক্ত করা হলো
    protected $casts = [
        'sample_question' => 'array',
        'answer'          => 'array',
        'product_models'  => 'array',
        'common_issues'   => 'array',

        // এআই ব্রেইন (Persona) এর ফিল্ড
        'greeting_rules'    => 'array',
        'behavior_rules'    => 'array',
        'closing_rules'     => 'array',

        // প্রোডাক্ট এবং কন্ট্রোল রুলস
        'mandatory_fields'  => 'array',
        'strict_validation' => 'array',
        'special_rules'     => 'array',
        'negative_rules'    => 'array',
        'escalation_rules'  => 'array',
    ];

    // IVR সার্ভিসের সাথে রিলেশন
    public function ivrService()
    {
        return $this->belongsTo(IvrService::class);
    }
}