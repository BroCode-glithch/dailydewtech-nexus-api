<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginVerification extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
        'used',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
}
