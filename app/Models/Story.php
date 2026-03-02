<?php

namespace App\Models;

use App\Models\Bookmark;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Media;
use App\Models\StoryView;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Story extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'cover_image_id',
        'language',
        'status',
        'visibility',
        'author_id',
        'is_featured',
        'published_at',
        'completed_at',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function coverImage(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_image_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(Chapter::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'story_category')->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'story_tag')->withTimestamps();
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(StoryView::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}
