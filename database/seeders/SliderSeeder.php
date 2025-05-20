<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SliderSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sliders')->insert([
            [
                'title' => 'Chào mừng đến với E-Viet',
                'description' => 'Khám phá thế giới mua sắm trực tuyến',
                'image_url' => 'sliders/welcome.jpg',
                'link_url' => '/',
                'display_order' => 1,
                'is_active' => true,
                'link_type' => 'promotion',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Ưu đãi đặc biệt',
                'description' => 'Giảm giá lên đến 50% cho các sản phẩm',
                'image_url' => 'sliders/special-offer.jpg',
                'link_url' => '/promotions',
                'display_order' => 2,
                'is_active' => true,
                'link_type' => 'promotion',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Sản phẩm mới',
                'description' => 'Khám phá các sản phẩm mới nhất',
                'image_url' => 'sliders/new-products.jpg',
                'link_url' => '/new-products',
                'display_order' => 3,
                'is_active' => true,
                'link_type' => 'product',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
} 