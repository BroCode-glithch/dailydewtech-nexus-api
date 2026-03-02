<?php

use Illuminate\Foundation\Application;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\AdminOnly;
use App\Http\Middleware\RequireRole;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Do not treat the frontend as stateful and do not inject the
        // EnsureFrontendRequestsAreStateful middleware. This application
        // is API-only and uses Bearer tokens; CSRF is intentionally
        // disabled.

        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'admin' => AdminOnly::class,
            'role' => RequireRole::class,
        ]);

        // CSRF token validation is intentionally disabled for this API.

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
