<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('patient_visits')) {
            return;
        }

        Artisan::call('patients:migrate-legacy-visits');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
