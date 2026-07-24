# Result Engine Production Guide and Closure Certification

## Certification status

**Status: NOT YET CERTIFIED FOR PRODUCTION ACTIVATION**

The centralized Result Engine implemented in Phases 0–10 is internally consistent and
test-stable. It is not possible to certify the repository against every absolute closure
criterion because active legacy compatibility paths still contain independent result
calculation and Blade/database logic. Removing those paths would change legacy behaviour
and expand this closure task into a new migration project, which is explicitly prohibited.

The code may be deployed with all Result Engine flags disabled after normal backup,
migration, and smoke-test controls. Centralized flags must not be activated as an
officially certified production cutover until the closure blockers below are resolved in a
separately approved project.

## Architecture

### Centralized calculation boundary

`BoardResultCalculator` is the pure academic calculator. It receives already-loaded student,
exam, mark, and subject data and returns an immutable `StudentResult` containing
`SubjectResult` values. It owns:

- percentage normalization;
- board grade and grade-point mapping;
- subject pairing;
- feature-wise CQ/MCQ/practical validation;
- Pass, Fail, and Incomplete status;
- compulsory GP total and denominator;
- fourth-subject bonus;
- GPA rounding and the 5.00 cap.

`ResultCalculationInputBuilder` resolves the applicable curriculum. The
`ResultCalculationBatchBuilder` loads a bounded academic scope and calls the calculator once
per student. Presenters transform `StudentResult` for transcript, tabulation, and summary;
they do not determine academic results.

Centralized consumers:

- single transcript: calculator → `TranscriptResultPresenter`;
- bulk transcript: `BulkTranscriptResultBuilder`;
- tabulation/summary: batch builder → `TabulationResultPresenter`;
- placement preview/write: batch builder;
- promotion preview/write: batch builder;
- promotion archive: the selected-exam `StudentResult`;
- centralized revert: immutable promotion archive/audit identity; it does not recalculate.

There is no circular dependency among Result Calculation services. Calculation, presentation,
and centralized persistence are separated.

### Closure blocker: legacy duplicate calculations

The legacy fallbacks remain active by design when flags are disabled and after some
centralized presentation failures. They independently calculate grades/GPA/status/pairs:

- `MarksheetController::allMarksheet` and its legacy helpers;
- `AdmissionController::confirmPromotData` legacy promotion/archive branch;
- `resources/views/result/marksheetGenerate.blade.php`;
- `resources/views/result/bulk-transcript-pdf.blade.php`;
- legacy grade resolution through `GradeList`.

Consequently, the repository does **not** currently satisfy “exactly one GPA/grade
implementation”, “controllers contain orchestration only”, or “no Blade calculations”.
The characterization tests intentionally prove several legacy/centralized differences.
This is a certification blocker, not dead code that can safely be deleted.

## Board calculation flow

1. Resolve selected exam and exact academic scope.
2. Load scoped students and selected-exam marks.
3. Resolve applicable compulsory, optional, religious, and paired subjects.
4. Normalize obtained marks against configured full marks.
5. Apply 80/70/60/50/40/33 grade boundaries.
6. In feature-wise mode, enforce required component pass marks.
7. Mark missing compulsory marks `Incomplete`; compulsory F makes the result `Fail`.
8. Exclude the assigned fourth subject from the denominator.
9. Apply `max(optional GP - 2.00, 0.00)`.
10. Calculate `(compulsory GP sum + bonus) / compulsory count`, round to two decimals, and
    cap at 5.00.
11. Return `StudentResult`; consumers only present or persist this result.

## Feature flags

All flags default to `false` in `.env.example` and `config/result_engine.php`.

