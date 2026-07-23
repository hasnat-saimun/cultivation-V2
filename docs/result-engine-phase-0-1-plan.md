# Result Engine Incremental Migration: Phase 0 and Phase 1

## Scope and safety boundary

This delivery documents the current result workflow and adds characterization tests only. It does not change production calculation logic, routes, request payloads, database schema, marks, grades, GPA, placements, archives, or published output.

No historical recalculation or data backfill is part of this phase.

## Current workflow and dependency map

```text
Marks-entry form
  -> MarksheetController::getMarks()
     -> authorization/context services
     -> religious/fourth-subject assignment filters
  -> MarksheetController::confirmMarks()
     -> sums CQ + MCQ + practical
     -> GradeList::forScore(raw total)
     -> persists marksheets marks/grade/point

marksheets + subjects + exams + grade_lists
  -> MarksheetController::allMarksheet()
     -> recalculates normalized subject grade/component status
     -> merges paired papers
     -> recalculates GPA and pass/fail
     -> allMarksheet / atGlanceResult / resultSummary

marksheets + student
  -> MarksheetController::generateMarksheet()
     -> marksheetGenerate Blade recalculates grade/GPA/status
  -> MarksheetController::bulkTranscriptPdf()
     -> bulk-transcript-pdf Blade independently recalculates grade/GPA/status

marksheets
  -> PlacementController / RecalculatePlacements command
     -> independently averages stored gradePoint values
     -> persists exam_placements

student promotion
  -> AdmissionController
     -> independently merges/averages result rows
     -> persists result_archives

result_archives
  -> ResultArchiveController
     -> displays stored snapshot without recalculation
```

## Value lifecycle

| Value | Stored | Dynamically calculated | Consumers |
|---|---:|---:|---|
| CQ/MCQ/practical marks | Yes, `marksheets` | No | All result workflows |
| Subject total | Yes | Also recalculated | marks entry, transcripts, tabulation, placement |
| Subject letter/GP | Yes | Also recalculated | placement uses stored; output commonly recalculates |
| Overall GPA/status | Placement/archive only | Yes elsewhere | tabulation, transcript, merit, reports |
| Optional bonus | No | Yes in selected output paths | tabulation/single/bulk transcript |
| Published state | Yes, publication records | Read for teacher lock | marks entry |

## Risk matrix

| Workflow | Risk | Historical sensitivity | Reason |
|---|---|---:|---|
| Marks selection/loading | Medium | No | Active teacher authorization and student filtering |
| Marks save/update | High | Yes | Concurrent live writes; stored derived grade/point |
| Grade configuration | High | Yes | Editable scale affects dynamic output |
| Single transcript | Medium | Yes | Read-only but recalculates in Blade |
| Bulk transcript PDF | Medium | Yes | Duplicate Blade calculation |
| Tabulation/at-a-glance | High | Yes | GPA/status/merit source for published output |
| Result summary | High | Yes | Inherits tabulation classification |
| Placement | High | Yes | Persists independently derived GPA/rank |
| Promotion archive | High | Yes | Persists historical snapshot during promotion |
| Existing archive display | Read-only | Yes | Displays frozen snapshot |
| Legacy copy files | Low | No | Not referenced by active routes/autoloading |

## Active marks-entry paths

- `MarksheetController::addMarks`
- `MarksheetController::getMarks`
- `MarksheetController::confirmMarks`
- `MarksEntryContextService`
- `MarksEntryAuthorizationService`
- `FourthSubjectAssignmentResolver`
- `ReligiousSubjectAssignmentResolver`
- `resources/views/result/add-marks.blade.php`
- `resources/views/result/get-marks.blade.php`
- Routes named `addMarks`, `getMarks`, and `confirmMarks`

## Files likely to change in later phases (not changed now)

- New classes under `app/Services/ResultCalculation/`
- Additive result-engine configuration and `.env.example` flags
- New calculator unit tests and parity/characterization tests
- One controller/view integration at a time, guarded by independent flags
- Potential additive shadow-comparison storage only if logging is insufficient

## Database impact assessment

