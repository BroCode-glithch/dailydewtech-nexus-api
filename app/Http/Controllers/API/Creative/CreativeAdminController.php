<?php

namespace App\Http\Controllers\API\Creative;

use App\Http\Controllers\Controller;
use App\Models\AuthorRequest;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Report;
use App\Models\Story;
use App\Models\StoryView;
use App\Models\User;
use App\Notifications\AuthorRequestStatusNotification;
use App\Notifications\StoryUpdateNotification;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CreativeAdminController extends Controller
{
    public function dashboard()
    {
        $days7 = now()->subDays(7);
        $days30 = now()->subDays(30);

        try {
            $topStories = Story::query()
                ->published()
                ->withCount([
                    'views as views_7d' => fn($query) => $query->where('viewed_at', '>=', $days7),
                    'likes as likes_7d' => fn($query) => $query->where('created_at', '>=', $days7),
                ])
                ->get()
                ->map(function ($story) {
                    $story->score_7d = ((int) ($story->views_7d ?? 0) * 1) + ((int) ($story->likes_7d ?? 0) * 3);
                    return $story;
                })
                ->sortByDesc('score_7d')
                ->values()
                ->take(10);

            return response()->json([
                'success' => true,
                'data' => [
                    'total_published_stories' => Story::where('status', 'published')->count(),
                    'chapters_published_this_week' => Chapter::where('status', 'published')->where('published_at', '>=', $days7)->count(),
                    'active_users_7d' => StoryView::where('viewed_at', '>=', $days7)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                    'active_users_30d' => StoryView::where('viewed_at', '>=', $days30)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                    'top_trending_stories_7d' => $topStories,
                ],
            ]);
        } catch (\Exception $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Dashboard data temporarily unavailable',
            ], 500);
        }
    }

    public function users(Request $request)
    {
        $users = User::query()->orderByDesc('created_at')->paginate((int) $request->input('per_page', 20));
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function updateUser(Request $request, int $id)
    {
        $payload = $request->validate([
            'role' => 'nullable|in:super_admin,admin,editor,author,moderator,user',
            'status' => 'nullable|in:active,suspended,banned',
        ]);

        $user = User::findOrFail($id);
        $user->update($payload);

        AuditLogger::log($request, 'creative.user.updated', $user, $payload);

        return response()->json(['success' => true, 'data' => $user]);
    }

    public function deleteUser(Request $request, int $id)
    {
        if ($request->user()->id === $id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }

        $user = User::findOrFail($id);
        $user->delete();

        AuditLogger::log($request, 'creative.user.deleted', $user);

        return response()->json(['success' => true, 'message' => 'User deleted successfully']);
    }

    public function authorRequests(Request $request)
    {
        $query = AuthorRequest::query()->with(['user:id,name,email,role', 'reviewer:id,name,email'])
            ->orderByDesc('created_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate((int) $request->input('per_page', 20)),
        ]);
    }

    public function stories(Request $request)
    {
        $payload = $request->validate([
            'status' => ['nullable', Rule::in(['all', 'draft', 'pending', 'published', 'archived'])],
            'author_id' => 'nullable|integer|exists:users,id',
            'search' => 'nullable|string|max:200',
            'sort' => ['nullable', Rule::in(['latest', 'oldest', 'published_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Story::query()
            ->with(['author:id,name,username,email', 'coverImage:id,path,thumbnail_path,alt_text'])
            ->withCount(['chapters', 'comments', 'likes', 'views']);

        if (($payload['status'] ?? 'all') !== 'all') {
            $query->where('status', $payload['status']);
        }

        if (!empty($payload['author_id'])) {
            $query->where('author_id', (int) $payload['author_id']);
        }

        if (!empty($payload['search'])) {
            $search = trim((string) $payload['search']);
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('summary', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $sort = $payload['sort'] ?? 'latest';
        $direction = $payload['direction'] ?? 'desc';
        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } elseif ($sort === 'published_at') {
            $query->orderBy('published_at', $direction);
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        $stories = $query->paginate((int) ($payload['per_page'] ?? 20));

        return response()->json([
            'success' => true,
            'data' => $stories,
        ]);
    }

    public function publishedStories(Request $request)
    {
        $payload = $request->validate([
            'author_id' => 'nullable|integer|exists:users,id',
            'search' => 'nullable|string|max:200',
            'sort' => ['nullable', Rule::in(['latest', 'oldest', 'published_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Story::query()
            ->with(['author:id,name,username,email', 'coverImage:id,path,thumbnail_path,alt_text'])
            ->withCount(['chapters', 'comments', 'likes', 'views'])
            ->where('status', 'published');

        if (!empty($payload['author_id'])) {
            $query->where('author_id', (int) $payload['author_id']);
        }

        if (!empty($payload['search'])) {
            $search = trim((string) $payload['search']);
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%' . $search . '%')
                    ->orWhere('summary', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%');
            });
        }

        $sort = $payload['sort'] ?? 'published_at';
        $direction = $payload['direction'] ?? 'desc';
        if ($sort === 'oldest') {
            $query->orderBy('created_at', 'asc');
        } elseif ($sort === 'latest') {
            $query->orderBy('updated_at', 'desc');
        } else {
            $query->orderBy('published_at', $direction);
        }

        $stories = $query->paginate((int) ($payload['per_page'] ?? 20));

        return response()->json([
            'success' => true,
            'data' => $stories,
        ]);
    }

    public function storyStatusSummary()
    {
        $summary = [
            'all' => Story::count(),
            'draft' => Story::where('status', 'draft')->count(),
            'pending' => Story::where('status', 'pending')->count(),
            'published' => Story::where('status', 'published')->count(),
            'archived' => Story::where('status', 'archived')->count(),
        ];

        $summary['unpublished_total'] = $summary['draft'] + $summary['pending'] + $summary['archived'];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    public function showStory(int $id)
    {
        $story = Story::query()
            ->with([
                'author:id,name,username,email',
                'coverImage:id,path,thumbnail_path,alt_text',
                'categories:id,name,slug',
                'tags:id,name,slug',
                'chapters' => fn($query) => $query->orderBy('chapter_number'),
            ])
            ->withCount(['chapters', 'comments', 'likes', 'views'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $story,
        ]);
    }

    public function showStoryBySlug(string $slug)
    {
        $story = Story::query()
            ->with([
                'author:id,name,username,email',
                'coverImage:id,path,thumbnail_path,alt_text',
                'categories:id,name,slug',
                'tags:id,name,slug',
                'chapters' => fn($query) => $query->orderBy('chapter_number'),
            ])
            ->withCount(['chapters', 'comments', 'likes', 'views'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $story,
        ]);
    }

    public function reviewAuthorRequest(Request $request, int $id)
    {
        $payload = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $authorRequest = AuthorRequest::with('user')->findOrFail($id);

        $authorRequest->update([
            'status' => $payload['status'],
            'admin_notes' => $payload['admin_notes'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($payload['status'] === 'approved' && $authorRequest->user) {
            $authorRequest->user->update(['role' => 'author']);
        }

        if ($authorRequest->user) {
            $authorRequest->user->notify(new AuthorRequestStatusNotification($authorRequest));
        }

        AuditLogger::log($request, 'creative.author_request.reviewed', $authorRequest, $payload);

        return response()->json(['success' => true, 'data' => $authorRequest]);
    }

    public function publishStory(Request $request, int $id)
    {
        $story = Story::findOrFail($id);
        $story->update([
            'status' => 'published',
            'published_at' => $story->published_at ?? now(),
        ]);

        $notificationDelivered = $this->notifyStoryAuthorSafely($story, 'Your story was published.');

        AuditLogger::log($request, 'creative.story.published', $story);

        return response()->json([
            'success' => true,
            'data' => $story,
            'notification_delivered' => $notificationDelivered,
        ]);
    }

    public function unpublishStory(Request $request, int $id)
    {
        $story = Story::findOrFail($id);
        $story->update(['status' => 'draft', 'published_at' => null]);

        $notificationDelivered = $this->notifyStoryAuthorSafely($story, 'Your story was unpublished.');

        AuditLogger::log($request, 'creative.story.unpublished', $story);

        return response()->json([
            'success' => true,
            'data' => $story,
            'notification_delivered' => $notificationDelivered,
        ]);
    }

    public function publishChapter(Request $request, int $id)
    {
        $chapter = Chapter::findOrFail($id);
        $chapter->update([
            'status' => 'published',
            'published_at' => $chapter->published_at ?? now(),
        ]);

        $story = $chapter->story;
        $notificationDelivered = false;
        if ($story) {
            $notificationDelivered = $this->notifyStoryAuthorSafely($story, 'A chapter was published for your story.');
        }

        AuditLogger::log($request, 'creative.chapter.published', $chapter);

        return response()->json([
            'success' => true,
            'data' => $chapter,
            'notification_delivered' => $notificationDelivered,
        ]);
    }

    public function unpublishChapter(Request $request, int $id)
    {
        $chapter = Chapter::findOrFail($id);
        $chapter->update(['status' => 'draft', 'published_at' => null]);

        $story = $chapter->story;
        $notificationDelivered = false;
        if ($story) {
            $notificationDelivered = $this->notifyStoryAuthorSafely($story, 'A chapter was unpublished for your story.');
        }

        AuditLogger::log($request, 'creative.chapter.unpublished', $chapter);

        return response()->json([
            'success' => true,
            'data' => $chapter,
            'notification_delivered' => $notificationDelivered,
        ]);
    }

    public function deleteStory(Request $request, int $id)
    {
        $story = Story::findOrFail($id);
        $story->delete();

        AuditLogger::log($request, 'creative.story.deleted', $story);

        return response()->json([
            'success' => true,
            'message' => 'Story deleted successfully.',
        ]);
    }

    public function deleteChapter(Request $request, int $id)
    {
        $chapter = Chapter::findOrFail($id);
        $chapter->delete();

        AuditLogger::log($request, 'creative.chapter.deleted', $chapter);

        return response()->json([
            'success' => true,
            'message' => 'Chapter deleted successfully.',
        ]);
    }

    private function notifyStoryAuthorSafely(Story $story, string $message): bool
    {
        if (!$story->author) {
            return false;
        }

        try {
            $story->author->notify(new StoryUpdateNotification($story, $message));
            return true;
        } catch (\Throwable $exception) {
            report($exception);
            return false;
        }
    }

    public function hideComment(Request $request, int $id)
    {
        $comment = Comment::findOrFail($id);
        $comment->update(['status' => 'hidden']);

        AuditLogger::log($request, 'creative.comment.hidden', $comment);

        return response()->json(['success' => true, 'data' => $comment]);
    }

    public function reports(Request $request)
    {
        $query = Report::query()->with(['reporter:id,name,email', 'resolver:id,name,email'])->latest();
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return response()->json(['success' => true, 'data' => $query->paginate((int) $request->input('per_page', 20))]);
    }

    public function resolveReport(Request $request, int $id)
    {
        $payload = $request->validate([
            'status' => 'required|in:resolved,rejected',
        ]);

        $report = Report::findOrFail($id);
        $report->update([
            'status' => $payload['status'],
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        AuditLogger::log($request, 'creative.report.resolved', $report, $payload);

        return response()->json(['success' => true, 'data' => $report]);
    }

    public function createRole(Request $request)
    {
        $payload = $request->validate([
            'name' => 'required|string|max:100',
            'guard_name' => 'nullable|string|max:50',
        ]);

        $roleClass = 'Spatie\\Permission\\Models\\Role';
        if (!class_exists($roleClass)) {
            return response()->json([
                'success' => false,
                'message' => 'spatie/laravel-permission is not installed in this environment.',
            ], 501);
        }

        $role = $roleClass::firstOrCreate([
            'name' => $payload['name'],
            'guard_name' => $payload['guard_name'] ?? 'sanctum',
        ]);

        return response()->json(['success' => true, 'data' => $role], 201);
    }

    public function createPermission(Request $request)
    {
        $payload = $request->validate([
            'name' => 'required|string|max:120',
            'guard_name' => 'nullable|string|max:50',
        ]);

        $permissionClass = 'Spatie\\Permission\\Models\\Permission';
        if (!class_exists($permissionClass)) {
            return response()->json([
                'success' => false,
                'message' => 'spatie/laravel-permission is not installed in this environment.',
            ], 501);
        }

        $permission = $permissionClass::firstOrCreate([
            'name' => $payload['name'],
            'guard_name' => $payload['guard_name'] ?? 'sanctum',
        ]);

        return response()->json(['success' => true, 'data' => $permission], 201);
    }
}
