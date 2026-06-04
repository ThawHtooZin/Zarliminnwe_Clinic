<?php

namespace App\Http\Controllers;

use App\Domain\Export\ListExportRegistry;
use App\Domain\Export\Services\ListExcelExportService;
use App\Domain\Export\Services\ListExportRowResolver;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListExportController extends Controller
{
    public function __construct(
        private readonly ListExportRegistry $registry,
        private readonly ListExportRowResolver $rowResolver,
        private readonly ListExcelExportService $exportService,
    ) {}

    public function products(Request $request): StreamedResponse
    {
        return $this->download($request, 'products');
    }

    public function productCategories(Request $request): StreamedResponse
    {
        return $this->download($request, 'product-categories');
    }

    public function suppliers(Request $request): StreamedResponse
    {
        return $this->download($request, 'suppliers');
    }

    public function incomeCategories(Request $request): StreamedResponse
    {
        return $this->download($request, 'finance.income-categories');
    }

    public function expenseCategories(Request $request): StreamedResponse
    {
        return $this->download($request, 'finance.expense-categories');
    }

    public function income(Request $request): StreamedResponse
    {
        return $this->download($request, 'finance.income');
    }

    public function expenses(Request $request): StreamedResponse
    {
        return $this->download($request, 'finance.expenses');
    }

    private function download(Request $request, string $exportKey): StreamedResponse
    {
        if (! $this->registry->has($exportKey)) {
            abort(404);
        }

        $rows = $this->rowResolver->rows($exportKey, $request);

        AuditLog::query()->create([
            'user_id' => $request->user()?->id,
            'action' => 'list_export.generated',
            'auditable_type' => 'list_export',
            'auditable_id' => null,
            'old_values' => null,
            'new_values' => [
                'export_key' => $exportKey,
                'row_count' => count($rows),
            ],
        ]);

        return $this->exportService->download($exportKey, $rows);
    }
}
