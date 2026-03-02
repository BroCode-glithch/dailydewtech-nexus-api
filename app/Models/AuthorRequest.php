<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'bio',
        'sample_link',
        'accepted_terms_version',
        'accepted_privacy_version',
        'accepted_ip_policy_version',
        'accepted_community_guidelines_version',
        'accepted_at',
        'accepted_ip',
        'accepted_user_agent',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
