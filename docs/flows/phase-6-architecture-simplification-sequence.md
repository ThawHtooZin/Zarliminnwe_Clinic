# Phase 6, Epic Architecture Simplification - Patient -> Visit -> POS

## Sequence Flow

1. User creates patient only from `Patients` module.
2. User creates a visit from `Patient Visits` or patient profile scoped visit form.
3. Visit detail allows diagnosis stacking and income entry linkage.
4. POS loads today's recent visits from `GET /sales/patient-visits/today-recent`.
5. Cashier selects optional visit context in POS.
6. Hold or checkout persists `sales.patient_visit_record_id`.
7. Sales history/detail/receipt show patient context from `patient_visit_records` only.

## Manual Test Steps

1. Run `php artisan migrate` and ensure cleanup migration executes.
2. Open POS and confirm there is no appointment dropdown or appointment hidden field.
3. Confirm POS calls `sales.patient-visits.today-recent` and renders visit options.
4. Complete sale with selected visit and verify `sales.patient_visit_record_id` is set.
5. Hold sale with selected visit, resume, and verify visit context is still selected.
6. Open sales detail and receipt; confirm patient data is shown from visit record.
7. Open patient visit detail; add multiple diagnoses and verify they are stacked.
8. Record service income from visit detail and verify linked `income_entries` display.
9. Confirm appointment screens/routes are inaccessible (removed).
10. Run `php artisan test` and confirm full suite passes.
