<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderDetail>
 */
class OrderDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = OrderDetail::class;
    public function definition(): array
    {
        // Lấy một sản phẩm ngẫu nhiên từ bảng products thay vì tạo mới
        $product = Product::inRandomOrder()->first();
        if (!$product) {
            $product = Product::factory()->create(); // Chỉ tạo mới nếu không có sản phẩm nào
        }

        return [
            'order_id' => Order::factory(),
            'product_id' => $product->id,
            'quantity' => $this->faker->numberBetween(1, 10),
            'unit_price' => $product->sale_price ?? $product->original_price ?? 0.0,

        ];
    }
}
