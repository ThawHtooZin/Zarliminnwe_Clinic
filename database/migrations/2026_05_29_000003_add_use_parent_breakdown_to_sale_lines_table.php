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
        Schema::table('sale_lines', function (Blueprint $table) {
            $table->boolean('use_parent_breakdown')->default(false)->after('product_unit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_lines', function (Blueprint $table) {
            $table->dropColumn('use_parent_breakdown');
        });
    }
};

