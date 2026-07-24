# Result Engine Phase C2 production-backup rehearsal

Production deployment is prohibited until this runbook succeeds against a restored,
verified latest production backup.

1. Record the backup source, creation timestamp, database engine/version, and responsible operator.
2. Calculate and retain a cryptographic checksum of the backup.
3. Restore into a new isolated database; never reuse the live production database.
4. Configure a dedicated application environment pointing only to that restored database.
5. verify the environment and database name before continuing.
6. Run `php artisan result-engine:integrity-preflight --json` and export its complete output.
7. Stop if the command exits nonzero. Resolve findings through a separately approved data-cleanup plan.
8. Capture table row counts, `SHOW CREATE TABLE`, indexes, data/index sizes, and duplicate diagnostics.
9. Back up the restored database again immediately before migration.
10. Run `php artisan migrate --force` while capturing per-migration duration and database process/lock observations.
11. Run the Phase C2 postcondition queries and compare all protected row counts.
12. Run focused Result Engine schema, calculation, transcript, tabulation, promotion, and archive tests.
13. Run the complete application test suite against an isolated test database.
14. Verify routes, cached Blade compilation, logs, generated scope values, and unique constraints.
15. Test rollback only on a disposable second restore. Do not drop scope/audit state after lifecycle usage.
16. Re-restore the original backup after rollback testing if any persistent state was changed.
17. Produce a signed deployment decision containing checksum, preflight result, migration durations,
    lock impact, postconditions, test totals, and remaining blockers.

Rollback boundary:

- Before lifecycle use, code and additive schema can be rolled back in reverse order, except canonical
  identifier spellings cannot be reconstructed automatically.
- After scope-state or audit events are used, preserve those tables and publication lifecycle columns.
  Disable mutations and deploy compatibility code instead of deleting lifecycle evidence.
