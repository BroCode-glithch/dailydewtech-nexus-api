<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Projects extends Model
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;
    protected $fillable = [
        'title',
        'slug',
        'thumbnail',
        'description',
        'category',
        'link',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function isPublished()
    {
        return $this->status === 'published';
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    public function getThumbnailUrlAttribute()
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : null;
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['thumbnail_url'] = $this->thumbnail_url;
        return $array;
    }

    public function linkOrDefault($default = '#')
    {
        return $this->link ?: $default;
    }

    public function categoryOrDefault($default = 'Uncategorized')
    {
        return $this->category ?: $default;
    }

    public function publish()
    {
        $this->status = 'published';
        $this->save();
    }

    public function unpublish()
    {
        $this->status = 'draft';
        $this->save();
    }

    public function toggleStatus()
    {
        $this->status = $this->isPublished() ? 'draft' : 'published';
        $this->save();
    }

    public function scopeSearch($query, $term)
    {
        $term = '%' . $term . '%';
        return $query->where(function ($query) use ($term) {
            $query->where('title', 'like', $term)
                  ->orWhere('description', 'like', $term)
                  ->orWhere('category', 'like', $term);
        });
    }

    public function scopeBySlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByTitle($query, $title)
    {
        return $query->where('title', 'like', '%' . $title . '%');
    }

    public function scopeByLink($query, $link)
    {
        return $query->where('link', 'like', '%' . $link . '%');
    }

    public function scopeRecentDrafts($query)
    {
        return $query->draft()->recent();
    }

    public function scopeRecentPublished($query)
    {
        return $query->published()->recent();
    }

    public function scopeWithCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeWithoutCategory($query, $category)
    {
        return $query->where('category', '!=', $category);
    }

    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWithoutStatus($query, $status)
    {
        return $query->where('status', '!=', $status);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeOldestFirst($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopePublishedBetween($query, $startDate, $endDate)
    {
        return $query->where('status', 'published')->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeDraftedBetween($query, $startDate, $endDate)
    {
        return $query->where('status', 'draft')->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeByTitleExact($query, $title)
    {
        return $query->where('title', $title);
    }
    
    public function scopeByLinkExact($query, $link)
    {
        return $query->where('link', $link);
    }

    public function scopeByDescription($query, $description)
    {
        return $query->where('description', 'like', '%' . $description . '%');
    }

    public function scopeByDescriptionExact($query, $description)
    {
        return $query->where('description', $description);
    }

    public function scopeByCategoryExact($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeWithThumbnail($query)
    {
        return $query->whereNotNull('thumbnail');
    }

    public function scopeWithoutThumbnail($query)
    {
        return $query->whereNull('thumbnail');
    }

    public function scopeRecentWithThumbnail($query)
    {
        return $query->withThumbnail()->recent();
    }

    public function scopeOldestWithThumbnail($query)
    {
        return $query->withThumbnail()->oldest();
    }

    public function scopeRecentWithoutThumbnail($query)
    {
        return $query->withoutThumbnail()->recent();
    }

    public function scopeOldestWithoutThumbnail($query)
    {
        return $query->withoutThumbnail()->oldest();
    }

    public function scopeBySlugExact($query, $slug)
    {
        return $query->where('slug', $slug);
    }
    
    public function scopeByCreatedAt($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }

    public function scopeByUpdatedAt($query, $date)
    {
        return $query->whereDate('updated_at', $date);
    }

    public function scopeByStatusAndCategory($query, $status, $category)
    {
        return $query->where('status', $status)->where('category', $category);
    }

    public function scopeByStatusAndTitle($query, $status, $title)
    {
        return $query->where('status', $status)->where('title', 'like', '%' . $title . '%');
    }

    public function scopeByCategoryAndTitle($query, $category, $title)
    {
        return $query->where('category', $category)->where('title', 'like', '%' . $title . '%');
    }

    public function scopeByStatusCategoryAndTitle($query, $status, $category, $title)
    {
        return $query->where('status', $status)
                     ->where('category', $category)
                     ->where('title', 'like', '%' . $title . '%');
    }

    public function scopePublishedInLastDays($query, $days)
    {
        return $query->where('status', 'published')
                     ->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeDraftedInLastDays($query, $days)
    {
        return $query->where('status', 'draft')
                     ->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeWithThumbnailAndCategory($query, $category)
    {
        return $query->withThumbnail()->where('category', $category);
    }
}
