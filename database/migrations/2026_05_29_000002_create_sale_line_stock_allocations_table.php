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
        Schema::create('sale_line_stock_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_line_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_balance_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_unit_id')->constrained()->restrictOnDelete();
            $table->string('allocation_type')->default('direct');
            $table->decimal('quantity', 18, 6);
            $table->decimal('sale_unit_quantity', 18, 6);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_line_stock_allocations');
    }
};

