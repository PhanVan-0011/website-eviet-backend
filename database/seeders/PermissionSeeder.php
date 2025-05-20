<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('permissions')->insert([
            [
                'name' => 'manage_users',
                'description' => 'Quản lý người dùng',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'manage_roles',
                'description' => 'Quản lý vai trò',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'manage_permissions',
                'description' => 'Quản lý quyền hạn',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'manage_sliders',
                'description' => 'Quản lý slider',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'view_dashboard',
                'description' => 'Xem trang tổng quan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 