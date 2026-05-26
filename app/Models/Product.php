<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_category_id',
        'name',
        'sku',
        'generic_name',
        'manufacturer',
        'description',
        'image_path',
        'track_batch',
        'track_expiry',
        'reorder_product_unit_id',
        'reorder_quantity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'track_batch' => 'boolean',
            'track_expiry' => 'boolean',
            'reorder_quantity' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class)->orderBy('level');
    }

    public function purchaseUnits(): HasMany
    {
        return $this->units()->where('is_purchase_unit', true);
    }

    public function saleUnits(): HasMany
    {
        return $this->units()->where('is_sale_unit', true);
    }

    public function reorderUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'reorder_product_unit_id');
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }
}
