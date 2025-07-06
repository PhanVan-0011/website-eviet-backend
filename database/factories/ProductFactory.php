<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Product::class;
    public function definition(): array
    {

        return [
            'name' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'size' => $this->faker->randomElement(['S', 'M', 'L', 'XL']),
            'original_price' => $this->faker->randomFloat(2, 100, 1000),
            'sale_price' => $this->faker->randomFloat(2, 50, 900),
            'stock_quantity' => $this->faker->numberBetween(0, 100),
            // 'image_url' => $this->faker->imageUrl(640, 480, 'products', true),
            'status' => $this->faker->boolean,
            // 'category_id' => \App\Models\Category::inRandomOrder()->first()?->id ?? 1,
        ];
    }
}
