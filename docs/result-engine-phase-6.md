# Result engine Phase 6 — read-only placement preview

Phase 6 adds only centralized placement preview and comparison. It does not alter, regenerate, delete, or create placement data.

## Active legacy workflow

- Admin list: `GET /placements`, route `placements.index`, `PlacementController::index()`, template `placement.index`.
- Web recalculation: `POST /placements/recalculate`, route `placements.recalculate`, `PlacementController::recalculate()`.
- Existing write command: `placements:recalculate {sessionId} {classId} {examId} {groupId?}`.
- Filters: session, class, exam, optional group/section and department.
- Web student source: selected-exam marks rows, optionally limited by department student IDs. Students with no marks are omitted.
- The active web action deletes every existing placement row in the selected scope, then recreates rows. The existing command performs the same delete/recreate cycle without department scope.

No explicit draft/final state exists on `exam_placements`; recalculation immediately replaces displayed placement data. Result publication is separate and placement recalculation does not check it.

## Exact legacy calculation and ranking

For each marksheet row in the selected scope:

- Subject count is raw marksheet-row count, including optional and duplicate rows.
- Total grade points are summed from stored `gradePoint`.
- GPA is stored-GP sum divided by raw row count, rounded to two decimals and capped only by the stored values themselves.
- Total marks are the sum of stored `totalMarks`, including optional rows.
- Any row with stored GP less than or equal to zero makes status Fail, including optional F.
- Both Pass and Fail rows receive a rank. Students without marks receive no placement row.

Sort keys, in exact order:

1. GPA descending.
2. Total marks descending.
3. Roll ascending; missing roll sorts last.

Positions are then assigned sequentially `1, 2, 3...`. Equal GPA and total marks therefore do not share rank: roll breaks the tie. If roll is also equal, the comparator reports equality and insertion/database order effectively determines the unique sequential positions. This is ordinal/unique ranking, not dense or competition ranking.

Optional subjects affect legacy GPA denominator, status, subject count, total marks, total-mark tie breaking, and final rank.

## Persistence and duplicate findings

The schema has a unique key on student/session/class/group/exam. Recalculation deletes its scope first, so repeated normal recalculation does not create duplicates. A nullable `groupId` can still permit duplicate logical rows on databases where unique indexes allow multiple NULL values; the preview detects and reports these. It also reports scoped placement rows without a matching scoped student.

## Centralized preview

`PlacementPreviewBuilder` reuses `ResultCalculationBatchBuilder` and its exact `StudentResult`. It does not calculate GPA. Centralized eligibility is:

- Pass: ranked.
- Fail: visible, preview rank null.
- Incomplete: visible, preview rank null.
- Optional F does not disqualify a Pass.

For comparison only, preview ranking preserves current sort policy: centralized GPA descending, current legacy all-subject total descending, roll ascending, then ordinal positions. No production policy changes.

Three total concepts are reported or documented separately:

- Legacy/all-subject total: current ranking and tie-break value; includes assigned and unassigned marked optional rows.
- Compulsory-only total: centralized compulsory `SubjectResult` obtained marks.
- Compulsory plus optional excess: not calculated because the project has no approved definition of optional mark excess. It requires a policy decision before write integration.

## Command

```text
php artisan result-engine:placement-preview --exam=12 --class=10 --session=2026 --section=1
php artisan result-engine:placement-preview --exam=12 --class=10 --session=2026 --student=345 --limit=100
php artisan result-engine:placement-preview --exam=12 --class=10 --session=2026 --all
```

Exam, class, and session are required positive integer IDs. The command is explicit and read-only, so no feature flag was added. Differences and warning rows are shown by default; `--all` includes unchanged rows. The summary includes checked students, existing rows, GPA/status/rank differences, eligibility changes, incomplete students, duplicate placements, and calculation errors.

An unexpected centralized batch error aborts ranking, prints an error summary, and returns failure. It never presents stored placement as a centralized preview and never silently omits a student.

## Difference and data-quality reasons

Supported reason codes include GPA cap, optional/duplicate denominator removal, optional bonus, optional F eligibility correction, missing compulsory data, Theory inclusion, configured pairs, component failure reevaluation, duplicate marks, and general calculator data-quality warnings. Calculator warnings cover duplicate marks, multiple optional marks, invalid fourth-subject assignment, invalid configured pair cardinality, missing subject configuration/full marks, and out-of-range components.

Selected-exam marks and selected-scope placement rows are isolated. Marks and placements from other exams are excluded. The preview detects other-exam data through comparison/scoping tests but does not classify correctly scoped legacy placement as mixed-exam behavior because both active recalculation implementations already filter by exam.

## Query and write safety

- One scoped student query.
- One selected-exam marks eager-load query.
- One applicable-subject query.
- One religious-default query.
- One scoped existing-placement query.
- In-memory legacy comparison, ranking, warning aggregation, and duplicate detection.
- No query inside the ranking comparator.

No placement, marksheet, student, archive, promotion, or publication record is inserted, updated, or deleted. No transaction or migration is needed.

## Decisions required before placement writes

1. Retain ordinal unique ranking, or approve dense/competition academic ties.
2. Decide whether roll may break an otherwise equal academic tie.
3. Approve ranking total: all-subject total, compulsory-only total, or a precisely defined optional-excess total.
4. Decide whether Fail students receive a separate rank series or remain unranked.
5. Confirm how existing ranks for newly Incomplete students should be retired during a future write migration.
6. Decide whether placement is draft, finalized, or locked after result publication.
7. Resolve nullable-group uniqueness and duplicate cleanup before persistence migration.
8. Define handling for invalid/multiple optional assignments and duplicate marks before ranks are saved.

Rollback removes the preview builder, command, tests, and this document. There is no database rollback. The recommended Phase 7 is policy approval plus a guarded, transaction-safe placement write design; no write implementation should begin until the decisions above are resolved.
