<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientDataSource extends Model
{
    protected $fillable = [
        'client_id', 'name', 'driver', 'host', 'port', 'database',
        'username', 'password_encrypted', 'table_or_view', 'custom_query',
        'col_name', 'col_phone', 'col_email', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'port'   => 'integer',
    ];

    protected $hidden = ['password_encrypted'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password_encrypted'] = encrypt($value);
    }

    public function getPasswordAttribute(): string
    {
        return $this->password_encrypted ? decrypt($this->password_encrypted) : '';
    }

    public function defaultPort(): int
    {
        return match ($this->driver) {
            'pgsql'  => 5432,
            'sqlsrv' => 1433,
            default  => 3306,
        };
    }
}
