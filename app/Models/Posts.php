<?php

namespace App\Models;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Posts extends Model
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'cover_image',
        'excerpt',
        'content',
        'tags',
        'status',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'published_at' => 'datetime',
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    public function scopeOldest($query)
    {
        return $query->orderBy('published_at', 'asc');
    }

    public function isPublished()
    {
        return $this->status === 'published' && $this->published_at !== null && $this->published_at <= now();
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function publish()
    {
        $this->status = 'published';
        $this->published_at = now();
        $this->save();
    }

    public function unpublish()
    {
        $this->status = 'draft';
        $this->published_at = null;
        $this->save();
    }

    public function setTagsAttribute($value)
    {
        $this->attributes['tags'] = json_encode($value);
    }

    public function getTagsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function scopeByTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where('title', 'like', '%' . $term . '%')
            ->orWhere('excerpt', 'like', '%' . $term . '%')
            ->orWhere('content', 'like', '%' . $term . '%');
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeRecentDrafts($query)
    {
        return $query->draft()->recent();
    }

    public function scopeRecentPublished($query)
    {
        return $query->published()->recent();
    }

    public function scopeWithTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeWithoutTag($query, $tag)
    {
        return $query->where(function ($q) use ($tag) {
            $q->whereNull('tags')
                ->orWhereJsonDoesntContain('tags', $tag);
        });
    }

    public function scopePublishedBetween($query, $start, $end)
    {
        return $query->where('published_at', '>=', $start)->where('published_at', '<=', $end);
    }

    public function scopeDraftedBetween($query, $start, $end)
    {
        return $query->where('created_at', '>=', $start)->where('created_at', '<=', $end)->where('status', 'draft');
    }

    public function scopePublishedByUser($query, $userId)
    {
        return $query->published()->where('user_id', $userId);
    }

    public function scopeDraftedByUser($query, $userId)
    {
        return $query->draft()->where('user_id', $userId);
    }

    public function scopeWithCoverImage($query)
    {
        return $query->whereNotNull('cover_image');
    }

    public function scopeWithoutCoverImage($query)
    {
        return $query->whereNull('cover_image');
    }

    public function scopeRecentWithCoverImage($query)
    {
        return $query->withCoverImage()->recent();
    }

    public function scopeOldestWithCoverImage($query)
    {
        return $query->withCoverImage()->oldest();
    }

    public function scopeRecentWithoutCoverImage($query)
    {
        return $query->withoutCoverImage()->recent();
    }

    public function scopeOldestWithoutCoverImage($query)
    {
        return $query->withoutCoverImage()->oldest();
    }

    public function scopeByTitle($query, $title)
    {
        return $query->where('title', 'like', '%' . $title . '%');
    }

    public function scopeByExcerpt($query, $excerpt)
    {
        return $query->where('excerpt', 'like', '%' . $excerpt . '%');
    }

    public function scopeByContent($query, $content)
    {
        return $query->where('content', 'like', '%' . $content . '%');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPublishedAt($query, $date)
    {
        return $query->whereDate('published_at', $date);
    }

    public function scopeByCreatedAt($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }

    public function scopeByUpdatedAt($query, $date)
    {
        return $query->whereDate('updated_at', $date);
    }

    public function scopeByUserEmail($query, $email)
    {
        return $query->whereHas('user', function ($q) use ($email) {
            $q->where('email', $email);
        });
    }

    public function scopeByUserName($query, $name)
    {
        return $query->whereHas('user', function ($q) use ($name) {
            $q->where('name', 'like', '%' . $name . '%');
        });
    }

    public function scopeWithUser($query)
    {
        return $query->with('user');
    }

    public function scopeWithoutUser($query)
    {
        return $query->doesntHave('user');
    }

    public function scopeRecentByUser($query, $userId)
    {
        return $query->byUser($userId)->recent();
    }

    public function scopeOldestByUser($query, $userId)
    {
        return $query->byUser($userId)->oldest();
    }

    /**
     * Ensure an excerpt is generated from content when not provided.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($post) {
            // if excerpt is empty but content exists, generate a trimmed excerpt
            if (empty($post->excerpt) && !empty($post->content)) {
                $post->excerpt = Str::limit(strip_tags($post->content), 200);
            }
        });
    }
}