| Environment flag | Runtime enabled | Disabled/failure behaviour | Independence |
|---|---|---|---|
| `RESULT_ENGINE_TRANSCRIPT_ENABLED` | Centralized single transcript | Legacy transcript; centralized exception logs then falls back | Independent |
| `RESULT_ENGINE_BULK_TRANSCRIPT_ENABLED` | Centralized per-student bulk result | Legacy bulk rendering; per-student calculation failure can fall back | Independent |
| `RESULT_ENGINE_TABULATION_ENABLED` | Centralized tabulation | Complete legacy tabulation; exception logs then falls back | Independent |
| `RESULT_ENGINE_SUMMARY_ENABLED` | Centralized summary | Complete legacy summary; exception logs then falls back | Independent |
| `RESULT_ENGINE_PLACEMENT_ENABLED` | Centralized preview/write path | Legacy placement path; centralized write service itself refuses when disabled | Independent |
| `RESULT_ENGINE_PROMOTION_ENABLED` | Published-exam, Pass-only centralized promotion | Existing legacy promotion path | Independent |
| `RESULT_ENGINE_PROMOTION_REVERT_ENABLED` | Exact-cycle centralized revert | Existing legacy latest-archive revert | Independent |

`RESULT_ENGINE_SHADOW_MODE` is diagnostic and is not a production output flag.

Promotion and revert flags are operationally related but technically independent: existing
centralized cycles may be reverted while new centralized promotion is disabled. Commands
permit read-only dry-run while the corresponding write flag is disabled. No command has a
force option that bypasses academic or identity validation.

Deactivation stops future centralized writes; it does not undo committed placement,
promotion, or revert transactions.

## Placement flow

The centralized placement service requires an explicit selected scope and published result,
builds every `StudentResult`, applies the configured ranking method, previews changes, and
replaces the placement scope atomically only when the placement flag is enabled. It locks
and verifies write counts. Marks and result publications are read-only inputs.

## Promotion flow

The centralized promotion service requires explicit students, selected published exam,
source/destination scope, and Pass status. It validates rolls, departments, fourth/religious
subjects, archives, and active promotion identity. One UUID promotion cycle links every
archive/audit row in the batch. Archive insert, student update, and audit insert are one
transaction with lock-time rechecks and count verification.

Archives contain only the selected exam's centralized result. No marks, placement, or
publication rows are modified.

## Revert flow

The centralized reverter requires an exact promotion cycle plus explicit students or
explicit `--all`. It resolves one exam-aware centralized audit and immutable archive per
student, rejects moved/ambiguous/later/already-reverted states, verifies source entities and
roll availability, then locks and rechecks all evidence. Student source fields and audit
revert metadata update atomically. Archives are never changed or deleted.

## Query and performance review

### Centralized paths

- Batch inputs eager-load scoped selected-exam marks.
- Subjects are resolved in bulk for students.
- Preview builders preload placements, archives, audits, and destination students.
- There are no queries in academic comparators.
- Placement/promotion/revert transactions keep calculation and rendering outside locks.
- Per-student promotion/revert saves are bounded and necessary because payloads differ.

### Unresolved legacy findings

- The single-transcript legacy max-subject preparation queries marks and students inside a
  student loop.
- Result Blades query models directly, including transcript, summary, and promotion legacy
  rendering.
- Legacy marksheet and bulk transcript Blades perform grade/pair/GPA calculations.
- Some legacy tabulation work repeats academic-scope queries.

These do not invalidate centralized arithmetic, but they violate the absolute query and
presentation criteria and can increase query count and memory use on large scopes.
No cache or architectural rewrite was introduced during closure.

## Security review

Verified safeguards:

- centralized write services enforce feature flags internally;
- web inputs use Laravel validation;
- promotion submission uses a one-time session token and cache replay lock;
- selected scopes, student identities, destinations, rolls, publication, and state are
  revalidated inside transactions;
- commands cannot bypass calculator, publication, identity, or transaction validation;
- archive/cycle database unique keys protect concurrent duplicate centralized writes;
- mass-assignment lists include only explicit archive/audit fields;
- failures log safe identifiers and do not expose stack traces through centralized web paths.

Closure defect fixed:

- Result Archive index/transcript routes were duplicated outside authentication. They are now
  defined only inside the `adminGuard` group.

Remaining authorization limitation:

