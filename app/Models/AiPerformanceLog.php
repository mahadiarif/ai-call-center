<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiPerformanceLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'missing_fields'   => 'array',
        'collected_fields' => 'array',
    ];

    // Auto-suggest training status based on performance
    public static function boot()
    {
        parent::boot();
        
        static::creating(function ($log) {
            $log->training_status = $log->training_status ?? 'pending';
            $log->ai_suggestion = self::generateSuggestion($log);
        });
    }

    // Generate AI suggestion for training
    public static function generateSuggestion($log): string
    {
        if ($log->score >= 90) {
            return "✅ EXCELLENT! এই conversation টা training data তে add করুন। AI perfectly কাজ করেছে।";
        } elseif ($log->score >= 70) {
            return "⚠️ GOOD এই data ভালো কিন্তু {$log->filled_fields}/{$log->total_fields} field পেয়েছে। Missing: " . implode(', ', $log->missing_fields ?? []);
        } else {
            return "❌ NEEDS IMPROVEMENT: AI weak performance ({$log->score}%). এই pattern avoid করতে হবে। Missing: " . implode(', ', $log->missing_fields ?? []);
        }
    }

    public function ivrService()
    {
        return $this->belongsTo(IvrService::class);
    }

    public function serviceRequest()
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    // Score এর রং — ভালো/খারাপ বোঝার জন্য
    public function getScoreColorAttribute(): string
    {
        if ($this->score >= 80) return 'success';
        if ($this->score >= 50) return 'warning';
        return 'danger';
    }

    // Score label
    public function getScoreLabelAttribute(): string
    {
        if ($this->score >= 80) return '✅ চমৎকার';
        if ($this->score >= 50) return '⚠️ মোটামুটি';
        return '❌ দুর্বল';
    }

    // Service request save হওয়ার পর performance log তৈরি করো
    public static function logFromServiceRequest(ServiceRequest $request, IvrService $service = null): self
    {
        $requiredFields = [];
        if ($service && $service->required_fields) {
            foreach ($service->required_fields as $field) {
                if ($field['is_mandatory']) {
                    $requiredFields[] = $field['field_name'];
                }
            }
        }

        // default mandatory fields
        $defaultFields = ['customer_name', 'mobile_number'];
        $allRequired   = array_unique(array_merge($defaultFields, $requiredFields));

        $extractedData   = $request->extracted_data ?? [];
        $collectedFields = [];
        $missingFields   = [];

        foreach ($allRequired as $field) {
            $value = $extractedData[$field] ?? null;
            if (!empty($value) && $value !== 'N/A') {
                $collectedFields[] = $field;
            } else {
                $missingFields[] = $field;
            }
        }

        $total  = count($allRequired);
        $filled = count($collectedFields);
        $score  = $total > 0 ? (int) round(($filled / $total) * 100) : 0;

        return self::create([
            'service_request_id' => $request->id,
            'ivr_service_id'     => $service ? $service->id : null,
            'total_fields'       => $total,
            'filled_fields'      => $filled,
            'score'              => $score,
            'missing_fields'     => $missingFields,
            'collected_fields'   => $collectedFields,
        ]);
    }
}
