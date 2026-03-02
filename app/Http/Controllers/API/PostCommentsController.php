<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Posts;
use Illuminate\Http\Request;

class PostCommentsController extends Controller
{
    public function index(Request $request, string $post)
    {
        $postModel = $this->resolvePost($post, true);

        $comments = Comment::query()
            ->where('commentable_type', Posts::class)
            ->where('commentable_id', $postModel->id)
            ->whereNull('parent_id')
            ->where('status', 'visible')
            ->with('user:id,name,username,avatar')
            ->withCount([
                'children as replies_count' => function ($query) {
                    $query->where('status', 'visible');
                },
            ])
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }

    public function replies(Request $request, int $id)
    {
        $parent = Comment::query()->findOrFail($id);

        if ($parent->commentable_type !== Posts::class) {
            return response()->json(['message' => 'Comment not found for posts.'], 404);
        }

        $sort = $request->input('sort', 'latest');

        $replies = Comment::query()
            ->where('commentable_type', Posts::class)
            ->where('commentable_id', $parent->commentable_id)
            ->where('parent_id', $parent->id)
            ->where('status', 'visible')
            ->with('user:id,name,username,avatar')
            ->withCount([
                'children as replies_count' => function ($query) {
                    $query->where('status', 'visible');
                },
            ]);

        if ($sort === 'oldest') {
            $replies->oldest();
        } else {
            $replies->latest();
        }

        $replies = $replies
            ->paginate((int) $request->input('per_page', 10));

        return response()->json([
            'success' => true,
            'data' => $replies,
        ]);
    }

    public function store(Request $request, string $post)
    {
        $payload = $request->validate([
            'body' => 'required|string|max:5000',
            'parent_id' => 'nullable|integer|exists:comments,id',
        ]);

        $postModel = $this->resolvePost($post, true);

        if (!empty($payload['parent_id'])) {
            $parent = Comment::query()->findOrFail((int) $payload['parent_id']);

            if ($parent->commentable_type !== Posts::class || (int) $parent->commentable_id !== (int) $postModel->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent comment does not belong to this post.',
                ], 422);
            }
        }

        $comment = Comment::create([
            'user_id' => $request->user()->id,
            'commentable_type' => Posts::class,
            'commentable_id' => $postModel->id,
            'parent_id' => $payload['parent_id'] ?? null,
            'body' => trim($payload['body']),
            'status' => 'visible',
        ]);

        $comment->load('user:id,name,username,avatar');

        return response()->json([
            'success' => true,
            'data' => $comment,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $comment = Comment::query()->findOrFail($id);

        if ($comment->commentable_type !== Posts::class) {
            return response()->json(['message' => 'Comment not found for posts.'], 404);
        }

        if ((int) $comment->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $payload = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        $comment->update([
            'body' => trim($payload['body']),
        ]);

        return response()->json([
            'success' => true,
            'data' => $comment,
        ]);
    }

    public function destroy(Request $request, int $id)
    {
        $comment = Comment::query()->findOrFail($id);

        if ($comment->commentable_type !== Posts::class) {
            return response()->json(['message' => 'Comment not found for posts.'], 404);
        }

        $rolesAllowed = ['admin', 'super_admin', 'moderator', 'editor'];
        $isOwner = (int) $comment->user_id === (int) $request->user()->id;
        $isModerator = in_array($request->user()->role, $rolesAllowed, true);

        if (!$isOwner && !$isModerator) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $comment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comment deleted',
        ]);
    }

    private function resolvePost(string $post, bool $publishedOnly): Posts
    {
        $query = Posts::query();

        if ($publishedOnly) {
            $query->published();
        }

        return $query
            ->where('id', $post)
            ->orWhere('slug', $post)
            ->firstOrFail();
    }
}
