<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            [
                'name' => 'Cà phê đen',
                'description' => 'Cà phê đen không đường',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bánh tráng',
                'description' => 'Bánh tráng phơi sương,bánh tráng xì ke',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        // Gán ảnh mẫu cho từng sản phẩm
        $products = \App\Models\Product::all();
        $sampleImages = [
            'products/caphe.jpg',
            'products/banhtrang.jpg',
        ];
        foreach ($products as $i => $product) {
            $product->images()->create([
                'image_url' => $sampleImages[$i % count($sampleImages)],
                'is_featured' => 1,
            ]);
        }
    }
}
