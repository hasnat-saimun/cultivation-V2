# Result engine Phase 4 — bulk transcript

Phase 4 migrates only the active bulk transcript PDF workflow behind an independent disabled-by-default flag.

## Active workflow

- Selection route: `GET /transcripts/bulk`, named `transcripts.bulk`.
- PDF route: `POST /transcripts/bulk/pdf`, named `transcripts.bulk.pdf`.
- Controller methods: `MarksheetController::transcriptList()` and `bulkTranscriptPdf()`.
- Template: `result.bulk-transcript-pdf`.
- Inputs: `examId` and selected `stdIds[]`; the selection page retains class, session, section/group, and department filters.
- Selected students are resolved in one query by admission ID or student ID, then their selected-exam marks are eager-loaded.
- Dompdf renders the same A4 portrait template and returns a downloaded PDF. The same controller path renders HTML internally and immediately feeds it to the PDF renderer; there is no separate bulk HTML response route.
- GPA, optional bonus, status, normalization, and pairing were previously calculated in the PDF Blade.
- Bulk merit remains the existing `null` value; no rank calculation or placement lookup was added.

Inactive `Copy` files and all non-bulk result workflows remain unchanged.

## Flags

```text
RESULT_ENGINE_SHADOW_MODE=false
RESULT_ENGINE_TRANSCRIPT_ENABLED=false
RESULT_ENGINE_BULK_TRANSCRIPT_ENABLED=false
```

The bulk switch is independent. Enabling either transcript flag does not enable the other.

## Centralized path

`ResultCalculationInputBuilder` is shared by single and bulk transcripts. It loads class/global and already-marked subjects, religious defaults, and the assigned fourth subject without grading. `BulkTranscriptResultBuilder` then calls the existing `BoardResultCalculator` and `TranscriptResultPresenter` once per selected student. It contains no grade or GPA formula.

The centralized Blade branch displays presenter-prepared subject rows, grade points, GPA, optional result, status, and missing-subject information. The legacy branch remains available and unchanged in calculation behavior.

## Query strategy

- Selected students: one query.
- Selected-exam marks: one eager-load query for all selected students.
- Applicable subjects: one query across selected class contexts and marked subject IDs.
- Religious defaults: one bounded query for selected classes.
- Session, class, fallback class, section, and department display metadata: one bounded query per metadata table, not per student.
- Grade scale: one query per rendered bulk document.
- Marks and subjects are grouped/filterable in memory; the centralized Blade performs no marks or individual-subject queries.

The legacy branch retains its existing query behavior. Existing institute configuration and PDF rendering queries are unchanged.

## Fallback

An unexpected error for one student logs only student, exam, class, session, and exception-class identifiers. That student uses the legacy Blade branch while other students continue with centralized results. The per-student mixed fallback is deliberate and safe because every page has its own branch and the workflow is read-only. Fail and Incomplete remain ordinary outcomes.

## Safety and compatibility

- Existing A4 portrait styles, `.transcript-page` page breaks, headers, metadata, subject tables, signatures, and typography are retained.
- No marksheet, grade, grade point, fourth-subject assignment, placement, archive, publication, or promotion data is written.
- No migration, route, authentication, authorization, or request parameter change is included.
- Single transcript behavior and its flag remain independent.

## Rollback and limitations

Immediate rollback is `RESULT_ENGINE_BULK_TRANSCRIPT_ENABLED=false`, followed by the deployment's normal config-cache refresh. Code rollback removes the bulk builder, shared input extraction (restoring the Phase 3 controller loader), guarded bulk Blade/controller changes, tests, flag, and this document. No database rollback is needed.

Known limitations:

- Curriculum membership still depends on current `assign_class`, existing marks, religious defaults, and fourth-subject assignment.
- Bulk merit is not calculated and remains legacy behavior.
- The feature flag is deployment-wide rather than exam/student-specific.
- PDF verification covers successful rendering and structural page wrappers, not binary or pixel-level comparison.

The recommended Phase 5 candidate is read-only tabulation, only after defining its missing-subject denominator and optional-F display contract and adding dedicated parity fixtures.
