<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active'         => 'boolean',
        'auto_send_on_sr'   => 'boolean',
        'auto_send_on_qm'   => 'boolean',
    ];

    /**
     * Always use the single settings row
     */
    public static function current(): self
    {
        return self::firstOrCreate([], [
            'gateway_name'    => 'Gennet',
            'gateway_url'     => 'https://isms.gennet.com.bd/api/v3/send-sms',
            'is_active'       => false,
            'auto_send_on_sr' => true,
            'auto_send_on_qm' => false,
            'sms_type'        => 'non_masking',
        ]);
    }
}
