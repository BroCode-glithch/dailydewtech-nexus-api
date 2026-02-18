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

class Messages extends Model
{
    use HasFactory, Notifiable, HasApiTokens, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function markAsRead()
    {
        $this->status = 'read';
        $this->save();
    }

    public function markAsUnread()
    {
        $this->status = 'unread';
        $this->save();
    }

    public function isRead()
    {
        return $this->status === 'read';
    }

    public function isUnread()
    {
        return $this->status === 'unread';
    }

    public function scopeRead($query)
    {
        return $query->where('status', 'read');
    }

    public function scopeUnread($query)
    {
        return $query->where('status', 'unread');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    public function scopeBySubject($query, $subject)
    {
        return $query->where('subject', 'like', "%$subject%");
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%$term%")
              ->orWhere('email', 'like', "%$term%")
              ->orWhere('subject', 'like', "%$term%")
              ->orWhere('message', 'like', "%$term%");
        });
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
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

    public function scopePaginateResults($query, $perPage = 15)
    {
        return $query->paginate($perPage);
    }

    public function scopeCountMessages($query)
    {
        return $query->count();
    }

    public function scopeClearMessages($query)
    {
        return $query->delete();
    }

    public function scopeLatestMessage($query)
    {
        return $query->orderBy('created_at', 'desc')->first();
    }

    public function scopeOldestMessage($query)
    {
        return $query->orderBy('created_at', 'asc')->first();
    }

    public function scopeDistinctEmails($query)
    {
        return $query->select('email')->distinct();
    }

    public function scopeDistinctSubjects($query)
    {
        return $query->select('subject')->distinct();
    }

    public function scopeTrashed($query)
    {
        return $query->onlyTrashed();
    }

    public function scopeWithTrashed($query)
    {
        return $query->withTrashed();
    }

    public function scopeWithoutTrashed($query)
    {
        return $query->withoutTrashed();
    }

    public function scopeRestoreMessage($query, $id)
    {
        return $query->withTrashed()->where('id', $id)->restore();
    }

    public function scopeForceDeleteMessage($query, $id)
    {
        return $query->withTrashed()->where('id', $id)->forceDelete();
    }

    public function scopeUpdateStatus($query, $id, $status)
    {
        return $query->where('id', $id)->update(['status' => $status]);
    }

    public function scopeCreateMessage($query, $data)
    {
        return $query->create($data);
    }

    public function scopeUpdateMessage($query, $id, $data)
    {
        return $query->where('id', $id)->update($data);
    }

}
