# UI Components Design System

## ZARLI MIN NWE Clinic Management System

### Purpose

This document is the strict UI source for AI-assisted layout generation.

Mockups may be used only for visual direction, spacing, component structure, Tailwind styling, and general UX. Mockup text, dummy data, fake buttons, and fake navigation must not be copied into the application.

The final source of truth is always:

- `docs/clinic-management-system-prd.md`
- `docs/phase-1-technical-design-and-tasks.md`
- `docs/phase-2-technical-design-and-tasks.md`

---

## 1. Master Theme Rules

### 1.1 Brand Identity

- Product name: `ZARLI MIN NWE Clinic Management System`
- Brand style: clean clinic dashboard, pharmacy-first, calm and professional.
- Primary visual tone: deep teal / dark blue-green.
- Logo source: `public/images/zlmnlogo.jpg`
- Banner source if needed: `public/images/banner.jpg`

### 1.2 Layout Backgrounds

- Main page background: `bg-gray-50`
- Sidebar background: `bg-gray-50`
- Top navigation background: `bg-gray-50`
- Form background: `bg-white`
- Table background: `bg-white`
- Modal background: `bg-white`

Do not use colorful gradients or decorative backgrounds unless explicitly approved.

### 1.3 Cards And Panels

Use this style for all major containers:

```html
bg-white border border-gray-200 rounded-lg shadow-sm
```

Card spacing:

- Page wrapper: `p-6` or `p-8`
- Card padding: `p-5` or `p-6`
- Section spacing: `space-y-6`
- Form grid gap: `gap-5`

### 1.4 Primary Buttons

Primary action buttons must use deep teal / dark blue-green:

```html
bg-[#00535b] text-white hover:bg-[#003f45]
```

Use primary buttons only for main actions:

- Save
- Create
- Post
- Complete Sale
- Print Receipt

### 1.5 Secondary Buttons

Use secondary buttons for cancel, back, view, or neutral actions:

```html
border border-gray-300 text-gray-700 bg-white hover:bg-gray-50
```

### 1.6 Danger Buttons

Use danger buttons only for destructive or reversal actions:

```html
bg-red-600 text-white hover:bg-red-700
```

Approved danger action:

- Void Sale

Do not use delete actions for posted stock, posted receipts, completed sales, or stock ledger rows.

### 1.7 Text Colors

- Main text: `text-gray-900`
- Secondary text: `text-gray-600`
- Muted text: `text-gray-500`
- Primary brand text: `text-[#00535b]`
- Error text: `text-red-600`
- Success text: `text-[#00535b]`

### 1.8 Inputs

Default input style:

```html
bg-gray-50 border border-gray-300 rounded-xl px-4 py-3 text-sm focus:border-[#00535b] focus:ring-[#00535b]/10
```

Inputs must always have visible labels. Placeholder text is optional and must never replace labels.

### 1.9 Data Tables

Use data tables for index/list screens.

Required table style:

- Table wrapper: `bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm`
- Header: `bg-gray-100 text-xs uppercase tracking-wide text-gray-600`
- Rows: `divide-y divide-gray-200`
- Row text: `text-sm text-gray-700`
- Important names/IDs: `font-medium text-gray-900`

### 1.10 Navigation Rules

Navigation must come only from technical design routes.

Do not copy fake navigation links from mockups.

Approved Phase 1 sidebar links:

- Dashboard
- Products
- Categories
- Suppliers
- Opening Stock
- Purchase Receipts
- Stock Ledger

Approved Phase 2 sales links when implemented:

- POS
- Sales
- Receipts

Do not add:

- Appointments
- Clinical Patients
- Staff
- EHR
- Prescriptions as clinical records
- Queue management
- Doctor scheduling

---

## 2. Phase 1 Screens Component Breakdown

### 2.1 Product List

Source: `docs/phase-1-technical-design-and-tasks.md`, Section 1.9.

Purpose:

- Let users search, filter, view, and manage products.

Required components:

