<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        //return $request->expectsJson() ? null : route('login');
        // Nếu là request API, trả về JSON thay vì chuyển hướng
        if ($request->expectsJson()) {
            return null; // Không chuyển hướng
        }

        return route('login'); // Chuyển hướng nếu không phải API


    }
    /**
     * Handle an unauthenticated user.
     */
    protected function unauthenticated($request, array $guards)
    {
        if ($request->expectsJson()) {
            abort(response()->json(['error' => 'Chưa được xác thực. Vui lòng đăng nhập.'], 401));
        }

        parent::unauthenticated($request, $guards);
    }
}
