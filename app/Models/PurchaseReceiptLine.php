<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReceiptLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_receipt_id',
        'product_id',
        'product_unit_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'batch_number',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:6',
            'unit_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'expires_at' => 'date',
        ];
    }

    public function purchaseReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }
}
