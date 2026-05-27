# Phase 4 Usage Notes - Ultra-Minimal Patient And Finance

## Purpose

Phase 4 adds ultra-minimal patient visits and clinic finance tracking. It does **not** add EHR, clinical records, prescriptions, vitals, diagnosis, medical notes, appointment booking, doctor scheduling, or queue management.

## Seeded Test Users

All seeded users use password `password`.

- Admin: `admin@zarliminnew.test`
- Pharmacist: `pharmacist@zarliminnew.test`
- Cashier: `cashier@zarliminnew.test`
- Stock Manager: `stock_manager@zarliminnew.test`

## Patient Visits (Ultra-Minimal)

A patient visit stores only:

- Patient name
- Age
- Visit datetime

There are no diagnosis, vitals, prescription, clinical note, doctor, appointment, or queue fields. The system rejects those fields if submitted.

### Who Can Use Patient Visits

- Admin, Pharmacist, and Cashier can create and edit patient visits.
- Stock Manager cannot access patient visit screens.

### Typical Flow

1. Log in as Admin, Pharmacist, or Cashier.
2. Open **Patient Visits** and create a visit with name, age, and visit time.
3. Open the visit detail page to review linked service income (if any).
4. Use **Record Service Income** to open the income form with the visit pre-selected.

Create and update actions are written to `audit_logs` as `patient_visit.created` and `patient_visit.updated`.

## Service Income Linked To A Visit

Service income uses income categories with type `service` (for example Consultation Fee).

- Optional `patient_visit_id` links one income entry to one visit.
- The income report shows only patient name, age, and visit datetime when a visit is linked.
- No clinical data is stored or displayed.

## Income Without A Patient Visit

General income uses categories with type `general` (for example Other Income).

- Leave patient visit empty when recording non-patient income.
- Income entries are stored in `income_entries` only.

Income entry create and update actions are audited as `income_entry.created` and `income_entry.updated`. Amount changes appear in `old_values` and `new_values` on update.

## Pharmacy Sales Stay Separate

Pharmacy POS sales remain in the `sales` table.

- Completing a POS sale does **not** create an `income_entries` row.
- The finance summary reads completed (non-voided) sales as **Pharmacy Sales (POS)**.
- Voided sales are excluded from the pharmacy sales total.

Do not copy pharmacy sales into income entries.

## Expenses

Expenses record operating costs by category (rent, salary, utilities, and so on).

- Expenses use `expense_entries` and do not affect stock ledger or stock balances.
- Product, batch, and stock fields are rejected on expense forms.

Expense entry create and update actions are audited as `expense_entry.created` and `expense_entry.updated`.

## Finance Categories

Income and expense categories are managed by Admin and Pharmacist only.

- Income category types are `service` or `general`.
- Inactive categories cannot be used for new entries.
- Category changes are audited as `income_category.created`, `income_category.updated`, `expense_category.created`, and `expense_category.updated`.

## Finance Reports

### Finance Summary (Admin and Pharmacist)

Shows for the selected date range:

- Service income (from `income_entries` + service categories)
- General income (from `income_entries` + general categories)
- Pharmacy sales income (from completed `sales` only)
- Total income and total expenses
- Net balance = total income − expenses
- Breakdowns by category

### Income Report (Admin, Pharmacist, Cashier)

Lists manual income entries with filters. Does not list POS sales.

### Expense Report (Admin, Pharmacist, Cashier)

Lists expense entries with filters.

Cashier cannot open the finance summary report.

## Role Summary

| Feature | Admin | Pharmacist | Cashier | Stock Manager |
| --- | --- | --- | --- | --- |
| Patient visits | Yes | Yes | Yes | No |
| Income / expense entries | Yes | Yes | Yes | No |
| Income / expense categories | Yes | Yes | No | No |
| Finance summary | Yes | Yes | No | No |
| Income / expense reports | Yes | Yes | Yes | No |

## What Phase 4 Does Not Include

- EHR or electronic health records
- Diagnosis, symptoms, vitals, prescriptions, or clinical notes
- Appointment booking, doctor scheduling, or queue management
- Automatic income entries from pharmacy sales
- Inventory changes from income or expense records

For manual QA steps per feature, see the flow documents under `docs/flows/phase-4-epic-*.md`.
