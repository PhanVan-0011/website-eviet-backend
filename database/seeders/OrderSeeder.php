<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderDetail;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo 10 đơn hàng, mỗi đơn hàng có 2-4 chi tiết sản phẩm
        Order::factory()
            ->count(10)
            ->has(OrderDetail::factory()->count(fake()->numberBetween(2, 4)))
            ->create();

        // Cập nhật lại total_amount cho mỗi đơn hàng
        foreach (Order::all() as $order) {
            $totalAmount = $order->orderDetails->sum(function ($detail) {
                return $detail->quantity * $detail->unit_price;
            });
            $order->update(['total_amount' => $totalAmount + $order->shipping_fee]);
        }
    }
}
