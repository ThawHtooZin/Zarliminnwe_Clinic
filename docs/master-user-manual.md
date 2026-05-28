# Master User Manual & Help Center

This guide is organized by permission screen slug so clinic teams can train staff and test each area clearly.

---

## Dashboard (`screen.dashboard`)

**Purpose:**  
Show a quick daily overview so staff can see important activity at a glance.

**Available Actions:**
- View high-level business and clinic summary cards.
- Open allowed modules from the sidebar.

**Consequences (Behind the Scenes):**
- This is a read-only overview page.
- No stock, finance, or patient records are changed here.

**Step-by-Step Test Flow:**
1. Login with a role that has dashboard access.
2. Open Dashboard.
3. Confirm summary blocks load without errors.
4. Click sidebar links and confirm only allowed screens are visible.

---

## POS (`screen.sales.pos`)

**Purpose:**  
Process pharmacy sales for active patient visits and walk-in sales.

**Available Actions:**
- Search products by name, SKU, generic name, or barcode.
- Add a product/unit to cart.
- Add the same product/unit again to increase quantity on the same row.
- Select a patient from today’s active visit list (optional).
- Complete sale, hold sale, and resume held sale.

**Consequences (Behind the Scenes):**
- Completing a sale deducts inventory immediately.
- Stock always moves in whole numbers only.
- If smaller-unit stock is short, the system auto-breaks a whole parent unit (for example, 1 strip into 10 capsules), sells what is needed, and keeps the remainder as stock.
- No same-day appointment flow exists. Patient choice comes only from today’s active visits.
- Completed sales appear in sales history and related finance reporting views.

**Step-by-Step Test Flow:**
1. Open POS.
2. Search a product and add one unit to cart.
3. Add the same product/unit again and confirm quantity increases (no duplicate line).
4. Set quantity to a whole number only and confirm decimals are not used.
5. Select a patient from today’s active visits.
6. Complete sale with valid payment.
7. Confirm success message and verify sale appears in Sales History.

---

## Patients (`screen.patients`)

**Purpose:**  
Manage patient profiles and create visit records.

**Available Actions:**
- Create a new patient profile.
- Search and view existing patient profiles.
- Edit patient profile details.
- Create a new visit inside a patient profile.
- Edit an existing visit and diagnosis details from patient/visit pages.

**Consequences (Behind the Scenes):**
- Patients are created only here.
- Visits are created only from inside a patient profile.
- This keeps the clinic workflow linear: Patient -> Visit -> POS.

**Step-by-Step Test Flow:**
1. Open Patients and create a new patient.
2. Open that patient profile.
3. Create a visit record from the profile.
4. Add or edit diagnosis details on the visit.
5. Go to POS and confirm today’s active visit can be selected.

---

## Sales History (`screen.sales.index`)

**Purpose:**  
Review completed, held, and voided sales.

**Available Actions:**
- View sales list and details.
- Filter by status/date (if shown for your role).
- Open sale receipt.
- Void sale (only if your role is allowed).

**Consequences (Behind the Scenes):**
- Voiding a sale reverses its stock impact.
- Sales history is the source for receipt review and audit checks.

**Step-by-Step Test Flow:**
1. Complete at least one sale in POS.
2. Open Sales History.
3. Open that sale and receipt.
4. If your role can void, void one sale and confirm status changes to voided.

---

## Income (`screen.finance.income`)

**Purpose:**  
Track service/general income records and view pharmacy sales in unified income view.

**Available Actions:**
- View income list with filters.
- Record a new service/general income entry.
- Edit an income entry.

**Consequences (Behind the Scenes):**
- Pharmacy sales are shown in income reporting automatically after POS completion.
- Staff should not manually re-enter pharmacy sale amounts as income entries.

**Step-by-Step Test Flow:**
1. Open Income screen.
2. Create a service income entry with amount and category.
3. Save and confirm it appears in the list.
4. Complete a POS sale and confirm pharmacy sale also appears in unified income view.

---

## Expenses (`screen.finance.expenses`)

**Purpose:**  
Record and review business expenses.

**Available Actions:**
- View expense list.
- Create a new expense entry.
- Edit an existing expense entry.

**Consequences (Behind the Scenes):**
- Expense records affect finance reporting totals.

**Step-by-Step Test Flow:**
1. Open Expenses.
2. Create an expense with category, amount, and date.
3. Save and confirm it appears in list.
4. Edit it and confirm updates are saved.

---

