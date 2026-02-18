<?php

namespace App\Http\Controllers\API;

use App\Models\Posts;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PostsController extends Controller
{
    /**
     * Display a listing of the posts.
     */
    public function index(Request $request)
    {
        $query = Posts::query()->withUser();

        // For public routes, default to published posts only
        // Admin can pass status=all or status=draft
        $status = $request->input('status', 'published');
        if ($status === 'all') {
            // Show all statuses (admin only)
        } elseif ($status) {
            $query->byStatus($status);
        }

        // Search filter
        if ($search = $request->input('search')) {
            $query->search($search);
        }

        // Tag filter
        if ($tag = $request->input('tag')) {
            $query->withTag($tag);
        }

        // User filter (admin feature)
        if ($userId = $request->input('user_id')) {
            $query->byUser($userId);
        }

        // Sorting
        $sortField = $request->input('sort', 'published_at');
        $sortDirection = $request->input('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $posts = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    /**
     * Store a newly created post.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'       => 'required|string|max:255',
            'excerpt'     => 'nullable|string|max:500',
            'content'     => 'required|string',
            'cover_image' => 'nullable|string',
            'tags'        => 'nullable|array',
            'status'      => 'in:draft,published',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Require an authenticated user for creating posts. If not authenticated,
        // return 401 to avoid inserting a post with null user_id (DB constraint).
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated. You must be logged in to create posts.'
            ], 401);
        }

        $data['slug'] = Str::slug($data['title']) . '-' . uniqid();
        $data['user_id'] = $user->id;

        if (($data['status'] ?? null) === 'published') {
            $data['published_at'] = now();
        }

        $post = Posts::create($data);

        return response()->json([
            'message' => 'Post created successfully.',
            'data'    => $post,
        ], 201);
    }

    /**
     * Display the specified post.
     */
    public function show($id)
    {
        $post = Posts::withUser()
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $post
        ]);
    }

    /**
     * Update the specified post.
     */
    public function update(Request $request, $id)
    {
        $post = Posts::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title'       => 'sometimes|string|max:255',
            'excerpt'     => 'nullable|string|max:500',
            'content'     => 'sometimes|string',
            'cover_image' => 'nullable|string',
            'tags'        => 'nullable|array',
            'status'      => 'in:draft,published',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']) . '-' . uniqid();
        }

        if (isset($data['status'])) {
            if ($data['status'] === 'published' && !$post->isPublished()) {
                $data['published_at'] = now();
            } elseif ($data['status'] === 'draft') {
                $data['published_at'] = null;
            }
        }

        $post->update($data);

        return response()->json([
            'message' => 'Post updated successfully.',
            'data'    => $post,
        ]);
    }

    /**
     * Remove the specified post from storage.
     */
    public function destroy($id)
    {
        $post = Posts::findOrFail($id);
        $post->delete();

        return response()->json([
            'message' => 'Post deleted successfully.',
        ]);
    }

    /**
     * Publish a post.
     */
    public function publish($id)
    {
        $post = Posts::findOrFail($id);
        $post->publish();

        return response()->json([
            'message' => 'Post published successfully.',
            'data'    => $post,
        ]);
    }

    /**
     * Unpublish a post.
     */
    public function unpublish($id)
    {
        $post = Posts::findOrFail($id);
        $post->unpublish();

        return response()->json([
            'message' => 'Post unpublished successfully.',
            'data'    => $post,
        ]);
    }
}
