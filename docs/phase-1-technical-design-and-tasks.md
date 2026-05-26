# Phase 1 Technical Design and Task Breakdown

## Clinic Management System - Phase 1 Foundation

### Purpose

Phase 1 builds the pharmacy foundation for the Clinic Management System. The focus is product catalog, complex unit management, stock ledger, opening stock, and purchase receiving.

Sales, patient management, EHR, appointment booking, and full reporting are not part of Phase 1.

### Source Inputs

- PRD: `docs/clinic-management-system-prd.md`
- Mockups: no mockup files were found in the repository. This design assumes standard admin screens for product management, unit setup, opening stock, and purchase receiving.
- Current app: fresh Laravel 13 project with Blade, Tailwind, Vite, default `User` model, and default routes.

---

## 1. Technical Design

### 1.1 Architecture Goals

- Keep pharmacy inventory logic independent from future patient and sales modules.
- Store stock ledger and balance quantities with the exact product unit used by the transaction.
- Use a multi-unit stock ledger as the source of truth for inventory changes.
- Make unit relationship calculations deterministic, validated, and test-covered.
- Avoid editing posted stock transactions directly. Use reversal or adjustment records later.
- Keep Phase 1 simple enough to ship, but structured for Phase 2 POS sales.

### 1.2 Proposed Domain Modules

#### Product Catalog

Owns medicine and product master data.

Responsibilities:

- Product CRUD.
- Product category and manufacturer references.
- SKU and barcode lookup.
- Product status.
- Batch and expiry tracking flags.

Suggested namespace:

```text
App\Domain\Catalog
```

#### Unit Management

Owns product-specific unit hierarchy and conversion.

Responsibilities:

- Define unit levels per product.
- Define relationships between units, such as box to strip and strip to pill.
- Support native fractional calculations across related units.
- Format stock into readable unit displays without forcing a single backend storage unit.
- Validate unit hierarchy.

Suggested namespace:

```text
App\Domain\Units
```

#### Inventory

Owns stock balances, ledger, opening stock, and purchase receiving.

Responsibilities:

- Post stock movements.
- Maintain current stock balance.
- Receive stock from purchase receipts.
- Record opening stock.
- Track batch and expiry when enabled.
- Prevent direct mutation of posted ledger rows.

Suggested namespace:

```text
App\Domain\Inventory
```

### 1.3 Recommended Laravel Structure

```text
app/
  Domain/
    Catalog/
      Actions/
      Data/
      Services/
    Units/
      Services/
      ValueObjects/
    Inventory/
      Actions/
      Data/
      Services/
  Http/
    Controllers/
      Catalog/
      Inventory/
    Requests/
      Catalog/
      Inventory/
  Models/
    Product.php
    ProductCategory.php
    ProductUnit.php
    Supplier.php
    PurchaseReceipt.php
    PurchaseReceiptLine.php
    StockLedger.php
    StockBalance.php
    StockBatch.php
database/
  migrations/
  factories/
  seeders/
resources/
  views/
    layouts/
    catalog/
    inventory/
tests/
  Feature/
  Unit/
```

### 1.4 Core Data Model

#### `product_categories`

Stores product grouping.

Key fields:

- `id`
- `name`
- `description`
- `is_active`
- timestamps

#### `products`

Stores medicine and product master data.

Key fields:

- `id`
- `product_category_id`
- `name`
- `sku`
- `generic_name`
- `manufacturer`
- `description`
- `image_path`
- `track_batch`
- `track_expiry`
- `reorder_product_unit_id`
- `reorder_quantity`
- `is_active`
- timestamps

Rules:

- `sku` must be unique.
- Product can be inactive but should not be deleted when used in stock records.
- Reorder level stores both unit and quantity.

#### `product_units`

Stores product-specific units.

Example:

- Box
- Strip
- Pill

Key fields:

- `id`
- `product_id`
- `name`
- `abbreviation`
- `level`
- `parent_product_unit_id`
- `conversion_factor`
- `is_purchase_unit`
- `is_sale_unit`
- `barcode`
- `sale_price`
- timestamps

Rules:

- Each unit belongs to exactly one product.
- A product can have many unit levels.
- Unit relationships store how one unit relates to another unit.
- Example: 1 box = 10 strips.
- Example: 1 strip = 10 pills.
- `barcode` should be unique when present.

Design decision:

