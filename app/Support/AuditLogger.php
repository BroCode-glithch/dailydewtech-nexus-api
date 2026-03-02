<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(Request $request, string $action, ?Model $target = null, array $metadata = []): void
    {
        AuditLog::create([
            'user_id' => optional($request->user())->id,
            'action' => $action,
            'target_type' => $target ? $target::class : StoryFallback::class,
            'target_id' => $target?->id ?? 0,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
}

class StoryFallback {}
