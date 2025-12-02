<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    public function test_can_create_order_from_valid_hold(): void
    {
        $product = Product::factory()->create([
            'price' => 500
        ]);
        $hold = Hold::factory()->create([
            'product_id' => $product->id,
            'quantity' => 5,
        ]);
        // Make API request to create order
        $response = $this->postJson('api/orders', [
            'hold_id' => $hold->id
        ]);

        // Assert response status and structure
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'product_id',
                    'hold_id',
                    'quantity',
                    'total_price'
                ]
            ]);

        // Verify that the order is created in the database
        $this->assertDatabaseHas('orders', [
            'product_id' => $product->id,
            'hold_id' => $hold->id,
            'quantity' => $hold->quantity,
            'total_price' => $product->price * $hold->quantity,
            'status' => 'pending'
        ]);

        $hold->refresh();
        // Verify that the hold status is updated to 'consumed'
        $this->assertEquals('consumed', $hold->status);
    }

    public function test_cannot_create_order_from_expired_hold()
    {
        $hold = Hold::factory()->create([
            'status' => 'active',
            'expires_at' => now()->subMinutes(1)
        ]);

        $response = $this->postJson('api/orders', [
            'hold_id' => $hold->id
        ]);

        $response->assertStatus(409);

        $this->assertDatabaseMissing('orders', [
            'hold_id' => $hold->id
        ]);
    }

    public function test_cannot_use_same_hold_twice()
    {
        $hold = Hold::factory()->create([
            'status' => 'active',
            'expires_at' => now()->addMinutes(2),
        ]);

        // First Order Should Succeed
        $this->postJson('api/orders', [
            'hold_id' => $hold->id,
        ])->assertStatus(201);

        // Second Order Should Fail
        $this->postJson('api/orders', [
            'hold_id' => $hold->id,
        ])->assertStatus(409);

        // Verify only one order exists for the hold
        $this->assertEquals(1, Order::where('hold_id', $hold->id)->count());
    }

    public function test_cannot_create_order_from_non_active_hold()
    {
        $hold = Hold::factory()->create([
            'status' => 'consumed',
            'expires_at' => now()->addMinutes(2),
        ]);

        $this->postJson('api/orders', [
            'hold_id' => $hold->id
        ])->assertStatus(409);


        // Verify that no order is created for the hold
        $this->assertDatabaseMissing('orders', [
            'hold_id' => $hold->id
        ]);
    }
}