- Store unit-to-unit relationships instead of forcing all quantities into one backend unit.
- Stock posting stores the transaction unit and quantity directly.
- Fractional calculations are handled by the unit relationship service when stock must move between related units.

#### `suppliers`

Stores supplier master data.

Key fields:

- `id`
- `name`
- `phone`
- `email`
- `address`
- `is_active`
- timestamps

#### `purchase_receipts`

Stores posted purchase headers.

Key fields:

- `id`
- `supplier_id`
- `receipt_number`
- `received_at`
- `status`
- `notes`
- `created_by`
- `posted_at`
- timestamps

Status values:

- `draft`
- `posted`
- `cancelled`

Phase 1 should support draft and posted. Posted receipts should not be edited directly.

#### `purchase_receipt_lines`

Stores purchase receipt details.

Key fields:

- `id`
- `purchase_receipt_id`
- `product_id`
- `product_unit_id`
- `quantity`
- `unit_cost`
- `total_cost`
- `batch_number`
- `expires_at`
- timestamps

Rules:

- Receipt lines preserve the exact product unit and quantity bought from the supplier.
- Batch and expiry are required only when product settings require them.

#### `stock_ledgers`

Immutable stock movement history.

Key fields:

- `id`
- `product_id`
- `stock_batch_id`
- `product_unit_id`
- `type`
- `direction`
- `quantity`
- `unit_cost`
- `reference_type`
- `reference_id`
- `reason`
- `created_by`
- timestamps

Type examples:

- `opening_stock`
- `purchase_receipt`
- `adjustment`

Direction values:

- `in`
- `out`

Rules:

- Phase 1 only posts stock-in for opening stock and purchase receipt.
- Ledger rows store the exact product unit and quantity used in the transaction.
- Ledger rows should not be updated after posting.
- Future sales will post `out` rows in Phase 2.

#### `stock_balances`

Fast current stock lookup.

Key fields:

- `id`
- `product_id`
- `stock_batch_id`
- `product_unit_id`
- `quantity`
- timestamps

Rules:

- Updated inside the same database transaction as ledger posting.
- Ledger remains the audit source.
- Balance is for fast UI and reports.
- Balance can contain multiple rows for the same product when stock exists in different units or batches.

#### `stock_batches`

Tracks batch and expiry when needed.

Key fields:

- `id`
- `product_id`
- `batch_number`
- `expires_at`
- `received_at`
- timestamps

Rules:

- Required only for products where `track_batch` or `track_expiry` is true.
- Same product, same batch number, and same expiry can reuse an existing batch.

### 1.5 Unit Relationship Calculation Design

Create a dedicated service:

```text
App\Domain\Units\Services\UnitRelationshipService
```

Main methods:

```php
convert(ProductUnit $fromUnit, ProductUnit $toUnit, int|float|string $quantity): string
canConvert(ProductUnit $fromUnit, ProductUnit $toUnit): bool
calculateDeduction(Collection $availableBalances, ProductUnit $saleUnit, int|float|string $quantity): Collection
formatStock(Product $product, Collection $balances, ?ProductUnit $preferredUnit = null): string
validateProductUnits(Collection $units): void
```

Important rule:

- The backend does not force all stock into one normalized storage unit.
- Ledger and balance records store the unit and quantity used by the transaction.
- Fractional calculations between related units are handled natively by the service.
- Use decimal columns with fixed scale for quantities to avoid floating point errors.

Example:

```text
1 box = 10 strips
1 strip = 10 pills

box -> strip conversion_factor = 10
strip -> pill conversion_factor = 10
```

Conversion:

```text
2 boxes can be represented as 20 strips or 200 pills when needed.
3 strips can be represented as 30 pills when needed.
Selling 5 pills from available bottle or strip stock can deduct the correct fractional amount from that stored unit.
```

Display:

```text
Stock can be shown as stored units, preferred unit, or readable breakdown depending on the screen.
```

### 1.6 Stock Posting Design

Create a dedicated service:

```text
App\Domain\Inventory\Services\StockPostingService
```

Responsibilities:

- Start a database transaction.
- Create stock ledger row.
- Update or create stock balance.
- Link reference model, such as purchase receipt.
- Preserve `product_unit_id` and quantity on ledger and balance records.
- Reject invalid quantity.
- Reject inactive products.

Main methods:

```php
postOpeningStock(Product $product, ProductUnit $unit, int|float|string $quantity, array $batchData): StockLedger
postPurchaseReceipt(PurchaseReceipt $receipt): void
```

