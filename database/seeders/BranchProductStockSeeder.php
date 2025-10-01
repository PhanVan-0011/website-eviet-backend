<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Branch;
use App\Models\BranchProductStock;

class BranchProductStockSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = Product::all();
        $branches = Branch::all();

        if ($products->isEmpty() || $branches->isEmpty()) {
            $this->command->warn('Cần có sẵn Product và Branch để seed BranchProductStock.');
            return;
        }

        foreach ($products as $product) {
            foreach ($branches as $branch) {
                BranchProductStock::create([
                    'product_id' => $product->id,
                    'branch_id' => $branch->id,
                    'quantity' => $product->name === 'Cà phê đen' ? 5 : 20,
                ]);
            }
        }

        $this->command->info('Đã tạo tồn kho cho ' . $products->count() . ' sản phẩm tại ' . $branches->count() . ' chi nhánh.');
    }
}
