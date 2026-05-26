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
        Schema::create('product_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_product_unit_id')->nullable()->constrained('product_units')->nullOnDelete();
            $table->string('name');
            $table->string('abbreviation');
            $table->unsignedInteger('level')->default(1);
            $table->decimal('conversion_factor', 18, 6)->nullable();
            $table->boolean('is_purchase_unit')->default(true);
            $table->boolean('is_sale_unit')->default(true);
            $table->string('barcode')->nullable()->unique();
            $table->decimal('sale_price', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'abbreviation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_units');
    }
};