Phase 0/1 has no production database impact. Tests use the guarded `cultivation_test` database. A future shadow audit table, if approved, must be additive, nullable, reversible, and must not reference existing records with cascading actions. No marksheet constraint or data rewrite is approved.

## Feature flag plan

All future flags default to `false`:

- `RESULT_ENGINE_SHADOW_MODE`
- `RESULT_ENGINE_ENABLED`
- `RESULT_ENGINE_TRANSCRIPT_ENABLED`
- `RESULT_ENGINE_BULK_TRANSCRIPT_ENABLED`
- `RESULT_ENGINE_TABULATION_ENABLED`
- `RESULT_ENGINE_PLACEMENT_ENABLED`
- `RESULT_ENGINE_ARCHIVE_ENABLED`

Flags must preserve exception-safe legacy fallback. No flag is introduced or enabled in Phase 0/1.

## Characterization testing plan

Phase 1 locks current behavior for:

- Raw-total grade persistence during marks submission and update
- Zero and blank component handling
- Optional/religious assignment filters (existing feature suites)
- Paired-subject total-mark behavior
- Tabulation optional-failure and missing-subject behavior
- Placement row-average behavior
- Current grade fallback boundaries

Single/bulk transcript, promotion, and archive parity remain explicit characterization targets before their respective integration phases; their current calculations are documented above and must not be changed without dedicated fixtures.

## Deployment sequence for this phase

1. Deploy documentation and test files only.
2. Do not run migrations; none exist.
3. Run tests only against `cultivation_test` with `APP_ENV=testing`.
4. Leave application processes and marks entry uninterrupted.

## Rollback

Remove/revert the Phase 0 document and Phase 1 test file. No feature flag, production code, schema, cache, queue, or database rollback is required.

## Later-phase rollback principle

Each future integration must be reversible by disabling its workflow-specific flag, immediately restoring the legacy calculation without a database restore.

## Phase 1.5 — remaining characterization coverage

### Tests added

- `tests/Feature/LegacyResultOutputCharacterizationTest.php`
- Deterministic fixtures for single transcript, bulk transcript preparation, promotion archive creation, frozen archive rendering, result summary, and cross-output comparison.

### Coverage completed

- Single transcript: pass/fail, optional bonus/F, uncapped GPA, zero/missing marks, non-100 full marks, feature-wise paired components, and Main/Theory/Optional classification.
- Bulk transcript view preparation for multiple students, including pass, compulsory F, optional F, missing, zero, optional bonus, and non-100 full marks.
- Promotion-created archive snapshot, including multiple-exam mixing, optional-F status, omitted optional bonus, subject-type treatment, and persisted snapshot fields.
- Frozen archive rendering after live marks and subject data change.
- Result-summary counts inherited from tabulation.
- Cross-output fixture comparing single, bulk, tabulation, placement, promotion archive, and frozen archive display.

### Confirmed legacy inconsistencies

- Single/bulk/tabulation can return GPA above 5.00 while placement and promotion archive return 5.00 for the same fixture.
- Promotion archives average Main rows without fourth-subject bonus and query marks without exam scope.
- Placement counts optional rows as ordinary subjects.
- Optional F fails tabulation and promotion archive but does not fail single/bulk transcript final status.
- Missing and all-zero main results can render with no final GPA/letter in transcript paths.
- `Theory` type is excluded from transcript Main GPA calculation.
- Paired subjects use combined total even when the exam is feature-wise.
- Frozen archive display uses stored snapshot values, not changed live marks or current subject names.

### Remaining gaps

- Binary PDF pagination/layout is deliberately not asserted; the bulk view data and rendered HTML calculation are covered.
- No spreadsheet/CSV result export with a separate grading algorithm was found in the active result routes.
- Dense-rank HTML formatting is not asserted; placement basis and controller-prepared merit/result data are covered.

### Phase 2 readiness decision

The executable baseline now covers the active calculation workflows sufficiently to design a shadow-only centralized engine. Phase 2 remains blocked on explicit review/approval; no centralized service or production integration has been started.
