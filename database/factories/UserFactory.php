<?php

namespace Database\Factories;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
               'email' => $this->faker->unique()->safeEmail,
               'phone' => substr($this->faker->numerify('##########'), 0, 15),
               'gender' => $this->faker->randomElement(['male', 'female']),
               'password' => Hash::make('password123'),
               'address' => $this->faker->address,
               'is_active' => true,
               'is_verified' => false,
               'date_of_birth' => $this->faker->date('Y-m-d', '2000-01-01'),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return $this
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
