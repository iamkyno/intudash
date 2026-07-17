<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name', 'contact_person', 'email', 'phone',
        'billing_address', 'vat_number', 'default_sms_rate', 'status', 'notes',
        'api_token', 'api_token_last_four', 'api_token_generated_at',
    ];

    protected $hidden = ['api_token'];

    protected $casts = [
        'default_sms_rate' => 'decimal:4',
        'api_token_generated_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function dataSources()
    {
        return $this->hasMany(ClientDataSource::class);
    }

    public function sendingDomains()
    {
        return $this->hasMany(SendingDomain::class);
    }

    public function recipients()
    {
        return $this->hasMany(ClientRecipient::class);
    }

    public function recipientGroups()
    {
        return $this->hasMany(RecipientGroup::class);
    }

    public function reminderTemplates()
    {
        return $this->hasMany(ReminderTemplate::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    /**
     * Generate a fresh API token, store its hash, and return the plaintext
     * (shown to the user once — never recoverable afterwards).
     */
    public function generateApiToken(): string
    {
        $plain = 'idk_' . Str::random(48);
        $this->forceFill([
            'api_token' => hash('sha256', $plain),
            'api_token_last_four' => substr($plain, -4),
            'api_token_generated_at' => now(),
        ])->save();

        return $plain;
    }

    public static function findByApiToken(string $plain): ?self
    {
        return static::where('api_token', hash('sha256', $plain))->first();
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
