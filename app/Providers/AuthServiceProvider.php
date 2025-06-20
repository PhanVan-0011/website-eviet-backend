<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // === BỔ SUNG ĐOẠN CODE NÀY VÀO ===

        // Gate::before() sẽ được chạy trước tất cả các lần kiểm tra quyền hạn khác.
        // Nó định nghĩa một "luật" đặc biệt cho Super Admin.
        Gate::before(function ($user, $ability) {
            // Kiểm tra xem người dùng có vai trò 'super-admin' hay không.
            // Nếu có, trả về `true` -> cho phép thực hiện mọi hành động mà không cần kiểm tra thêm.
            // Nếu không, trả về `null` -> để hệ thống tiếp tục kiểm tra quyền hạn theo cách thông thường.
            return $user->hasRole('super-admin') ? true : null;
        });
    }
}
