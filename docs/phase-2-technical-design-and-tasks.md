# Phase 2 Technical Design and Task Breakdown

## Clinic Management System - Phase 2 Sales

### Purpose

Phase 2 builds the pharmacy sales workflow on top of the completed Phase 1 foundation.

The focus is:

- Pharmacy POS.
- Unit-based selling.
- Payments and receipts.
- Sale void and stock reversal.
- Correct multi-unit stock deduction using the existing `UnitRelationshipService` and `StockPostingService`.

Patient selection at POS is optional. Pharmacy sales must work fully without a patient.

### Source Inputs

- PRD: `docs/clinic-management-system-prd.md`
- Phase 1 design: `docs/phase-1-technical-design-and-tasks.md`
- Existing Phase 1 modules:
  - Product catalog.
  - Product units and unit relationship service.
  - Stock ledger and stock balances.
  - Opening stock.
  - Purchase receiving.
  - Audit logs.
  - Auth and minimal roles.

---

## 1. Technical Design

### 1.1 Architecture Goals

- Keep sales logic separate from product, unit, inventory, and patient modules.
- Use the existing multi-unit stock architecture.
- Preserve the exact sale unit and quantity on each sale line.
- Use `UnitRelationshipService` for availability checks and fractional stock deduction.
- Use `StockPostingService` to post `out` direction stock ledger rows.
- Keep the stock ledger immutable.
- Allow sale void through reversal ledger entries, not silent deletion.
- Keep patient selection optional and decoupled.

### 1.2 Proposed Domain Module

#### Sales

Owns POS checkout, sales, sale lines, payment capture, receipt display, and sale void workflow.

Responsibilities:

- Create draft/held sales.
- Add products to sale lines by barcode, SKU, or search.
- Calculate line totals, discounts, tax, grand total, amount paid, and change.
- Validate stock availability before completing sale.
- Post stock-out movements through inventory services.
- Mark completed sales as immutable.
- Void completed sales with stock reversal.

Suggested namespace:

```text
App\Domain\Sales
```

Suggested Laravel structure:

```text
app/
  Domain/
    Sales/
      Actions/
      Data/
      Services/
  Http/
    Controllers/
      Sales/
    Requests/
      Sales/
  Models/
    Sale.php
    SaleLine.php
database/
  migrations/
resources/
  views/
    sales/
tests/
  Feature/
  Unit/
```

---

## 2. Core Data Model

### 2.1 `sales`

Stores sale header, payment summary, optional patient link, and sale lifecycle state.

Key fields:

- `id`
- `sale_number`
- `patient_visit_id`
- `status`
- `subtotal`
- `discount_total`
- `tax_total`
- `grand_total`
- `amount_paid`
- `change_amount`
- `payment_method`
- `notes`
- `sold_by`
- `sold_at`
- `voided_by`
- `voided_at`
- `void_reason`
- timestamps

Status values:

- `draft`
- `held`
- `completed`
- `voided`

Payment method values:

- `cash`
- `card`
- `mixed`
- `other`

Rules:

- `sale_number` must be unique.
- `patient_visit_id` is nullable.
- Patient link is optional. A sale without patient is valid.
- Completed sales should not be edited directly.
- Voided sales should remain visible for audit.
- `amount_paid` must be greater than or equal to `grand_total` unless a later credit/debt workflow is explicitly added.
- `change_amount = amount_paid - grand_total`.

Patient relationship:

- Phase 2 should not make pharmacy sales depend on patient management.
- If the ultra-minimal patient visit table exists, `patient_visit_id` can be a nullable foreign key.
- If patient visits are not implemented yet, keep this field nullable and do not block POS work.

Suggested migration:

```php
Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->string('sale_number')->unique();
    $table->unsignedBigInteger('patient_visit_id')->nullable()->index();
    $table->string('status')->default('draft');
    $table->decimal('subtotal', 14, 2)->default(0);
    $table->decimal('discount_total', 14, 2)->default(0);
    $table->decimal('tax_total', 14, 2)->default(0);
    $table->decimal('grand_total', 14, 2)->default(0);
    $table->decimal('amount_paid', 14, 2)->default(0);
    $table->decimal('change_amount', 14, 2)->default(0);
    $table->string('payment_method')->default('cash');
    $table->text('notes')->nullable();
    $table->foreignId('sold_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('sold_at')->nullable();
    $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('voided_at')->nullable();
    $table->text('void_reason')->nullable();
    $table->timestamps();
});
```

### 2.2 `sale_lines`

