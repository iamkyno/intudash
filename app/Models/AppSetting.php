<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /**
     * Keys whose values are encrypted at rest.
     */
    public const ENCRYPTED_KEYS = [
        'smsportal_api_secret',
        'aws_key',
        'aws_secret',
        'webhook_secret',
    ];

    public static function isEncryptedKey(string $key): bool
    {
        return in_array($key, self::ENCRYPTED_KEYS, true);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            if (!$setting) {
                return $default;
            }

            if (self::isEncryptedKey($key) && $setting->value !== null && $setting->value !== '') {
                try {
                    return Crypt::decryptString($setting->value);
                } catch (DecryptException) {
                    // Value was stored before encryption was enabled — return as-is.
                    return $setting->value;
                }
            }

            return $setting->value;
        });
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        $stored = $value;
        if (self::isEncryptedKey($key) && $value !== null && $value !== '') {
            $stored = Crypt::encryptString((string) $value);
        }

        static::updateOrCreate(['key' => $key], ['value' => $stored, 'group' => $group]);
        Cache::forget("setting_{$key}");
    }
}