### 1.7 Purchase Receiving Flow

1. User creates a purchase receipt as draft.
2. User adds receipt lines.
3. System validates product, unit, quantity, cost, batch, and expiry.
4. User posts the receipt.
5. System starts a transaction.
6. System preserves each line's product unit and quantity.
7. System creates or finds stock batch.
8. System creates stock ledger rows.
9. System updates stock balances.
10. System marks receipt as posted.

### 1.8 Opening Stock Flow

1. User selects product and unit.
2. User enters quantity.
3. System validates the selected unit belongs to the product.
4. System creates optional batch record.
5. System creates `opening_stock` ledger entry.
6. System updates stock balance.

### 1.9 UI Screens For Phase 1

Because no mockup files are present, the following screens are assumed.

#### Product List

- Search by name, SKU, generic name.
- Filter by category and active status.
- Show stock summary in readable units.
- Actions: create, edit, view.

#### Product Form

- Basic product fields.
- Optional product image upload.
- Batch and expiry toggles.
- Reorder level.
- Unit hierarchy builder.
- Sale price per unit.
- Barcode per unit.

#### Unit Builder

- Add unit rows.
- Set level order.
- Define parent or related unit.
- Enter conversion factor between related units.
- Mark purchase/sale units.
- Validate before save.

#### Opening Stock Screen

- Select product.
- Select unit.
- Enter quantity.
- Enter batch and expiry if required.
- Show how the quantity will be recorded in the selected unit.

#### Supplier List/Form

- Basic supplier CRUD.

#### Purchase Receipt Screen

- Header: supplier, received date, notes.
- Lines: product, unit, quantity, cost, batch, expiry.
- Save draft.
- Post receipt.
- Posted receipt is read-only.

### 1.10 Routes

Use authenticated web routes.

Suggested route groups:

```text
/products
/product-categories
/suppliers
/opening-stock
/purchase-receipts
/stock
```

Suggested named routes:

```text
products.index
products.create
products.store
products.edit
products.update
suppliers.index
suppliers.store
opening-stock.create
opening-stock.store
purchase-receipts.index
purchase-receipts.create
purchase-receipts.store
purchase-receipts.show
purchase-receipts.post
stock.index
stock.ledger
```

### 1.11 Authorization

Phase 1 roles can be simple enum-style values on users or policy-based gates.

Recommended roles:

- Admin
- Pharmacist
- Stock Manager
- Cashier

Phase 1 permissions:

- Admin: full access.
- Pharmacist: view products and stock, create purchase receipts.
- Stock Manager: product setup, supplier setup, opening stock, purchase receiving.
- Cashier: no Phase 1 write access, reserved for Phase 2 POS.

If role management is too large for Phase 1, add minimal authorization now and keep advanced role UI for later.

### 1.12 Validation Rules

Product:

- `name` required.
- `sku` required and unique.
- Category required.
- Reorder quantity must be non-negative.
- Reorder unit must belong to the selected product when provided.

Product units:

- At least one unit.
- Conversion must be positive.
- Unit relationships must not be circular or broken.
- Barcode must be unique when present.
- Unit abbreviation should be unique per product.

Purchase receipt:

- Supplier required.
- At least one line.
- Product required.
- Product unit must belong to selected product.
- Quantity must be positive.
- Unit cost must be non-negative.
- Batch required if product tracks batch.
- Expiry required if product tracks expiry.

Opening stock:

- Product required.
- Product unit required.
- Quantity must be positive.
- Reason or note recommended.

### 1.13 Testing Strategy

Use Pest for tests.

Unit tests:

- Convert between related units, such as box to strip and strip to pill.
- Calculate fractional deduction across units, such as pills sold from bottle stock.
- Format multi-unit balances into readable display.
- Reject invalid conversion factors.
- Reject broken or circular unit relationships.

Feature tests:

- Create product with valid units.
- Reject product with invalid unit relationships.
- Reject duplicate unit barcode.
- Create supplier.
- Create opening stock and verify ledger plus balance.
- Create purchase receipt draft.
- Post purchase receipt and verify ledger plus balance.
- Reject posting receipt with invalid batch or expiry.
- Ensure posted purchase receipt cannot be edited directly.

Security tests:

- Guest cannot access Phase 1 screens.
- Unauthorized user cannot post opening stock.
- Inputs are validated server-side.

