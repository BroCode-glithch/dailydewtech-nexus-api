<?php

namespace App\Http\Controllers\API\Creative;

use App\Http\Controllers\Controller;
use App\Models\Chapter;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Story;
use App\Models\StoryView;
use Illuminate\Http\Request;

class CreativeAuthorDashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $storyIds = Story::where('author_id', $user->id)->pluck('id');

        $totalStories = $storyIds->count();
        $publishedStories = Story::where('author_id', $user->id)->where('status', 'published')->count();
        $pendingStories = Story::where('author_id', $user->id)->where('status', 'pending')->count();
        $draftStories = Story::where('author_id', $user->id)->where('status', 'draft')->count();

        $chapterCount = Chapter::whereIn('story_id', $storyIds)->count();
        $commentCount = Comment::where('commentable_type', Story::class)
            ->whereIn('commentable_id', $storyIds)
            ->count();

        $likesCount = Like::whereIn('likeable_id', $storyIds)
            ->where('likeable_type', Story::class)
            ->count();

        $viewsCount = StoryView::whereIn('story_id', $storyIds)->count();

        $recentStories = Story::where('author_id', $user->id)
            ->latest()
            ->take(5)
            ->get(['id', 'title', 'status', 'published_at', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'counts' => [
                    'stories_total' => $totalStories,
                    'stories_published' => $publishedStories,
                    'stories_pending' => $pendingStories,
                    'stories_draft' => $draftStories,
                    'chapters_total' => $chapterCount,
                    'story_comments' => $commentCount,
                    'story_likes' => $likesCount,
                    'story_views' => $viewsCount,
                ],
                'recent_stories' => $recentStories,
            ],
        ]);
    }

    public function stories(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);

        $stories = Story::where('author_id', $request->user()->id)
            ->with(['categories', 'tags'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json(['success' => true, 'data' => $stories]);
    }

    public function showStory(Request $request, int $id)
    {
        $story = Story::where('author_id', $request->user()->id)
            ->with(['categories', 'tags', 'chapters'])
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $story]);
    }

    public function storyChapters(Request $request, int $id)
    {
        $story = Story::where('author_id', $request->user()->id)->findOrFail($id);

        $chapters = Chapter::where('story_id', $story->id)
            ->orderBy('chapter_number')
            ->get();

        return response()->json(['success' => true, 'data' => $chapters]);
    }
}
