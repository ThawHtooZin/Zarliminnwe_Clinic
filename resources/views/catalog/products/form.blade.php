@extends('layouts.app')

@section('title', $product->exists ? 'Edit Product' : 'New Product')
@section('page-title', 'Products')

@section('content')
    @php
        $existingRows = $units->values()->map(function ($unit, $index) use ($units) {
            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'abbreviation' => $unit->abbreviation,
                'level' => $unit->level,
                'parent_index' => $unit->parent_product_unit_id ? $units->values()->search(fn ($candidate) => $candidate->id === $unit->parent_product_unit_id) : null,
                'conversion_factor' => $unit->conversion_factor,
                'is_purchase_unit' => $unit->is_purchase_unit,
                'is_sale_unit' => $unit->is_sale_unit,
                'barcode' => $unit->barcode,
                'sale_price' => $unit->sale_price,
            ];
        })->all();

        $defaultRows = [
            ['name' => 'Box', 'abbreviation' => 'box', 'level' => 1, 'parent_index' => null, 'conversion_factor' => null, 'is_purchase_unit' => true, 'is_sale_unit' => true, 'sale_price' => 0],
            ['name' => 'Strip', 'abbreviation' => 'strip', 'level' => 2, 'parent_index' => 0, 'conversion_factor' => 10, 'is_purchase_unit' => true, 'is_sale_unit' => true, 'sale_price' => 0],
            ['name' => 'Pill', 'abbreviation' => 'pill', 'level' => 3, 'parent_index' => 1, 'conversion_factor' => 10, 'is_purchase_unit' => false, 'is_sale_unit' => true, 'sale_price' => 0],
        ];

        $unitRows = old('units', $existingRows ?: $defaultRows);
    @endphp

    <div class="mb-6">
        <h1 class="text-3xl font-semibold text-[#191c1d]">{{ $product->exists ? 'Edit Product' : 'New Product' }}</h1>
        <p class="mt-1 text-sm text-[#3e494a]">Define the item and its unit relationship, such as Box → Strip → Pill.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            Please fix the highlighted fields.
        </div>
    @endif

    <form method="POST" action="{{ $product->exists ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <section class="rounded-lg border border-[#bec8ca] bg-white p-6">
            <h2 class="mb-5 text-lg font-semibold">Product Details</h2>
            <div class="grid grid-cols-3 gap-5">
                <div>
                    <label class="mb-2 block text-sm font-medium">Name</label>
                    <input name="name" value="{{ old('name', $product->name) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm" required>
                    @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">SKU</label>
                    <input name="sku" value="{{ old('sku', $product->sku) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm" required>
                    @error('sku')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Category</label>
                    <select name="product_category_id" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm" required>
                        <option value="">Select category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('product_category_id', $product->product_category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Generic Name</label>
                    <input name="generic_name" value="{{ old('generic_name', $product->generic_name) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Manufacturer</label>
                    <input name="manufacturer" value="{{ old('manufacturer', $product->manufacturer) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium">Reorder Quantity</label>
                    <input name="reorder_quantity" type="number" step="0.0001" min="0" value="{{ old('reorder_quantity', $product->reorder_quantity) }}" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
                </div>
            </div>

            <div class="mt-5 grid grid-cols-3 gap-4">
                <label class="flex items-center gap-2 text-sm text-[#3e494a]"><input type="checkbox" name="track_batch" value="1" @checked(old('track_batch', $product->track_batch))> Track batch</label>
                <label class="flex items-center gap-2 text-sm text-[#3e494a]"><input type="checkbox" name="track_expiry" value="1" @checked(old('track_expiry', $product->track_expiry))> Track expiry</label>
                <label class="flex items-center gap-2 text-sm text-[#3e494a]"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Active</label>
            </div>

            <div class="mt-5 grid grid-cols-[1fr_auto] items-start gap-5">
                <div>
                    <label class="mb-2 block text-sm font-medium">Product Image</label>
                    <input name="image" type="file" accept="image/png,image/jpeg,image/webp" class="w-full rounded-xl border border-[#bec8ca] bg-[#f8f9fa] px-4 py-3 text-sm">
                    <p class="mt-2 text-xs text-[#3e494a]">Optional. JPG, PNG, or WebP up to 2MB.</p>
                    @error('image')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                @if ($product->image_path)
                    <x-product-image :product="$product" size="lg" />
                @endif
            </div>
        </section>

        <section class="rounded-lg border border-[#bec8ca] bg-white p-6">
            <h2 class="mb-2 text-lg font-semibold">Unit Relationship</h2>
            <p class="mb-5 text-sm text-[#3e494a]">One product can have many units. Use parent and conversion factor to describe real packaging.</p>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-[0.06em] text-[#3e494a]">
                        <tr>
                            <th class="py-2">Name</th>
                            <th class="py-2">Abbr.</th>
                            <th class="py-2">Level</th>
                            <th class="py-2">Parent Row</th>
                            <th class="py-2">Factor</th>
                            <th class="py-2">Purchase</th>
                            <th class="py-2">Sale</th>
                            <th class="py-2">Barcode</th>
                            <th class="py-2">Sale Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($index = 0; $index < 6; $index++)
                            @php($row = $unitRows[$index] ?? [])
                            <tr class="border-t border-[#bec8ca]">
                                <td class="py-2 pr-2">
                                    <input type="hidden" name="units[{{ $index }}][id]" value="{{ $row['id'] ?? '' }}">
                                    <input name="units[{{ $index }}][name]" value="{{ $row['name'] ?? '' }}" class="w-full rounded-lg border border-[#bec8ca] px-3 py-2">
                                </td>
                                <td class="py-2 pr-2"><input name="units[{{ $index }}][abbreviation]" value="{{ $row['abbreviation'] ?? '' }}" class="w-full rounded-lg border border-[#bec8ca] px-3 py-2"></td>
                                <td class="py-2 pr-2"><input name="units[{{ $index }}][level]" type="number" min="1" value="{{ $row['level'] ?? $index + 1 }}" class="w-20 rounded-lg border border-[#bec8ca] px-3 py-2"></td>
                                <td class="py-2 pr-2">
                                    <select name="units[{{ $index }}][parent_index]" class="w-28 rounded-lg border border-[#bec8ca] px-3 py-2">
                                        <option value="">None</option>
                                        @for ($parentIndex = 0; $parentIndex < $index; $parentIndex++)
                                            <option value="{{ $parentIndex }}" @selected(($row['parent_index'] ?? null) === $parentIndex)>Row {{ $parentIndex + 1 }}</option>
                                        @endfor
                                    </select>
                                </td>
                                <td class="py-2 pr-2"><input name="units[{{ $index }}][conversion_factor]" type="number" step="0.000001" min="0" value="{{ $row['conversion_factor'] ?? '' }}" class="w-24 rounded-lg border border-[#bec8ca] px-3 py-2"></td>
                                <td class="py-2 pr-2 text-center"><input type="checkbox" name="units[{{ $index }}][is_purchase_unit]" value="1" @checked($row['is_purchase_unit'] ?? false)></td>
                                <td class="py-2 pr-2 text-center"><input type="checkbox" name="units[{{ $index }}][is_sale_unit]" value="1" @checked($row['is_sale_unit'] ?? false)></td>
                                <td class="py-2 pr-2"><input name="units[{{ $index }}][barcode]" value="{{ $row['barcode'] ?? '' }}" class="w-full rounded-lg border border-[#bec8ca] px-3 py-2"></td>
                                <td class="py-2"><input name="units[{{ $index }}][sale_price]" type="number" step="0.01" min="0" value="{{ $row['sale_price'] ?? 0 }}" class="w-28 rounded-lg border border-[#bec8ca] px-3 py-2"></td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </section>

        <div class="flex gap-3">
            <button class="rounded-xl bg-[#00535b] px-4 py-2 text-sm font-semibold text-white">Save Product</button>
            <a href="{{ route('products.index') }}" class="rounded-xl border border-[#bec8ca] px-4 py-2 text-sm text-[#3e494a]">Cancel</a>
        </div>
    </form>
@endsection
