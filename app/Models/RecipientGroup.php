<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecipientGroup extends Model
{
    protected $fillable = ['client_id', 'name', 'description'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function recipients()
    {
        return $this->belongsToMany(ClientRecipient::class, 'recipient_group_member', 'recipient_group_id', 'client_recipient_id');
    }
}
