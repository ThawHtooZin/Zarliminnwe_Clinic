<?php

namespace App\Http\Controllers\Catalog;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Catalog\Services\SupplierDeletionService;
use App\Domain\Shared\Exceptions\DeletionBlockException;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly SupplierDeletionService $deletionService,
    ) {}

    public function index(): View
    {
        $suppliers = Supplier::latest()->paginate(15);

        return view('catalog.suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('catalog.suppliers.form', [
            'supplier' => new Supplier(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($this->validated($request));
        $this->auditLogger->log('supplier.created', $supplier, null, $supplier->toArray());

        return redirect()->route('suppliers.index')->with('status', 'Supplier created.');
    }

    public function edit(Supplier $supplier): View
    {
        return view('catalog.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $oldValues = $supplier->toArray();
        $supplier->update($this->validated($request));
        $this->auditLogger->log('supplier.updated', $supplier, $oldValues, $supplier->fresh()->toArray());

        return redirect()->route('suppliers.index')->with('status', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        try {
            $oldValues = $supplier->toArray();
            $this->deletionService->delete($supplier);
            $this->auditLogger->log('supplier.deleted', $supplier, $oldValues, null);
        } catch (DeletionBlockException $exception) {
            return redirect()->route('suppliers.index')->with('error', $exception->getMessage());
        }

        return redirect()->route('suppliers.index')->with('status', 'Supplier deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => false];
    }
}
