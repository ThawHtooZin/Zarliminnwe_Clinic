# Product Requirements Document (PRD)

## Clinic Management System - Pharmacy First

### 1. Project Description

#### Problems

- Pharmacy operations often use spreadsheets or generic POS tools that cannot correctly handle medicine units such as boxes, strips, and pills.
- Stock counts become inaccurate when purchases, sales, returns, and adjustments use different units without reliable conversion.
- Staff may sell partial packs, such as a few pills from a strip, without correct stock deduction or pricing.
- Patient records are often forced into sales flows, slowing checkout and creating unnecessary dependency between modules.
- Lack of audit trails makes it difficult to trace who changed stock, prices, or unit definitions.

#### Aims & Objectives

- Build a pharmacy-first clinic system focused on sales, stock, and complex unit management.
- Support multi-level unit hierarchies, such as box to strip to pill, with accurate conversion.
- Enable fast pharmacy sales workflows, including partial-unit sales.
- Maintain accurate real-time stock using a native multi-unit stock ledger.
- Provide an ultra-minimal patient module for service fee reference only.
- Track income and expenses, including service income linked to patient visit records.
- Exclude complex EHR and appointment booking to keep the product focused and maintainable.

### 2. Scope of the Project

#### Project Deliverables

##### Unit Management Engine

- Define product-specific unit hierarchies, such as box, strip, and pill.
- Enforce a strict one-to-many relationship between an item/product and its units: one item contains multiple unit levels, and each unit belongs to one item only.
- Configure conversion factors between units.
- Handle complex fractional calculations between related units natively, such as deducting part of a bottle when pills are sold.
- Allow different default units for purchasing and selling.
- Support barcode assignment per unit level.
- Validate unit definitions before saving.

##### Product & Catalog Management

- Manage medicines and products with name, SKU, category, generic name, manufacturer, and status.
- Support batch and expiry tracking where needed.
- Require every unit level inside an item to support its own distinct sale price.
- Search products by name, SKU, or barcode.

##### Stock Management

- Track all stock movements in a multi-unit ledger using the exact `product_unit_id` and quantity used by the transaction.
- Display stock in recognizable user-selected units through the unit relationship service, such as bottles, boxes, strips, or pills.
- Support opening balance, purchase receipt, sales deduction, stock adjustment, wastage, and returns.
- Track batch, lot, and expiry where enabled.
- Show current stock, available stock, and low-stock alerts.
- Support physical stock count and variance adjustment.
- Preserve audit history for stock changes.

##### Pharmacy Sales

- Provide a fast POS screen for pharmacy sales.
- Add sale items by barcode, SKU, or product search.
- Sell products in any allowed unit, such as box, strip, or pill.
- Deduct sale quantities using the selected product unit and the configured unit relationship rules.
- Support discounts, taxes if configured, and multiple payment methods.
- Print or export receipts.
- Allow sale hold and resume.
- Allow sale void or cancellation with stock reversal by authorized users only.

##### Purchasing & Receiving

- Maintain basic supplier records.
- Record purchase receipts by supplier, date, product, exact purchased unit, quantity, and unit cost.
- Support real-world supplier packaging during purchase receiving, such as buying 5 boxes or 20 strips.
- Preserve the exact purchased unit on the receipt and post it to the stock ledger using `product_unit_id` and quantity.
- Store batch and expiry details during receiving where applicable.

##### Reporting

- Stock on hand report.
- Stock movement report.
- Sales report by date, cashier, product, and payment method.
- Low-stock report.
- Expiry and near-expiry report.
- Stock adjustment report.

##### Patient Management

- Maintain ultra-minimal patient visit records with only name, age, and visit datetime.
- Do not store clinical records, history, prescriptions, vitals, notes, or appointments.
- Allow service income records to link directly to a patient visit record when a fee is collected.
- Keep patient management independent from stock, unit, pharmacy sales, and appointment workflows.

##### Income & Expense Tracking

- Record service income, such as consultation or clinic service fees.
- Link service income directly to the ultra-minimal patient record when applicable.
- Record general income categories when needed.
- Record expenses by category, amount, date, payee, and description.
- Provide income, expense, and net summary reports by date range.

##### Administration

- Manage users and roles.
- Support roles such as Admin, Pharmacist, Cashier, and Stock Manager.
- Maintain audit logs for critical actions.
- Configure pharmacy name, currency, receipt footer, and basic settings.

#### Project Exclusions

- Complex Electronic Health Records (EHR).
- Diagnosis, treatment plans, prescriptions as clinical records, lab results, and vitals.
- Appointment booking, doctor scheduling, queue management, and patient appointment records.
- Insurance claims and third-party billing.
- Patient portal or telemedicine.
- Multi-branch inventory in MVP.
- Loyalty programs and advanced CRM.
- Complex hardware integrations beyond barcode scanner and receipt printer support.

#### Project Constraints

