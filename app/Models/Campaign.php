<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'user_id', 'name', 'message', 'notes', 'status',
        'internal_cost_per_sms', 'client_rate_per_sms', 'estimated_recipients',
        'actual_recipients', 'sms_segments', 'estimated_cost', 'estimated_charge',
        'estimated_profit', 'actual_cost', 'actual_charge', 'actual_profit',
        'sender_name', 'scheduled_at', 'scheduled_end_at', 'sent_at', 'completed_at',
        'provider_response', 'provider', 'provider_campaign_id', 'admin_override_payment',
        'is_recurring_schedule',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'scheduled_end_at' => 'datetime',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'provider_response' => 'array',
        'admin_override_payment' => 'boolean',
        'internal_cost_per_sms' => 'decimal:4',
        'client_rate_per_sms' => 'decimal:4',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recipients()
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function validRecipients()
    {
        return $this->hasMany(CampaignRecipient::class)->where('status', 'valid');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'recipients_uploaded' => 'Recipients Uploaded',
            'invoice_generated' => 'Invoice Generated',
            'awaiting_payment' => 'Awaiting Payment',
            'ready_to_schedule' => 'Ready to Schedule',
            'scheduled' => 'Scheduled',
            'sending' => 'Sending',
            'completed' => 'Completed',
            'partially_completed' => 'Partially Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'recipients_uploaded' => 'info',
            'invoice_generated' => 'primary',
            'awaiting_payment' => 'warning',
            'ready_to_schedule' => 'success',
            'scheduled' => 'primary',
            'sending' => 'info',
            'completed' => 'success',
            'partially_completed' => 'warning',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }

    public function canBeScheduled(): bool
    {
        return in_array($this->status, ['ready_to_schedule', 'scheduled'])
            || $this->admin_override_payment;
    }

    public function canBePaused(): bool
    {
        return in_array($this->status, ['sending', 'scheduled']);
    }

    public function canBeResumed(): bool
    {
        return $this->status === 'paused';
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function recalculateEstimates(): void
    {
        $recipients = $this->validRecipients()->count();
        $this->estimated_recipients = $recipients;
        $this->estimated_cost = round($recipients * $this->sms_segments * $this->internal_cost_per_sms, 2);
        $this->estimated_charge = round($recipients * $this->sms_segments * $this->client_rate_per_sms, 2);
        $this->estimated_profit = round($this->estimated_charge - $this->estimated_cost, 2);
        $this->save();
    }
}
