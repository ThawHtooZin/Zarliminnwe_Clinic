<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncomeEntry extends Model
{
    use HasFactory;

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CARD = 'card';

    public const PAYMENT_MOBILE = 'mobile';

    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';

    public const PAYMENT_OTHER = 'other';

    protected $fillable = [
        'income_category_id',
        'patient_visit_record_id',
        'amount',
        'payment_method',
        'received_at',
        'received_by',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function paymentMethods(): array
    {
        return [
            self::PAYMENT_CASH,
            self::PAYMENT_CARD,
            self::PAYMENT_MOBILE,
            self::PAYMENT_BANK_TRANSFER,
            self::PAYMENT_OTHER,
        ];
    }

    public function incomeCategory(): BelongsTo
    {
        return $this->belongsTo(IncomeCategory::class);
    }

    public function patientVisitRecord(): BelongsTo
    {
        return $this->belongsTo(PatientVisitRecord::class);
    }

    /**
     * @deprecated Use patientVisitRecord() instead.
     */
    public function patientVisit(): BelongsTo
    {
        return $this->patientVisitRecord();
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
