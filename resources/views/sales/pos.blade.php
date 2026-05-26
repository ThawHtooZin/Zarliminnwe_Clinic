@extends('layouts.app')

@section('title', 'POS')
@section('page-title', 'POS')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    @php
        $heldSaleCart = $heldSale?->lines->map(function ($line) {
            return [
                'key' => 'held-'.$line->id,
                'productId' => $line->product_id,
                'productName' => $line->product->name,
                'sku' => $line->product->sku,
                'imageUrl' => $line->product->image_path ? asset('storage/'.$line->product->image_path) : null,
                'initial' => strtoupper(substr($line->product->name, 0, 1)),
                'units' => $line->product->saleUnits->map(fn ($unit) => [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'abbreviation' => $unit->abbreviation,
                    'sale_price' => (float) $unit->sale_price,
                ])->values(),
                'unitId' => $line->product_unit_id,
                'quantity' => (float) $line->quantity,
                'unitPrice' => (float) $line->unit_price,
            ];
        })->values() ?? collect();
    @endphp

    @if ($heldSale)
        <div class="mb-4 rounded-xl border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#00535b]">
            Resuming held sale {{ $heldSale->sale_number }}. Stock will be checked when you complete the sale.
        </div>
    @endif

    <div
        class="grid min-h-[calc(100vh-7rem)] grid-cols-[minmax(0,1fr)_480px] overflow-hidden rounded-lg border border-[#bec8ca] bg-white shadow-sm"
        x-data="posCart()"
        x-init="initializePos()"
    >
        <section class="flex min-w-0 flex-col bg-[#f8f9fa] p-6">
            <div class="mb-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-[#00535b]">Point of Sale</h1>
                        <p class="mt-1 text-sm text-[#3e494a]">Search products and sell by configured units.</p>
                    </div>
                    <span class="rounded border border-[#bec8ca] bg-white px-3 py-2 text-sm text-[#3e494a]">
                        Product Search
                    </span>
                </div>

                <label class="relative block">
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-[#3e494a]">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="m21 21-4.3-4.3m1.3-5.2a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <input
                        x-model.debounce.300ms="search"
                        x-on:input="searchProducts"
                        type="search"
                        placeholder="Search by name, SKU, generic name, or barcode"
                        class="h-12 w-full rounded-xl border border-[#bec8ca] bg-white py-3 pl-12 pr-4 text-sm text-[#191c1d] shadow-sm outline-none transition placeholder:text-[#6f797a] focus:border-[#00535b] focus:ring-2 focus:ring-[#00535b]/10"
                    >
                </label>
            </div>

            <div class="grid grid-cols-3 gap-4 overflow-y-auto pr-1">
                <template x-for="product in products" :key="product.id">
                    <article class="rounded-lg border border-[#bec8ca] bg-white p-4 shadow-sm">
                        <div class="mb-3 flex h-28 items-center justify-center overflow-hidden rounded-xl border border-[#bec8ca] bg-[#e8f3f4]">
                            <img x-show="product.image_url" :src="product.image_url" :alt="`${product.name} image`" class="h-full w-full object-cover">
                            <span x-show="!product.image_url" class="text-3xl font-semibold text-[#00535b]" x-text="product.initial"></span>
                        </div>

                        <div class="mb-4">
                            <h2 class="line-clamp-2 text-sm font-semibold text-[#191c1d]" x-text="product.name"></h2>
                            <p class="mt-1 text-xs text-[#3e494a]" x-text="product.sku"></p>
                            <p class="mt-2 text-xs font-medium text-[#00535b]" x-text="product.formatted_stock"></p>
                        </div>

                        <div class="space-y-2">
                            <template x-for="unit in product.units" :key="unit.id">
                                <button
                                    type="button"
                                    x-on:click="addToCart(product, unit)"
                                    class="flex w-full items-center justify-between rounded-lg border border-[#00535b0a] bg-[#00535b03] px-3 py-2 text-left transition hover:border-[#00535b] hover:bg-[#00535b08]"
                                >
                                    <span class="text-xs font-medium text-[#3e494a]" x-text="unit.name"></span>
                                    <span class="text-sm font-semibold text-[#00535b]" x-text="money(unit.sale_price)"></span>
                                </button>
                            </template>
                        </div>
                    </article>
                </template>

                <div x-show="products.length === 0" class="col-span-3 rounded-lg border border-dashed border-[#bec8ca] bg-white p-10 text-center text-sm text-[#3e494a]">
                    No products found.
                </div>
            </div>
        </section>

        <aside class="flex min-h-0 flex-col border-l border-[#bec8ca] bg-white">
            <section class="border-b border-[#bec8ca] bg-[#f3f4f5] p-6">
                <label class="mb-2 block text-xs font-bold uppercase tracking-[0.06em] text-[#6f797a]">Patient Information (Optional)</label>
                <select x-model="patientVisitId" name="patient_visit_id" class="w-full rounded border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#191c1d] shadow-sm">
                    <option value="">No patient selected</option>
                </select>
                <p class="mt-2 text-xs text-[#3e494a]">Patient selection is optional and contains no clinical records.</p>
            </section>

            <section class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-[#191c1d]">Cart</h2>
                    <span class="rounded-full bg-[#00535b08] px-3 py-1 text-xs font-medium text-[#00535b]" x-text="cart.length + ' line(s)'"></span>
                </div>

                <template x-if="cart.length === 0">
                    <div class="flex flex-1 items-center justify-center rounded-lg border border-dashed border-[#bec8ca] bg-[#f8f9fa] p-8 text-center text-sm text-[#6f797a]">
                        Cart is empty.
                    </div>
                </template>

                <div class="space-y-3">
                    <template x-for="(line, index) in cart" :key="line.key">
                        <div class="rounded-lg border border-[#bec8ca] bg-white p-3 shadow-sm">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-[#bec8ca] bg-[#e8f3f4]">
                                        <img x-show="line.imageUrl" :src="line.imageUrl" :alt="`${line.productName} image`" class="h-full w-full object-cover">
                                        <span x-show="!line.imageUrl" class="text-base font-semibold text-[#00535b]" x-text="line.initial"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-[#191c1d]" x-text="line.productName"></p>
                                        <p class="text-xs text-[#3e494a]" x-text="line.sku"></p>
                                    </div>
                                </div>
                                <button type="button" x-on:click="removeLine(index)" class="text-xs font-medium text-red-600">Remove</button>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <label class="text-xs text-[#3e494a]">
                                    Unit
                                    <select x-model.number="line.unitId" x-on:change="changeUnit(line)" class="mt-1 w-full rounded border border-[#bec8ca] bg-[#f8f9fa] px-2 py-2 text-sm text-[#191c1d]">
                                        <template x-for="unit in line.units" :key="unit.id">
                                            <option :value="unit.id" x-text="unit.name"></option>
                                        </template>
                                    </select>
                                </label>
                                <label class="text-xs text-[#3e494a]">
                                    Quantity
                                    <input x-model.number="line.quantity" type="number" min="0.000001" step="0.000001" class="mt-1 w-full rounded border border-[#bec8ca] bg-[#f8f9fa] px-2 py-2 text-sm text-[#191c1d]">
                                </label>
                                <label class="text-xs text-[#3e494a]">
                                    Unit Price
                                    <input x-model.number="line.unitPrice" type="number" min="0" step="0.01" class="mt-1 w-full rounded border border-[#bec8ca] bg-[#f8f9fa] px-2 py-2 text-sm text-[#191c1d]">
                                </label>
                                <div class="text-xs text-[#3e494a]">
                                    Line Total
                                    <p class="mt-3 text-base font-semibold text-[#00535b]" x-text="money(lineTotal(line))"></p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <section class="border-t border-[#bec8ca] bg-[#edeeef] p-6">
                <div class="space-y-3">
                    <div class="flex justify-between text-sm text-[#3e494a]">
                        <span>Subtotal</span>
                        <span x-text="money(subtotal())"></span>
                    </div>
                    <label class="flex items-center justify-between gap-4 text-sm text-[#3e494a]">
                        <span>Discount</span>
                        <input x-model.number="discount" type="number" min="0" step="0.01" class="w-28 rounded border border-[#bec8ca] bg-white px-3 py-2 text-right text-sm">
                    </label>
                    <div class="flex justify-between text-sm text-[#3e494a]">
                        <span>Tax</span>
                        <span>{{ config('app.currency', '') }}<span x-text="tax.toFixed(2)"></span></span>
                    </div>
                    <div class="flex justify-between border-t border-[#bec8ca] pt-3 text-lg font-bold text-[#191c1d]">
                        <span>Grand Total</span>
                        <span x-text="money(grandTotal())"></span>
                    </div>
                    <label class="block text-sm text-[#3e494a]">
                        Payment Method
                        <select x-model="paymentMethod" class="mt-1 w-full rounded border border-[#bec8ca] bg-white px-4 py-3 text-sm text-[#191c1d]">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="mixed">Mixed</option>
                            <option value="other">Other</option>
                        </select>
                    </label>
                    <label class="block text-sm text-[#3e494a]">
                        Amount Paid
                        <input x-model.number="amountPaid" type="number" min="0" step="0.01" class="mt-1 w-full rounded border-2 border-[#00535b0a] bg-white px-4 py-3 text-right text-base text-[#191c1d] focus:border-[#00535b]">
                    </label>
                    <div class="flex justify-between text-sm font-semibold text-[#00535b]">
                        <span>Change</span>
                        <span x-text="money(changeAmount())"></span>
                    </div>
                </div>

                <form method="POST" action="{{ route('sales.store') }}" class="mt-5 space-y-3">
                    @csrf
                    <input type="hidden" name="held_sale_id" :value="heldSaleId">
                    <input type="hidden" name="patient_visit_id" :value="patientVisitId">
                    <input type="hidden" name="cart_payload" :value="JSON.stringify(cart)">
                    <input type="hidden" name="discount_total" :value="Number(discount || 0).toFixed(2)">
                    <input type="hidden" name="tax_total" :value="Number(tax || 0).toFixed(2)">
                    <input type="hidden" name="amount_paid" :value="Number(amountPaid || 0).toFixed(2)">
                    <input type="hidden" name="payment_method" :value="paymentMethod">
                    <button type="submit" class="w-full rounded-lg bg-[#00535b] px-4 py-3 text-sm font-semibold text-white shadow-[0_8px_20px_#00535b33] transition hover:bg-[#003f45]">
                        Complete Sale
                    </button>
                    <button type="submit" formaction="{{ route('sales.hold') }}" class="w-full rounded-lg border border-[#bec8ca] bg-white px-4 py-3 text-sm font-semibold text-[#3e494a] transition hover:bg-[#f8f9fa]">
                        Hold Sale
                    </button>
                </form>
            </section>
        </aside>
    </div>

    <script>
        function posCart() {
            return {
                search: '',
                products: [],
                cart: @json($heldSaleCart),
                heldSaleId: @json($heldSale?->id),
                patientVisitId: @json((string) ($heldSale?->patient_visit_id ?? '')),
                discount: @json((float) ($heldSale?->discount_total ?? 0)),
                tax: @json((float) ($heldSale?->tax_total ?? 0)),
                amountPaid: 0,
                paymentMethod: @json($heldSale?->payment_method ?? 'cash'),
                initializePos() {
                    this.searchProducts();
                },
                async searchProducts() {
                    const params = new URLSearchParams({ search: this.search });
                    const response = await fetch(`{{ route('sales.products.search') }}?${params.toString()}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const payload = await response.json();
                    this.products = payload.data ?? [];
                },
                addToCart(product, unit) {
                    this.cart.push({
                        key: `${product.id}-${unit.id}-${Date.now()}-${Math.random()}`,
                        productId: product.id,
                        productName: product.name,
                        sku: product.sku,
                        imageUrl: product.image_url,
                        initial: product.initial,
                        units: product.units,
                        unitId: unit.id,
                        quantity: 1,
                        unitPrice: Number(unit.sale_price),
                    });
                },
                removeLine(index) {
                    this.cart.splice(index, 1);
                },
                changeUnit(line) {
                    const selected = line.units.find((unit) => Number(unit.id) === Number(line.unitId));
                    if (selected) {
                        line.unitPrice = Number(selected.sale_price);
                    }
                },
                lineTotal(line) {
                    return Number(line.quantity || 0) * Number(line.unitPrice || 0);
                },
                subtotal() {
                    return this.cart.reduce((total, line) => total + this.lineTotal(line), 0);
                },
                grandTotal() {
                    return Math.max(this.subtotal() - Number(this.discount || 0) + Number(this.tax || 0), 0);
                },
                changeAmount() {
                    return Math.max(Number(this.amountPaid || 0) - this.grandTotal(), 0);
                },
                money(amount) {
                    return new Intl.NumberFormat('en-US', {
                        style: 'currency',
                        currency: 'USD',
                    }).format(Number(amount || 0));
                },
            };
        }
    </script>
@endsection
