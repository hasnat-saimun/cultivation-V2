# Result engine Phase 5 — tabulation and summary

Phase 5 migrates only the active all-marksheet/tabulation and result-summary outputs behind two independent disabled-by-default flags.

## Active workflows

Tabulation uses `GET /marksheet/all` (`allMarksheet`) and the related `GET /marksheet/at-a-glance` route, both served by `MarksheetController::allMarksheet()`. The standard template is `result.allMarksheet`; request filters are `examId`, `classId`, `sessionId`, optional `sectionId`, `departmentId`, and `compact`. Students, active subject headers, grades, optional bonus, paired rows, GPA, status, incomplete handling, and merit display were prepared by the legacy controller, with additional display/rank handling in the Blade.

Summary uses `GET /marksheet/result-summary` (`result.summary`), `MarksheetController::resultSummary()`, and `result.result-summary`. It has the same academic filters. Legacy counts were derived by calling legacy tabulation, then comparing its rendered-result rows with total enrolled students. Subject pass/fail/missing statistics and failed-subject buckets were aggregated in the controller.

No routes, request parameters, inactive `Copy` files, placement, promotion, archive, publication, export, or marks-entry workflow were changed.

## Flags

```text
RESULT_ENGINE_TABULATION_ENABLED=false
RESULT_ENGINE_SUMMARY_ENABLED=false
```

These are independent from each other and from shadow, single-transcript, and bulk-transcript flags. A disabled summary explicitly uses legacy tabulation data even when centralized tabulation is enabled.

## Centralized preparation

`ResultCalculationBatchBuilder` performs one scoped student query, eager-loads selected-exam marks for all students, invokes the shared `ResultCalculationInputBuilder`, and calls `BoardResultCalculator` per student. It does not aggregate or render.

`TabulationResultPresenter` maps `StudentResult` into the existing row/cell contract and aggregates summary counts, percentages, GPA/letter distributions, subject statistics, and compulsory-failure buckets. It contains no grade boundary, GPA, optional bonus, normalization, pairing, or component-passing formula.

The centralized tabulation Blade displays prepared values. Stable columns are the union of applicable centralized subject rows across the scoped students. Missing cells remain blank while entered zero displays as zero. Student-varying religious and optional subjects therefore share stable union columns; unassigned optional subjects are absent from that student's calculation.

## Status and legacy Absent terminology

Centralized internal statuses are exactly `Pass`, `Fail`, and `Incomplete`, with missing compulsory data taking priority over compulsory failure. Optional F remains a subject-level F but does not make the student fail.

There is no dedicated attendance/absence marker in the legacy summary path. Legacy `Absent` means enrolled students who produced no legacy tabulation row, normally because they had no marks rows. In centralized summary presentation, `Absent = Incomplete` for compatibility and the header reads `Absent / Incomplete`; the internal status and separate Incomplete count remain unchanged. Pass, Fail, and Incomplete percentages use total scoped enrollment as denominator.

## Fallback and rank

Any unexpected batch error makes tabulation fall back to the complete legacy response. Summary also falls back completely so aggregate counts can never be partially centralized. Logs include exam, class, session, optional section, and exception class only. Pass, Fail, and Incomplete are normal outcomes.

Existing Blade-derived merit/rank remains in place and is not calculated by the Board result engine or persisted. Calculation parity covers subjects, GPA, and status—not rank.

## Query and write safety

- One scoped student query.
- One selected-exam marks eager-load query.
- One applicable-subject query.
- One bounded religious-default query.
- In-memory student/subject grouping and aggregation.
- No marks or per-subject queries in the centralized table branch.

Viewing either workflow does not update marksheets, component marks, stored grades/points, fourth-subject assignments, placements, archives, promotion data, or published-result records. No migration exists.

## Rollback and limitations

Immediate rollback is setting both Phase 5 flags to `false` and refreshing the deployment's configuration cache. Code rollback removes the batch builder, tabulation presenter, guarded controller/Blade branches, tests, flag entries, and this document. No database rollback is required.

Known limitations:

- Curriculum membership still derives from `assign_class`, existing marked subjects, religious defaults, and fourth-subject assignment.
- Religious and optional variation produces union columns, so some students intentionally have blank cells.
- `Absent / Incomplete` is a UI compatibility mapping, not verified attendance.
- Merit/rank remains legacy-derived.
- GPA/grade distributions are prepared for reporting but the existing summary UI is not redesigned with new distribution panels.

The recommended Phase 6 candidate is placement/rank read-only parity and shadow validation before any persistence integration; placement writes should remain disabled until its ranking and tie policy is explicitly approved.
