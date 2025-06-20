<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {$user = $request->user();

    if ($user && ($user->hasRole('super-admin') || $user->can('roles.manage'))) {
        return $next($request);
    }

    return response()->json(['message' => 'Bạn không có quyền truy cập chức năng này.'], 403);
    }
}
