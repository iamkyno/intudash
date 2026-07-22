<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsReply extends Model
{
    protected $fillable = ['from_number', 'phone_normalized', 'message', 'is_opt_out', 'raw_payload'];

    protected $casts = [
        'is_opt_out'  => 'boolean',
        'raw_payload' => 'array',
    ];
}
