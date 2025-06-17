<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        // Đảm bảo encoding UTF-8
        DB::statement('SET NAMES utf8mb4');
        DB::statement('SET CHARACTER SET utf8mb4');
        DB::statement('SET character_set_connection=utf8mb4');

        $promotions = [
            [
                'name' => 'Khuyến mãi tháng 3',
                'code' => 'MARCH2024',
                'description' => 'Giảm giá 20% cho tất cả sản phẩm',
                'application_type' => 'orders',
                'type' => 'percentage',
                'value' => '20.00',
                'min_order_value' => '500000.00',
                'max_discount_amount' => '200000.00',
                'max_usage' => 1000,
                'max_usage_per_user' => 1,
                'is_combinable' => 0,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addDays(30),
                'is_active' => 1,
            ],
            [
                'name' => 'Khuyến mãi sinh nhật',
                'code' => 'BIRTHDAY2024',
                'description' => 'Giảm giá 15% cho khách hàng trong tháng sinh nhật',
                'application_type' => 'orders',
                'type' => 'percentage',
                'value' => '15.00',
                'min_order_value' => '300000.00',
                'max_discount_amount' => '150000.00',
                'max_usage' => null,
                'max_usage_per_user' => 1,
                'is_combinable' => 0,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addYear(),
                'is_active' => 1,
            ],
            [
                'name' => 'Khuyến mãi mùa hè',
                'code' => 'SUMMER2024',
                'description' => 'Giảm giá 25% cho các sản phẩm mùa hè',
                'application_type' => 'products',
                'type' => 'percentage',
                'value' => '25.00',
                'min_order_value' => '1000000.00',
                'max_discount_amount' => '300000.00',
                'max_usage' => 500,
                'max_usage_per_user' => 2,
                'is_combinable' => 1,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(3),
                'is_active' => 1,
            ],
            [
                'name' => 'Khuyến mãi đặc biệt',
                'code' => 'SPECIAL2024',
                'description' => 'Giảm giá 30% cho đơn hàng đầu tiên',
                'application_type' => 'orders',
                'type' => 'percentage',
                'value' => '30.00',
                'min_order_value' => '200000.00',
                'max_discount_amount' => '500000.00',
                'max_usage' => 200,
                'max_usage_per_user' => 1,
                'is_combinable' => 0,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(6),
                'is_active' => 1,
            ],
            [
                'name' => 'Khuyến mãi cuối tuần',
                'code' => 'WEEKEND2024',
                'description' => 'Giảm giá 10% cho các đơn hàng vào cuối tuần',
                'application_type' => 'orders',
                'type' => 'percentage',
                'value' => '10.00',
                'min_order_value' => '100000.00',
                'max_discount_amount' => '100000.00',
                'max_usage' => null,
                'max_usage_per_user' => 1,
                'is_combinable' => 1,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(2),
                'is_active' => 1,
            ],
            [
                'name' => 'Khuyến mãi danh mục',
                'code' => 'CATEGORY2024',
                'description' => 'Giảm giá 15% cho các sản phẩm trong danh mục',
                'application_type' => 'categories',
                'type' => 'percentage',
                'value' => '15.00',
                'min_order_value' => '200000.00',
                'max_discount_amount' => '100000.00',
                'max_usage' => 300,
                'max_usage_per_user' => 1,
                'is_combinable' => 0,
                'start_date' => Carbon::now(),
                'end_date' => Carbon::now()->addMonths(2),
                'is_active' => 1,
            ],
        ];

        foreach ($promotions as $promotion) {
            try {
                DB::beginTransaction();

                $promo = new Promotion();
                $promo->fill($promotion);
                $promo->save();

                DB::commit();
                Log::info('Đã tạo khuyến mãi thành công: ' . $promotion['code']);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Lỗi khi tạo khuyến mãi: ' . $e->getMessage());
                Log::error('Dữ liệu khuyến mãi: ' . json_encode($promotion, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
