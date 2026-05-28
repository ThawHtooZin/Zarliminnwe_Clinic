<?php

namespace App\Domain\Inventory\Services;

use App\Models\StockCount;

class StockCountNumberGenerator
{
    public function generate(): string
    {
        $prefix = 'SC-'.now()->format('Ymd').'-';
        $nextNumber = StockCount::where('count_number', 'like', $prefix.'%')->count() + 1;

        do {
            $countNumber = $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (StockCount::where('count_number', $countNumber)->exists());

        return $countNumber;
    }
}
