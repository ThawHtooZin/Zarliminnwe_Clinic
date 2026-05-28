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
        if (! Schema::hasColumn('sales', 'patient_visit_id')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['patient_visit_id']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('patient_visit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_visit_id')->nullable()->index()->after('sale_number');
        });
    }
};
