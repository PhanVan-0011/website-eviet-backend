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
            // 1. Seeder cơ bản - không phụ thuộc vào bảng khác
            RolesAndPermissionsSeeder::class,
            PaymentMethodSeeder::class,
            OtpVerificationSeeder::class,

            // 2. Seeder cho người dùng và chi nhánh
            UserSeeder::class,
            BranchSeeder::class,

            // 3. Seeder cho nhà cung cấp
            SupplierGroupSeeder::class,
            SupplierSeeder::class,

            // 4. Seeder cho danh mục và sản phẩm
            CategoriesSeeder::class,
            ProductSeeder::class,
            ProductPriceSeeder::class,
            BranchProductStockSeeder::class,
            ProductAttributeSeeder::class,

            // 5. Seeder cho combo
            ComboSeeder::class,
            ComboPriceSeeder::class,
            ComboItemSeeder::class,

            // 6. Seeder cho slider và bài viết
            SliderSeeder::class,
            PostSeeder::class,

            // 7. Seeder cho khuyến mãi
            PromotionSeeder::class,
            PromotionRelationSeeder::class,

            // 8. Seeder cho đơn hàng (cần có sản phẩm trước)
            OrderSeeder::class,

            // 9. Seeder cho giỏ hàng (cần có sản phẩm và combo trước)
            CartSeeder::class,

            // 10. Seeder demo cuối cùng
            DashboardDemoSeeder::class,
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
