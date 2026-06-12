<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name', 'contact_person', 'email', 'phone',
        'billing_address', 'vat_number', 'default_sms_rate', 'status', 'notes',
    ];

    protected $casts = [
        'default_sms_rate' => 'decimal:4',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class);
    }

    public function totalSmsSent(): int
    {
        return $this->smsLogs()->whereIn('status', ['delivered', 'submitted'])->count();
    }

    public function totalBilled(): float
    {
        return $this->invoices()->where('status', 'paid')->sum('total');
    }

    public function totalProfit(): float
    {
        return $this->campaigns()->sum('actual_profit');
    }
}
