<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientDiagnosis extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_visit_record_id',
        'diagnosis_text',
        'recorded_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function visitRecord(): BelongsTo
    {
        return $this->belongsTo(PatientVisitRecord::class, 'patient_visit_record_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
