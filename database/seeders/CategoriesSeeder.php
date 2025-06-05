<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as FakerFactory;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = FakerFactory::create('vi_VN');
        $categories = [];
        for ($i = 1; $i <= 20; $i++) {
            $categories[] = [
                'name' => $faker->unique()->words(rand(1, 3), true),
                'status' => $faker->boolean(90), // 90% là hiển thị
                'description' => $faker->sentence(8, true),
                'parent_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        DB::table('categories')->insert($categories);
    }
}
