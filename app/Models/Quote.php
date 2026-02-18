<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'text',
        'author',
    ];

    protected static function booted(): void
    {
        static::creating(function ($quote) {
            $quote->unique_hash = hash('sha256', $quote->text . '|' . ($quote->author ?? ''));
        });
    }
}