Stores each sold product, exact sale unit, quantity, pricing, and stock deduction metadata.

Key fields:

- `id`
- `sale_id`
- `product_id`
- `product_unit_id`
- `quantity`
- `unit_price`
- `discount_amount`
- `tax_amount`
- `line_total`
- timestamps

Rules:

- `product_unit_id` must belong to `product_id`.
- `product_unit_id` must be a sale-enabled unit.
- `quantity` must be positive.
- `unit_price` defaults from `product_units.sale_price`, but can be overridden if role allows.
- `line_total = (quantity * unit_price) - discount_amount + tax_amount`.
- Sale lines preserve the exact unit sold. No normalized backend unit should be forced.

Suggested migration:

```php
Schema::create('sale_lines', function (Blueprint $table) {
    $table->id();
    $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->restrictOnDelete();
    $table->foreignId('product_unit_id')->constrained()->restrictOnDelete();
    $table->decimal('quantity', 18, 6);
    $table->decimal('unit_price', 14, 2);
    $table->decimal('discount_amount', 14, 2)->default(0);
    $table->decimal('tax_amount', 14, 2)->default(0);
    $table->decimal('line_total', 14, 2);
    $table->timestamps();
});
```

### 2.3 Optional Future Payment Table

Phase 2 can start with payment fields on `sales`.

If mixed payments become complex, add a later `sale_payments` table:

- `id`
- `sale_id`
- `method`
- `amount`
- `reference`
- timestamps

For Phase 2 MVP, keep payment summary on `sales` unless mixed payment details are required immediately.

---

## 3. Service Design

### 3.1 `SaleCheckoutService`

Create:

```text
App\Domain\Sales\Services\SaleCheckoutService
```

Responsibilities:

- Validate sale status.
- Validate sale lines.
- Calculate totals.
- Check stock availability.
- Deduct stock through `UnitRelationshipService` and `StockPostingService`.
- Mark sale as completed.
- Log audit event.

Main method:

```php
completeSale(Sale $sale, array $paymentData): Sale
```

Completion flow:

1. Load sale with lines, products, units, and current stock balances.
2. Validate sale is `draft` or `held`.
3. Validate each sale line has product, sale unit, quantity, and price.
4. Calculate subtotal, discounts, tax, grand total, amount paid, and change.
5. For each sale line, get available stock balances for the product.
6. Use `UnitRelationshipService::calculateDeduction($balances, $saleLine->productUnit, $saleLine->quantity)`.
7. The deduction result returns exact balance-level deductions, including fractional deductions when needed.
8. For each deduction result, call `StockPostingService` to post an `out` ledger row.
9. Update stock balances inside the same database transaction.
10. Mark sale as `completed`.
11. Save `sold_by` and `sold_at`.
12. Create audit log.

### 3.2 Exact Integration With `UnitRelationshipService`

POS must use `UnitRelationshipService` in two places:

#### Availability Preview

When cashier adds a product/unit/quantity:

```php
$deductions = $unitRelationshipService->calculateDeduction(
    availableBalances: $product->stockBalances,
    saleUnit: $saleLine->productUnit,
    quantity: $saleLine->quantity
);
```

Expected behavior:

- If enough stock exists in the same unit, deduct from that unit.
- If stock exists in a larger related unit, calculate the fractional amount to deduct.
- If stock exists across multiple balances, split deduction across balances.
- If not enough stock exists, throw a clear insufficient stock error.

Example:

```text
Available stock:
- 1 Bottle

Unit relationship:
- 1 Bottle = 100 Pills

Sale:
- 5 Pills

Deduction:
- 0.05 Bottle from the bottle balance
- Stock ledger row stores product_unit_id = Bottle unit ID, quantity = 0.05, direction = out
```

#### Stock Display

POS should use:

```php
$unitRelationshipService->formatStock($product, $product->stockBalances, $selectedUnit);
```

This shows available stock in a user-friendly unit while the ledger still preserves native unit rows.

### 3.3 Exact Integration With `StockPostingService`

Phase 1 already uses `StockPostingService` for stock-in. Phase 2 should extend it with a sale-specific method:

```php
postSale(Sale $sale): void
```

or use the existing lower-level method:

```php
postMovement(
    product: $product,
    unit: $deductionUnit,
    quantity: $deductionQuantity,
    type: 'sale',
    direction: StockLedger::DIRECTION_OUT,
    unitCost: $cost,
    reference: $sale,
    stockBatch: $balance->stockBatch,
    reason: 'Sale '.$sale->sale_number
);
```

Required `StockLedger` changes:

