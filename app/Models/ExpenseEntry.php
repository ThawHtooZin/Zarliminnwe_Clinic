<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseEntry extends Model
{
    use HasFactory;

    public const PAYMENT_CASH = 'cash';

    public const PAYMENT_CARD = 'card';

    public const PAYMENT_MOBILE = 'mobile';

    public const PAYMENT_BANK_TRANSFER = 'bank_transfer';

    public const PAYMENT_OTHER = 'other';

    protected $fillable = [
        'expense_category_id',
        'amount',
        'expense_date',
        'payee',
        'payment_method',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
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

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