### 1.14 Key Risks And Decisions

#### Risk: Incorrect unit design corrupts stock

Decision:

- Store unit-to-unit relationships per product.
- Keep ledger and balance records in the actual transaction unit and quantity.
- Test fractional unit relationship calculations heavily.

#### Risk: Ledger and balance drift

Decision:

- Update ledger and balance in the same database transaction.
- Use ledger as audit source.
- Use balance as performance cache.

#### Risk: Product setup UI becomes too complex

Decision:

- Start with a simple unit table in the product form.
- Add dynamic UI improvements later.

#### Risk: Phase 1 grows into POS or patient scope

Decision:

- Do not build sales or patient features in Phase 1.
- Only prepare the data model for future stock-out transactions.

---

## 2. Phase 1 Task Breakdown

### Epic 1: Project Foundation

#### Task 1.1 - Confirm Laravel baseline

Acceptance criteria:

- App boots locally.
- Database connection works.
- Existing migrations run cleanly.
- `.env.example` includes required database settings.

#### Task 1.2 - Add authentication foundation

Acceptance criteria:

- Users can log in and log out.
- Business routes require authentication.
- Default admin user can be seeded.

#### Task 1.3 - Add minimal role support

Acceptance criteria:

- User has a role value.
- Admin role can access all Phase 1 screens.
- Unauthorized users are blocked from stock posting.

### Epic 2: Product Catalog

#### Task 2.1 - Create product category model and migration

Acceptance criteria:

- Categories can be stored with name, description, and active status.
- Category name is required.

#### Task 2.2 - Create product model and migration

Acceptance criteria:

- Products store name, SKU, category, generic name, manufacturer, optional image path, batch flag, expiry flag, reorder level, and active status.
- SKU is unique.
- Product belongs to category.

#### Task 2.3 - Build product category CRUD

Acceptance criteria:

- Admin or Stock Manager can list, create, edit, and deactivate categories.
- Validation errors are shown clearly.

#### Task 2.4 - Build product CRUD basic fields

Acceptance criteria:

- Admin or Stock Manager can list, search, create, edit, view, and deactivate products.
- Product list supports search by name, SKU, and generic name.
- Product form supports optional product image upload.

### Epic 3: Unit Management Engine

#### Task 3.1 - Create product unit model and migration

Acceptance criteria:

- Units belong to products.
- Unit stores name, abbreviation, level, related parent unit, conversion factor, purchase flag, sale flag, barcode, and sale price.
- Barcode is unique when present.

#### Task 3.2 - Implement unit validation rules

Acceptance criteria:

- Conversion values must be positive.
- Unit relationships cannot be circular or broken.
- Duplicate unit abbreviations are rejected per product.

#### Task 3.3 - Implement `UnitRelationshipService`

Acceptance criteria:

- Service converts between related product units when needed.
- Service calculates fractional stock deduction across related units.
- Service formats multi-unit balances into readable unit breakdown.
- Service rejects invalid or mismatched product units.

#### Task 3.4 - Add units to product create/edit UI

Acceptance criteria:

- User can define multiple units while creating or editing a product.
- User can mark purchase and sale units.
- User can define parent or related unit conversions.
- User can enter barcode and sale price per unit.
- Form displays validation errors for invalid hierarchy.

#### Task 3.5 - Add unit relationship calculation tests

Acceptance criteria:

- Tests cover box, strip, and pill conversions.
- Tests cover fractional deductions, such as selling pills from bottle stock.
- Tests cover invalid unit relationship setup.
- Tests cover readable stock display.

### Epic 4: Supplier Management

#### Task 4.1 - Create supplier model and migration

Acceptance criteria:

- Supplier stores name, phone, email, address, and active status.
- Supplier name is required.

#### Task 4.2 - Build supplier CRUD

Acceptance criteria:

- Admin or Stock Manager can list, create, edit, view, and deactivate suppliers.
- Purchase receipt form can select active suppliers.

### Epic 5: Inventory Ledger

#### Task 5.1 - Create stock batch model and migration

Acceptance criteria:

- Batch belongs to product.
- Batch stores batch number, expiry date, and received date.
- Batch is required only when product configuration needs it.

#### Task 5.2 - Create stock ledger model and migration

Acceptance criteria:

- Ledger records product, batch, product unit, quantity, type, direction, unit cost, reference, reason, and user.
- Ledger rows are append-only from the application flow.

