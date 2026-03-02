<?php

namespace App\Http\Controllers\API\Creative;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Like;
use App\Models\ReadingProgress;
use App\Models\StoryView;
use Illuminate\Http\Request;

class CreativeUserController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $likesCount = Like::where('user_id', $user->id)->count();
        $bookmarksCount = Bookmark::where('user_id', $user->id)->count();
        $commentsCount = Comment::where('user_id', $user->id)->count();
        $storiesViewedCount = StoryView::where('user_id', $user->id)->distinct('story_id')->count('story_id');
        $readingProgressCount = ReadingProgress::where('user_id', $user->id)->count();
        $lastReadAt = ReadingProgress::where('user_id', $user->id)->max('last_read_at');

        $recentComments = Comment::where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get(['id', 'commentable_type', 'commentable_id', 'body', 'created_at']);

        $unreadNotifications = $user->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'counts' => [
                    'likes' => $likesCount,
                    'bookmarks' => $bookmarksCount,
                    'comments' => $commentsCount,
                    'stories_viewed' => $storiesViewedCount,
                    'reading_progress_entries' => $readingProgressCount,
                ],
                'last_read_at' => $lastReadAt,
                'recent_comments' => $recentComments,
                'unread_notifications' => $unreadNotifications,
            ],
        ]);
    }

    public function notifications(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $notifications = $request->user()->notifications()->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markNotificationRead(Request $request, string $id)
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json(['success' => true, 'message' => 'Notification marked as read']);
    }

    public function markAllNotificationsRead(Request $request)
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true, 'message' => 'All notifications marked as read']);
    }
}
