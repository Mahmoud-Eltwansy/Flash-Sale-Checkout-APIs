<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HoldService
{

    private const HOLD_DURATION_MINUTES = 2;

    public function createHold(int $productId, int $quantity)
    {

        return DB::transaction(function () use ($productId, $quantity) {
            // Lock the product row for update
            $product = Product::where('id', $productId)->lockForUpdate()->first();

            if (!$product) {
                throw new \Exception('Product not found', 404);
            }

            if ($product->available_stock < $quantity) {
                throw new \Exception("Insufficient stock available. Avalailable: {$product->available_stock}, Requested: {$quantity}", 422);
            }

            // Reserve the stock
            $product->increment('reserved_stock', $quantity);

            // Create the hold
            $hold = $product->holds()->create([
                'quantity' => $quantity,
                'expires_at' => now()->addMinutes(self::HOLD_DURATION_MINUTES),
                'status' => 'active',
            ]);

            // Invalidate cache
            Cache::forget("product:{$product->id}:data");

            return $hold;
        }, 3); // Retry up to 3 times in case of deadlock

    }
}
