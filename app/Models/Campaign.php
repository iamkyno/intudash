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
        'client_id', 'user_id', 'name', 'campaign_type', 'message', 'notes', 'status',
        'internal_cost_per_sms', 'client_rate_per_sms', 'estimated_recipients',
        'actual_recipients', 'sms_segments', 'estimated_cost', 'estimated_charge',
        'estimated_profit', 'actual_cost', 'actual_charge', 'actual_profit',
        'sender_name', 'scheduled_at', 'scheduled_end_at', 'sent_at', 'completed_at',
        'provider_response', 'provider', 'provider_campaign_id', 'admin_override_payment',
        'is_recurring_schedule', 'archived_at', 'campaign_group_id', 'campaign_group_run',
        'email_subject', 'email_from_name', 'email_from_address', 'email_reply_to', 'email_body',
        'internal_cost_per_email', 'client_rate_per_email',
        'estimated_email_recipients', 'actual_email_recipients',
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
        'internal_cost_per_email' => 'decimal:6',
        'client_rate_per_email' => 'decimal:6',
        'archived_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class)->withTrashed();
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

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
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

    public function canBeDeleted(): bool
    {
        // Anything that hasn't actually gone out (or failed to) can be deleted —
        // draft through ready-to-schedule, plus scheduled runs that haven't fired yet.
        if (in_array($this->status, ['sending', 'completed', 'partially_completed'])) {
            return false;
        }

        if ($this->status === 'scheduled') {
            return $this->scheduled_at && $this->scheduled_at->isFuture();
        }

        return true;
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeNotArchived($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function recalculateEstimates(): void
    {
        $type = $this->campaign_type ?? 'sms';
        $cost = 0;
        $charge = 0;

        if (in_array($type, ['sms', 'both'])) {
            $smsRecipients = $this->validRecipients()->count();
            $this->estimated_recipients = $smsRecipients;
            $cost   += $smsRecipients * $this->sms_segments * (float) $this->internal_cost_per_sms;
            $charge += $smsRecipients * $this->sms_segments * (float) $this->client_rate_per_sms;
        }

        if (in_array($type, ['email', 'both'])) {
            $emailRecipients = $this->validRecipients()->whereNotNull('email')->where('email', '!=', '')->count();
            $this->estimated_email_recipients = $emailRecipients;
            $cost   += $emailRecipients * (float) $this->internal_cost_per_email;
            $charge += $emailRecipients * (float) $this->client_rate_per_email;
        }

        $this->estimated_cost = round($cost, 2);
        $this->estimated_charge = round($charge, 2);
        $this->estimated_profit = round($charge - $cost, 2);
        $this->save();
    }
}
