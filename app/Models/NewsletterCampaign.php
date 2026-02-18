<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterCampaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject',
        'content',
        'status',
        'total_recipients',
        'sent_count',
        'failed_count',
        'sent_at',
        'created_by',
        'meta',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'meta' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function recipients()
    {
        return $this->hasMany(NewsletterCampaignRecipient::class, 'campaign_id');
    }
}