## Income Categories (`screen.finance.income-categories`)

**Purpose:**  
Manage selectable category names for income entries.

**Available Actions:**
- Create category.
- Edit category.
- View category list.

**Consequences (Behind the Scenes):**
- New categories become available in the Income entry form.

**Step-by-Step Test Flow:**
1. Open Income Categories.
2. Add a new category.
3. Open Income form and confirm new category appears.
4. Edit category name and confirm list updates.

---

## Expense Categories (`screen.finance.expense-categories`)

**Purpose:**  
Manage selectable category names for expense entries.

**Available Actions:**
- Create category.
- Edit category.
- View category list.

**Consequences (Behind the Scenes):**
- New categories become available in the Expense entry form.

**Step-by-Step Test Flow:**
1. Open Expense Categories.
2. Add a new category.
3. Open Expense form and confirm new category appears.
4. Edit category name and confirm list updates.

---

## Stock Ledger (`screen.stock`)

**Purpose:**  
Review current stock and stock movement history.

**Available Actions:**
- View stock on hand.
- View stock movement/ledger records.
- Filter and review movement entries.

**Consequences (Behind the Scenes):**
- Ledger is the main audit trail for stock changes from opening stock, purchase receipts, sales, voids, and adjustments.

**Step-by-Step Test Flow:**
1. Post opening stock for a product.
2. Complete a POS sale.
3. Open Stock Ledger.
4. Confirm both in/out movements are visible in history.

---

## Opening Stock (`screen.opening-stock`)

**Purpose:**  
Enter initial stock quantities for products.

**Available Actions:**
- Select product and unit.
- Enter opening quantity.
- Save opening stock entry.

**Consequences (Behind the Scenes):**
- Product stock on hand increases immediately.
- Opening stock becomes part of the stock audit history.

**Step-by-Step Test Flow:**
1. Open Opening Stock.
2. Choose product and unit.
3. Enter whole-number quantity and save.
4. Check Stock screen to confirm on-hand quantity increased.

---

## Purchase Receipts (`screen.purchase-receipts`)

**Purpose:**  
Record supplier deliveries and post received stock.

**Available Actions:**
- Create purchase receipt with supplier and line items.
- Save receipt.
- Post receipt.
- View receipt details.

**Consequences (Behind the Scenes):**
- Posting receipt increases stock in the exact received units.
- Posted receipts affect stock reports and availability.

**Step-by-Step Test Flow:**
1. Open Purchase Receipts and create a new receipt.
2. Add product, unit, quantity, and cost.
3. Save, then post the receipt.
4. Verify stock increase in Stock Ledger / stock views.

---

## Stock Counts (`screen.stock-counts`)

**Purpose:**  
Run physical stock counting and finalize differences.

**Available Actions:**
- Start a stock count.
- Enter counted quantities.
- Submit for review/posting.
- Post or cancel based on allowed flow.

**Consequences (Behind the Scenes):**
- Posting count applies stock corrections to match physical count.

**Step-by-Step Test Flow:**
1. Create a new stock count.
2. Enter counted values for sample products.
3. Submit and post the stock count.
4. Confirm stock quantities reflect posted count result.

---

## Low-Stock Alerts (`screen.stock-control.low-stock`)

**Purpose:**  
Show products that are close to stock-out.

**Available Actions:**
- View low-stock list.
- Review urgent items for replenishment.

**Consequences (Behind the Scenes):**
- Read-only alert view to support purchasing decisions.

**Step-by-Step Test Flow:**
1. Ensure one product stock is below expected threshold.
2. Open Low-Stock Alerts.
3. Confirm low-stock item appears.

---

## Expiry Alerts (`screen.stock-control.expiry`)

**Purpose:**  
Show products nearing expiry dates.

**Available Actions:**
- View expiring items list.
- Review by date urgency.

**Consequences (Behind the Scenes):**
- Read-only alert view to reduce wastage and unsafe dispensing.

**Step-by-Step Test Flow:**
1. Create stock with near expiry date.
2. Open Expiry Alerts.
3. Confirm the item appears in alert list.

---

## Products (`screen.products`)

**Purpose:**  
Manage product catalog and unit sale setup.

**Available Actions:**
- Create product.
- Edit product details.
- Configure unit levels and pricing.
- View product list/details.

**Consequences (Behind the Scenes):**
- Product/unit setup drives POS selling, stock handling, and reports.

