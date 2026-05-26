<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_HELD = 'held';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_VOIDED = 'voided';

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CARD = 'card';

    public const PAYMENT_MIXED = 'mixed';

    public const PAYMENT_OTHER = 'other';

    protected $fillable = [
        'sale_number',
        'patient_visit_id',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'amount_paid',
        'change_amount',
        'payment_method',
        'notes',
        'sold_by',
        'sold_at',
        'voided_by',
        'voided_at',
        'void_reason',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'sold_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sold_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isCompletable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_HELD], true);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
