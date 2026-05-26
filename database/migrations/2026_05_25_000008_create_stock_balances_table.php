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
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('stock_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_unit_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 6)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'stock_batch_id', 'product_unit_id'], 'stock_balance_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
