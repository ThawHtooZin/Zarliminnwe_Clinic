# Phase 3 Technical Design and Task Breakdown

## Clinic Management System - Phase 3 Stock Control

### Purpose

Phase 3 builds stock control workflows on top of the completed Phase 1 inventory foundation and Phase 2 sales stock movements.

The focus is:

- Low-stock alerts.
- Stock count and variance adjustment.
- Expiry and near-expiry tracking.
- Core stock reports.
- Correct multi-unit stock display using the existing `UnitRelationshipService`.

Phase 3 must not change the multi-unit stock ledger architecture. Stock ledger and stock balance rows continue to preserve the exact `product_unit_id` and quantity used by the transaction.

### Source Inputs

- PRD: `docs/clinic-management-system-prd.md`
- Phase 1 design: `docs/phase-1-technical-design-and-tasks.md`
- Phase 2 design: `docs/phase-2-technical-design-and-tasks.md`
- Existing inventory modules:
  - Product catalog.
  - Product units and unit relationship service.
  - Stock batches.
  - Stock balances.
  - Stock ledger.
  - Opening stock.
  - Purchase receiving.
  - Sales stock deduction.
  - Sale void stock reversal.
  - Audit logs.
  - Auth and role middleware.

---

## 1. Technical Design

### 1.1 Architecture Goals

- Keep stock control logic inside the inventory domain.
- Keep stock ledger rows immutable.
- Post stock corrections through adjustment ledger entries, not direct balance edits.
- Use `UnitRelationshipService` for readable stock display and cross-unit comparison.
- Use `StockPostingService` for all stock-affecting movements.
- Make stock count posting transactional.
- Keep reports traceable back to source tables.
- Avoid duplicating stock math in controllers or Blade views.

### 1.2 Proposed Domain Module

#### Inventory Stock Control

Owns low-stock alerts, stock count sessions, variance posting, expiry tracking, and stock reports.

Responsibilities:

- Detect low-stock products based on reorder unit and reorder quantity.
- Show current stock on hand per product, unit, and batch.
- Create physical stock count sessions.
- Capture counted quantities by product, batch, and unit.
- Calculate variance between expected and counted quantities.
- Post variance adjustments through stock ledger rows.
- Show expiring and expired stock batches.
- Generate stock reports with filters.
- Log audit events for stock count and adjustment actions.

Suggested namespace:

```text
App\Domain\Inventory
```

Suggested Laravel structure:

```text
app/
  Domain/
    Inventory/
      Data/
      Services/
  Http/
    Controllers/
      Inventory/
      Reports/
    Requests/
      Inventory/
  Models/
    StockCount.php
    StockCountLine.php
database/
  migrations/
resources/
  views/
    inventory/
      stock-counts/
      alerts/
    reports/
tests/
  Feature/
  Unit/
```

---

## 2. Core Data Model

### 2.1 Existing `products` Reorder Fields

Products already support reorder configuration.

Key fields:

- `reorder_product_unit_id`
- `reorder_quantity`

Rules:

- `reorder_product_unit_id` is nullable.
- `reorder_quantity` is nullable until low-stock alerting is configured.
- If one reorder field is set, the other should also be set.
- `reorder_product_unit_id` must belong to the same product.
- Low-stock comparison must use `UnitRelationshipService`, because current stock may exist in different related units.

Low-stock example:

```text
Product:
- Paracetamol 500mg
- reorder unit = Strip
- reorder quantity = 20

Stock balances:
- 1 Box
- 5 Strips

Unit relationship:
- 1 Box = 10 Strips

Comparable stock:
- 15 Strips

Result:
- Low-stock alert should show because 15 < 20.
```

### 2.2 Existing `stock_batches`

Stores batch and expiry metadata for products that track batch or expiry.

Key fields:

- `id`
- `product_id`
- `batch_number`
- `expires_at`
- `received_at`
- timestamps

Rules:

- Products with `track_batch = true` require batch number when stock is received.
- Products with `track_expiry = true` require expiry date when stock is received.
- Expiry reports should only include batches that have remaining stock balance.
- Expired stock is not automatically deducted in Phase 3.
- Wastage or expired-stock removal must be posted as an explicit stock adjustment.

