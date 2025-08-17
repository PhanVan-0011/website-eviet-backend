<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin',
                'email' => 'admin123@example.com',
                'phone' => '0123456789',
            ],
            [
                'name' => 'Editor',
                'email' => 'editor123@example.com',
                'phone' => '0123456788',
            ],
            [
                'name' => 'User',
                'email' => 'user123@example.com',
                'phone' => '0123456787',
            ],
            [
                'name' => 'Admin1',
                'email' => 'user2@example.com',
                'phone' => '01234567873',
            ],
            [
                'name' => 'Admin2',
                'email' => 'user22@example.com',
                'phone' => '01234567874',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']], // Điều kiện kiểm tra tồn tại
                [
                    'name' => $userData['name'],
                    'phone' => $userData['phone'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'is_verified' => true,
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                ]
            );
        }

        // Gán ảnh mẫu cho từng user
        $sampleImages = [
            '',
            '',
            '',
            '',
            '',
        ];
        $users = User::all();
        foreach ($users as $i => $user) {
            if (!$user->image()->exists()) {
                $user->image()->create([
                    'image_url' => $sampleImages[$i % count($sampleImages)],
                    'is_featured' => 1,
                ]);
            }
        }
    }
}
