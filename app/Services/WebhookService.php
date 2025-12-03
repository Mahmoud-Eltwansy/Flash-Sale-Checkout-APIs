<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentWebhook;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    private const MAX_ORDER_WAIT_ATTEMPTS = 5;
    public function processWebhook(array $data)
    {
        $idempotencyKey = $data['idempotency_key'];
        $existingWebhook = PaymentWebhook::find($idempotencyKey);

        if ($existingWebhook) {
            return $this->handleDuplicateWebhook($existingWebhook);
        }


        // Solving the Race Condition Problem (Webhook arrives before order exists in DB)
        $order = $this->checkAndWaitForOrderCreation($data['order_id']);
        if (!$order) {
            throw new \Exception('Order not found after retries', 404);
        }

        // Proccess Webhook
        return DB::transaction(function () use ($order, $data, $idempotencyKey) {
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            if ($order->status !== 'pending') {
                throw new \Exception("Order already processed. It's current status is {$order->status}", 409);
            }

            if ($data['status'] === 'success') {
                return $this->handleSuccessfulPayment($order, $data, $idempotencyKey);
            } else {
                return $this->handleFailedPayment($order, $data, $idempotencyKey);
            }
        });
    }

    private function handleDuplicateWebhook(PaymentWebhook $existingWebhook)
    {
        Log::info('Duplicate Webhook Found', [
            'idempotency_key' => $existingWebhook->idempotency_key,
            'order_id' => $existingWebhook->order_id
        ]);
        return [
            'message' => 'Webhook already processed',
            'processed_at' => $existingWebhook->processed_at->toIso8601String(),
        ];
    }

    /**
     * Wait for order creation until it's available in the database.
     *
     * @param int $orderId
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    private function checkAndWaitForOrderCreation($orderId)
    {
        $attempt = 0;
        while ($attempt < self::MAX_ORDER_WAIT_ATTEMPTS) {
            $order = Order::find($orderId);
            if ($order) {
                return $order;
            }
            // Wait with exponential backoff
            $waitMs = pow(2, $attempt) * 100;
            usleep($waitMs * 1000);

            $attempt++;
            Log::info("Waiting for order creation", [
                'order_id' => $orderId,
                'attempt' => $attempt,
                'wait_ms' => $waitMs
            ]);
        }
        return null;
    }

    private function handleSuccessfulPayment($order, $data, $idempotencyKey)
    {
        $order->update(['status' => 'paid']);

        $this->createWebhook($order, $data, $idempotencyKey, 'success');

        Log::info('Payment Successful', [
            'order_id' => $order->id,
            'product_id' => $order->product_id,
            'quantity' => $order->quantity
        ]);

        return [
            'message' => 'Payment confirmed',
            'order_id' => $order->id,
            'status' => 'paid'
        ];
    }

    private function handleFailedPayment($order, $data, $idempotencyKey)
    {
        // Lock the product record
        $product = Product::where('id', $order->product_id)->lockForUpdate()->first();

        // Update Stock
        $product->decrement('reserved_stock', $order->quantity);

        // Update Order status
        $order->update(['status' => 'cancelled']);

        // Invalidate cache
        Cache::forget("product:{$order->product_id}:data");

        // Create webhook record in DB.
        $this->createWebhook($order, $data, $idempotencyKey, 'failed');

        Log::warning('Payment failed', [
            'order_id' => $order->id,
            'product_id' => $order->product_id,
            'quantity' => $order->quantity,
            'stock_released' => true,
        ]);

        return [
            'message' => 'Payment failed, order cancelled',
            'order_id' => $order->id,
            'status' => 'cancelled',
        ];
    }

    private function createWebhook($order, $data, $idempotencyKey, $status)
    {
        PaymentWebhook::create([
            'idempotency_key' => $idempotencyKey,
            'order_id' => $order->id,
            'status' => $status,
            'payload' => $data,
            'processed_at' => now(),
        ]);
    }
}
