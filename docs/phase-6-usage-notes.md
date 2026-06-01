# Phase 6 Usage Notes

## Purpose

Phase 6 improves operations after Phases 1–4. Epic 1 added grouped navigation and finance seeders. Epic 2 added administration and permission-based access.

## Sidebar Groups

The left sidebar is grouped and scrolls when there are many links.

| Group | Examples |
|-------|----------|
| **Main Features** | Dashboard, POS, Patients, Sales History |
| **Management** | Products, Suppliers, Stock Ledger, Stock Counts |
| **Configurations** | Users, Roles & Permissions, Income/Expense Categories |
| **Finance** | Income, Expenses |
| **Reports** | Finance Summary, Income Report, Stock Reports |

Links are filtered by **permissions** (screen access), not hard-coded role strings.

## Go to POS

The green **Go to POS** button appears when the user has `screen.sales.pos` permission.

## Database seeding

See `docs/flows/database-seeding-strategy-sequence.md` for the full flow and QA steps.

**Local / dev (full sample data):**

```bash
php artisan migrate:fresh --seed
# or after migrate:
php artisan db:seed
```

Runs `DatabaseSeeder`: roles, permissions, users, finance categories, products, suppliers, and stock.

**Production (auth only, no demo catalog/stock):**

```bash
php artisan migrate
php artisan db:seed --class=DevelopmentDataSeeder
```

Do not use `migrate --seed` on production unless you intentionally want the full dev dataset.

## Administration (Epic 2)

### Users (`Configurations → Users`)

- Admin only
- Create/edit staff: name, email, role, active status
- **Reset Password** on edit screen
- Inactive users cannot log in

### Roles & Permissions (`Configurations → Roles & Permissions`)

- Admin only
- Toggle screen (sidebar) and route (HTTP) permissions per role
- Admin role always has full access
- No per-button permissions

## Patient Module (Epic 3)

Phase 4 `patient_visits` is replaced by:

| Table | Purpose |
|-------|---------|
| `patients` | Master record with auto `patient_code` (`PAT-YYYYMMDD-####`) |
| `patient_visit_records` | Visit event linked to a patient |
| `patient_diagnoses` | Stackable diagnosis rows per visit (UI in Epic 4) |

Open **Patients**, select a patient, then use **New Visit** on the patient detail page. Visit forms only ask for visit datetime; name, age, and address are edited on the patient profile.

There is no standalone **Patient Visits** sidebar screen. Visit detail opens from the patient’s visit list.

Income entries and sales link via `patient_visit_record_id`.

## Patient Management UI (Epic 4)

- New **Patients** screen with search by `patient_code` and name
- Create/edit patient profile: name, age, address
- Patient detail shows linked visit records and patient-scoped **New Visit**
- Visit detail supports stackable diagnosis add/edit
- Diagnoses are displayed in chronological order

## POS Stock Validation (Epic 6)

- POS product search now includes per-unit availability metadata
- Add-to-cart is blocked when total available stock is insufficient
- Checkout **auto-unpacks** whole parent units when the sale unit is short (e.g. 1 strip → 10 capsules; sell 5, leave 5 in stock)—no cashier toggle
- Adding the same product + unit again merges into one cart line (quantity increments)
- Cart line stores breakdown choice; checkout validates again on server
- Checkout persists `sale_line_stock_allocations` for stock audit traceability

## Finance & POS UI Unification (Epic 9)

- Patient Visit detail **Visit Income** section shows service income and completed pharmacy sales.
- Finance → Income and Reports → Income Report aggregate both sources in one table.
- Pharmacy rows use pseudo-category **Pharmacy Sale**; data stays in `sales` (not duplicated to `income_entries`).
- Voided sales are excluded from unified income lists.

## POS Visit Integration (Architecture Simplification)

- POS patient selector loads from `GET /sales/patient-visits/today-recent` (today's visits without a completed sale yet)
- Selector only shows today's recent `patient_visit_records`
- POS saves `patient_visit_record_id` on hold and checkout
- Sales detail and receipt render patient context only from linked visit records

## Epic 8 QA Closure

- Audit logs verified for admin, patient, diagnosis, and sale workflows
- Route-permission checks verified across all new Phase 6 endpoints
- Flow sequence documents completed for Epic 1 through Epic 8
- Full regression test suite is the release gate

## Coming In Later Phase 6 Epics

- Phase 6 Epics are fully implemented.

## Final Verification Checklist

- Run: `php artisan migrate:fresh --seed` (local) or `migrate` + `db:seed --class=DevelopmentDataSeeder` (production auth setup)
- Run: `php artisan test`
- Confirm `audit_logs` entries for recent patient and sale actions
- Confirm POS selector shows today's recent visits and saves `patient_visit_record_id`

## Seeded Test Users

All seeded users use password `password`.

- Admin: `admin@zarliminnew.test`
- Pharmacist: `pharmacist@zarliminnew.test`
- Cashier: `cashier@zarliminnew.test`
- Stock Manager: `stock_manager@zarliminnew.test`
