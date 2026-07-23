# Result Engine Phase 8

Phase 8 is a read-only audit and centralized promotion eligibility preview. It adds no
migration, feature flag or promotion persistence.

## Active legacy workflow

The active routes are:

- `GET /student/promotion` (`studentPromotion`) → `AdmissionController::studentPromotion`
  → `cultivation.promotStd`.
- `POST /student/promotion/getData` (`getPromotionData`) →
  `AdmissionController::getPromotionData` → `cultivation.promotData`.
- `POST /student/promotion/confirm` (`confirmPromotData`) →
  `AdmissionController::confirmPromotData`.
- `POST /promotion/revert/{stdId}` (`promotion.revert`) →
  `AdmissionController::revertPromotion`.

They are in the authenticated administrative route group. No active promotion command,
job or service existed before this preview. The workflow supports class-wide or
section-wise listing, checkbox-selected bulk promotion, select-all, manual destination
session/class/section, manual roll override and old-roll retention. A single student is
supported by selecting one checkbox. Department is neither a source filter nor a
destination input.

The list query filters current `new_admissions` rows by optional session/class and, for
section-wise mode, section. It accepts no exam. Every row is displayed as `Eligible:
Yes`. Confirmation checks selected IDs still match source class/session/section but
does not inspect marks, GPA, status, placement, publication or result archive.
Consequently legacy eligibility is manual for Pass, Fail, Incomplete and no-marks
students.

## Destination and roll policy

Destination session, class and section are manually selected from global tables. The
workflow does not calculate the next class, enforce class order, prevent retention or
prevent a lower class. Department, religious subject and fourth subject remain
unchanged. Group is represented by `sectionName`.

The operator may type a destination roll per student. Blank input retains the current
roll. Roll uniqueness is enforced within the selected batch and against existing
students for destination session/class/section. Conflicts skip that student. Placement
position is not used. Therefore Phase 7 shared competition ranks do not currently
create duplicate rolls unless an operator manually enters those ranks; such duplicates
are then rejected.

## Existing write and archive order

`confirmPromotData` consumes a one-time session token, obtains a cache replay lock and
runs one database transaction with three retry attempts:

1. Lock selected student rows.
2. Revalidate source membership and target mismatch.
3. Resolve manual or retained roll and check conflicts.
4. Load **all marks for the student across all exams**.
5. Rebuild a legacy archive snapshot using stored grades/GP and legacy pair helpers.
6. `firstOrCreate` `result_archives` by student/source class/roll/session/section.
7. Update student session, class, section and roll.
8. Insert `promotion_audit_logs`.

The archive key does not include exam and active promotion does not populate
`exam_id`, despite that nullable column existing. Reruns reuse the matching archive.
Archived result data contains merged subjects, total marks, legacy GPA and Pass/Fail.
It does not preserve centralized optional bonus as a separate value. Archive pages and
historical transcripts read this JSON snapshot. Archive, student update and audit log
are atomic within the transaction, but replay prevention is a cache/session mechanism.
Revert uses the latest archive to restore session/class/section/roll, does not delete
the archive or audit log, and is not wrapped in the promotion transaction.

Promotion does not change department, fourth/religious subject, marks, attendance,
fees, placements or publication state.

## Centralized preview

`PromotionPreviewBuilder` reuses `ResultCalculationBatchBuilder` and its exact
`StudentResult`. Pass is normally eligible; Fail and Incomplete remain visible and are
not normally eligible. The legacy manual capability is reported independently.

It compares selected-exam legacy stored-row GPA/status, centralized GPA/status,
same-exam placement, exact-exam archive, promotion audit history, destination state,
student-ID duplication and roll conflicts. Another exam's marks cannot affect the
centralized result; another exam's placement/archive is ignored for active-scope
comparison. Legacy archives with null exam are reported as unknown rather than treated
as exact-exam archives.

Detected issues include missing/identical destination scope, unverified class order,
global section/department mapping, destination student/roll conflicts, duplicate
source IDs, duplicate placements, duplicate archives/history, archive-without-
promotion, promotion-without-archive and already-promoted state.

The current schema has one mutable admission row per student, so “already in
destination” normally means the source query no longer selects that row. Audit history
and duplicate `stdId` records provide the remaining evidence of prior/partial moves.

## Command

```bash
php artisan result-engine:promotion-preview \
  --exam=12 --class=10 --session=2026 \
  --to-class=11 --to-session=2027 \
  --section=2 --to-section=3
```

Optional inputs are `--department`, `--to-department`, `--group` (source-section
alias), `--student`, `--limit` and `--all`. Default output shows differences, blockers
and warnings. No feature flag is required.

## Query and no-write guarantee

The preview uses the centralized batch query/eager-load strategy, then bounded
scope-level queries for placement, archives, promotion audit logs, destination
students and duplicate student IDs. Eligibility and conflict comparison is in memory;
there are no queries in sorting or row rendering. Scope existence checks are bounded
single-record lookups.

Neither the builder nor command calls create, update, save, delete, transaction or
archive/promotion code. Tests snapshot admissions, marks, placements, archives,
publication and promotion history before and after service/command execution.

## Phase 9 policy decisions

Before writes are designed, approve:

1. Whether only Pass is normally promotable.
2. Whether Fail can receive an explicit override.
3. Whether Incomplete can ever be promoted.
4. Which exam controls eligibility.
5. Whether placement is required and must be current.
6. Whether results must be published.
7. How destination class/session are selected and class order validated.
8. How destination section/department/group are assigned.
9. Whether rolls are retained, manual, sequential or placement-derived.
10. Whether shared rank may ever become a duplicate roll.
11. What happens to fourth/religious subject assignments.
12. What happens to marks, attendance and fees.
13. Whether archive data must use centralized results and an exact exam.
14. Whether archive plus promotion must remain one atomic transaction.
15. The durable idempotency key and rerun/partial-recovery policy.
16. Whether and how promotion can be reversed.
17. What happens to source placements after promotion.

Phase 9 should not add writes until these policies, especially controlling exam,
publication requirement, roll assignment, archive schema/identity and failed-student
override, are explicitly approved.
