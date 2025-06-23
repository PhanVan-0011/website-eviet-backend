<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next,$permission): Response
    {
        if (! $request->user() || ! $request->user()->can($permission)) {
            return response()->json(['message' => 'Bạn không có quyền truy cập chức năng này.'], 403);
        }
        return $next($request);
    }
}
