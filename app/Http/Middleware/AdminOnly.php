<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (in_array($user->status ?? 'active', ['suspended', 'banned'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Account is not allowed to access this resource.',
            ], 403);
        }

        if (!in_array($user->role, ['admin', 'super_admin'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Admin access required.',
            ], 403);
        }

        return $next($request);
    }
}
