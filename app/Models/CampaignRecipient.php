<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id', 'name', 'phone', 'phone_normalized',
        'email', 'custom_fields', 'status', 'invalid_reason',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function smsLog()
    {
        return $this->hasOne(SmsLog::class);
    }
}
