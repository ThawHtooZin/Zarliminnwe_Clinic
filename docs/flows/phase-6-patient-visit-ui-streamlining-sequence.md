# Phase 6 - Patient -> Visit UI Streamlining

## Flow

1. Staff opens **Patients** from the sidebar.
2. Staff creates or selects a master patient (name, age, address).
3. On patient detail, staff clicks **New Visit**.
4. Staff enters visit datetime only and saves.
5. System creates `patient_visit_records` linked to the parent `patients.id`.
6. Staff opens visit detail from the patient’s visit list for diagnoses and income.

## Manual Test Steps

1. Confirm sidebar shows **Patients** but not **Patient Visits**.
2. Confirm `/patient-visits` and `/patient-visits/create` return 404.
3. Open a patient profile → **New Visit** → verify no name/age fields.
4. Save visit → verify redirect to visit detail and visit appears on patient profile.
5. Run `php artisan test`.
