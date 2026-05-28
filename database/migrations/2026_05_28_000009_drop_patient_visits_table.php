<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('patient_visits');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('patient_visits', function ($table) {
            $table->id();
            $table->string('patient_name');
            $table->unsignedSmallInteger('age');
            $table->dateTime('visited_at');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('patient_name');
            $table->index('visited_at');
        });
    }
};