#### Task 5.3 - Create stock balance model and migration

Acceptance criteria:

- Balance stores product, optional batch, product unit, and quantity.
- Balance can be updated atomically during stock posting.

#### Task 5.4 - Implement `StockPostingService`

Acceptance criteria:

- Service posts stock ledger rows.
- Service updates stock balance in the same transaction.
- Service rejects invalid quantity and inactive products.

#### Task 5.5 - Build stock on hand view

Acceptance criteria:

- User can view product stock in readable units.
- User can search stock by product name or SKU.
- System shows stored unit quantities and readable converted summaries.

### Epic 6: Opening Stock

#### Task 6.1 - Build opening stock form

Acceptance criteria:

- User can select product and unit.
- User can enter quantity.
- User can enter batch and expiry when required.
- UI shows the selected unit and quantity that will be posted.

#### Task 6.2 - Post opening stock

Acceptance criteria:

- Opening stock creates stock batch when needed.
- Opening stock creates stock ledger entry.
- Opening stock updates stock balance.
- Operation runs inside database transaction.

#### Task 6.3 - Test opening stock

Acceptance criteria:

- Test confirms ledger row is created.
- Test confirms balance is increased.
- Test confirms batch and expiry validation.

### Epic 7: Purchase Receiving

#### Task 7.1 - Create purchase receipt migrations and models

Acceptance criteria:

- Purchase receipt has supplier, receipt number, received date, status, notes, created user, and posted date.
- Purchase receipt line has product, unit, quantity, unit cost, total cost, batch, and expiry.

#### Task 7.2 - Build purchase receipt draft UI

Acceptance criteria:

- User can create purchase receipt header.
- User can add multiple product lines.
- User can save as draft.

#### Task 7.3 - Implement purchase receipt posting

Acceptance criteria:

- Posting validates all lines.
- Posting preserves each line's product unit and quantity.
- Posting creates stock ledger rows.
- Posting updates stock balances.
- Posted receipt becomes read-only.

#### Task 7.4 - Test purchase receiving

Acceptance criteria:

- Test confirms draft creation.
- Test confirms posting updates ledger and stock balance.
- Test confirms invalid product unit is rejected.
- Test confirms posted receipt cannot be edited directly.

### Epic 8: Audit And Security

#### Task 8.1 - Add audit log model and migration

Acceptance criteria:

- Critical Phase 1 actions are logged.
- Log includes user, action, auditable type, auditable ID, old values, new values, and timestamp.

#### Task 8.2 - Log product, unit, opening stock, and purchase posting actions

Acceptance criteria:

- Product changes are logged.
- Unit changes are logged.
- Opening stock posting is logged.
- Purchase receipt posting is logged.

#### Task 8.3 - Add authorization tests

Acceptance criteria:

- Guest users are redirected from protected screens.
- Unauthorized roles cannot post stock.
- Admin can perform all Phase 1 actions.

### Epic 9: QA And Documentation

#### Task 9.1 - Add seed data for local testing

Acceptance criteria:

- Admin user is seeded.
- Sample categories are seeded.
- Sample products include box, strip, pill units.
- Sample supplier is seeded.

#### Task 9.2 - Write Phase 1 usage notes

Acceptance criteria:

- Notes explain how to create products with units.
- Notes explain how to post opening stock.
- Notes explain how to post purchase receipt.

#### Task 9.3 - Run code quality and tests

Acceptance criteria:

- Laravel Pint passes.
- Pest or Laravel test suite passes.
- Critical unit relationship calculation and stock posting tests pass.

---

## 3. Phase 1 Completion Criteria

Phase 1 is complete when:

- Admin can create product categories.
- Admin can create products with box, strip, and pill units.
- Unit conversion is tested and reliable.
- Admin can create suppliers.
- Admin can post opening stock.
- Admin can create and post purchase receipts.
- Stock ledger records every stock-in movement.
- Stock balance shows current quantity by stored unit and readable converted summaries.
- Posted stock records are traceable and not silently editable.
- Patient, sales, EHR, and appointment features are not included.

---

## 4. Recommended Build Order

1. Authentication and role foundation.
2. Product categories.
3. Products.
4. Product units and unit relationship service.
5. Suppliers.
6. Stock batches, ledger, and balances.
7. Opening stock.
8. Purchase receipts.
9. Audit logs.
10. Tests and documentation.
