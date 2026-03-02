<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $role = strtolower((string) ($user->role ?? 'user'));
        $allowed = collect($roles)->map(fn($item) => strtolower($item))->values();

        if (!$allowed->contains($role)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
