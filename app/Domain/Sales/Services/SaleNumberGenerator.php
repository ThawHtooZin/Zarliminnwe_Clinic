<?php

namespace App\Domain\Sales\Services;

use App\Models\Sale;

class SaleNumberGenerator
{
    public function generate(): string
    {
        $prefix = 'S-'.now()->format('Ymd').'-';
        $nextNumber = Sale::where('sale_number', 'like', $prefix.'%')->count() + 1;

        do {
            $saleNumber = $prefix.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;
        } while (Sale::where('sale_number', $saleNumber)->exists());

        return $saleNumber;
    }
}
