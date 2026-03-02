<?php

namespace App\Http\Controllers\API\Creative;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Story;
use App\Support\CreativeRichText;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CreativeAuthorController extends Controller
{
    public function createStory(Request $request)
    {
        $payload = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'cover_image_id' => 'nullable|integer|exists:media,id',
            'language' => 'nullable|string|max:10',
            'visibility' => 'nullable|in:public,unlisted,private',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        $story = Story::create([
            'title' => $payload['title'],
            'slug' => Str::slug($payload['title']) . '-' . Str::lower(Str::random(6)),
            'summary' => $payload['summary'] ?? null,
            'description' => $payload['description'] ?? null,
            'cover_image_id' => $payload['cover_image_id'] ?? null,
            'language' => $payload['language'] ?? 'en',
            'visibility' => $payload['visibility'] ?? 'public',
            'author_id' => $request->user()->id,
            'status' => 'draft',
        ]);

        if (!empty($payload['category_ids'])) {
            $story->categories()->sync($payload['category_ids']);
        }

        if (!empty($payload['tag_ids'])) {
            $story->tags()->sync($payload['tag_ids']);
        }

        return response()->json(['success' => true, 'data' => $story], 201);
    }

    public function updateStory(Request $request, int $id)
    {
        $story = Story::findOrFail($id);
        if ($story->author_id !== $request->user()->id && !in_array($request->user()->role, ['admin', 'super_admin', 'editor'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $payload = $request->validate([
            'title' => 'sometimes|string|max:255',
            'summary' => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'cover_image_id' => 'nullable|integer|exists:media,id',
            'language' => 'sometimes|string|max:10',
            'visibility' => 'sometimes|in:public,unlisted,private',
            'is_featured' => 'sometimes|boolean',
            'status' => 'sometimes|in:draft,pending,published,archived',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        if (isset($payload['title'])) {
            $payload['slug'] = Str::slug($payload['title']) . '-' . Str::lower(Str::random(6));
        }

        $story->update($payload);

        if (array_key_exists('category_ids', $payload)) {
            $story->categories()->sync($payload['category_ids'] ?? []);
        }

        if (array_key_exists('tag_ids', $payload)) {
            $story->tags()->sync($payload['tag_ids'] ?? []);
        }

        return response()->json(['success' => true, 'data' => $story->fresh(['categories', 'tags'])]);
    }

    public function submitStory(Request $request, int $id)
    {
        $story = Story::findOrFail($id);
        if ($story->author_id !== $request->user()->id && !in_array($request->user()->role, ['admin', 'super_admin', 'editor'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $story->update(['status' => 'pending']);

        return response()->json(['success' => true, 'message' => 'Story submitted for review']);
    }

    public function destroyStory(Request $request, int $id)
    {
        $story = Story::findOrFail($id);
        if ($story->author_id !== $request->user()->id && !in_array($request->user()->role, ['admin', 'super_admin', 'editor'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $story->delete();

        return response()->json([
            'success' => true,
            'message' => 'Story deleted successfully.',
            'data' => ['id' => $id],
        ]);
    }

    public function createChapter(Request $request, int $storyId)
    {
        $story = Story::findOrFail($storyId);
        if ($story->author_id !== $request->user()->id && !in_array($request->user()->role, ['admin', 'super_admin', 'editor'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $payload = $request->validate([
            'chapter_number' => 'required|integer|min:1',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content_html' => 'required|string',
            'excerpt' => 'nullable|string',
            'featured_image_id' => 'nullable|integer|exists:media,id',
        ]);

        $payload['content_html'] = CreativeRichText::sanitize($payload['content_html']);
        $payload['story_id'] = $story->id;
        $payload['word_count'] = CreativeRichText::estimateWordCount($payload['content_html']);
        $payload['read_time_minutes'] = CreativeRichText::estimateReadTimeMinutes($payload['word_count']);

        $chapter = Chapter::create($payload);

        return response()->json(['success' => true, 'data' => $chapter], 201);
    }

    public function updateChapter(Request $request, int $id)
    {
        $chapter = Chapter::with('story')->findOrFail($id);
        if ($chapter->story->author_id !== $request->user()->id && !in_array($request->user()->role, ['admin', 'super_admin', 'editor'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $payload = $request->validate([
            'chapter_number' => 'sometimes|integer|min:1',
            'title' => 'sometimes|string|max:255',
            'slug' => 'nullable|string|max:255',
            'content_html' => 'sometimes|string',
            'excerpt' => 'nullable|string',
            'featured_image_id' => 'nullable|integer|exists:media,id',
            'published_at' => 'nullable|date',
            'status' => 'sometimes|in:draft,pending,published',
        ]);

        if (isset($payload['content_html'])) {
            $payload['content_html'] = CreativeRichText::sanitize($payload['content_html']);
            $payload['word_count'] = CreativeRichText::estimateWordCount($payload['content_html']);
            $payload['read_time_minutes'] = CreativeRichText::estimateReadTimeMinutes($payload['word_count']);
        }

        $chapter->update($payload);

        return response()->json(['success' => true, 'data' => $chapter->fresh()]);
    }

    public function submitChapter(Request $request, int $id)
    {
        $chapter = Chapter::with('story')->findOrFail($id);
        if ($chapter->story->author_id !== $request->user()->id && !in_array($request->user()->role, ['admin', 'super_admin', 'editor'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $chapter->update(['status' => 'pending']);

        return response()->json(['success' => true, 'message' => 'Chapter submitted for review']);
    }
}
