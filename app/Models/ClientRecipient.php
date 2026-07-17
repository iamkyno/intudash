<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientRecipient extends Model
{
    protected $fillable = [
        'client_id', 'name', 'phone', 'phone_normalized',
        'email', 'custom_fields', 'status', 'invalid_reason',
    ];

    protected $casts = [
        'custom_fields' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function groups()
    {
        return $this->belongsToMany(RecipientGroup::class, 'recipient_group_member', 'client_recipient_id', 'recipient_group_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
