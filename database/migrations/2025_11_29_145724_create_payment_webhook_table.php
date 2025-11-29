<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_webhook', function (Blueprint $table) {
            $table->string('idempotency_key')->primary();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->enum('status', ['success', 'failed']);
            $table->json('payload')->nullable();
            $table->text('response')->nullable();
            $table->timestamp('processed_at');

            $table->index('order_id'); // For lookups by order
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhook');
    }
};
