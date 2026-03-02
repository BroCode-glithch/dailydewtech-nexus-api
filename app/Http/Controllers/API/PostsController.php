<?php

namespace App\Http\Controllers\API;

use App\Models\PostSlugRedirect;
use App\Models\Posts;
use App\Support\RichText;
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
        $posts->getCollection()->transform(function ($post) {
            return $this->formatPostPayload($post);
        });

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
        $data['content'] = RichText::sanitize($data['content']);
        if (array_key_exists('excerpt', $data)) {
            $data['excerpt'] = RichText::toPlainText($data['excerpt'], 500);
        }

        // Require an authenticated user for creating posts. If not authenticated,
        // return 401 to avoid inserting a post with null user_id (DB constraint).
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated. You must be logged in to create posts.'
            ], 401);
        }

        $data['slug'] = $this->generateUniqueSlug($data['title']);
        $data['user_id'] = $user->id;

        if (($data['status'] ?? null) === 'published') {
            $data['published_at'] = now();
        }

        $post = Posts::create($data);

        return response()->json([
            'message' => 'Post created successfully.',
            'data'    => $this->formatPostPayload($post),
        ], 201);
    }

    /**
     * Display the specified post.
     */
    public function show(Request $request, $id)
    {
        $post = Posts::query()
            ->withUser()
            ->where('id', $id)
            ->orWhere('slug', $id)
            ->first();

        if (!$post && !is_numeric($id)) {
            $redirect = PostSlugRedirect::query()
                ->where('old_slug', $id)
                ->first();

            if ($redirect) {
                return redirect()->to(url('/api/public/posts/' . $redirect->new_slug), 301);
            }
        }

        if (!$post) {
            abort(404);
        }

        $paginateContent = (bool) $request->boolean('paginate_content', false);
        $contentPage = max(1, (int) $request->input('page', 1));
        $pageSize = min(5000, max(600, (int) $request->input('page_size', 1800)));

        return response()->json([
            'success' => true,
            'data' => $this->formatPostPayload($post, [
                'paginate_content' => $paginateContent,
                'page' => $contentPage,
                'page_size' => $pageSize,
            ])
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
            'slug'        => 'sometimes|string|max:191',
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
        if (array_key_exists('content', $data)) {
            $data['content'] = RichText::sanitize($data['content']);
        }
        if (array_key_exists('excerpt', $data)) {
            $data['excerpt'] = RichText::toPlainText($data['excerpt'], 500);
        }

        $oldSlug = $post->slug;

        if (array_key_exists('slug', $data)) {
            $candidate = Str::slug($data['slug']);
            if ($candidate === '') {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => ['slug' => ['Slug format is invalid.']],
                ], 422);
            }

            if ($candidate !== $post->slug) {
                $data['slug'] = $this->makeSlugUnique($candidate, $post->id);
            } else {
                unset($data['slug']);
            }
        } else {
            unset($data['slug']);
        }

        if (isset($data['status'])) {
            if ($data['status'] === 'published' && !$post->isPublished()) {
                $data['published_at'] = now();
            } elseif ($data['status'] === 'draft') {
                $data['published_at'] = null;
            }
        }

        $post->update($data);

        if (isset($data['slug']) && $oldSlug !== $post->slug) {
            PostSlugRedirect::query()->updateOrCreate(
                ['old_slug' => $oldSlug],
                [
                    'post_id' => $post->id,
                    'new_slug' => $post->slug,
                ]
            );
        }

        return response()->json([
            'message' => 'Post updated successfully.',
            'data'    => $this->formatPostPayload($post),
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
            'data'    => $this->formatPostPayload($post),
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
            'data'    => $this->formatPostPayload($post),
        ]);
    }

    private function formatPostPayload($post, ?array $contentPaging = null): array
    {
        $payload = $post->toArray();
        $sanitizedContent = RichText::sanitize((string) ($post->content ?? ''));

        $paginateContent = (bool) ($contentPaging['paginate_content'] ?? false);
        $contentPage = max(1, (int) ($contentPaging['page'] ?? 1));
        $pageSize = min(5000, max(600, (int) ($contentPaging['page_size'] ?? 1800)));

        $pagedContent = $this->paginateContentHtml($sanitizedContent, $contentPage, $pageSize);
        if ($paginateContent) {
            $sanitizedContent = $pagedContent['content_html'];
        }

        $payload['content_html'] = $sanitizedContent;
        $payload['content_text'] = RichText::toPlainText($sanitizedContent, 2000) ?? '';
        $payload['excerpt_text'] = RichText::toPlainText($post->excerpt, 500) ?? '';

        if ($contentPaging !== null) {
            $payload['content_pagination'] = [
                'enabled' => $paginateContent,
                'current_page' => $pagedContent['current_page'],
                'total_pages' => $pagedContent['total_pages'],
                'page_size' => $pageSize,
                'has_previous' => $pagedContent['has_previous'],
                'has_next' => $pagedContent['has_next'],
                'previous_page' => $pagedContent['previous_page'],
                'next_page' => $pagedContent['next_page'],
                'total_characters' => $pagedContent['total_characters'],
            ];
        }

        return $payload;
    }

    private function paginateContentHtml(string $html, int $page, int $pageSize): array
    {
        $normalized = trim($html);
        if ($normalized === '') {
            return [
                'content_html' => '',
                'current_page' => 1,
                'total_pages' => 1,
                'has_previous' => false,
                'has_next' => false,
                'previous_page' => null,
                'next_page' => null,
                'total_characters' => 0,
            ];
        }

        $segments = preg_split('/(?<=<\/p>)|(?<=<\/h1>)|(?<=<\/h2>)|(?<=<\/h3>)|(?<=<\/h4>)|(?<=<\/li>)|(?<=<\/blockquote>)|(?<=<\/div>)/i', $normalized);
        $segments = array_values(array_filter(array_map(static fn($segment) => trim((string) $segment), $segments), static fn($segment) => $segment !== ''));

        if (count($segments) === 0) {
            $segments = [$normalized];
        }

        $pages = [];
        $current = '';
        foreach ($segments as $segment) {
            $candidate = $current === '' ? $segment : $current . "\n" . $segment;
            if ($current !== '' && mb_strlen($candidate) > $pageSize) {
                $pages[] = $current;
                $current = $segment;
                continue;
            }

            $current = $candidate;
        }

        if ($current !== '') {
            $pages[] = $current;
        }

        $totalPages = max(1, count($pages));
        $currentPage = min($totalPages, max(1, $page));
        $index = $currentPage - 1;

        return [
            'content_html' => $pages[$index] ?? '',
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null,
            'total_characters' => mb_strlen($normalized),
        ];
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'post';
        }

        return $this->makeSlugUnique($base);
    }

    private function makeSlugUnique(string $base, ?int $ignorePostId = null): string
    {
        $slug = $base;
        $suffix = 2;

        while ($this->slugExists($slug, $ignorePostId)) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignorePostId = null): bool
    {
        $query = Posts::query()->where('slug', $slug);

        if ($ignorePostId) {
            $query->where('id', '!=', $ignorePostId);
        }

        return $query->exists();
    }
}