- Pharmacy sales, stock, and unit management are the highest priority.
- Patient management must remain ultra-minimal and decoupled.
- Unit relationship calculation errors can corrupt stock, so this area requires strong validation and testing.
- Stock ledger updates must be transactional to prevent partial updates.
- MVP should avoid features that push the project into full hospital or EHR scope.
- The system should be designed for Laravel and Pest testing.

#### Assumptions

- The first version supports one clinic or pharmacy location.
- Products may have different unit structures.
- Each item/product owns its own unit levels, and units are not shared globally across unrelated items.
- Stock ledger rows store the transaction unit and quantity directly instead of forcing all quantities into one backend unit.
- Stock screens must use the unit relationship service to calculate and display user-recognizable inventory across related units.
- Barcode scanners behave like keyboard input.
- Staff understand common pharmacy units but need the system to prevent mistakes.
- Offline mode is not required in MVP.
- English UI is acceptable for the first release.

### 3. Requirements Specification

#### Functional Requirements

##### Unit Management

- The system shall allow each item/product to define a unit hierarchy.
- The system shall enforce a strict one-to-many relationship between item/product and units: one item/product can contain many unit levels, and each unit level belongs to exactly one item/product.
- The system shall support units such as box, strip, pill, bottle, vial, and tablet.
- The system shall store conversion factors between unit levels.
- The system shall not require one normalized backend storage unit for all stock.
- The system shall handle complex fractional calculations between unit relationships natively, such as deducting a fraction of a bottle when pills are sold.
- The system shall preserve the product unit used by each stock transaction.
- The system shall allow purchase and sale in different real-world units configured for the item/product.
- The system shall convert quantities between units during purchase, sale, adjustment, and reporting.
- The system shall prevent invalid unit definitions, including zero conversion, negative conversion, broken unit relationships, and duplicate unit levels.
- The system shall allow barcode mapping to specific product units.
- The system shall display stock in readable, recognizable, user-selected unit formats, such as "2 boxes, 3 strips, 5 pills".

##### Product Management

- The system shall allow admins to create, edit, deactivate, and view products.
- The system shall store product name, SKU, category, generic name, manufacturer, and description.
- The system shall support product status as active or inactive.
- The system shall support optional batch and expiry tracking.
- The system shall require every unit level inside an item/product to support its own distinct sale price.
- The system shall support cost price during stock receiving.
- The system shall allow product search by name, SKU, barcode, category, or generic name.

##### Stock Management

- The system shall record every stock movement in an immutable stock ledger.
- The system shall record `product_unit_id` and quantity on every stock ledger row.
- The system shall record `product_unit_id` and quantity on stock balances so inventory can be tracked natively in the unit received, sold, or adjusted.
- The system shall use the unit relationship service for all stock display screens so users can view stock in recognizable units across bottles, boxes, strips, pills, or other configured units.
- The system shall support fractional cross-unit deduction when stock is sold in a smaller related unit than it was received in.
- The system shall increase stock after purchase receipt or positive adjustment.
- The system shall decrease stock after sale, wastage, return, or negative adjustment.
- The system shall require a reason for manual stock adjustment.
- The system shall show stock on hand per product.
- The system shall warn users when stock is low.
- The system shall block sale when available stock is insufficient, unless an authorized override is configured.
- The system shall track batch and expiry where enabled.
- The system shall support stock count and variance posting.
- The system shall reverse stock correctly when a sale is voided.

##### Pharmacy Sales

- The system shall allow cashier users to create sales.
- The system shall allow products to be added to a sale by search or barcode.
- The system shall allow each sale line to specify product, unit, quantity, price, discount, and subtotal.
- The system shall deduct stock using the sale line unit and quantity, applying fractional unit relationship calculations when needed.
- The system shall calculate subtotal, discount, tax if enabled, total, amount paid, and change.
- The system shall support cash, card, and mixed payments.
- The system shall generate a unique sale number.
- The system shall generate a printable receipt.
- The system shall allow sale hold and resume.
- The system shall allow authorized users to void a sale.
- The system shall record user, date, payment method, and stock effect for each sale.
- The system shall allow optional patient selection, but patient is not required.

##### Purchasing

- The system shall allow supplier creation and management.
- The system shall allow users to create purchase receipts.
- The system shall allow purchase lines with product, exact purchased unit, quantity, cost, batch, and expiry.
- The system shall support purchasing in real-world supplier packaging, such as buying 5 boxes, 12 strips, or 3 bottles.
- The system shall store the exact purchased unit and quantity on each receipt line.
- The system shall post the exact purchased unit and quantity to stock ledger and stock balance records.
- The system shall update stock ledger after purchase posting.
- The system shall prevent editing posted purchase receipts without a controlled reversal or adjustment process.

##### Patient Management

- FR-PT1: The system shall allow creation of an ultra-minimal patient visit record with only name, age, and visit datetime.
- FR-PT2: The system shall allow viewing and editing of the ultra-minimal patient visit record fields only.
- FR-PT3: The system shall not store diagnosis, prescriptions, vitals, medical notes, clinical history, treatment history, or appointment data.
- FR-PT4: The system shall allow service income records to link directly to a patient visit record when a fee is collected.
- FR-PT5: The system shall ensure patient records are decoupled from pharmacy stock, product units, and pharmacy sales workflows.

