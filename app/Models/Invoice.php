<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'campaign_id', 'invoice_number', 'status',
        'sms_quantity', 'sms_rate', 'email_quantity', 'email_rate',
        'subtotal', 'vat_enabled', 'vat_rate', 'vat_amount', 'total',
        'notes', 'due_date', 'paid_at',
    ];

    protected $casts = [
        'vat_enabled' => 'boolean',
        'paid_at' => 'datetime',
        'due_date' => 'date',
        'sms_rate' => 'decimal:4',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'secondary',
            'sent' => 'primary',
            'paid' => 'success',
            'overdue' => 'danger',
            'cancelled' => 'secondary',
            default => 'secondary',
        };
    }
}
