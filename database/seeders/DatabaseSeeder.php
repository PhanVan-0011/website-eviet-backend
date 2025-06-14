<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\OrderDetail;
use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
            RoleUserSeeder::class,
            CategoriesSeeder::class,
            ProductSeeder::class,
            SliderSeeder::class,
            PostSeeder::class,
            OrderSeeder::class,
            ComboSeeder::class,
            ComboItemSeeder::class,
        ]);

        // Tạo 5 người dùng
        User::factory()->count(0)->create();

        // Tạo 5 sản phẩm
        Product::factory()->count(2)->create();

        // Cập nhật total_amount cho mỗi đơn hàng
        foreach (Order::all() as $order) {
            $totalAmount = $order->orderDetails->sum(function ($detail) {
                return $detail->quantity * $detail->unit_price;
            });
            $order->update(['total_amount' => $totalAmount + $order->shipping_fee]);
        }
    }
}
