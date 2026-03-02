<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreativeImportLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'story_id',
        'source_type',
        'original_filename',
        'file_size',
        'status',
        'warnings_json',
        'errors_json',
        'import_reference',
    ];

    protected $casts = [
        'warnings_json' => 'array',
        'errors_json' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }
}
