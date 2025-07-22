<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        config(['auth.defaults.guard' => 'api']);
        $user = $request->user();
        $route = $request->path();
        if (!$user) {
            Log::warning("[PERMISSION] Chưa đăng nhập | Route: $route");
            return response()->json(['message' => 'Bạn không có quyền truy cập chức năng này.'], 403);
        }
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();
        Log::info("[PERMISSION] User: {$user->id} ({$user->email}) | Route: $route | Kiểm tra quyền: " . json_encode($permissions) . " | User có: " . json_encode($userPermissions));
        Log::info("[PERMISSION-DEBUG] User roles: " . json_encode($user->roles->pluck('name', 'id')));
        Log::info("[PERMISSION-DEBUG] User permissions: " . json_encode($userPermissions));
        foreach ($permissions as $permission) {
            $can = $user->can($permission);
            Log::info("[PERMISSION-DEBUG] User: {$user->id} | Đang kiểm tra quyền: $permission | Kết quả: " . ($can ? 'YES' : 'NO'));
            if ($can) {
                Log::info("[PERMISSION] User: {$user->id} ({$user->email}) | Route: $route | ĐƯỢC PHÉP với quyền: $permission");
                return $next($request);
            }
        }
        Log::warning("[PERMISSION] User: {$user->id} ({$user->email}) | Route: $route | KHÔNG có quyền nào trong: " . json_encode($permissions));
        return response()->json(['message' => 'Bạn không có quyền truy cập chức năng này.'], 403);
    }
}
