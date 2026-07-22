<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = ['name', 'channel', 'min_units', 'unit_price', 'description', 'active'];

    protected $casts = [
        'min_units'  => 'integer',
        'unit_price' => 'decimal:4',
        'active'     => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }

    /**
     * Best (highest) tier whose minimum volume the given message count reaches.
     */
    public static function bestFor(string $channel, int $units): ?self
    {
        return static::active()
            ->where('channel', $channel)
            ->where('min_units', '<=', $units)
            ->orderByDesc('min_units')
            ->first();
    }
}
