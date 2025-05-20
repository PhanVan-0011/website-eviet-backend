<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'name' => 'admin',
                'description' => 'Quản trị viên hệ thống',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'user',
                'description' => 'Người dùng thông thường',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'editor',
                'description' => 'Biên tập viên',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 