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
                'email' => 'admin@example.com',
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
                'email' => 'editor@example.com',
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
                'email' => 'user@example.com',
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
    }
}
