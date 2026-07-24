# Result Engine Production Rollback

This policy is governed by the [production governance](result-engine-production-governance.md), [approval checklist](result-engine-production-approval-checklist.md), and [historical exception manifest](result-engine-historical-exception-manifest.md). Rollback verification must confirm both manifest SHA-256 fingerprints are unchanged.

## Principle

After lifecycle use begins, an ordinary rollback to legacy code is unsafe. Legacy code may treat retained Unpublished rows as Published, ignore Confirmed locks, or delete history.

## Application rollback

1. Disable result mutations and keep administrative read access.
2. Deploy only a compatibility build that honors `status=published`, Confirmed marks locks, revisions, and retained Unpublished rows.
3. Preserve `marks_scope_states`, `result_lifecycle_events`, and all publication lifecycle columns.
4. Run read-only integrity checks and inspect application/deadlock logs.
5. Re-enable mutations only after a forward fix passes rehearsal.

## Database rollback

Before any lifecycle mutation, migration `down` behavior may be rehearsed only on an isolated restored backup. After lifecycle mutation, do not drop state/event tables, lifecycle columns, generated scopes, revisions, or history. Operational rollback is application read-only compatibility plus preserved additive schema, not destructive schema reversal.

If a migration fails, leave mutations disabled, capture the exact completed migration boundary, restore the verified pre-deployment backup when approved, and validate counts/checksums before service resumes. Never use delete-based unpublish logic.

## Backup restore verification

The operator must document exact host-approved backup/restore commands, file checksum, database name, timestamps, and restored row counts. A backup is not considered recoverable until isolated restoration and lifecycle event/state verification pass.
