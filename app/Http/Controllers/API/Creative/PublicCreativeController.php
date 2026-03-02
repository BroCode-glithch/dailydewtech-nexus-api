<?php

namespace App\Http\Controllers\API\Creative;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Story;
use App\Models\Tag;
use App\Models\User;
use App\Support\CreativeRichText;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PublicCreativeController extends Controller
{
    public function stories(Request $request)
    {
        $query = Story::query()
            ->with(['author:id,name,username', 'coverImage:id,path,thumbnail_path,alt_text'])
            ->withCount(['likes', 'bookmarks', 'views'])
            ->published();

        if ($search = $request->input('search')) {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('summary', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        if ($category = $request->input('category')) {
            $query->whereHas('categories', fn($builder) => $builder->where('slug', $category));
        }

        if ($tag = $request->input('tag')) {
            $query->whereHas('tags', fn($builder) => $builder->where('slug', $tag));
        }

        $sort = $request->input('sort', 'latest');
        if ($sort === 'trending') {
            $query->withCount(['views as recent_views_count' => function ($builder) {
                $builder->where('viewed_at', '>=', now()->subDays(7));
            }])->orderByDesc('recent_views_count');
        } else {
            $query->orderByDesc('published_at');
        }

        $stories = $query->paginate((int) $request->input('per_page', 12));

        return response()->json(['success' => true, 'data' => $stories]);
    }

    public function showStory(Request $request, string $slug)
    {
        $story = $this->resolveReadableStory($request, $slug)
            ->with([
                'author:id,name,username',
                'coverImage:id,path,thumbnail_path,alt_text',
                'categories:id,name,slug',
                'tags:id,name,slug',
                'chapters' => function ($builder) {
                    $builder->orderBy('chapter_number');
                },
            ])
            ->withCount(['likes', 'bookmarks', 'views'])
            ->firstOrFail();

        if (!$this->canPreviewUnpublished($request, $story)) {
            $story->setRelation('chapters', $story->chapters->filter(function ($chapter) {
                return $chapter->status === 'published'
                    && !is_null($chapter->published_at)
                    && $chapter->published_at <= now();
            })->values());
        }

        return response()->json(['success' => true, 'data' => $story]);
    }

    public function chapterReader(Request $request, string $slug, int $chapterNumber)
    {
        $story = $this->resolveReadableStory($request, $slug)->firstOrFail();
        $canPreviewUnpublished = $this->canPreviewUnpublished($request, $story);

        $chapterQuery = Chapter::query()
            ->with(['featuredImage:id,path,thumbnail_path,alt_text'])
            ->where('story_id', $story->id)
            ->where('chapter_number', $chapterNumber);

        if (!$canPreviewUnpublished) {
            $chapterQuery->published();
        }

        $chapter = $chapterQuery->firstOrFail();

        $fullContentHtml = CreativeRichText::sanitize((string) $chapter->content_html);
        $chapter->content_html = $fullContentHtml;

        $paginateContent = (bool) $request->boolean('paginate_content', false);
        $contentPage = max(1, (int) $request->input('page', 1));
        $pageSize = min(5000, max(600, (int) $request->input('page_size', 1800)));

        $pagedContent = $this->paginateContentHtml($fullContentHtml, $contentPage, $pageSize);
        if ($paginateContent) {
            $chapter->content_html = $pagedContent['content_html'];
        }

        $prevQuery = Chapter::query()
            ->where('story_id', $story->id)
            ->where('chapter_number', '<', $chapter->chapter_number)
            ->orderByDesc('chapter_number');

        $nextQuery = Chapter::query()
            ->where('story_id', $story->id)
            ->where('chapter_number', '>', $chapter->chapter_number)
            ->orderBy('chapter_number');

        if (!$canPreviewUnpublished) {
            $prevQuery->published();
            $nextQuery->published();
        }

        $prev = $prevQuery->first(['id', 'chapter_number', 'title']);

        $next = $nextQuery->first(['id', 'chapter_number', 'title']);

        return response()->json([
            'success' => true,
            'data' => [
                'story' => [
                    'id' => $story->id,
                    'title' => $story->title,
                    'slug' => $story->slug,
                ],
                'chapter' => $chapter,
                'navigation' => [
                    'previous' => $prev,
                    'next' => $next,
                ],
                'content_pagination' => [
                    'enabled' => $paginateContent,
                    'current_page' => $pagedContent['current_page'],
                    'total_pages' => $pagedContent['total_pages'],
                    'page_size' => $pageSize,
                    'has_previous' => $pagedContent['has_previous'],
                    'has_next' => $pagedContent['has_next'],
                    'previous_page' => $pagedContent['previous_page'],
                    'next_page' => $pagedContent['next_page'],
                    'total_characters' => $pagedContent['total_characters'],
                ],
            ],
        ]);
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

    private function resolveReadableStory(Request $request, string $slug)
    {
        $query = Story::query()->where('slug', $slug);

        $publishedStory = (clone $query)->published()->first();
        if ($publishedStory) {
            return Story::query()->where('id', $publishedStory->id);
        }

        $user = auth('sanctum')->user();
        if (!$user) {
            return Story::query()->where('id', 0);
        }

        if (in_array($user->role, ['admin', 'super_admin', 'editor'], true)) {
            return Story::query()->where('slug', $slug);
        }

        return Story::query()->where('slug', $slug)->where('author_id', $user->id);
    }

    private function canPreviewUnpublished(Request $request, Story $story): bool
    {
        if ($story->status === 'published') {
            return true;
        }

        /** @var User|null $user */
        $user = auth('sanctum')->user();
        if (!$user) {
            return false;
        }

        if ((int) $story->author_id === (int) $user->id) {
            return true;
        }

        return in_array($user->role, ['admin', 'super_admin', 'editor'], true);
    }

    public function categories()
    {
        return response()->json([
            'success' => true,
            'data' => Category::query()->orderBy('name')->get(),
        ]);
    }

    public function tags()
    {
        return response()->json([
            'success' => true,
            'data' => Tag::query()->orderBy('name')->get(),
        ]);
    }

    public function trending(Request $request)
    {
        $days = max(1, min(30, (int) $request->input('days', 7)));
        $from = Carbon::now()->subDays($days);

        try {
            $stories = Story::query()
                ->published()
                ->with(['author:id,name,username', 'coverImage:id,path,thumbnail_path,alt_text'])
                ->withCount([
                    'views as views_window' => fn($builder) => $builder->where('viewed_at', '>=', $from),
                    'likes as likes_window' => fn($builder) => $builder->where('created_at', '>=', $from),
                ])
                ->get()
                ->map(function ($story) {
                    $story->trending_score = ((int) ($story->views_window ?? 0) * 1) + ((int) ($story->likes_window ?? 0) * 3);
                    return $story;
                })
                ->sortByDesc('trending_score')
                ->values()
                ->take(10);

            return response()->json(['success' => true, 'data' => $stories]);
        } catch (\Exception $e) {
            // Log the error internally but return safe response
            report($e);
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Trending data temporarily unavailable',
            ]);
        }
    }
}