**Step-by-Step Test Flow:**
1. Create a new product.
2. Add unit hierarchy (example: box -> strip -> capsule) with whole-number conversion.
3. Set sale prices.
4. Confirm product appears in POS search.

---

## Product Categories (`screen.product-categories`)

**Purpose:**  
Group products under category labels.

**Available Actions:**
- Create category.
- Edit category.
- View list.

**Consequences (Behind the Scenes):**
- Categories help product organization and filtering.

**Step-by-Step Test Flow:**
1. Open Product Categories.
2. Add a category.
3. Create/edit a product and assign that category.
4. Confirm category shows correctly in product list/form.

---

## Suppliers (`screen.suppliers`)

**Purpose:**  
Manage supplier records used in purchasing.

**Available Actions:**
- Create supplier.
- Edit supplier.
- View supplier list.

**Consequences (Behind the Scenes):**
- Supplier info is used when creating purchase receipts.

**Step-by-Step Test Flow:**
1. Add a new supplier.
2. Open Purchase Receipt form.
3. Confirm the new supplier is selectable.

---

## Roles & Permissions (`screen.admin.roles`)

**Purpose:**  
Control what each role can see and do.

**Available Actions:**
- Open role permission editor.
- Toggle screen permissions.
- Toggle route permissions.
- Save role permission changes.

**Consequences (Behind the Scenes):**
- Turning off a screen also disables and clears related routes automatically.
- Access changes apply immediately to users with that role.

**Step-by-Step Test Flow:**
1. Open Roles & Permissions and choose a role.
2. Uncheck one screen permission (example: Income).
3. Confirm related route permissions auto-uncheck/disable.
4. Save and login as that role.
5. Confirm that screen and related routes are no longer accessible.

---

## Users (`screen.admin.users`)

**Purpose:**  
Manage staff accounts and role assignment.

**Available Actions:**
- Create user account.
- Edit user details and role.
- Activate/deactivate account.
- Reset password.

**Consequences (Behind the Scenes):**
- Role assignment immediately controls screen and route access.
- Deactivated users cannot sign in.

**Step-by-Step Test Flow:**
1. Create a user with cashier role.
2. Login as that user and verify allowed screens only.
3. Change role to another role and verify access changes.
4. Deactivate user and confirm login is blocked.

---

## Finance Summary (`screen.reports.finance-summary`)

**Purpose:**  
Show summary-level finance totals and trends.

**Available Actions:**
- View overall finance summary for selected period.
- Review totals for decision making.

**Consequences (Behind the Scenes):**
- Read-only report screen combining finance data points.

**Step-by-Step Test Flow:**
1. Create sample income, expense, and POS sale entries.
2. Open Finance Summary report.
3. Confirm totals reflect entered data.

---

## Income Report (`screen.reports.finance-income`)

**Purpose:**  
Show detailed income report lines.

**Available Actions:**
- View filtered income report.
- Review service income and pharmacy-sale income together.

**Consequences (Behind the Scenes):**
- Completed POS sales flow into this report automatically.

**Step-by-Step Test Flow:**
1. Record service income.
2. Complete a POS sale.
3. Open Income Report.
4. Confirm both income sources appear.

---

## Expense Report (`screen.reports.finance-expenses`)

**Purpose:**  
Show detailed expense report lines.

**Available Actions:**
- View and filter expense report.
- Review expense trends.

**Consequences (Behind the Scenes):**
- Read-only reporting based on entered expense records.

**Step-by-Step Test Flow:**
1. Record multiple expense entries.
2. Open Expense Report.
3. Apply filters and verify expected totals/list.

---

## Stock Reports (`screen.reports.stock`)

**Purpose:**  
Provide stock-focused reporting views for operations.

**Available Actions:**
- View stock on hand report.
- View stock movements report.
- View low-stock, expiry, and stock adjustment reports.

**Consequences (Behind the Scenes):**
- Read-only reporting from stock activity and current balances.

**Step-by-Step Test Flow:**
1. Perform opening stock, purchase receipt posting, POS sale, and one adjustment/count flow.
2. Open Stock Reports screens.
3. Confirm each report reflects the matching activity.

---

## Workflow Rules (Phase 6 Staff Reminder)

- No same-day appointment workflow is used.
- Patient flow is strict: **Patient -> Visit -> POS**.
- Patients are created only in **Patients**.
- Visits are created only inside a patient profile.
- POS patient dropdown shows only **today’s active visits**.
- POS stock deduction is whole-number only with automatic parent-unit breakdown when needed.