- Add type constant: `TYPE_SALE = 'sale'`.
- Add type constant: `TYPE_SALE_VOID = 'sale_void'`.

Important rule:

- The sale line records the unit sold.
- The stock ledger records the unit actually deducted from stock balance.
- These can be different when fractional deduction is needed.

Example:

```text
Sale line:
- product_unit_id = Pill
- quantity = 5

Stock balance:
- product_unit_id = Bottle
- quantity = 1

Stock ledger out row:
- product_unit_id = Bottle
- quantity = 0.05
- direction = out
- reference_type = Sale
- reference_id = sale ID
```

### 3.4 `SaleVoidService`

Create:

```text
App\Domain\Sales\Services\SaleVoidService
```

Responsibilities:

- Validate sale is completed.
- Require void reason.
- Reverse stock by reading original sale stock ledger rows.
- Post opposite `in` ledger rows with type `sale_void`.
- Mark sale as voided.
- Save `voided_by`, `voided_at`, and `void_reason`.
- Log audit event.

Void flow:

1. Load completed sale.
2. Read stock ledger rows where `reference_type = Sale::class`, `reference_id = sale ID`, `type = sale`, `direction = out`.
3. For each row, post a new ledger row:
   - same product.
   - same stock batch.
   - same product unit.
   - same quantity.
   - type `sale_void`.
   - direction `in`.
4. Update stock balances through `StockPostingService`.
5. Mark sale as `voided`.

No original ledger rows should be deleted or edited.

### 3.5 `SaleNumberGenerator`

Create a small service:

```text
App\Domain\Sales\Services\SaleNumberGenerator
```

Format:

```text
S-YYYYMMDD-0001
```

Rules:

- Unique per sale.
- Sequence can be daily.
- Must be generated server-side.

---

## 4. POS UI Design

### 4.1 POS Screen

Route:

```text
/sales/pos
```

Named route:

```text
sales.pos
```

UI areas:

- Product search by name, SKU, barcode, generic name.
- Product result list with available unit buttons.
- Cart panel with sale lines.
- Quantity input per line.
- Unit selector per line.
- Unit price and line total.
- Discount input.
- Payment summary.
- Optional patient selector.
- Complete sale button.
- Hold sale button.

Patient selector:

- Optional.
- Default state: no patient selected.
- POS checkout must not require patient.
- If patient records exist, cashier can search and attach one.
- No clinical data appears in POS.

### 4.2 Product Search

Search should support:

- `products.name`
- `products.sku`
- `products.generic_name`
- `product_units.barcode`

Response should include:

- product ID.
- product name.
- SKU.
- available units.
- sale price per unit.
- formatted stock.

### 4.3 Receipt Screen

Route:

```text
/sales/{sale}/receipt
```

Receipt shows:

- clinic name/logo.
- sale number.
- date/time.
- cashier.
- optional patient name if selected.
- line items.
- totals.
- payment method.
- amount paid.
- change.

### 4.4 Sales List

Route:

```text
/sales
```

Shows:

- sale number.
- sold date/time.
- cashier.
- optional patient.
- total.
- status.
- actions: view receipt, void if authorized.

---

## 5. Routes

Suggested route group:

```php
Route::middleware(['auth', 'role:admin,pharmacist,cashier'])->group(function () {
    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/pos', [PosController::class, 'create'])->name('sales.pos');
    Route::post('/sales', [PosController::class, 'store'])->name('sales.store');
    Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
    Route::get('/sales/{sale}/receipt', [SaleReceiptController::class, 'show'])->name('sales.receipt');
});

Route::middleware(['auth', 'role:admin,pharmacist'])->group(function () {
    Route::post('/sales/{sale}/void', [SaleVoidController::class, 'store'])->name('sales.void');
});
```

Authorization:

- Admin: full sales access and void.
- Pharmacist: POS, sales view, void.
- Cashier: POS and receipt view only.
- Stock Manager: no POS access unless explicitly approved.

---

## 6. Validation Rules

### Sale Completion

- Sale must have at least one line.
- Product required.
- Product unit required.
- Product unit must belong to product.
- Product unit must be sale-enabled.
- Quantity must be greater than zero.
- Unit price must be non-negative.
- Discount cannot exceed line subtotal.
- Payment method required.
- Amount paid must be greater than or equal to grand total.
- Patient is optional.

### Void Sale

- Sale must be completed.
- Sale must not already be voided.
- Void reason required.
- User must be authorized.

---

## 7. Stock Deduction Examples

### Example 1: Same Unit Deduction

