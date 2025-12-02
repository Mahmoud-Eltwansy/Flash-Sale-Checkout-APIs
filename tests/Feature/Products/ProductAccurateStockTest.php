<?php

namespace Tests\Feature\Products;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Product;
use Tests\TestCase;

class ProductAccurateStockTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_endpoint_returns_accurate_stock(): void
    {
        $product = Product::factory()->create([
            'total_stock' => 50,
            'reserved_stock' => 20,
        ]);
        $response = $this->getJson("api/products/{$product->id}");
        $response->assertJson([
            'success' => true,
            'message' => 'Success',
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'available_stock' => 30, // 50 - 20 = 30
                'price' => $product->price,
            ],
        ])->assertStatus(200);
    }
}
