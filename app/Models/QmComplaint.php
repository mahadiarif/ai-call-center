<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QmComplaint extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'extracted_data' => 'array',
        'synced_at'      => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate QM number on create
        static::creating(function ($model) {
            if (empty($model->qm_number)) {
                $lastId = static::max('id') ?? 0;
                $model->qm_number = 'QM-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function ivrService()
    {
        return $this->belongsTo(IvrService::class);
    }

    public function getComplaintCategoryLabelAttribute(): string
    {
        return match($this->complaint_category) {
            'service_expert'  => 'সার্ভিস এক্সপার্ট',
            'showroom'        => 'শো-রুম / প্লাজা',
            'product_quality' => 'পণ্যের মান',
            'billing'         => 'বিল',
            default           => $this->complaint_category ?? 'অন্যান্য',
        };
    }
}
