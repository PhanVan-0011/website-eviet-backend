<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Combo;
use App\Models\Branch;
use App\Models\ComboPrice;

class ComboPriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $combos = Combo::all();
        $branches = Branch::all();

        if ($combos->isEmpty() || $branches->isEmpty()) {
            $this->command->warn('Cần có sẵn Combo và Branch để seed ComboPrice.');
            return;
        }

        $comboPrices = [
            'Combo Mùa Hè Sôi Động' => 1500000,
            'Combo Khởi Đầu Mới' => 2000000,
            'Combo Cao Cấp' => 5000000,
            'Combo Tiết Kiệm' => 1000000,
            'Combo Đặc Biệt Cuối Tuần' => 3000000,
        ];

        foreach ($combos as $combo) {
            foreach ($branches as $branch) {
                $basePrice = $comboPrices[$combo->name] ?? 1000000;

                // Tạo giá app (app_price)
                ComboPrice::create([
                    'combo_id' => $combo->id,
                    'branch_id' => $branch->id,
                    'price_type' => 'app_price',
                    'price' => $basePrice,
                    'start_date' => $combo->start_date,
                    'end_date' => $combo->end_date,
                ]);

                // Tạo giá khuyến mãi (promo_price) - thường thấp hơn giá app
                ComboPrice::create([
                    'combo_id' => $combo->id,
                    'branch_id' => $branch->id,
                    'price_type' => 'promo_price',
                    'price' => $basePrice * 0.8, // Giảm 20%
                    'start_date' => $combo->start_date,
                    'end_date' => $combo->end_date,
                ]);
            }
        }

        $this->command->info('Đã tạo giá cho ' . $combos->count() . ' combo tại ' . $branches->count() . ' chi nhánh.');
    }
}
