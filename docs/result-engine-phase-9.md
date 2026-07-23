# Result Engine Phase 9

> Phase 10 extends this contract with permanent promotion/revert cycle identity,
> exam-aware audit fields, database uniqueness, atomic centralized revert, and safe
> re-promotion. See `docs/result-engine-phase-10.md`.

Phase 9 is the first centralized promotion write integration. It is disabled by
default with `RESULT_ENGINE_PROMOTION_ENABLED=false`. No migration or new history
table is introduced.

## Frozen policy

An explicit selected exam controls centralized eligibility, publication validation,
archive contents and archive identity. Only centralized `Pass` students may be
promoted. `Fail`, `Incomplete` and calculation errors block the complete selected
batch. There is no override.

The destination session, class and section remain administrator-selected. No class
sequence is inferred. Blank roll input preserves the source roll; explicit roll input
is used after normalization. Rolls must be unique within destination
session/class/section, both against existing students and inside the selected batch.
Placement rank and selection order are never used as rolls.

Placement is not an eligibility dependency. The selected scope must be published via
`result_publishes` for the exact exam/class/session and either its section or a
null-group publication. Unpublished promotion cannot be forced.

Department is preserved only when destination compatibility can be established. The
current web workflow supplies no destination department, so a student with a
department is blocked as compatibility-unproven. The command accepts an explicit
validated destination department. A fourth subject is preserved only if it exists,
is Optional and applies globally or to the destination class. Religious subject
assignment/default must resolve to a religious subject applicable to the destination.
Neither assignment is cleared or substituted.

## Archive and identity

Centralized promotion writes one `result_archives` row with:

- selected `exam_id`;
- source session/class/section/roll;
- centralized GPA, status, optional bonus and compulsory summary;
- centralized subject/component/failure data;
- selected-exam raw component marks only;
- destination scope and roll;
- promotion timestamp and calculator version.

The archive also carries a legacy-compatible selected-exam subject projection so
existing archive transcript views can display name, marks, grade and grade point.
This projection does not calculate GPA or status.

Logical identity uses student + selected exam + source session/class/section, with
destination confirmation from `promotion_audit_logs`. Another exam's identified
archive does not block. An exact archive plus matching audit returns
`ALREADY_PROMOTED`. Partial archive/audit evidence or a matching legacy archive whose
`exam_id` is null returns `AMBIGUOUS_PROMOTION_STATE`. Historical archives are not
changed or backfilled.

## Transaction

Before the transaction the processor validates entities, feature flag (write only),
publication, tolerant preview, strict centralized results, source membership,
Pass-only eligibility, rolls, destination identity, department, fourth/religious
subjects, archive identity and audit history. It builds complete archive, student and
audit payloads in memory.

Inside one retryable transaction it:

1. locks selected source students;
2. rechecks source membership;
3. rechecks destination roll and archive conflicts;
4. bulk-inserts selected-exam archives;
5. updates the bounded selected student rows;
6. bulk-inserts existing promotion audit rows;
7. verifies exact archive, destination-student and audit counts;
8. commits.

Any exception rolls back all three write types. Marks, placements, publication,
attendance, fees and other students are not written.

## Commands

Dry-run works while the flag is false:

```bash
php artisan students:promote \
  --exam=12 --class=10 --session=2026 \
  --to-class=11 --to-session=2027 --to-section=3 \
  --section=2 --student=101 --engine=centralized --dry-run
```

Repeat `--student` for a selected batch. `--roll=101=55` supplies an explicit roll.
`--all` deliberately selects the complete filtered source scope.

Real write requires the flag:

```bash
php artisan students:promote \
  --exam=12 --class=10 --session=2026 \
  --to-class=11 --to-session=2027 --to-section=3 \
  --section=2 --student=101 --engine=centralized
```

Optional filters are `--group`, `--department` and `--to-department`. There is no
`--force`.

## Web integration

With the flag false, the existing promotion list/form/confirmation and legacy archive
behavior remain unchanged. With the flag true, the existing confirmation form shows a
minimal controlling-exam selector and the guarded branch invokes
`CentralizedPromotionProcessor`. A centralized failure returns summarized blockers
and never falls through to the legacy transaction. Replay token and cache lock remain
in effect.

## Deployment and rollback

Deploy with the flag false, run all tests, rebuild config cache, confirm legacy web
promotion, run a controlled centralized dry-run, review publication/archive/roll
diagnostics and back up source students plus relevant archive/audit rows. Enable the
flag only for a very small verified batch.

Runtime rollback is setting `RESULT_ENGINE_PROMOTION_ENABLED=false` and rebuilding
config cache. This prevents future centralized writes but does not reverse committed
promotions. Existing `promotion.revert` is unchanged and should be used only after
manual verification.

## Known limitations and Phase 10

- Sections and departments do not have a reliable class-curriculum relation; strict
  department preservation may block until an explicit mapping policy exists.
- The audit table has no exam column. Idempotency therefore combines the exam-scoped
  archive with destination-scoped audit evidence.
- Database uniqueness is enforced by locked preflight/rechecks rather than a unique
  archive constraint; no migration was authorized.
- Revert restores source academic fields but does not remove centralized archive or
  audit records, so a later rerun remains intentionally blocked.
- Existing legacy archives with null `exam_id` can make identity ambiguous and require
  manual review; Phase 9 does not repair them.

Phase 10 should first decide whether archive/audit database uniqueness and exam-aware
audit identity need an approved migration, and whether revert requires a centralized,
audited reversal contract.
