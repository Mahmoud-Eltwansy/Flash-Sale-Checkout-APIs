<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Hold;
use App\Models\Product;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CreateHoldTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_can_create_hold_when_stock_available(): void
    {
        $product = Product::factory()->create([
            'total_stock' => 10,
            'reserved_stock' => 0,
        ]);
        $response = $this->postJson('api/holds', [
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        // Assert response structure and status
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'product_id',
                ]
            ]);

        // Assert that the hold is created in the database
        $this->assertDatabaseHas('holds', [
            'product_id' => $product->id,
            'quantity' => 5,
            'status' => 'active',
        ]);

        $product->refresh();

        // Assert that the reserved stock has been updated correctly
        $this->assertEquals(5, $product->reserved_stock);
    }

    public function test_cannot_create_hold_when_insufficient_stock()
    {
        $product = Product::factory()->create([
            'total_stock' => 5,
            'reserved_stock' => 0
        ]);
        $response = $this->postJson('api/holds', [
            'product_id' => $product->id,
            'quantity' => 10, // Exceeds the available stock
        ]);

        // Assert response structure and status
        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
            ]);

        $product->refresh();
        // Assert that the reserved stock has not changed
        $this->assertEquals(0, $product->reserved_stock);
    }

    public function test_cannot_create_hold_for_nonexistent_product()
    {
        $response = $this->postJson('api/holds', [
            'product_id' => 9999, // Non-existent product ID
            'quantity' => 5,
        ]);

        // Assert response structure and status
        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message',
            ]);
    }

    public function test_prevents_overselling_under_concurrent_requests()
    {
        $product = Product::factory()->create([
            'total_stock' => 10,
            'reserved_stock' => 0,
        ]);

        $responses = [];
        // Simulate 3 concurrent requests trying to hold 5 units each
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('api/holds', [
                'product_id' => $product->id,
                'quantity' => 5,
            ]);
        }

        $successfulHolds = 0;
        $failedHolds = 0;

        foreach ($responses as $response) {
            if ($response->getStatusCode() === 201) {
                $successfulHolds++;
            } else {
                $failedHolds++;
            }
        }

        // Only 2 holds should succeed (5 + 5 = 10), the third should fail
        $this->assertEquals(2, $successfulHolds);
        $this->assertEquals(1, $failedHolds);

        $product->refresh();
        // Assert that the reserved stock is equal to total stock
        $this->assertEquals(10, $product->reserved_stock);
    }


    public function test_expired_holds_release_stock()
    {
        $product = Product::factory()->create([
            'total_stock' => 10,
            'reserved_stock' => 5,
        ]);

        $hold = Hold::factory()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'status' => 'active',
            'expires_at' => now()->subMinutes(1), // Expired
        ]);

        // Run the expire holds command
        Artisan::call('holds:expires');

        $product->refresh();
        $this->assertEquals(0, $product->reserved_stock);

        $hold->refresh();
        $this->assertEquals('expired', $hold->status);
    }
}
