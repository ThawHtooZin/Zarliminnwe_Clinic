<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReceipt extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_POSTED = 'posted';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'supplier_id',
        'receipt_number',
        'received_at',
        'status',
        'notes',
        'created_by',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseReceiptLine::class);
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }
}
