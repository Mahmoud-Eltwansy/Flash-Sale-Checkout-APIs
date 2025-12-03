<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Models\Order;
use App\Models\Product;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{

    public function test_webhook_is_idempotent(): void
    {
        $order = Order::factory()->create(['status' => 'pending']);

        $webhookData = [
            'order_id' => $order->id,
            'status' => 'success',
            'idempotency_key' => 'aaaaaaaaaa',
        ];

        // First webhook
        $response1 = $this->postJson('/api/payments/webhook', $webhookData);
        $response1->assertStatus(200);

        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // Duplicate webhook (same idempotency_key)
        $response2 = $this->postJson('/api/payments/webhook', $webhookData);
        $response2->assertStatus(200)
            ->assertJsonFragment(['message' => 'Webhook already processed']);

        // Order status unchanged
        $order->refresh();
        $this->assertEquals('paid', $order->status);
    }

    public function test_successful_payment_marks_order_as_paid()
    {
        $product = Product::factory()->create([
            'total_stock' => 10,
            'reserved_stock' => 2,
        ]);

        $order = Order::factory()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/payments/webhook', [
            'order_id' => $order->id,
            'status' => 'success',
            'idempotency_key' => 'bbbbbbbb',
        ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('paid', $order->status);

        // Stock remains reserved
        $product->refresh();
        $this->assertEquals(2, $product->reserved_stock);
    }

    public function test_failed_payment_releases_stock()
    {
        $product = Product::factory()->create([
            'total_stock' => 10,
            'reserved_stock' => 2,
        ]);

        $order = Order::factory()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/payments/webhook', [
            'order_id' => $order->id,
            'status' => 'failed',
            'idempotency_key' => 'ccccccccccccc',
        ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);

        // Stock released
        $product->refresh();
        $this->assertEquals(0, $product->reserved_stock);
    }
}
