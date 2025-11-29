<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->unsignedInteger('total_stock');
            $table->unsignedInteger('reserved_stock')->default(0);
            $table->timestamps();

            $table->index('id');
        });
        // To ensure the reserved_stock never exceeds total_stock
        DB::statement('ALTER TABLE products ADD CONSTRAINT check_available_stock CHECK(reserved_stock <= total_stock)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
