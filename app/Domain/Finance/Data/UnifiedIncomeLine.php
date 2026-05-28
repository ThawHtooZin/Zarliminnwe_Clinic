<?php

namespace App\Domain\Finance\Data;

use App\Models\PatientVisitRecord;
use Carbon\CarbonInterface;

class UnifiedIncomeLine
{
    public const SOURCE_INCOME_ENTRY = 'income_entry';

    public const SOURCE_PHARMACY_SALE = 'pharmacy_sale';

    public const CATEGORY_PHARMACY_SALE = 'Pharmacy Sale';

    public function __construct(
        public readonly string $source,
        public readonly int $sourceId,
        public readonly CarbonInterface $occurredAt,
        public readonly string $categoryLabel,
        public readonly ?string $categoryType,
        public readonly float $amount,
        public readonly string $paymentMethod,
        public readonly ?int $patientVisitRecordId,
        public readonly ?PatientVisitRecord $patientVisitRecord,
        public readonly ?string $recordedByName,
        public readonly ?string $description,
        public readonly ?string $actionRouteName = null,
        public readonly array $actionRouteParameters = [],
        public readonly string $actionLabel = 'View',
    ) {}

    public function isIncomeEntry(): bool
    {
        return $this->source === self::SOURCE_INCOME_ENTRY;
    }

    public function isPharmacySale(): bool
    {
        return $this->source === self::SOURCE_PHARMACY_SALE;
    }
}
