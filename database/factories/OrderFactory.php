<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
         // Lấy một người dùng ngẫu nhiên từ bảng users thay vì tạo mới
         $user = User::inRandomOrder()->first();
         if (!$user) {
             $user = User::factory()->create(); // Chỉ tạo nếu không có người dùng nào
         }
        return [
               'order_date' => $this->faker->dateTimeBetween('-1 month', 'now'),
               'total_amount' => 0, // Sẽ được tính sau khi tạo order_details
               'status' => $this->faker->randomElement(['pending', 'processing', 'delivered', 'cancelled']),
               'client_name' => $this->faker->name,
               'client_phone' => substr(preg_replace('/\D/', '', $this->faker->phoneNumber), 0, 11),
               'shipping_fee' => $this->faker->randomFloat(2, 0, 50000),
               'cancelled_at' => $this->faker->optional()->dateTime(),
               'user_id' => $user->id,
        ];
    }
}
