# Result engine Phase 2

Phase 2 adds a Board-compliant, read-only calculator and an opt-in comparison command. It does not replace or alter any published result workflow.

## Calculator contract

`BoardResultCalculator::calculate($student, $exam, $marks, $subjects): StudentResult`

The method is deterministic for the same inputs and performs no queries, writes, redirects, rendering, or request access. Callers provide the student, exam passing mode, marks, and applicable subjects.

The returned `StudentResult` contains subject results, compulsory GP sum/count, optional GP/bonus, failed and missing compulsory subjects, GPA, status, and warnings. Each `SubjectResult` contains normalized marks, grade, GP, status, optional/compulsory classification, component failures, missing state, and source subject IDs.

## Board policy implemented

- Grades: A+ 80+, A 70+, A- 60+, B 50+, C 40+, D 33+, F below 33.
- Percentage is `obtained / full marks * 100`; raw marks are not treated as percentages.
- Any compulsory F makes the overall result Fail and GPA 0.00.
- Missing compulsory input makes the result Incomplete and GPA null.
- Zero is an entered mark and is therefore F, not missing.
- Only the student's assigned fourth subject is optional.
- Optional bonus is `max(optional GP - 2.00, 0)` and the optional subject is excluded from the denominator.
- Final GPA is rounded to two decimal places and capped at 5.00.
- Compulsory Theory subjects are included.

## Pairs and components

Only pairs already declared in `config/subject_pairs.php` are merged. The merged grade uses combined obtained and full marks; source-paper failures remain in component details and warnings. Similar names alone never trigger a merge.

When the exam uses feature-wise passing, a required CQ, MCQ, or practical component below the normalized 33% threshold fails the subject. In total-mark mode, the combined subject total controls pass/fail while component details remain available. Null and zero remain distinct.

## Shadow comparison

Shadow mode defaults off:

```text
RESULT_ENGINE_SHADOW_MODE=false
```

Explicit read-only usage:

```text
php artisan result-engine:compare --exam=12 --class=8 --session=2026 --limit=100
php artisan result-engine:compare --exam=12 --student=345
```

The command reports only differing students, then summarizes GPA, status, optional-subject, missing-subject, and calculation-error counts. It reads the selected connection and does not save marks, placements, promotion data, or archives.

## Integration and rollback

No controller, route, Blade view, request, marks-entry path, placement, promotion, archive, schema, or stored result was changed. Rollback consists of removing the Phase 2 service/DTOs, command, tests, config file, this document, and the environment example entry. No database rollback is required.

## Decisions deferred to integration

- Confirm the Board's exact fractional component-threshold and rounding convention for every exam type.
- Define the authoritative curriculum/subject set each future caller must provide; the command currently infers it from the legacy exam/class/session context.
- Decide how duplicate marks rows should be repaired. The calculator currently warns and deterministically uses the row with the greatest ID.
- Confirm paired-paper component-failure policy beyond the existing project's configured passing mode.

The recommended first Phase 3 integration candidate is the single-student transcript, behind a dedicated disabled-by-default workflow flag with legacy fallback. It has the narrowest output scope and the strongest direct characterization coverage.