##### Income & Expense Tracking

- FR-IE1: The system shall allow users to record service income with income category, amount, payment method, collected datetime, and collected user.
- FR-IE2: The system shall allow service income to link directly to one ultra-minimal patient visit record when a patient fee is collected.
- FR-IE3: The system shall allow service income to be recorded without a patient link for non-patient service income.
- FR-IE4: The system shall keep pharmacy sales income separate from service income while allowing both to appear in financial summaries.
- FR-IE5: The system shall allow users to record expenses with expense category, amount, expense date, payee, and description.
- FR-IE6: The system shall provide income, expense, and net balance summaries by date range.
- FR-IE7: The system shall allow filtering income and expense records by category, date range, payment method, and user where applicable.

##### Reporting

- The system shall provide a stock on hand report.
- The system shall provide a stock movement report.
- The system shall provide a sales report by date range.
- The system shall provide a sales report by cashier.
- The system shall provide a product sales report.
- The system shall provide a low-stock report.
- The system shall provide an expiry report for batch-enabled products.
- The system shall provide income and expense summary reports.
- The system shall allow report export where practical.

##### Administration & Security

- The system shall require authentication for all business modules.
- The system shall support role-based access control.
- The system shall restrict stock adjustment, sale void, price change, and user management to authorized roles.
- The system shall keep audit logs for critical actions.
- The system shall validate all user input.
- The system shall protect forms with CSRF tokens.
- The system shall escape Blade output to prevent XSS.
- The system shall protect models from mass assignment vulnerabilities.

#### Non-Functional Requirements

##### Performance

- Product search should return results in under 300 ms for up to 10,000 products.
- Sale posting should complete in under 2 seconds under normal load.
- Reports should support date filtering to avoid expensive full-table scans.

##### Accuracy

- Unit relationship calculations must be deterministic and test-covered.
- Stock ledger and stock balance records must preserve `product_unit_id` and quantity instead of forcing all stock into one backend unit.
- Unit relationship calculations must support fractional deductions across related units without losing precision.
- Purchase receipts must preserve the exact real-world unit bought from the supplier.
- Sale, purchase, void, and adjustment calculations must produce traceable stock movements.

##### Reliability

- Stock-affecting operations must run inside database transactions.
- Failed sale or purchase posting must not leave partial stock updates.
- Stock corrections must use adjustment or reversal records instead of silent edits.

##### Security

- Passwords must be securely hashed.
- User permissions must be checked on every protected action.
- Sensitive actions must be audited.
- Inputs must be validated on server side.
- Blade output must be escaped by default.

##### Usability

- POS flow should be fast and simple for pharmacy staff.
- Unit labels must be clear on all sale, purchase, and stock screens.
- Stock screens must show user-recognizable units through the unit relationship service.
- Users should see available stock before completing a sale.
- Error messages should be clear and actionable.

##### Maintainability

- Unit, stock, sales, product, and patient domains should be separated.
- Patient module must not contain stock or sales business logic.
- Shared logic such as unit relationship calculation should live in a dedicated service.
- Code should follow SOLID principles and be covered by Pest tests.

##### Testability

- Unit relationship calculations must have focused unit tests.
- Sales and stock posting must have feature tests for happy and unhappy paths.
- Purchase receiving must have tests for unit relationship calculations and stock increase.
- Patient management must have tests proving only name, age, and visit datetime are stored.
- Service income tests must prove patient linkage is optional but directly supported.
- Critical POS flows should be covered by Pest browser tests where practical.

##### Auditability

- Stock ledger entries should be immutable after posting.
- Price changes, stock adjustments, sale voids, and permission changes should be logged.
- Reports should be traceable back to source transactions.

### Recommended MVP Phases

#### Phase 1: Foundation

- Product catalog.
- Unit management engine.
- Stock ledger.
- Opening stock and purchase receiving.

#### Phase 2: Sales

- Pharmacy POS.
- Unit-based selling.
- Payments and receipts.
- Sale void and stock reversal.

#### Phase 3: Stock Control

- Low-stock alerts.
- Stock count.
- Expiry tracking.
- Core reports.

#### Phase 4: Ultra-Minimal Patient And Finance Module

- Ultra-minimal patient visit records.
- Service income linked to patient visit records.
- Income and expense tracking.
- No EHR or appointment features.

#### Phase 5: Hardening

- Pest tests.
- Security review.
- Audit log review.
- Performance checks for POS and reports.

### Success Metrics

- Stock variance after monthly stock count is below 2 percent by value.
- 95 percent of sales are completed without unit-related manual correction.
- Average checkout time is below 60 seconds for baskets with 5 or fewer items.
- No critical production bugs in unit relationship calculations during the first 30 days after launch.
- Patient module remains ultra-minimal and does not block pharmacy sales.
- Service income can be traced to a patient visit when a patient fee is collected.