- Result Engine routes rely primarily on the broad authenticated `adminGuard`; there is no
  separately documented placement/promotion/revert permission matrix. Confirm that every
  authenticated admin role is intended to have these operational powers before activation.

## Dead-code cleanup

The following tracked, unreferenced duplicate/development files were removed:

- `app/Http/Controllers/MarksheetController - Copy.php`
- `app/Services/MarksEntryContextService - Copy.php`
- `routes/web - Copy.php`
- `tests/Feature/MarksEntryTest - Copy.php`
- `resources/views/result/add-marks.blade (2).php`
- `resources/views/result/CARDVARIANT.BLADE.PHP`

No production logging was removed. No Result Calculation service was found without an
application/command/test reference. No Phase 0–10 service was removed.

## Legacy compatibility

With all output/write flags disabled, existing transcript, bulk transcript, tabulation,
summary, placement, promotion, and latest-archive revert paths remain selected. Frozen
characterization tests cover current legacy behaviour, including known differences from the
board calculator. The security fix changes only unauthenticated archive access; authenticated
archive behaviour is unchanged.

Legacy compatibility is therefore test-covered, but it is also the reason the absolute
single-calculator closure criterion is not satisfied.

## Testing

Required verification:

```bash
php artisan test --filter="BoardResultCalculatorTest|SingleTranscriptResultEngineTest|BulkTranscriptResultEngineTest|TabulationSummaryResultEngineTest|CentralizedPlacementRecalculatorTest|PlacementPreviewTest|PromotionPreviewTest|CentralizedPromotionProcessorTest|CentralizedPromotionReverterTest|LegacyResult"
php artisan test
git diff --check
```

Also inspect:

```bash
php artisan route:list --name=resultArchive
php artisan migrate:status
```

Tests must use the isolated test database. Never point PHPUnit at `cultivation` or a backup
database.

Closure verification on 2026-07-24:

- targeted Result Engine suite: 152 tests, 831 assertions, 0 failed, 0 skipped;
- complete project suite: 246 tests, 1,075 assertions, 0 failed, 0 skipped;
- Result Archive routes: exactly one index and one transcript route, both protected by
  `web` and `adminGuard`;
- runtime Result Engine flags: all false;
- Phase 7 and Phase 10 migrations: present as applied in the inspected database;
- centralized placement, publication, archive, and promotion-audit rows: zero.

The deployed application had a production configuration cache. Test runs therefore used a
process-local `APP_CONFIG_CACHE` path so PHPUnit could load its `APP_ENV=testing` and
`cultivation_test` settings without clearing or changing the operational cache. The safety
guard correctly refused the first attempt before any test executed when it detected the
cached production environment.

## Production deployment guide

Documentation only; these steps were not executed during closure.

### Pre-deployment

1. Obtain change approval and a maintenance window.
2. Back up the full database and verify a restore in a non-production environment.
3. Back up current code and record the deployed commit.
4. Record row counts and checksums for marks, placements, publications, archives, and
   promotion audits.
5. Review all pending migrations in timestamp order.
6. Confirm both Phase 7 and Phase 10 migrations against a restored production copy.
7. Set every Result Engine output/write flag to `false`.
8. Verify application key, database, filesystem, queue, session, URL, timezone, log, and PDF
   production configuration.
9. Run the complete suite against the isolated test database.
10. Resolve the certification blockers in this document before approving centralized flag
    activation.

### Deployment

1. Enable maintenance mode.
2. Update code to the approved commit.
3. Run `composer install --no-dev --prefer-dist --optimize-autoloader`.
4. Run reviewed pending migrations in timestamp order with `php artisan migrate --force`.
5. Run `php artisan optimize:clear`.
6. Run `php artisan config:cache`.
7. Run `php artisan view:cache`.
8. Use `php artisan route:cache` only after confirming the project has no uncacheable route
   closures and the command succeeds in staging; otherwise leave routes uncached.
9. Verify storage permissions and restart the applicable PHP workers/services.
10. Disable maintenance mode.

