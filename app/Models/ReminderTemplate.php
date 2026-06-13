<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReminderTemplate extends Model
{
    protected $fillable = [
        'client_id', 'name', 'slug', 'channel',
        'sms_body', 'sender_name',
        'email_subject', 'email_from_name', 'email_from_address', 'email_reply_to', 'email_body',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    public static function makeSlug(string $name): string
    {
        return Str::slug($name);
    }
}
