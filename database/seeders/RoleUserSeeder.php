<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleUserSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy ID của các vai trò
        $adminRoleId = DB::table('roles')->where('name', 'admin')->first()->id;
        $editorRoleId = DB::table('roles')->where('name', 'editor')->first()->id;
        $userRoleId = DB::table('roles')->where('name', 'user')->first()->id;

        // Lấy ID của các user
        $adminUserId = DB::table('users')->where('email', 'admin@example.com')->first()->id;
        $editorUserId = DB::table('users')->where('email', 'editor@example.com')->first()->id;
        $normalUserId = DB::table('users')->where('email', 'user@example.com')->first()->id;

        // Gán vai trò cho user
        DB::table('role_users')->insert([
            [
                'role_id' => $adminRoleId,
                'user_id' => $adminUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => $editorRoleId,
                'user_id' => $editorUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => $userRoleId,
                'user_id' => $normalUserId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 