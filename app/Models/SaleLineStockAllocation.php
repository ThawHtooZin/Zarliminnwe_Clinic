<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleLineStockAllocation extends Model
{
    use HasFactory;

    public const TYPE_DIRECT = 'direct';

    public const TYPE_PARENT_BREAKDOWN = 'parent_breakdown';

    protected $fillable = [
        'sale_line_id',
        'stock_balance_id',
        'product_unit_id',
        'allocation_type',
        'quantity',
        'sale_unit_quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'sale_unit_quantity' => 'decimal:6',
        ];
    }

    public function saleLine(): BelongsTo
    {
        return $this->belongsTo(SaleLine::class);
    }

    public function stockBalance(): BelongsTo
    {
        return $this->belongsTo(StockBalance::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }
}

