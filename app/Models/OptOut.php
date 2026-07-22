<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptOut extends Model
{
    protected $fillable = ['phone_normalized', 'channel', 'source', 'keyword', 'opted_out_at'];

    protected $casts = [
        'opted_out_at' => 'datetime',
    ];

    public static function isOptedOut(?string $phoneNormalized): bool
    {
        if (!$phoneNormalized) {
            return false;
        }

        return static::where('phone_normalized', $phoneNormalized)->exists();
    }
}
