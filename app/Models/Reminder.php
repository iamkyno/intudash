<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reminder extends Model
{
    protected $fillable = [
        'client_id', 'reminder_template_id', 'template_slug',
        'recipient_name', 'phone', 'phone_normalized', 'email', 'data',
        'channel', 'send_at', 'status', 'source',
        'sms_provider_message_id', 'email_provider_message_id',
        'sent_at', 'failure_reason',
    ];

    protected $casts = [
        'data' => 'array',
        'send_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function template()
    {
        return $this->belongsTo(ReminderTemplate::class, 'reminder_template_id');
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'pending')->where('send_at', '<=', now());
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'sent' => 'success',
            'partially_sent' => 'warning',
            'pending' => 'info',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
}
