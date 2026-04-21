<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Product;

class OrderFactory extends Factory

{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'sending_postcode' => $this->faker->postcode(),
            'sending_address' => $this->faker->address(),
            'sending_building' => $this->faker->secondaryAddress(),
            'payment_method' => $this->faker->randomElement(['card', 'convenience']),
        ];
    }
}