### Verification with all flags disabled

1. Login/logout and role smoke tests.
2. Authenticated Result Archive index/transcript; confirm unauthenticated requests redirect.
3. Legacy single and bulk transcripts.
4. Legacy tabulation and summary.
5. Legacy placement preview/write in a controlled test scope.
6. Legacy promotion and revert only with approved disposable records.
7. Confirm marks, publications, archives, placement, and audit row counts.
8. Review application and web-server logs.

### Controlled flag activation

Activation is blocked until the certification status becomes READY. After separate approval,
enable one flag at a time, rebuild config cache, run a controlled scope, compare evidence,
and observe logs before continuing:

1. Transcript
2. Bulk Transcript
3. Tabulation
4. Summary
5. Placement
6. Promotion
7. Promotion Revert

Keep promotion revert disabled until centralized promotion records with cycle/exam identity
have been verified.

## Rollback guide

### Feature-flag rollback

Set the affected flag to `false`, run `php artisan config:cache`, and verify the legacy path.
This is the first response for output discrepancies. It prevents future centralized writes
but does not undo committed writes.

### Deployment rollback

1. Enter maintenance mode.
2. Capture logs and current database evidence.
3. Disable all Result Engine flags.
4. Restore the last approved code release.
5. Install the matching locked dependencies.
6. clear/rebuild configuration and views; handle route cache as validated in staging.
7. Restart PHP workers and perform flags-off smoke tests.

### Migration rollback

Do not blindly run a multi-step rollback. Determine which deployed code requires each
migration. Phase 7 and Phase 10 migrations are additive; Phase 10 rollback removes only its
cycle/audit columns and indexes. Roll back a migration only after code is reverted and after
confirming no production centralized records require those fields. Never delete archive or
audit evidence as an operational rollback.

### Emergency data recovery

- Stop new writes and preserve logs.
- Do not manually recalculate or infer results.
- Restore from a verified backup or use the exact centralized audit/archive contract.
- Do not run centralized revert against legacy history.
- Reconcile row counts and immutable archive evidence before reopening writes.

## Final consistency checklist

| Requirement | Result |
|---|---|
| Centralized transcript uses `StudentResult` | PASS |
| Centralized bulk transcript uses `StudentResult` | PASS |
| Centralized summary/tabulation use `StudentResult` | PASS |
| Centralized placement/promotion use `StudentResult` | PASS |
| Centralized archive stores selected exam only | PASS |
| Centralized calculator is the only centralized academic calculator | PASS |
| No cross-exam centralized archive | PASS |
| Feature flags default false and are independently guarded | PASS |
| Legacy flags-off compatibility is covered | PASS |
| No circular Result Calculation dependency | PASS |
| No independent GPA/grade logic anywhere in repository | **FAIL** |
| Controllers contain orchestration only | **FAIL** |
| No Blade calculations or queries | **FAIL** |
| No query inside relevant loops | **FAIL (legacy path)** |
| Result archive routes require authentication | PASS after closure fix |
| No remaining known duplicate development copies | PASS |

## Known limitations and required next action

1. Legacy result calculations duplicate the board calculator and have characterized policy
   differences.
2. Legacy Result Blades perform calculations and database queries.
3. Some legacy paths contain N+1/repeated scope queries.
4. Broad admin authorization must be confirmed against the intended permission policy.
5. MariaDB database uniqueness uses student/cycle identity; full nullable logical identity is
   additionally enforced by locked application checks.
6. Deterministic multi-connection concurrency scheduling remains environment-dependent.

The required next action is **not Phase 11**. It is a separately scoped and approved legacy
retirement/cutover project with explicit permission to change fallback behaviour, move
legacy Blade/controller calculations behind the centralized boundary, and define the
operator permission matrix. Until that work and its regression testing are complete, this
document must retain the `NOT YET CERTIFIED FOR PRODUCTION ACTIVATION` status.

Future enhancements are separate projects and are not continuations of the Result Engine
refactor.
