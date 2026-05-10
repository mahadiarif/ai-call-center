<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedbackCampaign extends Model
{
    protected $fillable = [
        'company_profile_id', 'name', 'description', 'status',
        'greeting_company', 'custom_script',
        'total_contacts', 'called_count', 'completed_count',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class, 'company_profile_id');
    }

    public function surveys(): HasMany
    {
        return $this->hasMany(FeedbackSurvey::class, 'campaign_id');
    }

    public function pendingSurveys(): HasMany
    {
        return $this->hasMany(FeedbackSurvey::class, 'campaign_id')->where('call_status', 'pending');
    }

    public function completedSurveys(): HasMany
    {
        return $this->hasMany(FeedbackSurvey::class, 'campaign_id')->where('call_status', 'completed');
    }

    // Satisfaction % for completed surveys
    public function satisfactionRate(): ?float
    {
        $completed = $this->completedSurveys();
        $total = $completed->count();
        if ($total === 0) return null;
        $satisfied = $completed->where('satisfied', 'yes')->count();
        return round(($satisfied / $total) * 100, 1);
    }

    // Sync counts from surveys table
    public function syncCounts(): void
    {
        $this->update([
            'total_contacts'   => $this->surveys()->count(),
            'called_count'     => $this->surveys()->whereNotIn('call_status', ['pending'])->count(),
            'completed_count'  => $this->completedSurveys()->count(),
        ]);
    }
}
