<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Combo;
use App\Models\Post;

class SliderSeeder extends Seeder
{
    public function run(): void
    {
        // Lấy ID của một số sản phẩm, combo và bài viết để làm ví dụ
        $productId = Product::first()?->id ?? 1;
        $comboId = Combo::first()?->id ?? 1;
        $postId = Post::first()?->id ?? 1;

        DB::table('sliders')->insert([
            [
                'title' => 'Chào mừng đến với E-Viet',
                'description' => 'Khám phá thế giới mua sắm trực tuyến',
                'image_url' => 'sliders/welcome.jpg',
                'display_order' => 1,
                'is_active' => true,
                'linkable_type' => 'App\\Models\\Product',
                'linkable_id' => $productId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Ưu đãi đặc biệt',
                'description' => 'Giảm giá lên đến 50% cho các sản phẩm',
                'image_url' => 'sliders/special-offer.jpg',
                'display_order' => 2,
                'is_active' => true,
                'linkable_type' => 'App\\Models\\Combo',
                'linkable_id' => $comboId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Sản phẩm mới',
                'description' => 'Khám phá các sản phẩm mới nhất',
                'image_url' => 'sliders/new-products.jpg',
                'display_order' => 3,
                'is_active' => true,
                'linkable_type' => 'App\\Models\\Post',
                'linkable_id' => $postId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
