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

        // Danh sách các vai trò được xem là vai trò quản trị
        $adminRoles = [
            'super-admin', 
            'sales-manager', 
            'product-manager', 
            'content-editor', 
            'support-staff'
        ];

        // Kiểm tra xem người dùng có bất kỳ vai trò nào trong danh sách quản trị không.
        // Dùng hasAnyRole() là cách làm đúng chuẩn của Spatie.
        if ($request->user()->hasAnyRole($adminRoles)) {
            // Nếu có, cho phép request đi tiếp vào lớp bảo vệ tiếp theo (permission middleware)
            return $next($request);
        }

        // Nếu không có vai trò nào hợp lệ, trả về lỗi.
        return response()->json(['message' => 'Bạn không có quyền truy cập vào khu vực quản trị.'], 403);
    }
}
