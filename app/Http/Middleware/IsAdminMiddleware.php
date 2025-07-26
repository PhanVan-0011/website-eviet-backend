<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class IsAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Nếu người dùng chưa đăng nhập, để middleware 'auth:sanctum' xử lý.
        if (!auth()->check()) {
            return $next($request);
        }

        // "Bảo" với hệ thống phân quyền rằng hãy dùng cổng 'api' cho request này.
        // Điều này đảm bảo việc kiểm tra quyền sẽ khớp với cách người dùng đăng nhập.
        config(['auth.defaults.guard' => 'api']);

        // Nếu user có ít nhất 1 role bất kỳ thì cho qua
        if ($request->user()->roles()->exists()) {
            return $next($request);
        }

        // Nếu không có vai trò nào hợp lệ, trả về lỗi.
        return response()->json(['message' => 'Bạn không có quyền truy cập vào khu vực quản trị.'], 403);
    }
}