- Page Header
- Primary Action Button
- Search Bar
- Filter Controls
- Product Data Table
- Row Action Buttons
- Pagination
- Empty State
- Status Message Alert

Required fields and content:

- Product name
- SKU
- Generic name
- Category
- Active status
- Unit summary
- Stock summary in readable units

Search fields:

- Product name
- SKU
- Generic name

Filters:

- Category
- Active status

Actions:

- Create product
- View product
- Edit product

Do not include:

- Clinical patient fields
- Appointment fields
- Prescription clinical workflow

### 2.2 Product Form

Purpose:

- Create and edit product master data.

Required components:

- Page Header
- Product Details Card
- Unit Builder Card
- Batch/Expiry Toggle Group
- Reorder Level Section
- Save Button
- Cancel Button
- Validation Error Summary

Required product fields:

- Product name
- SKU
- Product category
- Generic name
- Manufacturer
- Description
- Track batch toggle
- Track expiry toggle
- Reorder quantity
- Reorder unit
- Active status

Required unit-related content:

- Unit hierarchy builder
- Sale price per unit
- Barcode per unit

Actions:

- Save product
- Cancel and return to product list

### 2.3 Unit Builder

Purpose:

- Define one item/product with many unit levels.
- Define unit-to-unit relationships without forcing one normalized backend stock unit.

Required components:

- Unit Rows Table
- Add Unit Row Control
- Parent Unit Selector
- Conversion Factor Input
- Purchase Unit Toggle
- Sale Unit Toggle
- Barcode Input
- Sale Price Input
- Validation Messages

Required fields per unit row:

- Unit name
- Unit abbreviation
- Level/order
- Parent or related unit
- Conversion factor
- Is purchase unit
- Is sale unit
- Barcode
- Sale price

Validation rules shown in UI:

- At least one unit is required.
- Conversion factor must be positive when parent unit is selected.
- Unit relationships must not be circular.
- Unit abbreviation must be unique per product.
- Barcode must be unique when present.

Example wording allowed:

- `1 Box = 10 Strips`
- `1 Strip = 10 Pills`

Do not show:

- Smallest base unit language
- Forced base unit language
- Backend-only conversion claims not present in current docs

### 2.4 Opening Stock Screen

Purpose:

- Post starting stock using the exact product unit available.

Required components:

- Page Header
- Opening Stock Form Card
- Product Selector
- Unit Selector
- Quantity Input
- Batch Fields
- Expiry Field
- Reason Input
- Submit Button
- Cancel Button
- Validation Error Summary

Required fields:

- Product
- Product unit
- Quantity
- Batch number when product tracks batch
- Expiry date when product tracks expiry
- Reason or note

Required display:

- Show how the quantity will be recorded in the selected unit.
- Show clear validation if selected unit does not belong to selected product.

Actions:

- Post opening stock
- Cancel

Do not include:

- Sale checkout
- Patient selector
- Appointment fields

### 2.5 Supplier List

Purpose:

- Manage supplier records used by purchase receiving.

Required components:

- Page Header
- Primary Action Button
- Supplier Data Table
- Supplier Form
- Status Badge
- Row Action Buttons
- Pagination
- Empty State

Required list fields:

- Supplier name
- Phone
- Email
- Address summary if space allows
- Active status

Required form fields:

- Supplier name
- Phone
- Email
- Address
- Active status

Actions:

- Create supplier
- Edit supplier
- Deactivate supplier

### 2.6 Purchase Receipt Screen

Purpose:

- Record stock received from suppliers using the exact purchased unit.

Required components:

- Page Header
- Receipt Header Card
- Receipt Lines Table
- Supplier Selector
- Received Date Input
- Notes Input
- Save Draft Button
- Post Receipt Button
- Read-only Posted State
- Validation Error Summary

Required header fields:

- Supplier
- Receipt number
- Received date
- Notes
- Status

Required line fields:

- Product
- Product unit
- Quantity
- Unit cost
- Total cost
- Batch number
- Expiry date

Required behavior:

- Receipt lines must preserve exact purchased unit and quantity.
- Posted receipt must be read-only.
- Posting creates stock ledger rows and stock balances.

