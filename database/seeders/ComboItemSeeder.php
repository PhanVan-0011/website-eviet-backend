<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComboItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Combo Mùa Hè Sôi Động (id: 1)
        DB::table('combo_items')->insert([
            [
                'combo_id' => 1,
                'product_id' => 1,
                'quantity' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'combo_id' => 1,
                'product_id' => 2,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Combo Khởi Đầu Mới (id: 2)
        DB::table('combo_items')->insert([
            [
                'combo_id' => 2,
                'product_id' => 1,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'combo_id' => 2,
                'product_id' => 2,
                'quantity' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Combo Cao Cấp (id: 3)
        DB::table('combo_items')->insert([
            [
                'combo_id' => 3,
                'product_id' => 1,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'combo_id' => 3,
                'product_id' => 2,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Combo Tiết Kiệm (id: 4)
        DB::table('combo_items')->insert([
            [
                'combo_id' => 4,
                'product_id' => 1,
                'quantity' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Combo Đặc Biệt Cuối Tuần (id: 5)
        DB::table('combo_items')->insert([
            [
                'combo_id' => 5,
                'product_id' => 2,
                'quantity' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
