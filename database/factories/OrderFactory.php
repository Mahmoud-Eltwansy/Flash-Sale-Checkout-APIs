<?php

namespace Database\Factories;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        $product = Product::factory()->create();
        $hold = Hold::factory()->create([
            'product_id' => $product->id,
        ]);

        return [
            'hold_id' => $hold->id,
            'product_id' => $product->id,
            'quantity' => $hold->quantity,
            'total_price' => $product->price * $hold->quantity,
            'status' => 'pending',
        ];
    }
}
