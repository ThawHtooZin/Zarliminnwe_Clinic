<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Data\UnifiedIncomeLine;
use App\Models\IncomeEntry;
use App\Models\PatientVisitRecord;
use App\Models\Sale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;

class UnifiedIncomeQueryService
{
    public const PHARMACY_SALE_FILTER = 'pharmacy_sale';

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginatedForFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $normalized = $this->normalizeFilters($filters);
        $lines = $this->mergedLines($normalized);

        $page = max((int) request()->query('page', 1), 1);
        $total = $lines->count();
        $items = $lines->slice(($page - 1) * $perPage, $perPage)->values();

        return new Paginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    public function forPatientVisit(PatientVisitRecord $visit): Collection
    {
        $visit->loadMissing('patient');

        return $this->mergedLines([
            'patient_visit_id' => $visit->id,
        ]);
    }

    public function visitIncomeTotal(PatientVisitRecord $visit): float
    {
        return (float) $this->forPatientVisit($visit)->sum(fn (UnifiedIncomeLine $line): float => $line->amount);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, UnifiedIncomeLine>
     */
    public function collectionForFilters(array $filters): Collection
    {
        return $this->mergedLines($this->normalizeFilters($filters));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, UnifiedIncomeLine>
     */
    private function mergedLines(array $filters): Collection
    {
        return $this->incomeEntryLines($filters)
            ->concat($this->pharmacySaleLines($filters))
            ->sort(function (UnifiedIncomeLine $a, UnifiedIncomeLine $b): int {
                $dateCompare = $b->occurredAt->getTimestamp() <=> $a->occurredAt->getTimestamp();

                return $dateCompare !== 0 ? $dateCompare : $b->sourceId <=> $a->sourceId;
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, UnifiedIncomeLine>
     */
    private function incomeEntryLines(array $filters): Collection
    {
        if ($this->shouldExcludeIncomeEntries($filters)) {
            return collect();
        }

        $query = IncomeEntry::query()
            ->with(['incomeCategory', 'patientVisitRecord.patient', 'receivedBy']);

        if (! empty($filters['date_from'])) {
            $query->whereDate('received_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('received_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['income_category_id'])) {
            $query->where('income_category_id', $filters['income_category_id']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['patient_visit_id'])) {
            $query->where('patient_visit_record_id', $filters['patient_visit_id']);
        }

        if (! empty($filters['received_by'])) {
            $query->where('received_by', $filters['received_by']);
        }

        return $query
            ->orderByDesc('received_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (IncomeEntry $entry): UnifiedIncomeLine => $this->mapIncomeEntry($entry));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, UnifiedIncomeLine>
     */
    private function pharmacySaleLines(array $filters): Collection
    {
        if ($this->shouldExcludePharmacySales($filters)) {
            return collect();
        }

        $query = Sale::query()
            ->with(['patientVisitRecord.patient', 'cashier'])
            ->where('status', Sale::STATUS_COMPLETED);

        if (! empty($filters['date_from'])) {
            $query->whereDate('sold_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('sold_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (! empty($filters['patient_visit_id'])) {
            $query->where('patient_visit_record_id', $filters['patient_visit_id']);
        }

        if (! empty($filters['received_by'])) {
            $query->where('sold_by', $filters['received_by']);
        }

        return $query
            ->orderByDesc('sold_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Sale $sale): UnifiedIncomeLine => $this->mapPharmacySale($sale));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function shouldExcludeIncomeEntries(array $filters): bool
    {
        return ($filters['income_category_id'] ?? null) === self::PHARMACY_SALE_FILTER;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function shouldExcludePharmacySales(array $filters): bool
    {
        $categoryFilter = $filters['income_category_id'] ?? null;

        return $categoryFilter !== null
            && $categoryFilter !== ''
            && $categoryFilter !== self::PHARMACY_SALE_FILTER;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        $categoryFilter = $filters['income_category_id'] ?? null;

        if ($categoryFilter === self::PHARMACY_SALE_FILTER) {
            $normalizedCategory = self::PHARMACY_SALE_FILTER;
        } elseif ($categoryFilter !== null && $categoryFilter !== '') {
            $normalizedCategory = (int) $categoryFilter;
        } else {
            $normalizedCategory = null;
        }

        return [
            'date_from' => $filters['date_from'] ?? $filters['received_from'] ?? null,
            'date_to' => $filters['date_to'] ?? $filters['received_to'] ?? null,
            'income_category_id' => $normalizedCategory,
            'payment_method' => $filters['payment_method'] ?? null,
            'patient_visit_id' => isset($filters['patient_visit_id']) ? (int) $filters['patient_visit_id'] : null,
            'received_by' => isset($filters['received_by']) ? (int) $filters['received_by'] : null,
        ];
    }

    private function mapIncomeEntry(IncomeEntry $entry): UnifiedIncomeLine
    {
        return new UnifiedIncomeLine(
            source: UnifiedIncomeLine::SOURCE_INCOME_ENTRY,
            sourceId: $entry->id,
            occurredAt: $entry->received_at,
            categoryLabel: $entry->incomeCategory->name,
            categoryType: $entry->incomeCategory->type,
            amount: (float) $entry->amount,
            paymentMethod: $entry->payment_method,
            patientVisitRecordId: $entry->patient_visit_record_id,
            patientVisitRecord: $entry->patientVisitRecord,
            recordedByName: $entry->receivedBy?->name,
            description: $entry->description,
            actionRouteName: 'finance.income.edit',
            actionRouteParameters: ['incomeEntry' => $entry->id],
            actionLabel: 'Edit',
        );
    }

    private function mapPharmacySale(Sale $sale): UnifiedIncomeLine
    {
        return new UnifiedIncomeLine(
            source: UnifiedIncomeLine::SOURCE_PHARMACY_SALE,
            sourceId: $sale->id,
            occurredAt: $sale->sold_at ?? $sale->created_at,
            categoryLabel: UnifiedIncomeLine::CATEGORY_PHARMACY_SALE,
            categoryType: 'pharmacy',
            amount: (float) $sale->grand_total,
            paymentMethod: $sale->payment_method,
            patientVisitRecordId: $sale->patient_visit_record_id,
            patientVisitRecord: $sale->patientVisitRecord,
            recordedByName: $sale->cashier?->name,
            description: $sale->sale_number,
            actionRouteName: 'sales.show',
            actionRouteParameters: ['sale' => $sale->id],
            actionLabel: 'View Sale',
        );
    }
}
