<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Check if user has any of the required roles
        foreach ($roles as $role) {
            if ($role === 'admin' && $user->is_admin) {
                return $next($request);
            }
            if ($role === 'user' && !$user->is_admin) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Forbidden: Insufficient permissions'], 403);
    }
}