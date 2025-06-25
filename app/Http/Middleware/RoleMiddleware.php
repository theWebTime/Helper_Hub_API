<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $role): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($role === 'admin' && !$user->is_admin) {
            return response()->json(['message' => 'Forbidden: Admins only'], 403);
        }

        if ($role === 'user' && $user->is_admin) {
            return response()->json(['message' => 'Forbidden: Users only'], 403);
        }

        return $next($request);
    }
}
