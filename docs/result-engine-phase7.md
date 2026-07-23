# Result Engine Phase 7

Phase 7 adds the first centralized placement write path. It is disabled by default with
`RESULT_ENGINE_PLACEMENT_ENABLED=false`.

The institution setting `server_configs.ranking_method` is a 30-character string with
default `grading`; accepted values are `grading` and `total_marks`. Missing or invalid
stored values resolve once per recalculation to `grading` with a warning.

Pass ranking uses `[GPA, compulsory actual marks]` for `grading` and
`[compulsory actual marks, GPA]` for `total_marks`. Fail ranking always uses
`[compulsory actual marks, inverse failed-compulsory count]`. Both are independent
competition rank series (`1, 2, 2, 4`). Roll and student ID only stabilize display
order. Incomplete rows have a null position.

GPA uses the calculator's normalized percentages. Ranking totals sum actual obtained
marks from centralized compulsory `SubjectResult` values, so optional marks are
excluded and configured pairs are counted once.

Academic comparison retains the exact in-memory decimal total. The existing
`exam_placements.totalMarks` column is an unsigned integer, so its persisted display
value is rounded to the nearest integer; no additional Phase 7 schema change was
authorized.

## Operations

Dry-run (available while the flag is false):

`php artisan placements:recalculate --exam=12 --class=10 --session=2026 --engine=centralized --dry-run`

Write (requires the flag):

`php artisan placements:recalculate --exam=12 --class=10 --session=2026 --engine=centralized`

Published scope override:

`php artisan placements:recalculate --exam=12 --class=10 --session=2026 --engine=centralized --force`

Preflight and payload construction happen before the transaction. Inside the
transaction, exact-scope placement rows are locked, deleted, bulk inserted and
count-verified. Any exception rolls back the original scope. The web action retains
legacy behavior while the flag is false and never falls back to legacy after a
centralized failure.

Deploy with the flag false, migrate, confirm the setting, run a controlled dry-run,
back up the exact placement scope, then enable the flag for one controlled write.
Runtime rollback is disabling the flag and refreshing config. Database rollback drops
only `ranking_method`; it does not restore previously committed placement rows.