```text
Stock balance:
- 10 Strips

Sale:
- 2 Strips

Ledger:
- direction = out
- product_unit_id = Strip
- quantity = 2
```

### Example 2: Fractional Larger Unit Deduction

```text
Stock balance:
- 1 Box

Unit relationship:
- 1 Box = 10 Strips
- 1 Strip = 10 Pills

Sale:
- 5 Pills

UnitRelationshipService:
- 5 Pills = 0.05 Box

Ledger:
- direction = out
- product_unit_id = Box
- quantity = 0.05
```

### Example 3: Split Deduction Across Balances

```text
Stock balances:
- 2 Strips
- 1 Box

Sale:
- 30 Pills

Deduction:
- 2 Strips out
- 0.1 Box out
```

---

## 8. Audit Logging

Sales audit actions:

- `sale.created`
- `sale.completed`
- `sale.held`
- `sale.voided`
- `sale.receipt_viewed` optional

Audit should include:

- user ID.
- sale ID.
- old values.
- new values.
- timestamp.

Critical audit rules:

- Completed sale edits are not allowed.
- Voiding creates reversal stock ledger rows.
- Original sale ledger rows remain untouched.

---

## 9. Testing Strategy

Use Pest/Laravel feature tests.

### Unit Tests

- Sale total calculation.
- Discount calculation.
- Payment change calculation.
- Sale number generation.
- Unit relationship fractional deduction for POS sale.

### Feature Tests

- Cashier can open POS.
- Product search returns products and sale units.
- Sale can be completed without patient.
- Sale can optionally store patient link.
- Sale line preserves exact sale unit and quantity.
- Sale completion posts stock ledger `out` rows.
- Sale completion updates stock balances.
- Sale fails when stock is insufficient.
- Completed sale cannot be edited directly.
- Authorized user can void sale.
- Void posts stock ledger `in` reversal rows.
- Cashier cannot void sale.
- Guest cannot access sales routes.

### Browser Tests If Practical

- Add product to cart.
- Change unit and quantity.
- Complete cash sale.
- Print/view receipt.
- Hold and resume sale.

---

## 10. Key Risks And Decisions

### Risk: Fractional deductions corrupt stock

Decision:

- Use only `UnitRelationshipService::calculateDeduction()` for sale stock deduction.
- Do not manually calculate unit conversions inside controllers.
- Cover fractional deduction with tests.

### Risk: Sale line unit and deducted stock unit differ

Decision:

- Preserve sale line unit for receipt and reporting.
- Preserve deducted stock unit in stock ledger.
- Link stock ledger rows to sale through `reference_type` and `reference_id`.

### Risk: Completed sales are edited after stock posting

Decision:

- Completed sales are immutable.
- Corrections must use void and re-sale flow.

### Risk: Patient scope expands into clinical records

Decision:

- Patient selection is optional.
- No diagnosis, prescription, vitals, notes, appointment, or clinical history in POS.
- Sale works fully without patient.

---

## 11. Phase 2 Task Breakdown

### Epic 1: Sales Foundation

#### Task 1.1 - Create sales migrations

Acceptance criteria:

- `sales` table exists.
- `sale_lines` table exists.
- Sale has nullable `patient_visit_id`.
- Sale lines store product, product unit, quantity, pricing, and line total.

#### Task 1.2 - Create sales models

Acceptance criteria:

- `Sale` model exists.
- `SaleLine` model exists.
- Sale has many lines.
- Sale belongs to cashier through `sold_by`.
- Sale supports status helper methods.

#### Task 1.3 - Add sales route group

Acceptance criteria:

- Authenticated sales routes exist.
- Cashier can access POS.
- Admin and pharmacist can void sale.
- Guest users are redirected.

### Epic 2: POS Cart And Product Search

#### Task 2.1 - Build product search endpoint

Acceptance criteria:

- Search by product name, SKU, generic name, and barcode.
- Response includes sale-enabled units and prices.
- Response includes formatted stock.

#### Task 2.2 - Build POS screen

Acceptance criteria:

- Cashier can open POS.
- Cashier can search products.
- Cashier can add products to cart.
- Cashier can select unit and quantity.
- Patient selector is optional and defaults to empty.

#### Task 2.3 - Build cart total calculation

Acceptance criteria:

- Line subtotal is calculated.
- Discount is applied.
- Tax placeholder is supported.
- Grand total is displayed.
- Amount paid and change are displayed.

### Epic 3: Sale Checkout

#### Task 3.1 - Create `SaleCheckoutService`

