# Result Engine Controlled Production Deployment

## Governing documents

Deployment decisions must use:

- [Production governance](result-engine-production-governance.md)
- [Manual remediation policy](result-engine-manual-remediation-policy.md)
- [Deployment decision framework](result-engine-deployment-decision-framework.md)
- [Risk assessment](result-engine-risk-assessment.md)
- [Production approval checklist](result-engine-production-approval-checklist.md)
- [Historical exception manifest](result-engine-historical-exception-manifest.md)

The manifest documents pre-existing records only. It does not authorize bypassing the preflight or migrating while current C2 migrations would touch those records.

## RC1 technical gate

The current release must not proceed until a separately reviewed correction proves all three conditions:

1. Category A active corruption remains blocking.
2. Category B manifest rows remain byte-for-byte/field-for-field unchanged.
3. Migrations do not create lifecycle scope-state rows for scopes whose student, exam, or subject master is absent.

Current migration `2026_07_24_000005_normalize_result_identifiers.php` issues blanket updates over `marksheets`; `2026_07_24_000016_initialize_marks_scope_states_as_draft.php` initializes every distinct historical marks scope; and `000004` cannot distinguish manifest exceptions. Therefore the current code revision is not yet authorized for production migration.

## Mandatory evidence gate

Deployment is prohibited until the latest verified production backup is identified by source, timestamp, size, SHA-256 checksum, database name, restore timestamp, isolated rehearsal database, and responsible operator. Local or empty test database timings are not production evidence.

## Rehearsal

Restore the backup to an isolated database and configure a non-production environment for that database. Capture row counts, database size, relevant `SHOW CREATE TABLE` output, duplicates, warnings, and schema before and after.

Run:

```powershell
php artisan result-engine:integrity-preflight
php artisan result-engine:integrity-preflight --json
php artisan migrate --force
php artisan test --filter=ResultIntegrity
php artisan test --filter=ResultMarks
php artisan test --filter=ResultPublication
php artisan test --filter=ResultLifecycleSecurity
php artisan test --filter=ResultLifecycleConcurrency
php artisan test --filter=Result
php artisan test
php artisan route:list
php artisan view:cache
php artisan config:cache
php artisan config:clear
```

Stop if preflight is blocked. Do not auto-repair duplicate identities, ambiguous sessions, invalid groups, oversized IDs, or publication collisions. Produce a separately approved reconciliation report.

Measure each C2 migration, especially identifier normalization, varchar resizing, generated scope columns, unique indexes, publication backfill, and Draft initialization. Observe metadata locks. The measured result determines whether the window is short, extended, or requires an online migration tool.

## Deployment order

1. Announce and begin an approved marks-entry freeze.
2. Enter maintenance/read-only mode.
3. Take a fresh full backup, checksum it, verify readability, and retain credentials outside logs.
4. Run both integrity preflight forms against production; stop on BLOCKED.
5. Run additive C2 migrations and record duration/warnings.
6. Deploy the compatible C3-C5 application build.
7. Run configuration, route, and view cache checks.
8. Execute only approved non-production academic smoke records.
9. Run read-only integrity checks.
10. Re-enable mutations and start the monitoring window.

No feature flag is introduced: two write paths would permit lifecycle bypass. The code/schema deployment therefore requires a controlled mutation freeze. The legacy `confirmMarks` route remains a Draft-save compatibility adapter and has the same service authorization, revision, CSRF, method, and throttle controls. Remove it only after UI/integration telemetry proves no consumer remains.
