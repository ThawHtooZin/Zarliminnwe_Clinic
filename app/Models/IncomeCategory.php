<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncomeCategory extends Model
{
    use HasFactory;

    public const TYPE_SERVICE = 'service';

    public const TYPE_GENERAL = 'general';

    protected $fillable = [
        'name',
        'type',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function incomeEntries(): HasMany
    {
        return $this->hasMany(IncomeEntry::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isService(): bool
    {
        return $this->type === self::TYPE_SERVICE;
    }

    public function isGeneral(): bool
    {
        return $this->type === self::TYPE_GENERAL;
    }
}
