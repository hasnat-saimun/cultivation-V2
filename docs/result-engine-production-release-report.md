# Result Engine RC1 Production Release Report

Date: 2026-07-24  
Review mode: code, documentation, tests, and read-only rehearsal evidence

## Executive decision

Application development phases C1–C5 and governance phases PB1–PB5 have produced a centralized, secured Result Engine and documented the known legacy data. RC1 identified a new deployment-path incompatibility: current Phase C2 gates and migrations cannot preserve the manifested historical exceptions untouched while enforcing valid-master scope creation.

No historical row was changed during RC1.

## Completed phases

- C1: production-data and schema compatibility analysis
- C2: additive integrity schema
- C3: Draft, Confirm, Reopen lifecycle
- C4: Publish, Unpublish, visibility
- C5: authorization, security, audit, rate limiting, operational policy
- PB1–PB3: backup/restore/preflight evidence
- PB4: production data reconciliation
- PB5: governance, remediation policy, decision framework

## Historical exception summary

- 95 marks rows reference eight missing internal student IDs: 5, 6, 48, 64, 257, 332, 344, 347.
- One separate marks row references missing exam 1 and subject 1.
- These records are fingerprinted in the [historical exception manifest](result-engine-historical-exception-manifest.md).
- No deletion, recreation, remapping, or identity fabrication is authorized.

## New-write integrity review

Current Result Engine service writes fail closed:

- Draft save resolves an existing subject and exam, canonical academic scope, authorized population, and existing students.
- A submitted student outside the resolved population rejects the complete batch.
- Confirmation requires an existing scope state, subject, exam, complete expected population, complete marks, and current revision.
- Reopen requires an existing Confirmed scope, General/Super authority, revision, reason, and unpublished parent.
- Publication resolves existing exam/session/class/section relationships and requires confirmed complete subject scopes.
- Actor IDs, roles, revisions, states, timestamps, and audit UUIDs are service-derived.
- Promotions and placements require Published result status and strict scoped population.

The normal application write paths do not intentionally create new invalid result references.

## Preflight classification

### Category A — deployment blocking

- missing required tables/columns;
- schema or migration failure;
- new invalid student/exam/subject/class/session/section references;
- duplicate or normalized-collision identities;
- invalid publication actors/scopes;
- unexpected changes to manifested row counts/fingerprints;
- any invalid reference not exactly present in the approved manifest.

### Category B — historical legacy exceptions

- the exact 95 manifested orphan-student marks rows;
- the exact manifested marks row referencing missing exam 1 and subject 1.

Category B is a governance classification, not a wildcard validation bypass. New records can never enter it.

## Migration preservation review

The current migration sequence does not satisfy the RC1 preservation contract:

1. `2026_07_24_000004_assert_result_integrity_preconditions.php` treats all invalid references identically and blocks before a Category A/B distinction can be certified.
2. If that gate were bypassed, `2026_07_24_000005_normalize_result_identifiers.php` executes blanket `UPDATE marksheets` statements, including manifested rows. Even equivalent value assignments violate the requirement that legacy exceptions remain untouched.
3. `2026_07_24_000016_initialize_marks_scope_states_as_draft.php` creates state rows for every distinct marks scope, including scopes backed by missing student/exam/subject masters.
4. `2026_07_24_000017_assert_result_integrity_postconditions.php` requires a state for every marks scope, making safe exclusion impossible without an explicit, tested preservation design.

Simply changing findings to non-blocking would weaken integrity and is prohibited. Bypassing migration `000004` would also be unsafe. This is a new technical release issue, not a re-investigation of PB4 data.

## Security and lifecycle status

Service authorization, CSRF/method controls, rate limits, revision locking, transactional audit insertion, append-only event controls, bounded validation, publication visibility, and promotion/placement guards remain in place. RC1 made no grading, GPA, publication, authorization, lifecycle, or database changes.

## Regression status

RC1 fresh verification results:

- Result integrity: 6 tests, 25 assertions — PASS
- Draft/Confirm/Reopen: 13 tests, 50 assertions — PASS
- Publication readiness: 5 tests, 14 assertions — PASS
- Lifecycle security: 4 tests, 13 assertions — PASS
- Lifecycle concurrency: 1 test, 3 assertions — PASS
- Full project suite: 308 tests, 1,323 assertions — PASS

Execution used the dedicated `cultivation_test` database under the testing environment. The restored rehearsal database was not migrated or mutated.

## Deployment checklist status

- Governance documents: ready
- Historical manifest: ready
- New-write integrity: verified by code review
- Historical row preservation by migrations: **failed**
- Clean Category A/B preflight contract: **not implemented**
- Migration rehearsal with unchanged fingerprints: **not performed**
- Approval checklist sign-off: incomplete
- Production migration authorization: not granted

## Rollback readiness

The compatibility/read-only rollback policy is documented. Destructive schema rollback after lifecycle use remains prohibited. Manifest fingerprints provide an additional preservation check. Rollback readiness cannot cure the migration-preservation defect and does not authorize deployment.

## Remaining risks and required next action

Create a separately authorized correction phase that designs and tests an exact, fingerprint-bound historical exception mechanism without changing the historical rows or allowing new invalid writes. It must:

- keep every non-manifest invalid reference blocking;
- avoid blanket updates to manifest rows;
- avoid creating lifecycle states for invalid legacy scopes;
- define postconditions that distinguish preserved history from active lifecycle data;
- pass rehearsal against a fresh restored backup with unchanged counts/fingerprints;
- rerun full integrity, lifecycle, security, report, migration, and rollback certification.

Until that work is approved and passes, production deployment is technically blocked.
