<?php

namespace App\Http\Controllers\API\Creative;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Chapter;
use App\Models\Like;
use App\Models\ReadingProgress;
use App\Models\Story;
use App\Models\StoryView;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CreativeEngagementController extends Controller
{
    public function trackView(Request $request, int $id)
    {
        $payload = $request->validate([
            'chapter_id' => 'nullable|integer|exists:chapters,id',
        ]);

        $story = Story::findOrFail($id);

        StoryView::create([
            'story_id' => $story->id,
            'chapter_id' => $payload['chapter_id'] ?? null,
            'user_id' => optional($request->user())->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'viewed_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'View tracked']);
    }

    public function like(Request $request)
    {
        $payload = $request->validate([
            'type' => ['required', Rule::in(['story', 'chapter'])],
            'id' => 'required|integer',
        ]);

        $modelClass = $payload['type'] === 'story' ? Story::class : Chapter::class;
        $model = $modelClass::findOrFail($payload['id']);

        Like::firstOrCreate([
            'user_id' => $request->user()->id,
            'likeable_type' => $model::class,
            'likeable_id' => $model->id,
        ]);

        return response()->json(['success' => true, 'message' => 'Liked']);
    }

    public function unlike(Request $request, string $type, int $id)
    {
        $modelClass = $type === 'story' ? Story::class : Chapter::class;

        Like::query()
            ->where('user_id', $request->user()->id)
            ->where('likeable_type', $modelClass)
            ->where('likeable_id', $id)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Like removed']);
    }

    public function bookmark(Request $request)
    {
        $payload = $request->validate([
            'story_id' => 'required|integer|exists:stories,id',
        ]);

        Bookmark::firstOrCreate([
            'user_id' => $request->user()->id,
            'story_id' => $payload['story_id'],
        ]);

        return response()->json(['success' => true, 'message' => 'Bookmarked']);
    }

    public function removeBookmark(Request $request, int $storyId)
    {
        Bookmark::query()
            ->where('user_id', $request->user()->id)
            ->where('story_id', $storyId)
            ->delete();

        return response()->json(['success' => true, 'message' => 'Bookmark removed']);
    }

    public function saveReadingProgress(Request $request)
    {
        $payload = $request->validate([
            'story_id' => 'required|integer|exists:stories,id',
            'chapter_id' => 'nullable|integer|exists:chapters,id',
            'progress' => 'required|integer|min:0|max:100',
        ]);

        $progress = ReadingProgress::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'story_id' => $payload['story_id'],
            ],
            [
                'chapter_id' => $payload['chapter_id'] ?? null,
                'progress_percent' => $payload['progress'],
                'last_read_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'data' => $progress]);
    }
}