Acceptance criteria:

- Service completes draft or held sale.
- Service validates all sale lines.
- Service calculates totals.
- Service rejects insufficient stock.

#### Task 3.2 - Integrate `UnitRelationshipService`

Acceptance criteria:

- Checkout calls `calculateDeduction()` for each sale line.
- Same-unit deduction works.
- Fractional larger-unit deduction works.
- Split deduction across balances works.

#### Task 3.3 - Integrate `StockPostingService`

Acceptance criteria:

- Checkout posts stock ledger rows with `direction = out`.
- Ledger rows reference the sale.
- Stock balances are reduced inside the same transaction.
- Sale is marked completed only after stock posting succeeds.

#### Task 3.4 - Save completed sale

Acceptance criteria:

- Sale status becomes `completed`.
- `sold_by` and `sold_at` are saved.
- Payment method, amount paid, and change are saved.
- Patient link remains nullable.

### Epic 4: Receipts And Sales History

#### Task 4.1 - Build sales list

Acceptance criteria:

- User can list completed, held, and voided sales.
- User can filter by date and status.
- User can open sale details.

#### Task 4.2 - Build receipt page

Acceptance criteria:

- Receipt shows clinic branding.
- Receipt shows sale number, date, cashier, optional patient, items, and totals.
- Receipt can be printed through browser print.

#### Task 4.3 - Build sale detail page

Acceptance criteria:

- Sale lines are visible.
- Stock movement references are visible or linked.
- Voided sale status is clearly shown.

### Epic 5: Hold And Resume

#### Task 5.1 - Save held sale

Acceptance criteria:

- Cashier can save sale as `held`.
- Held sale does not post stock ledger rows.
- Held sale does not affect stock balances.

#### Task 5.2 - Resume held sale

Acceptance criteria:

- Cashier can resume held sale.
- Cashier can complete resumed sale.
- Stock is checked at completion time, not hold time.

### Epic 6: Sale Void

#### Task 6.1 - Create `SaleVoidService`

Acceptance criteria:

- Service voids completed sale only.
- Service requires reason.
- Service posts reversal stock ledger rows with `direction = in`.
- Original ledger rows remain unchanged.

#### Task 6.2 - Build void UI

Acceptance criteria:

- Admin and pharmacist can void.
- Cashier cannot void.
- Void reason is required.
- Voided sale is visible in sales list.

#### Task 6.3 - Test sale void

Acceptance criteria:

- Void restores stock balance.
- Void creates audit log.
- Double void is rejected.

### Epic 7: Audit And Security

#### Task 7.1 - Log sales events

Acceptance criteria:

- Sale completion is logged.
- Sale hold is logged.
- Sale void is logged.

#### Task 7.2 - Add authorization tests

Acceptance criteria:

- Guest cannot access POS.
- Cashier can complete sale.
- Cashier cannot void sale.
- Admin can void sale.

### Epic 8: QA And Documentation

#### Task 8.1 - Add sales seed data if useful

Acceptance criteria:

- Sample product stock exists for POS testing.
- Sample cashier user exists.

#### Task 8.2 - Write Phase 2 usage notes

Acceptance criteria:

- Notes explain POS sale.
- Notes explain optional patient selection.
- Notes explain sale void.

#### Task 8.3 - Run verification

Acceptance criteria:

- Laravel Pint passes.
- Test suite passes.
- Frontend build passes.
- Critical sale stock deduction tests pass.

---

## 12. Phase 2 Completion Criteria

Phase 2 is complete when:

- Cashier can create a pharmacy sale.
- Sale works without patient.
- Patient link is optional when patient records exist.
- Sale lines preserve exact sold unit and quantity.
- Sale checkout uses `UnitRelationshipService` for stock deduction.
- Sale checkout uses `StockPostingService` for `out` ledger rows.
- Stock balances reduce correctly.
- Receipt can be viewed or printed.
- Sales list and sale detail pages exist.
- Held sales can be resumed.
- Authorized users can void sales.
- Voids restore stock through reversal ledger entries.
- Tests cover happy and unhappy paths.

---

## 13. Recommended Build Order

1. Sales migrations and models.
2. Sale number generator.
3. POS routes and basic screen.
4. Product search for POS.
5. Cart and total calculation.
6. `SaleCheckoutService`.
7. `UnitRelationshipService` deduction integration.
8. `StockPostingService` sale-out integration.
9. Receipt and sales list.
10. Hold/resume.
11. Void/reversal workflow.
12. Audit logs.
13. Tests and documentation.
