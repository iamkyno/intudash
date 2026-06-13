<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'campaign_id', 'invoice_id', 'quote_number', 'status',
        'sms_quantity', 'sms_rate', 'email_quantity', 'email_rate',
        'subtotal', 'vat_enabled', 'vat_rate', 'vat_amount', 'total',
        'notes', 'valid_until', 'accepted_at',
    ];

    protected $casts = [
        'vat_enabled' => 'boolean',
        'accepted_at' => 'datetime',
        'valid_until' => 'date',
        'sms_rate' => 'decimal:4',
        'email_rate' => 'decimal:6',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }

    public static function getNextQuoteNumber(): string
    {
        $year = date('Y');
        $last = static::withTrashed()
            ->where('quote_number', 'like', "QUO-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $seq = $last
            ? ((int) explode('-', $last->quote_number)[2]) + 1
            : 1;

        return "QUO-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && $this->valid_until->isPast() && $this->status !== 'accepted';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'sent' => 'info',
            'accepted' => 'success',
            'declined' => 'danger',
            'expired' => 'secondary',
            default => 'secondary',
        };
    }
}
