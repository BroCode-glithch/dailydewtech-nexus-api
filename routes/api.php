<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\API\Creative\CreativeAdminController;
use App\Http\Controllers\API\Creative\CreativeAuthorDashboardController;
use App\Http\Controllers\API\Creative\CreativeAuthorController;
use App\Http\Controllers\API\Creative\CreativeAuthorRequestController;
use App\Http\Controllers\API\Creative\CreativeCommentsController;
use App\Http\Controllers\API\Creative\CreativeEngagementController;
use App\Http\Controllers\API\Creative\CreativeMediaController;
use App\Http\Controllers\API\Creative\CreativeOgController;
use App\Http\Controllers\API\Creative\StoryImportController;
use App\Http\Controllers\API\Creative\PublicCreativeController;
use App\Http\Controllers\API\Creative\CreativeUserController;
use App\Http\Controllers\API\{
    MessagesController,
    PostCommentsController,
    ProjectsController,
    DashboardController,
    PostsController,
    PostsTagsController,
    QuotesController,
    ContactController,
    NewsletterController,
    AdminNewsletterController
};

// ====================
// PUBLIC ENDPOINTS
// ====================

// Authentication (rate limited)
Route::middleware('throttle:10,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Auth aliases for SPA compatibility
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
});

// Public contact form (rate limited)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/contact', [ContactController::class, 'store']);
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
    Route::post('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe']);
});

// Public read-only content endpoints
Route::prefix('public')->group(function () {
    // Public highlights for frontend KPI cards
    Route::get('/highlights', [DashboardController::class, 'publicHighlights']);

    // Posts - public can only read published posts
    Route::get('/posts', [PostsController::class, 'index']);
    Route::get('/posts/tags', [PostsTagsController::class, 'index']);
    Route::get('/posts/{post}/comments', [PostCommentsController::class, 'index']);
    Route::get('/posts/comments/{id}/replies', [PostCommentsController::class, 'replies']);
    Route::get('/posts/{id}', [PostsController::class, 'show']);

    // Projects - public can only read published projects
    Route::get('/projects', [ProjectsController::class, 'index']);
    Route::get('/projects/{id}', [ProjectsController::class, 'show']);
    Route::get('/projects/{id}/related', [ProjectsController::class, 'related']);

    // Random quote
    Route::get('/quotes/random', [QuotesController::class, 'random']);
});

// ====================
// CREATIVE - PUBLIC
// ====================
Route::prefix('creative')->group(function () {
    Route::get('/stories', [PublicCreativeController::class, 'stories']);
    Route::get('/stories/{slug}', [PublicCreativeController::class, 'showStory']);
    Route::get('/stories/{slug}/chapters/{chapterNumber}', [PublicCreativeController::class, 'chapterReader']);
    Route::get('/og/story/{slug}', [CreativeOgController::class, 'storyMeta']);
    Route::get('/og/story/{slug}/image', [CreativeOgController::class, 'storyImage']);
    Route::get('/og/story/{slug}/chapter/{chapterNumber}', [CreativeOgController::class, 'chapterMeta']);
    Route::get('/og/story/{slug}/chapter/{chapterNumber}/image', [CreativeOgController::class, 'chapterImage']);
    Route::get('/categories', [PublicCreativeController::class, 'categories']);
    Route::get('/tags', [PublicCreativeController::class, 'tags']);
    Route::get('/trending', [PublicCreativeController::class, 'trending']);
    Route::get('/comments', [CreativeCommentsController::class, 'index']);
    Route::post('/stories/{id}/view', [CreativeEngagementController::class, 'trackView'])->middleware('throttle:120,1');
});

// Public media access fallback (works even when /storage static serving is unavailable)
Route::get('/media/{id}', [CreativeMediaController::class, 'show'])->whereNumber('id');

// ====================
// ADMIN AUTHENTICATION
// ====================
Route::prefix('admin')->middleware('throttle:5,1')->group(function () {
    Route::post('/login/request-code', [AuthController::class, 'requestLoginCode']);
    Route::post('/login/verify-code', [AuthController::class, 'verifyLoginCode']);

    // Alias routes for frontend compatibility
    Route::post('/request-code', [AuthController::class, 'requestLoginCode']);
    Route::post('/verify-code', [AuthController::class, 'verifyLoginCode']);
});

