<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\Supplier;
use App\Models\SupplierGroup;
use Illuminate\Database\Seeder;
use Database\Seeders\PaymentMethodSeeder;


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
            RolesAndPermissionsSeeder::class,
            UserSeeder::class,
            // Seeder cho danh mục và sản phẩm
            CategoriesSeeder::class,
            ProductSeeder::class,
            ProductAttributeSeeder::class,  
            // Seeder cho combo
            ComboSeeder::class,
            ComboItemSeeder::class,

            // Seeder cho slider và bài viết
            SliderSeeder::class,
            PostSeeder::class,

            // Seeder cho đơn hàng
            OrderSeeder::class,

            // Seeder cho khuyến mãi
            PromotionSeeder::class,
            PromotionRelationSeeder::class,

            // Gọi seeder phương thức thanh toán
            PaymentMethodSeeder::class,

            DashboardDemoSeeder::class,
            OtpVerificationSeeder::class,
            CartSeeder::class,
            BranchSeeder::class,
            SupplierGroup::class,
            Supplier::class,
        ]);

        // Cập nhật total_amount cho mỗi đơn hàng
        foreach (Order::all() as $order) {
            $totalAmount = $order->orderDetails->sum(function ($detail) {
                return $detail->quantity * $detail->unit_price;
            });
            $order->update(['total_amount' => $totalAmount + $order->shipping_fee]);
        }
    }
}
