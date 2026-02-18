<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\API\{
    MessagesController,
    ProjectsController,
    DashboardController,
    PostsController,
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
});

// Public contact form (rate limited)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/contact', [ContactController::class, 'store']);
    Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe']);
    Route::post('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe']);
});

// Public read-only content endpoints
Route::prefix('public')->group(function () {
    // Posts - public can only read published posts
    Route::get('/posts', [PostsController::class, 'index']);
    Route::get('/posts/{id}', [PostsController::class, 'show']);

    // Projects - public can only read published projects
    Route::get('/projects', [ProjectsController::class, 'index']);
    Route::get('/projects/{id}', [ProjectsController::class, 'show']);
    Route::get('/projects/{id}/related', [ProjectsController::class, 'related']);

    // Random quote
    Route::get('/quotes/random', [QuotesController::class, 'random']);
});

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
        Route::post('/newsletter/broadcast', [AdminNewsletterController::class, 'sendBroadcast']);
    });
});