Actions:

- Save draft
- Post receipt
- View receipt

Do not include:

- POS sale buttons
- Patient fields
- Appointment fields

---

## 3. Phase 2 Screens Component Breakdown

### 3.1 Receipt Screen

Source: `docs/phase-2-technical-design-and-tasks.md`, Section 4.3.

Route:

```text
/sales/{sale}/receipt
```

Required components:

- Receipt Header
- Clinic Branding Block
- Sale Metadata Block
- Optional Patient Block
- Sale Line Items Table
- Totals Summary
- Payment Summary
- Print Button
- Back To Sales Button

Required fields:

- Clinic name
- Clinic logo
- Sale number
- Date/time
- Cashier
- Optional patient name if selected
- Line items
- Product name per line
- Product unit per line
- Quantity per line
- Unit price per line
- Discount per line if present
- Line total
- Subtotal
- Discount total
- Tax total
- Grand total
- Payment method
- Amount paid
- Change

Patient rule:

- Patient name appears only if a patient was selected.
- Receipt must work without patient.
- Do not display clinical patient records.

Actions:

- Print receipt
- Back to sales list

Do not include:

- Diagnosis
- Prescription as clinical record
- Appointment details
- Doctor schedule

### 3.2 Sales List

Source: `docs/phase-2-technical-design-and-tasks.md`, Section 4.4.

Route:

```text
/sales
```

Required components:

- Page Header
- New POS Sale Button
- Sales Filter Bar
- Sales Data Table
- Status Badge
- Row Action Buttons
- Pagination
- Empty State

Required fields:

- Sale number
- Sold date/time
- Cashier
- Optional patient
- Total
- Status

Filters:

- Date range
- Status
- Cashier
- Payment method if implemented

Actions:

- View sale
- View receipt
- Void sale if user is authorized

Authorization display rules:

- Admin can void sale.
- Pharmacist can void sale.
- Cashier cannot void sale.
- Hide or disable void button when user is not authorized.

Patient rule:

- Optional patient column may show empty value.
- Never require patient for a sale row.

---

## 4. POS Screen (Phase 2)

Source: `docs/phase-2-technical-design-and-tasks.md`, Section 4.1 and 4.2.

Route:

```text
/sales/pos
```

Named route:

```text
sales.pos
```

Purpose:

- Provide a fast, pharmacy-first Point of Sale screen.
- Allow unit-based selling with fractional deduction via `UnitRelationshipService`.
- Keep patient selection optional.

Required components:

- Product Search Bar (Search by name, SKU, generic name, barcode)
- Product Result Grid (Shows available units and formatted stock)
- Cart Panel (Sale lines table)
- Checkout Summary Sidebar

Required cart fields per line:

- Product Name
- Unit Selector Dropdown
- Quantity Input
- Unit Price
- Line Total
- Remove Item Button

Required checkout summary fields:

- Subtotal
- Discount Input
- Tax (Optional/Placeholder)
- Grand Total
- Amount Paid Input
- Change Calculation
- Optional Patient Selector Dropdown
- Complete Sale Button (Primary)
- Hold Sale Button (Secondary)

Strict rules:

- Patient selector must default to empty and is completely optional.
- No clinical patient records, diagnosis, or prescriptions in the POS.

---

## 5. Global Anti-Hallucination Rules

- Do not invent screens that are not in the PRD or phase technical design documents.
- Do not copy dummy labels from mockups.
- Do not copy fake navigation from mockups.
- Do not add appointment features.
- Do not add clinical patient records.
- Do not add EHR features.
- Do not add doctor scheduling.
- Do not add queue management.
- Do not add insurance workflows.
- Do not add loyalty or CRM features.
- Always check the current phase technical design before generating UI.

---

## 6. Approved UI Source Priority

When documents conflict, use this order:

1. `docs/clinic-management-system-prd.md`
2. Current phase technical design document
3. This UI design-system document
4. Mockups for visual layout only

Mockups never override product requirements, route names, data fields, or business logic.
