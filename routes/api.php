<?php

use App\Http\Controllers\HoldController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Products Endpoints
Route::get('products/{id}', [ProductController::class, 'show']);

// Holds Endpoints
Route::post('holds', [HoldController::class, 'store']);

// Orders Endpoints
Route::post('orders', [OrderController::class, 'store']);

// Payment Webhook Endpoints
Route::post('payments/webhook', [PaymentWebhookController::class, 'handle']);
