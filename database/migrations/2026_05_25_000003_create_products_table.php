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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('generic_name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->text('description')->nullable();
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_expiry')->default(false);
            $table->unsignedBigInteger('reorder_product_unit_id')->nullable();
            $table->decimal('reorder_quantity', 14, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
