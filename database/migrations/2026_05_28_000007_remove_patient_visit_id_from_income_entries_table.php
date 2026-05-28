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
        if (! Schema::hasColumn('income_entries', 'patient_visit_id')) {
            return;
        }

        Schema::table('income_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('patient_visit_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('income_entries', function (Blueprint $table) {
            $table->foreignId('patient_visit_id')->nullable()->after('income_category_id')->constrained()->nullOnDelete();
        });
    }
};
