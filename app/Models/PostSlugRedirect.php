<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostSlugRedirect extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'old_slug',
        'new_slug',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Posts::class, 'post_id');
    }
}
