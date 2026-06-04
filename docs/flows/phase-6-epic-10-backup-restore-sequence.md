# Phase 6, Epic 10 - Backup, Restore, And Module Data Exchange

## Sequence

```mermaid
sequenceDiagram
    participant Admin
    participant UI as BackupRestoreController
    participant Exp as DatasetExporter
    participant Imp as DatasetImporter
    participant DB as Database

    Admin->>UI: Export CSV / SQL
    UI->>Exp: toCsv / toSql
    Exp->>DB: SELECT rows per table
    Exp-->>Admin: Download file

    Admin->>UI: Import CSV or XLSX
    UI->>Imp: importCsvOrXlsx
    Imp->>DB: upsert rows (transaction)
    Imp-->>Admin: Flash success

    Admin->>UI: Restore dataset SQL
    UI->>Imp: restoreSql
    Imp->>DB: run INSERT statements (transaction)
    Imp-->>Admin: Flash success

    Admin->>UI: Download full SQL backup
    UI->>DB: dump all app tables
    Admin->>UI: Restore full SQL + confirmation phrase
    UI->>DB: replace all data (transaction)
```

## Manual QA

1. Log in as **admin@zarliminnew.test**.
2. Open **Management → Backup & Restore**.
3. **Suppliers:** click **Export CSV**, open file — confirm `#TABLE:suppliers` header and rows.
4. Delete a supplier in the UI, then **Import** the CSV — supplier returns.
5. **Product Catalog:** **Export SQL**, then **Restore SQL** with **Replace** unchecked on a copy DB only.
6. **Full database:** **Download SQL backup** — file downloads. On a test environment only, restore with phrase `RESTORE DATABASE`.
7. Log in as **cashier** — page must be **403 Forbidden**.
8. Check **audit_logs** for `backup.dataset.exported` and `backup.dataset.imported`.
