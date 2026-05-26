<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockLedger extends Model
{
    use HasFactory;

    public const DIRECTION_IN = 'in';

    public const DIRECTION_OUT = 'out';

    public const TYPE_OPENING_STOCK = 'opening_stock';

    public const TYPE_PURCHASE_RECEIPT = 'purchase_receipt';

    public const TYPE_SALE = 'sale';

    public const TYPE_SALE_VOID = 'sale_void';

    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'product_id',
        'stock_batch_id',
        'product_unit_id',
        'type',
        'direction',
        'quantity',
        'unit_cost',
        'reference_type',
        'reference_id',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    public function stockBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'reference_type', 'reference_id');
    }
}
