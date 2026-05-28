# Phase 6, Epic Dashboard Analytics UI - Daily Operations Sequence

## Sequence Flow

1. User opens `Dashboard`.
2. `DashboardController` queries actionable stock alerts:
   - Low stock products below reorder threshold.
   - Expiring batches within 30 days or already expired with remaining stock.
   - Pending stock count documents in `draft` or `submitted`.
3. `DashboardController` queries today's finance:
   - Service income total from `income_entries`.
   - Completed pharmacy sales total from `sales`.
   - Expenses total from `expense_entries`.
4. `DashboardController` queries today's clinic activity:
   - Patient visit records created today.
5. `DashboardController` builds a 7-day revenue trend:
   - Aggregates daily service income.
   - Aggregates daily completed pharmacy sales.
   - Merges both into one daily revenue array.
6. View renders three sections:
   - Actionable Alerts.
   - Today's Overview.
   - 7-Day Revenue Trend bar chart.
7. Dashboard intentionally excludes:
   - Complex EHR graphs.
   - Patient demographic charts.
   - Deep accounting charts.

## Manual Testing Checklist

- [ ] Open `Dashboard` and confirm **Actionable Alerts** shows:
  - [ ] Low Stock count.
  - [ ] Expiring Batches count.
  - [ ] Pending Counts count.
- [ ] Confirm **Today's Overview** shows:
  - [ ] Service Income total for today.
  - [ ] Pharmacy Sales total for completed sales today.
  - [ ] Expenses total for today.
  - [ ] Patient Visits count created today.
- [ ] Confirm **7-Day Revenue Trend** chart renders seven bars.
- [ ] Create one new `income_entry` today and verify:
  - [ ] Service Income increases.
  - [ ] Revenue trend updates current day.
- [ ] Complete one POS sale today and verify:
  - [ ] Pharmacy Sales increases.
  - [ ] Revenue trend updates current day.
- [ ] Create one `expense_entry` today and verify:
  - [ ] Expenses increases.
- [ ] Create one `patient_visit_record` today and verify:
  - [ ] Today's Patient Visits increases.
- [ ] Create/update stock to trigger low stock and expiring batch cases, then verify counts update.
- [ ] Confirm dashboard does **not** show EHR charts, demographic charts, or advanced accounting analytics.
