<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy id các category mẫu (giả sử đã có 3 category)
        $categoryIds = DB::table('categories')->pluck('id')->toArray();

        // Seed 3 bài viết mẫu
        $posts = [
            [
                'title' => 'Cách pha cà phê ngon tại nhà',
                'content' => 'Hướng dẫn chi tiết cách pha cà phê ngon như ngoài tiệm.',
                'slug' => Str::slug('Cách pha cà phê ngon tại nhà'),
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Bí quyết chọn trái cây tươi',
                'content' => 'Những mẹo nhỏ giúp bạn chọn được trái cây tươi ngon.',
                'slug' => Str::slug('Bí quyết chọn trái cây tươi'),
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Ăn vặt lành mạnh cho dân văn phòng',
                'content' => 'Gợi ý các món ăn vặt vừa ngon vừa tốt cho sức khỏe.',
                'slug' => Str::slug('Ăn vặt lành mạnh cho dân văn phòng'),
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Thêm bài viết vào bảng posts
        foreach ($posts as $index => $post) {
            $postId = DB::table('posts')->insertGetId($post);
            // Gán mỗi bài viết vào 1 category (nếu có category)
            if (!empty($categoryIds)) {
                DB::table('category_post')->insert([
                    'category_id' => $categoryIds[$index % count($categoryIds)],
                    'post_id' => $postId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            // Gán ảnh mẫu cho từng post
            $sampleImages = [
                'posts/post1.jpg',
                'posts/post2.jpg',
                'posts/post3.jpg',
            ];
            $postModel = \App\Models\Post::find($postId);
            if ($postModel) {
                $postModel->images()->create([
                    'image_url' => $sampleImages[$index % count($sampleImages)],
                    'is_featured' => 1,
                ]);
            }
        }
    }
}
