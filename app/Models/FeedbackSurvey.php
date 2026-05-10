<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackSurvey extends Model
{
    protected $fillable = [
        'campaign_id', 'sr_number', 'customer_name', 'mobile_number',
        'product_name', 'district', 'service_date', 'call_status',
        'service_received', 'has_problem', 'problem_details',
        'satisfied', 'satisfaction_comment', 'general_comment',
        'call_transcript', 'called_at', 'completed_at',
    ];

    protected $casts = [
        'service_date' => 'date',
        'called_at'    => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FeedbackCampaign::class, 'campaign_id');
    }

    // ─── AI Context Helper ──────────────────────────────────────────────────
    // Returns the intro context for AI prompt (greet customer by name + SR info)
    public function buildOutboundContext(string $greetingCompany = 'ওয়ালটন'): array
    {
        $timeOfDay = $this->getTimeOfDay();
        return [
            'greeting_company' => $greetingCompany,
            'time_of_day'      => $timeOfDay,
            'customer_name'    => $this->customer_name ?? 'স্যার/ম্যাডাম',
            'sr_number'        => $this->sr_number ?? 'N/A',
            'product_name'     => $this->product_name ?? 'পণ্য',
            'mobile_number'    => $this->mobile_number,
        ];
    }

    private function getTimeOfDay(): string
    {
        $hour = (int) now('Asia/Dhaka')->format('H');
        if ($hour >= 4  && $hour < 12) return 'শুভ সকাল';
        if ($hour >= 12 && $hour < 17) return 'শুভ অপরাহ্ণ';
        if ($hour >= 17 && $hour < 20) return 'শুভ সন্ধ্যা';
        return 'শুভ রাত্রি';
    }
}
