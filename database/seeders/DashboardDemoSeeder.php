<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Tạo user mẫu (không có role)
        User::factory()->count(50)->create();

        // 2. Tạo sản phẩm mẫu
        $products = Product::factory()->count(20)->create();

        // 3. Tạo đơn hàng với nhiều trạng thái
        $statuses = ['delivered', 'pending', 'shipped', 'cancelled', 'processing'];
        $users = User::all();

        for ($i = 1; $i <= 100; $i++) {
            $status = $statuses[array_rand($statuses)];
            $user = $users->random();
            $order = Order::create([
                'order_code' => 'ORD' . now()->format('Ym') . sprintf('%04d', $i),
                'client_name' => $user->name,
                'client_phone' => $user->phone ?? '09' . rand(10000000, 99999999),
                'shipping_address' => fake()->address,
                'status' => $status,
                'grand_total' => 0, // sẽ cập nhật sau
                'total_amount' => 0,
                'user_id' => $user->id,
                'order_date' => Carbon::now()->subDays(rand(0, 60)),
            ]);

            // 4. Tạo chi tiết đơn hàng
            $total = 0;
            $details = [];
            $numProducts = rand(1, 4);
            $chosenProducts = $products->random($numProducts);
            foreach ($chosenProducts as $product) {
                $qty = rand(1, 5);
                $unitPrice = $product->price ?? rand(20000, 100000);
                $details[] = [
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $total += $qty * $unitPrice;
            }
            OrderDetail::insert($details);

            // Cập nhật tổng tiền đơn hàng
            $order->grand_total = $total;
            $order->total_amount = $total;
            $order->save();

            // 5. Tạo payment cho đơn hàng
            $paymentStatus = ($status === 'delivered') ? 'success' : (rand(0, 1) ? 'pending' : 'failed');
            $paidAt = ($paymentStatus === 'success') ? $order->order_date->copy()->addDays(rand(0, 3)) : null;
            Payment::create([
                'order_id' => $order->id,
                'payment_method_id' => 1, // giả sử có sẵn 1 phương thức thanh toán
                'status' => $paymentStatus,
                'amount' => $total,
                'paid_at' => $paidAt,
            ]);
        }
    }
}
