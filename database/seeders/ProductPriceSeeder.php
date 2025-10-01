<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Branch;
use App\Models\ProductPrice;

class ProductPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        $branches = Branch::all();

        if ($products->isEmpty() || $branches->isEmpty()) {
            $this->command->warn('Cần có sẵn Product và Branch để seed ProductPrice.');
            return;
        }

        foreach ($products as $product) {
            foreach ($branches as $branch) {
                // Tạo giá cửa hàng (store_price)
                ProductPrice::create([
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'price_type' => 'store_price',
                    'unit_of_measure' => 'cái',
                    'unit_multiplier' => 1,
                    'price' => $product->name === 'Cà phê đen' ? 20000 : 6000,
                    'start_date' => now(),
                ]);

                // Tạo giá app (app_price) - thường thấp hơn giá cửa hàng
                ProductPrice::create([
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'price_type' => 'app_price',
                    'unit_of_measure' => 'cái',
                    'unit_multiplier' => 1,
                    'price' => $product->name === 'Cà phê đen' ? 18000 : 5500,
                    'start_date' => now(),
                ]);

                // Tạo giá sỉ (wholesale_price) - thường thấp nhất
                ProductPrice::create([
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'price_type' => 'wholesale_price',
                    'unit_of_measure' => 'cái',
                    'unit_multiplier' => 1,
                    'price' => $product->name === 'Cà phê đen' ? 15000 : 5000,
                    'start_date' => now(),
                ]);
            }
        }

        $this->command->info('Đã tạo giá cho ' . $products->count() . ' sản phẩm tại ' . $branches->count() . ' chi nhánh.');
    }
}
