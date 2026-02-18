<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'name',
        'status',
        'unsubscribe_token',
        'subscribed_at',
        'unsubscribed_at',
        'source',
        'meta',
    ];

    protected $casts = [
        'subscribed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (NewsletterSubscriber $subscriber) {
            if (empty($subscriber->unsubscribe_token)) {
                $subscriber->unsubscribe_token = Str::random(64);
            }

            if (empty($subscriber->subscribed_at)) {
                $subscriber->subscribed_at = now();
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUnsubscribed($query)
    {
        return $query->where('status', 'unsubscribed');
    }
}
