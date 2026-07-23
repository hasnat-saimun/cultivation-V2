# Result engine Phase 3 — single transcript

Phase 3 migrates only the active single-student transcript behind a disabled-by-default flag. All other result workflows remain legacy.

## Active workflow before integration

- Route: `GET /marksheet/generate`, named `marksheetGenerate`.
- Request parameters: existing `examId` and `studentId`/`stdId` lookup inputs.
- Controller: `MarksheetController::generateMarksheet()`.
- Blade: `result.marksheetGenerate`.
- Existing view data: student with exam-scoped marks, exam ID, server configuration, class maximum/current marked-subject counts, hide notice, and legacy merit rank.
- Legacy GPA, optional bonus, overall pass/fail, normalized subject grade, component failure, and paired-subject calculations are performed inside the Blade.
- Merit is supplied by the controller using its existing total-marks ranking logic and remains unchanged.
- The same page provides browser printing; it is not the bulk PDF route and has no separate single-transcript PDF endpoint.

Inactive `Copy` files were not modified.

## Flag and execution

`RESULT_ENGINE_TRANSCRIPT_ENABLED=false` is independent from shadow mode. When false, the calculator and presenter are not executed and the existing Blade branch remains active.

When true, the controller loads applicable class/global and marked subjects, filters religious and fourth subjects for the student, invokes `BoardResultCalculator`, and passes its result through `TranscriptResultPresenter`. The existing Blade displays prepared rows, GPA, letter, status, failed subjects, and missing-subject notice without recalculating grades or GPA in that branch.

Unexpected calculator or presenter exceptions are logged with student, exam, class, and session IDs plus exception class, then render the untouched legacy branch. Fail and Incomplete are normal results and do not trigger fallback.

## Display and compatibility

- Incomplete displays `Incomplete` for both overall letter and GPA, a missing-subject notice, and an `Incomplete` remark.
- Fail displays the centralized 0.00 GPA and F letter.
- Pass displays the centralized GPA capped at 5.00.
- Rank remains legacy-derived; no placement or merit data is recalculated or saved.
- Student, institution, exam, subject tables, print design, route name, and request parameters are retained.
- Bulk transcript, tabulation, summary, placement, promotion, archives, exports, and marks entry are unchanged.

## Query and write safety

The enabled branch adds one exam lookup, one applicable-subject query, and the existing religious-default resolution where needed. Marks are eager-loaded once. The presenter performs no marks/subject lookups; its only model lookup is the existing overall GPA-to-letter resolver. Legacy rank and maximum-subject queries remain unchanged in this phase.

Viewing the transcript does not update marksheets, stored grades/points, placements, result archives, or student fourth-subject assignment. No migration is included.

## Rollback

Set `RESULT_ENGINE_TRANSCRIPT_ENABLED=false` for immediate runtime rollback. Code rollback removes the presenter, integration test, controller/Blade guarded branch, config/environment entry, and this document. No database rollback is required.

## Known limitations

- The authoritative curriculum subject assignment is still inferred from current `assign_class`, marked-subject, religious-default, and fourth-subject data.
- Existing class maximum-subject and merit calculation are query-heavy and legacy-derived; Phase 3 deliberately does not refactor them.
- The live switch is deployment-wide rather than per student or per exam.
- The HTML page supports browser printing; no new PDF-generation path was introduced.

The recommended Phase 4 candidate is bulk transcript, using the same presenter contract behind its own disabled-by-default flag, after focused bulk-PDF pagination and parity tests.
