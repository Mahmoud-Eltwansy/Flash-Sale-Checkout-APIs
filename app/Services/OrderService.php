<?php

namespace App\Services;

use App\Models\Hold;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder($holdId)
    {
        return DB::transaction(function () use ($holdId) {
            $hold = Hold::where('id', $holdId)->lockForUpdate()->first();

            // Validate Hold
            $this->validateHold($hold);

            $product = $hold->product;
            $totalPrice = $product->price * $hold->quantity;

            $order = Order::create([
                'product_id' => $product->id,
                'hold_id' => $hold->id,
                'quantity' => $hold->quantity,
                'total_price' => $totalPrice
            ]);

            // Updating the hold status
            $hold->update(['status' => 'consumed']);

            return $order;
        });
    }

    private function validateHold($hold)
    {
        if (!$hold) {
            throw new \Exception('Hold not found', 404);
        }
        if ($hold->status !== 'active') {
            throw new \Exception("Hold is {$hold->status}", 409);
        }
        if ($hold->expires_at->isPast()) {
            throw new \Exception('Hold has expired', 409);
        }
        if (Order::where('hold_id', $hold->id)->exists()) {
            throw new \Exception('Hold already used', 409);
        }
    }
}