### 2.3 `stock_counts`

Stores a physical stock count session header.

Key fields:

- `id`
- `count_number`
- `status`
- `counted_by`
- `reviewed_by`
- `started_at`
- `submitted_at`
- `posted_at`
- `notes`
- timestamps

Status values:

- `draft`
- `submitted`
- `posted`
- `cancelled`

Rules:

- `count_number` must be unique.
- Draft counts can be edited.
- Submitted counts can be reviewed.
- Posted counts are immutable.
- Cancelled counts do not affect stock.
- Posting a count creates stock ledger adjustment rows for non-zero variances.

Suggested migration:

```php
Schema::create('stock_counts', function (Blueprint $table) {
    $table->id();
    $table->string('count_number')->unique();
    $table->string('status')->default('draft');
    $table->foreignId('counted_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('started_at')->nullable();
    $table->timestamp('submitted_at')->nullable();
    $table->timestamp('posted_at')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### 2.4 `stock_count_lines`

Stores each counted product, unit, batch, expected quantity, counted quantity, and variance.

Key fields:

- `id`
- `stock_count_id`
- `product_id`
- `stock_batch_id`
- `product_unit_id`
- `expected_quantity`
- `counted_quantity`
- `variance_quantity`
- `adjustment_ledger_id`
- `notes`
- timestamps

Rules:

- `product_unit_id` must belong to `product_id`.
- `stock_batch_id` is nullable for non-batch-tracked products.
- Expected quantity is captured at the time the count line is created.
- Counted quantity must be zero or greater.
- `variance_quantity = counted_quantity - expected_quantity`.
- Positive variance posts `direction = in`.
- Negative variance posts `direction = out`.
- Posted variance ledger rows use `type = adjustment`.
- Original expected quantities should not be recalculated after posting.

Suggested migration:

```php
Schema::create('stock_count_lines', function (Blueprint $table) {
    $table->id();
    $table->foreignId('stock_count_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->restrictOnDelete();
    $table->foreignId('stock_batch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('product_unit_id')->constrained()->restrictOnDelete();
    $table->decimal('expected_quantity', 18, 6)->default(0);
    $table->decimal('counted_quantity', 18, 6)->default(0);
    $table->decimal('variance_quantity', 18, 6)->default(0);
    $table->foreignId('adjustment_ledger_id')->nullable()->constrained('stock_ledgers')->nullOnDelete();
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### 2.5 Existing `stock_ledgers`

Stock ledger remains the immutable source of truth for movements.

Required type values:

- `opening_stock`
- `purchase_receipt`
- `sale`
- `sale_void`
- `adjustment`

Adjustment examples:

```text
Physical count found more stock:
- type = adjustment
- direction = in
- reference_type = StockCount::class
- reference_id = stock count ID

Physical count found less stock:
- type = adjustment
- direction = out
- reference_type = StockCount::class
- reference_id = stock count ID
```

Rules:

- Never edit old ledger rows.
- Never delete old ledger rows to correct stock.
- Every adjustment requires a reason.
- Every adjustment should reference the source stock count when it comes from stock count posting.

### 2.6 Report Data Sources

Phase 3 reports should not require new reporting tables.

Report sources:

- Stock on hand: `stock_balances`, `products`, `product_units`, `stock_batches`.
- Stock movements: `stock_ledgers`, `products`, `product_units`, `stock_batches`, `users`.
- Low-stock report: `products`, `stock_balances`, `product_units`.
- Expiry report: `stock_batches`, `stock_balances`, `products`.
- Stock adjustment report: `stock_ledgers` where `type = adjustment`.

Rules:

- Reports must support filters before export is considered.
- Reports should avoid unfiltered full-table scans where practical.
- Reports should show traceable source rows and timestamps.

---

## 3. Service Design

### 3.1 `LowStockAlertService`

Create:

```text
App\Domain\Inventory\Services\LowStockAlertService
```

Responsibilities:

- Load products that have reorder configuration.
- Convert available stock into the reorder unit.
- Compare available quantity against reorder quantity.
- Return alert rows for products below threshold.
- Support filters by category, product, and active status.

Main method:

```php
getLowStockProducts(array $filters = []): Collection
```

Expected alert row:

- product.
- reorder unit.
- reorder quantity.
- available quantity in reorder unit.
- formatted available stock.
- shortage quantity.

Important rule:

- Do not compare raw stock balance quantities across different units.
- Always use `UnitRelationshipService` for comparable quantity.

### 3.2 `StockCountNumberGenerator`

Create:

```text
App\Domain\Inventory\Services\StockCountNumberGenerator
```

Format:

```text
SC-YYYYMMDD-0001
```

Rules:

- Unique per stock count.
- Sequence can be daily.
- Must be generated server-side.

### 3.3 `StockCountService`

Create:

```text
App\Domain\Inventory\Services\StockCountService
```

Responsibilities:

- Create stock count sessions.
- Add stock count lines from current balances.
- Validate counted quantities.
- Calculate variances.
- Submit count for review.
- Post approved variances.
- Log audit events.

Posting flow:

1. Load stock count with lines.
2. Validate status is `submitted`.
3. Begin database transaction.
4. Lock related stock balances.
5. For each line with non-zero variance:
   - if variance is positive, post `adjustment` ledger row with `direction = in`.
   - if variance is negative, post `adjustment` ledger row with `direction = out`.
6. Use `StockPostingService::postMovement()` for every movement.
7. Store `adjustment_ledger_id` on each posted count line.
8. Mark stock count as `posted`.
9. Save `reviewed_by` and `posted_at`.
10. Create audit log.

### 3.4 `StockAdjustmentService`

Create:

```text
App\Domain\Inventory\Services\StockAdjustmentService
```

Responsibilities:

- Provide a controlled way to post manual stock adjustments.
- Require product, unit, quantity, direction, reason, and optional batch.
- Validate product and unit relationship.
- Validate batch requirement for batch-tracked or expiry-tracked products.
- Post adjustment ledger rows through `StockPostingService`.
- Log audit event.

Use cases:

- Wastage.
- Expired stock removal.
- Damaged product.
- Correction outside formal stock count.

Important rule:

- Manual adjustment should not silently edit `stock_balances`.
- Manual adjustment must create `stock_ledgers.type = adjustment`.

### 3.5 `ExpiryAlertService`

Create:

```text
App\Domain\Inventory\Services\ExpiryAlertService
```

Responsibilities:

- Load batches with remaining stock balance.
- Detect expired batches.
- Detect near-expiry batches.
- Support configurable day windows, defaulting to 30, 60, and 90 days.
- Return rows with product, batch number, expiry date, remaining quantity, and severity.

Main method:

```php
getExpiringBatches(int $days = 90, array $filters = []): Collection
```

Severity values:

- `expired`
- `within_30_days`
- `within_60_days`
- `within_90_days`

Rules:

- Expiry reports should ignore batches with zero remaining balance.
- Expired stock is not automatically removed.
- Removing expired stock requires adjustment posting with reason.

### 3.6 `InventoryReportService`

Create:

```text
App\Domain\Inventory\Services\InventoryReportService
```

Responsibilities:

- Generate stock on hand report data.
- Generate stock movement report data.
- Generate low-stock report data.
- Generate expiry report data.
- Generate stock adjustment report data.
- Apply date, product, category, batch, unit, type, and direction filters.

Reports:

- `stockOnHand(array $filters = [])`
- `stockMovements(array $filters = [])`
- `lowStock(array $filters = [])`
- `expiry(array $filters = [])`
- `adjustments(array $filters = [])`

Rules:

- Reports should return query builders or paginated results for large data.
- Export can be deferred unless the UI requires it immediately.
- Report rows must keep links back to product, batch, unit, ledger, or stock count where applicable.

---

## 4. Stock Control UI Design

### 4.1 Stock Control Dashboard

Route:

```text
/stock-control
```

Named route:

```text
stock-control.dashboard
```

Shows:

- Low-stock alert count.
- Expired batch count.
- Near-expiry batch count.
- Pending stock counts.
- Recent stock adjustments.
- Links to stock reports.

### 4.2 Low-Stock Alerts

Route:

```text
/stock-control/low-stock
```

Shows:

- product image.
- product name.
- SKU.
- category.
- current formatted stock.
- reorder unit.
- reorder quantity.
- available quantity in reorder unit.
- shortage quantity.

Filters:

- product search.
- category.
- active status.

### 4.3 Stock Count List

Route:

```text
/stock-counts
```

Shows:

- count number.
- status.
- started date.
- counted by.
- submitted date.
- posted date.
- line count.
- actions.

Actions:

- create count.
- view count.
- continue draft.
- submit count.
- post count if authorized.
- cancel draft or submitted count if authorized.

### 4.4 Stock Count Entry Screen

Route:

```text
/stock-counts/{stockCount}
```

Shows:

- count header.
- product search/add line.
- count lines table.
- expected quantity.
- counted quantity input.
- variance.
- notes.
- submit button.
- post button for authorized reviewers.

Rules:

- Draft counts are editable.
- Submitted counts are read-only except for post/cancel actions.
- Posted counts are read-only.
- Variance should be visible before posting.

### 4.5 Expiry Alerts

Route:

```text
/stock-control/expiry
```

Shows:

- product.
- batch number.
- expiry date.
- days until expiry.
- remaining quantity.
- severity badge.
- action link to stock adjustment if stock should be removed.

Filters:

- expired only.
- near-expiry window.
- product search.
- category.

### 4.6 Stock Reports

Routes:

```text
/reports/stock-on-hand
/reports/stock-movements
/reports/low-stock
/reports/expiry
/reports/stock-adjustments
```

Stock on hand shows:

- product.
- SKU.
- category.
- batch.
- unit.
- quantity.
- formatted stock.

Stock movements shows:

- date.
- product.
- batch.
- unit.
- type.
- direction.
- quantity.
- reference.
- created by.
- reason.

Stock adjustment report shows:

- date.
- product.
- batch.
- unit.
- direction.
- quantity.
- reason.
- source stock count if applicable.
- created by.

---

## 5. Routes

Suggested route group:

```php
Route::middleware(['auth', 'role:admin,stock_manager,pharmacist'])->group(function () {
    Route::get('/stock-control', [StockControlDashboardController::class, 'index'])->name('stock-control.dashboard');
    Route::get('/stock-control/low-stock', [LowStockAlertController::class, 'index'])->name('stock-control.low-stock');
    Route::get('/stock-control/expiry', [ExpiryAlertController::class, 'index'])->name('stock-control.expiry');

    Route::resource('stock-counts', StockCountController::class)->only(['index', 'create', 'store', 'show', 'update']);
    Route::post('/stock-counts/{stockCount}/submit', [StockCountWorkflowController::class, 'submit'])->name('stock-counts.submit');
    Route::post('/stock-counts/{stockCount}/post', [StockCountWorkflowController::class, 'post'])->name('stock-counts.post');
    Route::post('/stock-counts/{stockCount}/cancel', [StockCountWorkflowController::class, 'cancel'])->name('stock-counts.cancel');

    Route::get('/reports/stock-on-hand', [StockReportController::class, 'stockOnHand'])->name('reports.stock-on-hand');
    Route::get('/reports/stock-movements', [StockReportController::class, 'stockMovements'])->name('reports.stock-movements');
    Route::get('/reports/low-stock', [StockReportController::class, 'lowStock'])->name('reports.low-stock');
    Route::get('/reports/expiry', [StockReportController::class, 'expiry'])->name('reports.expiry');
    Route::get('/reports/stock-adjustments', [StockReportController::class, 'adjustments'])->name('reports.stock-adjustments');
});
```

Authorization:

- Admin: full stock control access.
- Stock Manager: stock counts, low-stock, expiry, reports, and adjustments.
- Pharmacist: stock visibility, low-stock, expiry, reports, and stock count participation.
- Cashier: no stock control access unless explicitly approved later.

---

## 6. Validation Rules

### Low-Stock Configuration

- Product required.
- Reorder unit must belong to product.
- Reorder quantity must be greater than zero.
- Product can have no reorder setup, but then it does not appear in low-stock alerts.

### Stock Count

- Count number is generated server-side.
- Count status must be valid.
- Count line product required.
- Count line product unit required.
- Product unit must belong to product.
- Batch must belong to product when selected.
- Counted quantity must be zero or greater.
- Draft count can be updated.
- Posted count cannot be edited.

### Stock Count Posting

- Stock count must be submitted.
- User must be authorized.
- Variance must be calculated from saved expected and counted quantity.
- Adjustment reason must be generated from count number and optional line notes.
- Posting must be transactional.

### Manual Stock Adjustment

- Product required.
- Product unit required.
- Product unit must belong to product.
- Quantity must be greater than zero.
- Direction must be `in` or `out`.
- Reason required.
- Batch required when product tracks batch or expiry.
- User must be authorized.

### Expiry Report

- Day window must be positive.
- Date filters must be valid dates.
- Product and category filters must reference existing records.

---

## 7. Stock Control Examples

### Example 1: Low-Stock Alert With Same Unit

```text
Product reorder setup:
- reorder unit = Strip
- reorder quantity = 20

Stock balance:
- 12 Strips

Result:
- Low-stock alert because 12 < 20.
```

### Example 2: Low-Stock Alert With Related Units

```text
Product reorder setup:
- reorder unit = Strip
- reorder quantity = 20

Stock balances:
- 1 Box
- 5 Strips

Unit relationship:
- 1 Box = 10 Strips

Comparable stock:
- 15 Strips

Result:
- Low-stock alert because 15 < 20.
```

### Example 3: Positive Stock Count Variance

```text
Expected stock:
- 10 Strips

Counted stock:
- 12 Strips

Variance:
- +2 Strips

Ledger:
- type = adjustment
- direction = in
- product_unit_id = Strip
- quantity = 2
```

### Example 4: Negative Stock Count Variance

```text
Expected stock:
- 10 Strips

Counted stock:
- 8 Strips

Variance:
- -2 Strips

Ledger:
- type = adjustment
- direction = out
- product_unit_id = Strip
- quantity = 2
```

### Example 5: Expired Batch Removal

```text
Expired stock:
- Batch PARA-B001
- 3 Strips remaining

Manual adjustment:
- type = adjustment
- direction = out
- product_unit_id = Strip
- quantity = 3
- reason = Expired stock removal
```

---

## 8. Audit Logging

Stock control audit actions:

- `low_stock.viewed` optional
- `stock_count.created`
- `stock_count.updated`
- `stock_count.submitted`
- `stock_count.posted`
- `stock_count.cancelled`
- `stock_adjustment.posted`
- `expiry_report.viewed` optional
- `stock_report.viewed` optional

Audit should include:

- user ID.
- stock count ID or stock ledger ID.
- old values.
- new values.
- timestamp.

Critical audit rules:

- Posted stock counts are immutable.
- Stock adjustments must be traceable to stock ledger rows.
- Physical count variance must be traceable to count lines.
- Expired stock removal must be traceable to an adjustment reason.

---

## 9. Testing Strategy

Use Pest/Laravel feature tests.

### Unit Tests

- Low-stock comparison in same unit.
- Low-stock comparison across related units.
- Stock count variance calculation.
- Expiry severity calculation.
- Report filter query construction where practical.

### Feature Tests

- Authorized user can view low-stock alerts.
- Cashier cannot view stock control screens.
- Low-stock alert appears when stock is below reorder threshold.
- Low-stock alert does not appear when stock is above threshold.
- Authorized user can create stock count.
- Draft stock count can be updated.
- Submitted stock count can be posted.
- Posted stock count creates adjustment ledger rows.
- Positive variance increases stock balance.
- Negative variance decreases stock balance.
- Posted stock count cannot be edited.
- Expiry report shows expired batches with remaining stock.
- Expiry report hides batches with zero remaining stock.
- Stock movement report filters by date, product, type, and direction.
- Stock adjustment report shows variance adjustment rows.

### Browser Tests If Practical

- Create a stock count.
- Enter counted quantities.
- Submit and post count.
- Review low-stock alert list.
- Review expiry alert list.
- Filter stock movement report.

---

## 10. Key Risks And Decisions

### Risk: Low-stock comparison uses raw quantities across different units

Decision:

- Always compare using the product reorder unit.
- Use `UnitRelationshipService` to convert related unit quantities for comparison.
- Do not compare raw `stock_balances.quantity` values when units differ.

### Risk: Stock count posting corrupts stock balance

Decision:

- Stock count posting must run inside a database transaction.
- Use `StockPostingService` to post all variance adjustments.
- Store adjustment ledger references on count lines.
- Keep posted stock counts immutable.

### Risk: Expired stock is silently removed

Decision:

- Expiry tracking is informational by default.
- Removing expired stock requires explicit adjustment posting.
- Every removal must include a reason and audit log.

### Risk: Reports become slow as stock ledger grows

Decision:

- Require date and product/category filters where practical.
- Paginate report results.
- Add indexes in migrations if report queries need them.
- Defer export until filtered report pages are stable.

### Risk: Stock balance and ledger disagree

Decision:

- Stock balances are updated only through `StockPostingService`.
- Reports should use stock balances for current on-hand view.
- Reports should use stock ledger for movement history.
- QA should include reconciliation checks between balances and ledger movements.

---

## 11. Phase 3 Task Breakdown

### Epic 1: Stock Control Foundation

#### Task 1.1 - Create stock count migrations

Acceptance criteria:

- `stock_counts` table exists.
- `stock_count_lines` table exists.
- Each table has a dedicated migration file.
- Count status fields support draft, submitted, posted, and cancelled.
- Count lines store product, unit, optional batch, expected quantity, counted quantity, variance, and adjustment ledger reference.

#### Task 1.2 - Create stock count models

Acceptance criteria:

- `StockCount` model exists.
- `StockCountLine` model exists.
- Stock count has many lines.
- Stock count belongs to counted user and reviewed user.
- Stock count line belongs to product, product unit, optional batch, and optional adjustment ledger.
- Status helper methods exist.

#### Task 1.3 - Create stock count number generator

Acceptance criteria:

- Count number is generated server-side.
- Format is `SC-YYYYMMDD-0001`.
- Count number is unique.

### Epic 2: Low-Stock Alerts

#### Task 2.1 - Add reorder validation to product form

Acceptance criteria:

- Reorder unit can be selected from product units.
- Reorder quantity can be entered.
- Reorder unit must belong to product.
- Reorder quantity must be greater than zero when provided.

#### Task 2.2 - Create `LowStockAlertService`

Acceptance criteria:

- Service finds products below reorder threshold.
- Service supports same-unit stock comparison.
- Service supports related-unit stock comparison through `UnitRelationshipService`.
- Service returns formatted stock for display.

#### Task 2.3 - Build low-stock alert page

Acceptance criteria:

- Authorized users can view low-stock products.
- Cashiers cannot view low-stock page.
- Page shows product, SKU, category, current stock, reorder unit, reorder quantity, and shortage.
- Filters by product and category work.

### Epic 3: Stock Count And Variance Adjustment

#### Task 3.1 - Build stock count creation

Acceptance criteria:

- Authorized user can create a draft stock count.
- Draft count captures started time and counted user.
- User can add products from current stock balances.
- Expected quantity is copied into count lines.

#### Task 3.2 - Build stock count entry screen

Acceptance criteria:

- User can enter counted quantity per line.
- Variance is calculated and displayed.
- Counted quantity cannot be negative.
- Draft count can be saved multiple times.

#### Task 3.3 - Submit stock count

Acceptance criteria:

- Draft count can be submitted.
- Submitted count becomes read-only for entry fields.
- Submitted timestamp is saved.
- Audit log is created.

#### Task 3.4 - Post stock count adjustments

Acceptance criteria:

- Submitted count can be posted by authorized user.
- Posting creates adjustment ledger rows for non-zero variances.
- Positive variance posts `direction = in`.
- Negative variance posts `direction = out`.
- Stock balances update through `StockPostingService`.
- Count line stores adjustment ledger reference.
- Posted count cannot be edited.

#### Task 3.5 - Test stock count workflow

Acceptance criteria:

- Draft count creation is tested.
- Variance calculation is tested.
- Positive variance stock increase is tested.
- Negative variance stock decrease is tested.
- Posted count immutability is tested.
- Unauthorized access is tested.

### Epic 4: Expiry Tracking

#### Task 4.1 - Create `ExpiryAlertService`

Acceptance criteria:

- Service returns expired batches with remaining stock.
- Service returns near-expiry batches within selected day window.
- Batches with zero stock are excluded.
- Severity labels are calculated.

#### Task 4.2 - Build expiry alert page

Acceptance criteria:

- Authorized users can view expired and near-expiry batches.
- Page shows product, batch, expiry date, days remaining, quantity, and severity.
- Filters by product, category, and expiry window work.

#### Task 4.3 - Add expired stock adjustment path

Acceptance criteria:

- User can start a stock adjustment from an expired batch.
- Adjustment requires reason.
- Adjustment posts `type = adjustment` and `direction = out`.
- Original batch and product unit are preserved.

### Epic 5: Core Stock Reports

#### Task 5.1 - Build stock on hand report

Acceptance criteria:

- Report shows product, SKU, category, batch, unit, quantity, and formatted stock.
- Filters by product, category, batch, and active status work.
- Report is paginated.

#### Task 5.2 - Build stock movement report

Acceptance criteria:

- Report shows date, product, batch, unit, type, direction, quantity, reference, user, and reason.
- Filters by date range, product, type, and direction work.
- Report rows link to reference where practical.

#### Task 5.3 - Build low-stock report

Acceptance criteria:

- Report reuses `LowStockAlertService`.
- Report shows reorder threshold and shortage.
- Filters by category and product work.

#### Task 5.4 - Build expiry report

Acceptance criteria:

- Report reuses `ExpiryAlertService`.
- Report shows expired and near-expiry batches.
- Filters by day window, product, and category work.

#### Task 5.5 - Build stock adjustment report

Acceptance criteria:

- Report shows stock ledger rows with `type = adjustment`.
- Report shows reason, user, direction, quantity, product, unit, and batch.
- Report links to stock count when adjustment came from stock count posting.

### Epic 6: Audit, Security, And Documentation

#### Task 6.1 - Add stock control audit logs

Acceptance criteria:

- Stock count create/update/submit/post/cancel actions are logged.
- Manual stock adjustment posting is logged.
- Audit logs include user and auditable model.

#### Task 6.2 - Add authorization tests

Acceptance criteria:

- Guest cannot access stock control routes.
- Cashier cannot access stock control routes.
- Stock Manager can manage stock counts.
- Admin can post stock count adjustments.
- Pharmacist can view reports where allowed.

#### Task 6.3 - Add Phase 3 usage notes

Acceptance criteria:

- Notes explain low-stock alerts.
- Notes explain stock counts.
- Notes explain variance adjustments.
- Notes explain expiry reports.
- Notes explain stock reports.

#### Task 6.4 - Generate sequence and manual QA docs

Acceptance criteria:

- Flow docs are created under `docs/flows`.
- Each flow doc starts with exact Phase and Epic label.
- Manual QA steps cover database checks and service interactions.

#### Task 6.5 - Run verification

Acceptance criteria:

- Laravel Pint passes.
- Test suite passes.
- Frontend build passes.
- Critical stock count and report tests pass.

---

## 12. Phase 3 Completion Criteria

Phase 3 is complete when:

- Products can be configured with reorder unit and reorder quantity.
- Low-stock alerts compare stock correctly across related units.
- Authorized users can create and post stock counts.
- Stock count variances post immutable adjustment ledger rows.
- Stock balances update through `StockPostingService`.
- Expired and near-expiry batches are visible.
- Expired stock removal uses explicit adjustment posting.
- Stock on hand report exists.
- Stock movement report exists.
- Low-stock report exists.
- Expiry report exists.
- Stock adjustment report exists.
- Cashiers cannot access stock control workflows.
- Audit logs exist for critical stock control actions.
- Tests cover happy and unhappy paths.

---

## 13. Recommended Build Order

1. Stock count migrations and models.
2. Stock count number generator.
3. Low-stock reorder validation.
4. `LowStockAlertService`.
5. Low-stock alert page.
6. Stock count create and entry screens.
7. Stock count submit workflow.
8. Stock count posting with adjustment ledger rows.
9. `ExpiryAlertService`.
10. Expiry alert page.
11. Manual stock adjustment path for expiry/wastage.
12. `InventoryReportService`.
13. Stock on hand report.
14. Stock movement report.
15. Low-stock and expiry reports.
16. Stock adjustment report.
17. Audit logs and authorization tests.
18. Phase 3 usage notes and flow QA docs.
