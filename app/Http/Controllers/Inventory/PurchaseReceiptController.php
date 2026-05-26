<?php

namespace App\Http\Controllers\Inventory;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Inventory\Services\StockPostingService;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseReceiptController extends Controller
{
    public function __construct(
        private readonly StockPostingService $stockPostingService,
        private readonly AuditLogger $auditLogger
    ) {}

    public function index(): View
    {
        $receipts = PurchaseReceipt::with('supplier')
            ->latest()
            ->paginate(15);

        return view('inventory.purchase-receipts.index', compact('receipts'));
    }

    public function create(): View
    {
        return view('inventory.purchase-receipts.form', [
            'receipt' => new PurchaseReceipt([
                'receipt_number' => 'PR-'.now()->format('Ymd-His'),
                'received_at' => now()->toDateString(),
            ]),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::with('units')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        DB::transaction(function () use ($validated, &$receipt): void {
            $receipt = PurchaseReceipt::create($validated['receipt']);

            foreach ($validated['lines'] as $line) {
                $receipt->lines()->create([
                    ...$line,
                    'total_cost' => (float) $line['quantity'] * (float) $line['unit_cost'],
                ]);
            }

            $this->auditLogger->log('purchase_receipt.created', $receipt, null, $receipt->load('lines')->toArray());
        });

        return redirect()->route('purchase-receipts.show', $receipt)->with('status', 'Purchase receipt saved as draft.');
    }

    public function show(PurchaseReceipt $purchaseReceipt): View
    {
        $purchaseReceipt->load(['supplier', 'lines.product', 'lines.productUnit', 'creator']);

        return view('inventory.purchase-receipts.show', ['receipt' => $purchaseReceipt]);
    }

    public function post(PurchaseReceipt $purchaseReceipt): RedirectResponse
    {
        $this->stockPostingService->postPurchaseReceipt($purchaseReceipt);

        return redirect()->route('purchase-receipts.show', $purchaseReceipt)->with('status', 'Purchase receipt posted.');
    }

    private function validated(Request $request): array
    {
        $request->merge([
            'lines' => collect($request->input('lines', []))
                ->filter(fn (array $line): bool => filled($line['product_id'] ?? null))
                ->values()
                ->all(),
        ]);

        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'receipt_number' => ['required', 'string', 'max:255', 'unique:purchase_receipts,receipt_number'],
            'received_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.product_unit_id' => ['required', 'exists:product_units,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.000001'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.batch_number' => ['nullable', 'string', 'max:255'],
            'lines.*.expires_at' => ['nullable', 'date'],
        ]);

        return [
            'receipt' => [
                'supplier_id' => $validated['supplier_id'],
                'receipt_number' => $validated['receipt_number'],
                'received_at' => $validated['received_at'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ],
            'lines' => $validated['lines'],
        ];
    }
}
