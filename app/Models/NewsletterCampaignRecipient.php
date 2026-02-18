<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterCampaignRecipient extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'subscriber_id',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(\App\Models\NewsletterCampaign::class, 'campaign_id');
    }

    public function subscriber()
    {
        return $this->belongsTo(\App\Models\NewsletterSubscriber::class, 'subscriber_id');
    }
}
