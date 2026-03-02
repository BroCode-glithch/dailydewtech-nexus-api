<?php

namespace App\Http\Controllers\API\Creative;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Report;
use App\Models\Story;
use App\Notifications\CommentOnStoryNotification;
use App\Notifications\CommentReplyNotification;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CreativeCommentsController extends Controller
{
    public function index(Request $request)
    {
        $payload = $request->validate([
            'type' => ['required', Rule::in(['story', 'chapter'])],
            'id' => 'required|integer',
        ]);

        $modelClass = $payload['type'] === 'story' ? Story::class : Chapter::class;

        $comments = Comment::query()
            ->where('commentable_type', $modelClass)
            ->where('commentable_id', $payload['id'])
            ->whereNull('parent_id')
            ->where('status', 'visible')
            ->with(['user:id,name,username', 'children.user:id,name,username'])
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return response()->json(['success' => true, 'data' => $comments]);
    }

    public function store(Request $request)
    {
        $payload = $request->validate([
            'type' => ['required', Rule::in(['story', 'chapter'])],
            'id' => 'required|integer',
            'body' => 'required|string|max:5000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $modelClass = $payload['type'] === 'story' ? Story::class : Chapter::class;
        $model = $modelClass::findOrFail($payload['id']);

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'commentable_type' => $model::class,
            'commentable_id' => $model->id,
            'parent_id' => $payload['parent_id'] ?? null,
            'body' => trim($payload['body']),
            'status' => 'visible',
        ]);

        $story = $model instanceof Story ? $model : $model->story;
        $chapter = $model instanceof Chapter ? $model : null;

        if (!empty($payload['parent_id'])) {
            $parent = Comment::with('user')->find($payload['parent_id']);
            if ($parent && $parent->user_id !== $request->user()->id && $parent->user) {
                $parent->user->notify(new CommentReplyNotification($comment, $story, $chapter));
            }
        }

        if ($story && $story->author_id !== $request->user()->id && $story->author) {
            $story->author->notify(new CommentOnStoryNotification($comment, $story, $chapter));
        }

        return response()->json(['success' => true, 'data' => $comment], 201);
    }

    public function update(Request $request, int $id)
    {
        $comment = Comment::findOrFail($id);
        if ($comment->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $payload = $request->validate(['body' => 'required|string|max:5000']);
        $comment->update(['body' => trim($payload['body'])]);

        return response()->json(['success' => true, 'data' => $comment]);
    }

    public function destroy(Request $request, int $id)
    {
        $comment = Comment::findOrFail($id);
        if ($comment->user_id !== $request->user()->id && !in_array($request->user()->role, ['admin', 'super_admin', 'moderator', 'editor'], true)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comment->delete();
        return response()->json(['success' => true, 'message' => 'Comment deleted']);
    }

    public function createReport(Request $request)
    {
        $payload = $request->validate([
            'type' => ['required', Rule::in(['story', 'chapter', 'comment', 'user'])],
            'id' => 'required|integer',
            'reason' => 'required|string|max:150',
            'notes' => 'nullable|string|max:2000',
        ]);

        $map = [
            'story' => Story::class,
            'chapter' => Chapter::class,
            'comment' => Comment::class,
            'user' => \App\Models\User::class,
        ];

        $modelClass = $map[$payload['type']];
        $target = $modelClass::findOrFail($payload['id']);

        $report = Report::create([
            'reported_by' => $request->user()->id,
            'target_type' => $target::class,
            'target_id' => $target->id,
            'reason' => $payload['reason'],
            'notes' => $payload['notes'] ?? null,
            'status' => 'open',
        ]);

        return response()->json(['success' => true, 'data' => $report], 201);
    }
}
