# Phase 6 Usage Notes

## Purpose

Phase 6 improves operations after Phases 1–4. Epic 1 added grouped navigation and finance seeders. Epic 2 added administration and permission-based access.

## Sidebar Groups

The left sidebar is grouped and scrolls when there are many links.

| Group | Examples |
|-------|----------|
| **Main Features** | Dashboard, POS, Patients, Sales History |
| **Management** | Opening Stock, Purchase Receipts, Stock Ledger, Low-Stock/Expiry Alerts, Stock Counts, **Backup & Restore** *(Epic 10)* |
| **Configurations** | Products, Categories, Suppliers, Users, Roles & Permissions, Income/Expense Categories |
| **Finance** | Income, Expenses |
| **Reports** | Finance Summary, Income Report, Stock Reports |

Links are filtered by **permissions** (screen access), not hard-coded role strings.

## Go to POS

The green **Go to POS** button appears when the user has `screen.sales.pos` permission.

## Fresh Database Seed

```bash
php artisan migrate:fresh --seed
```

Seed order includes roles, permissions, role-permission map, users, and finance categories.

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

## POS Stock Validation (Epic 5)

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

## POS Visit Integration (Epic 6)

- POS patient selector loads from `GET /sales/patient-visits/today-recent` (today's visits without a completed sale yet)
- Selector only shows today's recent `patient_visit_records`
- POS saves `patient_visit_record_id` on hold and checkout
- Sales detail and receipt render patient context only from linked visit records

## Backup & Restore (Epic 10)

**Management → Backup & Restore** (admin only).

### File formats

| Action | CSV | XLSX | SQL |
|--------|-----|------|-----|
| Export | Yes | No | Yes (per dataset or full database) |
| Import / restore | Yes | Yes | Yes |

### Datasets (configuration and operations)

Each dataset supports **Export CSV**, **Export SQL**, **Import** (`.csv` or `.xlsx`), and **Restore SQL**.

| Dataset | What it includes |
|---------|------------------|
| Product Catalog | Categories, products, units |
| Suppliers | Supplier master |
| Finance Categories | Income and expense categories |
| Patients & Visits | Patients, visit records, diagnoses |
| Income & Expenses | Income and expense entries |
| Pharmacy Sales (POS) | Sales, lines, stock allocations |
| Inventory & Stock | Purchase receipts, ledger, balances, batches, stock counts |
| Users & Access | Roles, permissions, users (no password import) |

### Module shortcuts (same CSV as dataset)

Use the same screen to export CSV for: **POS / Sales**, **Patients**, **Finance**, **Inventory**, **Catalog** — labels match daily modules; files use the dataset contract above.

### Full database

- **Download SQL backup** — entire database in one `.sql` file.
- **Restore from SQL** — replaces database content; requires confirmation. Use only for disaster recovery or cloned environments.

### Safe usage tips

1. Export **Product Catalog** before bulk import of products.
2. Avoid importing **Inventory** or **Pharmacy Sales** while a stock count is submitted.
3. After importing **Users & Access**, reset passwords for any new users.
4. Prefer **upsert** import for configuration; use **replace** only when you intend to wipe that dataset’s tables.
5. Test imports on a copy of the database (e.g. after a full SQL backup download).

## Configuration Delete (Epic 11)

Adds **Delete** to configuration screens: Products, Categories, Suppliers, Income Categories, Expense Categories, Users.

| Record | Usually deletes when | Blocked when |
|--------|----------------------|--------------|
| Income / Expense category | No entries use it | One or more income/expense rows exist |
| Supplier | No purchase receipts | Any purchase receipt exists |
| Product | No sales, stock, purchases, or counts reference it | Used on POS, stock, or purchases |
| Product category | All products in category are deletable | Any product still blocked |
| User | Admin removes unused staff | You, last admin, or policy blocks |

**Not automatic:** Deleting a product does **not** remove past sales or stock history (by design). Use **Deactivate** to hide items from daily lists without touching history.

**Permissions:** Admin + Pharmacist for catalog/finance categories; **Users** delete is Admin only.

## Epic 8 QA Closure

- Audit logs verified for admin, patient, diagnosis, and sale workflows
- Route-permission checks verified across all new Phase 6 endpoints
- Flow sequence documents completed for Epic 1 through Epic 8
- Full regression test suite is the release gate

## Final Verification Checklist

- Run: `php artisan migrate:fresh --seed`
- Run: `php artisan test`
- Confirm `audit_logs` entries for recent patient and sale actions
- Confirm POS selector shows today's recent visits and saves `patient_visit_record_id`
- *(After Epic 10)* Confirm Backup & Restore exports CSV, imports CSV/XLSX, and audits actions

## Seeded Test Users

All seeded users use password `password`.

- Admin: `admin@zarliminnew.test`
- Pharmacist: `pharmacist@zarliminnew.test`
- Cashier: `cashier@zarliminnew.test`
- Stock Manager: `stock_manager@zarliminnew.test`
