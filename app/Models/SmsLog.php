<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'campaign_id', 'client_id', 'campaign_recipient_id', 'recipient_number',
        'message', 'sms_segments', 'provider', 'provider_message_id',
        'provider_event_id', 'status', 'failure_reason', 'sent_at',
        'delivered_at', 'raw_response',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'raw_response' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function recipient()
    {
        return $this->belongsTo(CampaignRecipient::class, 'campaign_recipient_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'delivered' => 'success',
            'pending', 'submitted', 'staged' => 'warning',
            'undelivered', 'expired', 'blacklisted', 'no_route', 'failed', 'cancelled' => 'danger',
            default => 'secondary',
        };
    }
}
