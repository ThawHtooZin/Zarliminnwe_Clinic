# Phase 6, Epic 9 - Finance & POS UI Unification

## Sequence Flow

1. Cashier completes POS sale with optional `patient_visit_record_id`.
2. Sale is stored in `sales` with `status = completed` (not copied to `income_entries`).
3. Staff records service income via Finance → Record Service Income (optional visit link).
4. `UnifiedIncomeQueryService` queries `income_entries` and completed `sales` separately.
5. Results are mapped to `UnifiedIncomeLine` DTO rows and merged sorted by datetime.
6. Patient Visit detail renders unified visit income (service + pharmacy).
7. Finance Income index and Income Report render the same unified list with filters.
8. Finance Summary continues to aggregate pharmacy sales separately at summary level.

## Manual Test Steps

1. Create a patient and visit record.
2. Record service income linked to the visit.
3. Complete a POS sale linked to the same visit.
4. Open Patient Visit detail → confirm **Visit Income** shows both rows and correct total.
5. Open Finance → Income → confirm pharmacy sale appears with category **Pharmacy Sale**.
6. Filter category to **Pharmacy Sale** → only sales rows appear.
7. Void a sale → confirm it disappears from unified income lists.
8. Open Reports → Income Report → confirm pharmacy sale appears with visit link.
9. Run `php artisan test --filter=UnifiedIncomeQuery`.
