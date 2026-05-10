<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QmPartsQuery extends Model
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

        static::creating(function ($model) {
            if (empty($model->qm_number)) {
                $lastId = static::max('id') ?? 0;
                $model->qm_number = 'QM-P' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function ivrService()
    {
        return $this->belongsTo(IvrService::class);
    }
}
