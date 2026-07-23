# Result Engine Phase 10

## Scope

Phase 10 adds permanent promotion-cycle identity, exam-aware centralized promotion audits,
database protection for centralized archive identity, and an atomic centralized revert. It
does not change result calculation, eligibility, publication, marks, placement, attendance,
fees, or legacy history.

## Active storage and schema

- Archives: `result_archives`, model `App\Models\ResultArchive`.
- Promotion history: `promotion_audit_logs`, model `App\Models\PromotionAuditLog`.
- Migration: `2026_07_23_000002_add_centralized_promotion_identity.php`.

Archive addition:

- nullable `promotion_cycle_id varchar(36)`
- unique `result_archives_student_cycle_unique(student_id, promotion_cycle_id)`

Audit additions:

- nullable `exam_id`, `promotion_cycle_id`, `engine`
- nullable `old_department`, `new_department`, `actor_context`
- nullable `reverted_at`, `reverted_by`, `revert_cycle_id`, `revert_reason`
- unique `promotion_audit_student_cycle_unique(student_id, promotion_cycle_id)`
- state lookup index `(promotion_cycle_id, engine, reverted_at)`
- revert-cycle index `(revert_cycle_id)`

MariaDB 10.4 does not support the required portable partial-index form. The nullable-cycle
strategy protects every centralized row while permitting multiple historical null-cycle
rows. No legacy row is backfilled or rewritten.

## Promotion identity and write contract

Each real centralized promotion creates a UUID `promotion_cycle_id`. One batch shares one
cycle; each student is protected by the student/cycle unique keys. The ID is written to the
selected-exam archive and centralized audit, returned by command/web reports, and logged.
For compatibility, existing `promotion_id` receives the same UUID.

The Phase 9 processor now writes `exam_id`, `engine=centralized`, source/destination
department values, and actor context to the audit. Pass-only eligibility, selected published
exam, destination checks, roll checks, and batch atomicity are unchanged.

An active matching centralized audit plus matching archive returns `ALREADY_PROMOTED`.
A reverted pair is historical evidence and does not block a new cycle if the student is
back in the exact source state and every Phase 9 check passes. Null-cycle archives for the
same selected exam, or archives without exam identity, remain ambiguous and block.

## Active, reverted, and ambiguous states

Active:

- one centralized audit with cycle and exam identity;
- `reverted_at` and `revert_cycle_id` are null;
- exactly one archive with the same student, cycle, and exam;
- student matches the recorded destination scope and roll.

Reverted:

- the same immutable archive remains;
- audit has both `reverted_at` and a distinct `revert_cycle_id`;
- student matches the recorded source scope and roll.

Missing, duplicate, mismatched, partial, manually moved, or otherwise inconsistent evidence
is `AMBIGUOUS_PROMOTION_STATE` (or a more specific blocker). It is never auto-repaired.

## Centralized revert contract

`CentralizedPromotionReverter` resolves an explicit promotion cycle and either explicit
students or the entire cycle. It requires:

- centralized engine, cycle ID, and selected exam ID;
- exactly one matching archive per audit;
- archive/audit exam and source/destination snapshots to agree;
- current student destination session, class, section, department, and roll to agree;
- source session/class/optional section/optional department to exist;
- source roll to be free;
- no later centralized or provable legacy promotion;
- no previous revert.

Restore fields are limited to `sessName`, `className`, `sectionName`, `departmentName`, and
`rollNumber`, exclusively from the audit and immutable archive snapshot. Fourth/religious
subjects are not restored because centralized promotion does not mutate them. Marks, GPA,
grades, placement, publication, archive data, attendance, and fees are never touched.

Before writes, all rows are validated in memory. Inside one transaction the service locks
students, audits, and archives in stable order, repeats all critical checks, restores
students, marks audits reverted, verifies counts, then commits. Any failure rolls everything
back. A batch is all-or-nothing.

Every successful real revert generates a new UUID `revert_cycle_id`; it is never the
promotion cycle. It is saved with timestamp, actor, optional reason, returned in reports,
and logged. The existing promotion audit is the project's supported revert audit; no generic
audit/event table was added.

## Commands

Dry-run works with the revert flag disabled:

```bash
php artisan students:promotion-revert \
  --promotion-cycle=<uuid> \
  --student=<database-id> \
  --engine=centralized \
  --dry-run
```

Use repeated `--student` or explicit `--all`. Real execution uses the same command without
`--dry-run` and requires:

```env
RESULT_ENGINE_PROMOTION_REVERT_ENABLED=true
```

There is no force option and no fallback to legacy revert.

## Web integration

When the revert flag is enabled, the promotion list preloads active centralized audits and
shows selected exam, source/destination scope, cycle student count, and an explicit
cycle-bound confirmation action. Errors return concise blocker messages.

When the flag is disabled, the existing legacy route and behavior are unchanged. When the
flag is enabled, direct use of that legacy route is blocked so it cannot bypass centralized
identity checks. Legacy promotions are never guessed or passed to the centralized service.

## Concurrency protection

Promotion locks student rows and rechecks destination and archive/audit identity. Revert
locks student, audit, and archive rows and repeats state, later-promotion, and roll checks.
Database unique keys prevent duplicate archive and audit insertion for the same
student/cycle even if application preflight races. Bounded per-student model saves are used
because each restored payload may differ.

## Deployment

Safe defaults:

```env
RESULT_ENGINE_PROMOTION_ENABLED=false
RESULT_ENGINE_PROMOTION_REVERT_ENABLED=false
```

1. Back up `result_archives` and `promotion_audit_logs`.
2. Deploy code with both flags false.
3. Verify no non-null centralized cycle conflicts exist.
4. Run migrations.
5. Verify legacy counts/content and new nullable columns/indexes.
6. Clear and rebuild config cache.
7. Run targeted and full tests.
8. Dry-run promotion.
9. Create controlled centralized test promotion and dry-run its revert.
10. Enable promotion for a controlled scope and verify cycle/exam audit fields.
11. Keep revert disabled initially; later enable it for one controlled cycle.
12. Verify restoration, immutable archive, and re-promotion with a new cycle.

No production migration is run as part of development.

## Runtime and migration rollback

Set either feature flag false and rebuild config cache to stop future centralized writes.
This does not undo committed transactions and must not delete evidence.

Migration rollback drops only the two Phase 10 unique keys, two audit indexes, and Phase 10
columns. Roll it back only after all deployed code stops requiring those fields. It never
deletes the pre-existing archive/audit tables.

## Known limitations and Phase 11 recommendation

- Full logical source/destination uniqueness remains an application-level locked check;
  database uniqueness is deliberately limited to student/cycle to avoid nullable-scope and
  legacy deployment hazards.
- MariaDB/InnoDB integration tests exercise unique violations and transactional rollback,
  but deterministic multi-connection race scheduling is environment-dependent.
- The web action intentionally reverts one selected student at a time; batch-cycle operations
  are available through the command.
- There is no separate revert event table; the existing audit row carries the immutable
  revert metadata.
- Null-cycle legacy history cannot be safely centralized and remains on the legacy path.

Phase 11 should consider an operator-facing cycle history screen and a purpose-built,
append-only promotion/revert event ledger only if operational requirements justify the
additional architecture.
