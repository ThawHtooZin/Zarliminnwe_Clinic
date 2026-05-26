<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'parent_product_unit_id',
        'name',
        'abbreviation',
        'level',
        'conversion_factor',
        'is_purchase_unit',
        'is_sale_unit',
        'barcode',
        'sale_price',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'conversion_factor' => 'decimal:6',
            'is_purchase_unit' => 'boolean',
            'is_sale_unit' => 'boolean',
            'sale_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_product_unit_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_product_unit_id');
    }
}
