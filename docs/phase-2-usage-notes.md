# Phase 2 Usage Notes - Sales

## Purpose

Phase 2 completes the pharmacy sales workflow: POS checkout, held sales, receipts, sales history, sale voids, audit logs, and role security.

## Seeded Test Users

All seeded users use password `password`.

- Admin: `admin@zarliminnew.test`
- Pharmacist: `pharmacist@zarliminnew.test`
- Cashier: `cashier@zarliminnew.test`

## POS Sale Flow

1. Log in as a cashier, pharmacist, or admin.
2. Open `Sales > POS`.
3. Search products by name, SKU, or barcode.
4. Add a sale-enabled unit to the cart.
5. Confirm quantity, unit price, discount, tax, payment method, and amount paid.
6. Complete the sale.
7. The system creates a completed sale, sale lines, stock-out ledger rows, and an audit log entry.

## Optional Patient Selection

Patient selection is optional in Phase 2.

- A pharmacy sale can be completed without a patient.
- If a patient visit is selected later, the sale can store `patient_visit_id`.
- Sales logic must stay decoupled from clinical records.

## Stock Deduction

The POS preserves the exact sold unit on `sale_lines`.

Stock deduction uses `UnitRelationshipService` and `StockPostingService`:

- Same-unit stock deducts directly.
- Smaller-unit sales can deduct fractional quantities from larger-unit stock.
- Split deductions can create more than one stock ledger row.
- Original sale stock movements use `type = sale` and `direction = out`.

## Hold And Resume

Holding a sale saves the cart without deducting stock.

1. Add items to the POS cart.
2. Click `Hold Sale`.
3. The sale is saved with `status = held`.
4. No stock ledger rows are created.
5. Resume the held sale from Sales History.
6. Stock is checked only when the resumed sale is completed.

## Sales History And Receipts

Sales History shows completed, held, and voided sales.

- Use status and date filters to find sales.
- Open Details to review lines, payment summary, and stock movements.
- Open Receipt to print an 80mm POS receipt.

## Sale Void

Only admin and pharmacist users can void completed sales.

Void rules:

- Void reason is required.
- Cashiers cannot void.
- Held sales cannot be voided.
- Already voided sales cannot be voided again.
- Original sale ledger rows are never deleted or edited.
- Reversal rows are posted with `type = sale_void` and `direction = in`.
- The sale is marked `status = voided` with `voided_by`, `voided_at`, and `void_reason`.

## Audit Logs

Critical sales actions are logged through the existing audit log system:

- `sale.completed`
- `sale.held`
- `sale.voided`

Use these logs to verify which user performed a critical sales action and which sale record was affected.

## POS Test Stock

Seeded product, unit, batch, and stock balance data include common pharmacy items such as:

- `PARA-500` Paracetamol 500mg with Box, Strip, and Pill units.
- `AMOX-500` Amoxicillin 500mg with Box, Strip, and Capsule units.
- `COUGH-100` Cough Syrup 100ml with Bottle unit.

Run the seeders after migrations to prepare a local POS testing database.
