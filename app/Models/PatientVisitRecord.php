<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientVisitRecord extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'patient_id',
        'visited_at',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'visited_at' => 'datetime',
        ];
    }

    protected $with = [
        'patient',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(PatientDiagnosis::class)->orderBy('recorded_at');
    }

    public function incomeEntries(): HasMany
    {
        return $this->hasMany(IncomeEntry::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function completedSales(): HasMany
    {
        return $this->sales()->where('status', Sale::STATUS_COMPLETED);
    }

    /**
     * Backward-compatible accessors for legacy patient visit views.
     */
    protected function patientName(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->patient?->name);
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn (): ?int => $this->patient !== null ? (int) $this->patient->age : null);
    }
}
