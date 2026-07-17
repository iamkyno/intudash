<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SendingDomain extends Model
{
    protected $fillable = [
        'client_id', 'domain', 'label', 'verification_token', 'dkim_tokens',
        'verification_status', 'dkim_status', 'last_checked_at',
    ];

    protected $casts = [
        'dkim_tokens'     => 'array',
        'last_checked_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * DNS records the user must add for this domain to verify (SES identity + DKIM).
     * Returned as a flat list ready for display, independent of provider quirks.
     */
    public function dnsRecords(): array
    {
        $records = [];

        if ($this->verification_token) {
            $records[] = [
                'type'  => 'TXT',
                'host'  => "_amazonses.{$this->domain}",
                'value' => $this->verification_token,
                'purpose' => 'Domain ownership verification',
            ];
        }

        foreach ((array) $this->dkim_tokens as $token) {
            $records[] = [
                'type'  => 'CNAME',
                'host'  => "{$token}._domainkey.{$this->domain}",
                'value' => "{$token}.dkim.amazonses.com",
                'purpose' => 'DKIM signing (improves deliverability & "friendly" trust)',
            ];
        }

        return $records;
    }
}
