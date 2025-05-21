<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
                'description' => 'Cà phê đen đâm',
                'size' => 'L',
                'original_price' => 15000,
                'sale_price' => 20000,
                'stock_quantity' => 5,
                'image_url' => 'https://example.com/images/ca-phe.jpg',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'category_id' => 12, 
            ],
            [
                'name' => 'Bánh tráng',
                'description' => 'Bánh tráng phơi sương,bánh tráng xì ke ăn vaywj',
                'size' => null,
                'original_price' => 5000,
                'sale_price' => 6000,
                'stock_quantity' => 20,
                'image_url' => 'https://example.com/images/banh-trang.jpg',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
                'category_id' => 13, 
            ],
        ]);
    }   
}
