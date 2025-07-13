<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ComboSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('combos')->insert([
            [
                'name' => 'Combo Mùa Hè Sôi Động',
                'description' => 'Bộ sưu tập các sản phẩm mùa hè với giá ưu đãi',
                'price' => 1500000,
                // 'discount_price' => 1200000,
                'slug' => Str::slug('Combo Mùa Hè Sôi Động'),
                'start_date' => now(),
                'end_date' => now()->addMonths(2),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Combo Khởi Đầu Mới',
                'description' => 'Dành cho khách hàng mới với nhiều ưu đãi hấp dẫn',
                'price' => 2000000,
                // 'discount_price' => 1500000,
                'slug' => Str::slug('Combo Khởi Đầu Mới'),
                'start_date' => now(),
                'end_date' => now()->addMonths(1),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Combo Cao Cấp',
                'description' => 'Bộ sưu tập các sản phẩm cao cấp với giá đặc biệt',
                'price' => 5000000,
                // 'discount_price' => 4000000,
                'slug' => Str::slug('Combo Cao Cấp'),
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Combo Tiết Kiệm',
                'description' => 'Các sản phẩm tiết kiệm với giá tốt nhất',
                'price' => 1000000,
                // 'discount_price' => 800000,
                'slug' => Str::slug('Combo Tiết Kiệm'),
                'start_date' => now(),
                'end_date' => now()->addMonths(1),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Combo Đặc Biệt Cuối Tuần',
                'description' => 'Ưu đãi đặc biệt chỉ có vào cuối tuần',
                'price' => 3000000,
                // 'discount_price' => 2500000,
                'slug' => Str::slug('Combo Đặc Biệt Cuối Tuần'),
                'start_date' => now(),
                'end_date' => now()->addDays(7),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
        // Gán ảnh mẫu cho từng combo
        $combos = \App\Models\Combo::all();
        $sampleImages = [
            'combos/combo1.jpg',
            'combos/combo2.jpg',
            'combos/combo3.jpg',
            'combos/combo4.jpg',
            'combos/combo5.jpg',
        ];
        foreach ($combos as $i => $combo) {
            $combo->image()->create([
                'image_url' => $sampleImages[$i % count($sampleImages)],
                'is_featured' => 1,
            ]);
        }
    }
}
