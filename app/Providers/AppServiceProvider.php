<?php

namespace App\Providers;

use App\Models\PatientVisitRecord;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Route::bind('patient_visit', fn (string $value): PatientVisitRecord => PatientVisitRecord::query()->findOrFail($value));
    }
}