// ====================
// AUTHENTICATED ENDPOINTS
// ====================
Route::middleware('auth:sanctum')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', fn(Request $request) => $request->user());
    Route::get('/user', fn(Request $request) => $request->user());

    // ====================
    // ADMIN PANEL ROUTES
    // ====================
    Route::prefix('admin')->middleware('admin')->group(function () {

        // Dashboard & Statistics
        Route::get('/stats', [DashboardController::class, 'stats']);

        // Messages Management
        Route::prefix('messages')->group(function () {
            Route::get('/trashed', [MessagesController::class, 'trashed']);
            Route::post('/{id}/restore', [MessagesController::class, 'restore']);
            Route::delete('/{id}/force', [MessagesController::class, 'forceDelete']);
        });
        Route::apiResource('messages', MessagesController::class);

        // Posts Management (full CRUD)
        Route::apiResource('posts', PostsController::class);
        Route::post('/posts/{id}/publish', [PostsController::class, 'publish']);
        Route::post('/posts/{id}/unpublish', [PostsController::class, 'unpublish']);

        // Projects Management (full CRUD)
        Route::apiResource('projects', ProjectsController::class);

        // Quotes Management
        Route::get('/quotes', [QuotesController::class, 'index']);
        Route::get('/quotes/inspire', [QuotesController::class, 'inspire']);

        // Newsletter Management
        Route::get('/newsletter/subscribers', [AdminNewsletterController::class, 'subscribers']);
        Route::patch('/newsletter/subscribers/{id}', [AdminNewsletterController::class, 'updateSubscriber']);
        Route::get('/newsletter/campaigns', [AdminNewsletterController::class, 'campaigns']);
        Route::get('/newsletter/campaigns/{id}', [AdminNewsletterController::class, 'campaignDetails']);
        Route::get('/newsletter/templates', [AdminNewsletterController::class, 'templates']);
        Route::post('/newsletter/preview', [AdminNewsletterController::class, 'preview']);
        Route::post('/newsletter/broadcast', [AdminNewsletterController::class, 'sendBroadcast']);

        // Creative Admin APIs
        Route::get('/creative/dashboard', [CreativeAdminController::class, 'dashboard']);
        Route::get('/creative/stories/status-summary', [CreativeAdminController::class, 'storyStatusSummary']);
        Route::get('/creative/stories', [CreativeAdminController::class, 'stories']);
        Route::get('/creative/stories/published', [CreativeAdminController::class, 'publishedStories']);
        Route::get('/creative/stories/by-slug/{slug}', [CreativeAdminController::class, 'showStoryBySlug']);
        Route::get('/creative/stories/{id}', [CreativeAdminController::class, 'showStory']);
        Route::get('/creative/users', [CreativeAdminController::class, 'users']);
        Route::patch('/creative/users/{id}', [CreativeAdminController::class, 'updateUser']);
        Route::delete('/creative/users/{id}', [CreativeAdminController::class, 'deleteUser']);
        Route::get('/creative/author-requests', [CreativeAdminController::class, 'authorRequests']);
        Route::patch('/creative/author-requests/{id}', [CreativeAdminController::class, 'reviewAuthorRequest']);
        Route::post('/creative/stories/{id}/publish', [CreativeAdminController::class, 'publishStory']);
        Route::post('/creative/stories/{id}/unpublish', [CreativeAdminController::class, 'unpublishStory']);
        Route::delete('/creative/stories/{id}', [CreativeAdminController::class, 'deleteStory']);
        Route::post('/creative/chapters/{id}/publish', [CreativeAdminController::class, 'publishChapter']);
        Route::post('/creative/chapters/{id}/unpublish', [CreativeAdminController::class, 'unpublishChapter']);
        Route::delete('/creative/chapters/{id}', [CreativeAdminController::class, 'deleteChapter']);
        Route::post('/creative/moderation/comments/{id}/hide', [CreativeAdminController::class, 'hideComment']);
        Route::get('/creative/reports', [CreativeAdminController::class, 'reports']);
        Route::post('/creative/reports/{id}/resolve', [CreativeAdminController::class, 'resolveReport']);
        Route::post('/creative/roles', [CreativeAdminController::class, 'createRole']);
        Route::post('/creative/permissions', [CreativeAdminController::class, 'createPermission']);
    });

    // Creative User Dashboard + Notifications
    Route::prefix('creative/me')->group(function () {
        Route::get('/dashboard', [CreativeUserController::class, 'dashboard']);
        Route::get('/notifications', [CreativeUserController::class, 'notifications']);
        Route::post('/notifications/{id}/read', [CreativeUserController::class, 'markNotificationRead']);
        Route::post('/notifications/read-all', [CreativeUserController::class, 'markAllNotificationsRead']);
    });

    // Author request flow (user applies)
    Route::get('/author/request', [CreativeAuthorRequestController::class, 'show']);
    Route::post('/author/request', [CreativeAuthorRequestController::class, 'store']);

    // Creative Engagement APIs
    Route::middleware('throttle:20,1')->post('/likes', [CreativeEngagementController::class, 'like']);
    Route::delete('/likes/{type}/{id}', [CreativeEngagementController::class, 'unlike']);
    Route::post('/bookmarks', [CreativeEngagementController::class, 'bookmark']);
    Route::delete('/bookmarks/{story_id}', [CreativeEngagementController::class, 'removeBookmark']);
    Route::post('/reading-progress', [CreativeEngagementController::class, 'saveReadingProgress']);

    // Creative Author APIs
    Route::prefix('author')->middleware('role:author,editor,admin,super_admin')->group(function () {
        Route::get('/dashboard', [CreativeAuthorDashboardController::class, 'dashboard']);
        Route::get('/stories', [CreativeAuthorDashboardController::class, 'stories']);
        Route::get('/stories/{id}', [CreativeAuthorDashboardController::class, 'showStory']);
        Route::get('/stories/{id}/chapters', [CreativeAuthorDashboardController::class, 'storyChapters']);
        Route::post('/stories/import-docx', [StoryImportController::class, 'importDocx'])->middleware('throttle:5,1');
        Route::get('/stories/import-docx/jobs/{jobId}', [StoryImportController::class, 'jobStatus']);
        Route::post('/stories', [CreativeAuthorController::class, 'createStory']);
        Route::patch('/stories/{id}', [CreativeAuthorController::class, 'updateStory']);
        Route::delete('/stories/{id}', [CreativeAuthorController::class, 'destroyStory']);
        Route::post('/stories/{id}/submit', [CreativeAuthorController::class, 'submitStory']);
        Route::post('/stories/{id}/chapters', [CreativeAuthorController::class, 'createChapter']);
        Route::patch('/chapters/{id}', [CreativeAuthorController::class, 'updateChapter']);
        Route::post('/chapters/{id}/submit', [CreativeAuthorController::class, 'submitChapter']);
    });

    // Comments and reports
    Route::middleware('throttle:15,1')->group(function () {
        Route::post('/posts/{post}/comments', [PostCommentsController::class, 'store']);
        Route::patch('/posts/comments/{id}', [PostCommentsController::class, 'update']);
        Route::delete('/posts/comments/{id}', [PostCommentsController::class, 'destroy']);
        Route::post('/comments', [CreativeCommentsController::class, 'store']);
        Route::patch('/comments/{id}', [CreativeCommentsController::class, 'update']);
        Route::delete('/comments/{id}', [CreativeCommentsController::class, 'destroy']);
        Route::post('/reports', [CreativeCommentsController::class, 'createReport']);
    });

    // Media upload
    Route::post('/media/upload', [CreativeMediaController::class, 'upload'])->middleware('throttle:10,1');
    Route::get('/media/upload-limits', [CreativeMediaController::class, 'uploadLimits']);
    Route::get('/media/health', [CreativeMediaController::class, 'mediaHealth']);
});
