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

        // Danh sách slider mẫu
        $sliders = [
            [
                'title' => 'Chào mừng đến với E-Viet',
                'description' => 'Khám phá thế giới mua sắm trực tuyến',
                'display_order' => 1,
                'is_active' => true,
                'linkable_type' => 'App\\Models\\Product',
                'linkable_id' => $productId,
            ],
            [
                'title' => 'Ưu đãi đặc biệt',
                'description' => 'Giảm giá lên đến 50% cho các sản phẩm',
                'display_order' => 2,
                'is_active' => true,
                'linkable_type' => 'App\\Models\\Combo',
                'linkable_id' => $comboId,
            ],
            [
                'title' => 'Sản phẩm mới',
                'description' => 'Khám phá các sản phẩm mới nhất',
                'display_order' => 3,
                'is_active' => true,
                'linkable_type' => 'App\\Models\\Post',
                'linkable_id' => $postId,
            ],
        ];

        $imagePaths = [
            'sliders/welcome.jpg',
            'sliders/special-offer.jpg',
            'sliders/new-products.jpg',
        ];

        foreach ($sliders as $i => $sliderData) {
            $slider = \App\Models\Slider::create($sliderData);
            $slider->image()->create([
                'image_url' => $imagePaths[$i],
                'is_featured' => 1,
            ]);
        }
    }
}
