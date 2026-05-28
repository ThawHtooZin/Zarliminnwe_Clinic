<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'appointment_id')) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('appointment_id');
            });
        }

        Schema::dropIfExists('appointments');
    }

    public function down(): void
    {
        if (! Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table): void {
                $table->id();
                $table->string('guest_name');
                $table->unsignedTinyInteger('guest_age');
                $table->string('guest_address');
                $table->date('appointment_date');
                $table->string('status')->default('scheduled');
                $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('patient_visit_record_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('checked_in_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('sales') && ! Schema::hasColumn('sales', 'appointment_id')) {
            Schema::table('sales', function (Blueprint $table): void {
                $table->foreignId('appointment_id')->nullable()->after('patient_visit_record_id')->constrained()->nullOnDelete();
            });
        }
    }
};
