<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin',
                'email' => 'admin123@example.com',
                'phone' => '0123456789',
                'password' => Hash::make('password'),
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Editor',
                'email' => 'editor123@example.com',
                'phone' => '0123456788',
                'password' => Hash::make('password'),
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'User',
                'email' => 'user123@example.com',
                'phone' => '0123456787',
                'password' => Hash::make('password'),
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Admin1',
                'email' => 'user2@example.com',
                'phone' => '01234567873',
                'password' => Hash::make('password'),
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Admin2',
                'email' => 'user22@example.com',
                'phone' => '01234567874',
                'password' => Hash::make('password'),
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        // Gán ảnh mẫu cho từng user
        $users = \App\Models\User::all();
        $sampleImages = [
            'users/admin.jpg',
            'users/editor.jpg',
            'users/user.jpg',
            'users/admin1.jpg',
            'users/admin2.jpg',
        ];
        foreach ($users as $i => $user) {
            $user->image()->create([
                'image_url' => $sampleImages[$i % count($sampleImages)],
                'is_featured' => 1,
            ]);
        }
    }
}
