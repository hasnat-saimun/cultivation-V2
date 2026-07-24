# Result Engine Release Policy

Policy date: 2026-07-24  
Authority: PB4 reconciliation, PB5 production governance, and RC2 release-policy alignment

## Release policy

Production release is permitted only when the integrity preflight completes without a blocking finding, the approved historical manifest verifies exactly, the application regression suite passes, and the deployment approvals and rollback controls are complete.

Historical exceptions are evidence-bound warnings. They are not valid application data, a validation bypass, or permission to create another invalid reference.

## Historical exception policy

The only approved historical exceptions are:

- 95 existing `marksheets` rows whose `studentId` values reference missing `new_admissions.id` values 5, 6, 48, 64, 257, 332, 344, and 347;
- the existing `marksheets.id = 1` reference to missing `exams.id = 1`;
- the existing `marksheets.id = 1` reference to missing `subjects.id = 1`.

They must remain unchanged. No deletion, recreation, remapping, identity inference, or lifecycle enrollment is authorized by this policy.

The policy applies only to the production and restored-production database names explicitly configured in `result_engine.historical_exception_manifest`. Other databases receive no historical exception classification.

## Manifest verification

`App\Services\ResultHistoricalExceptionManifest` obtains the current exception set using read-only queries and compares it with the immutable configured manifest. Verification requires exact equality for:

- sorted orphan student IDs;
- orphan marks count;
- sorted missing exam IDs;
- sorted missing subject IDs;
- SHA-256 fingerprint of all orphan-student marks rows;
- SHA-256 fingerprint of the marks rows with missing exam or subject masters.

Fingerprints cover the documented marksheet fields in ascending `marksheets.id` order. A count or identifier match without a fingerprint match is insufficient.

## Blocking rules

Deployment is blocked when any of the following occurs:

- the database is manifest-applicable and any expected value or fingerprint differs;
- an invalid reference is outside the exact verified historical set;
- a required identifier is empty, oversized, nonnumeric, or otherwise invalid;
- a class, session, section, group, publication actor, or publication scope is invalid;
- a duplicate or normalized business-key collision exists;
- a required table, column, index, or migration precondition is absent;
- an application, security, lifecycle, publication, grading, authorization, audit, or migration regression is demonstrated.

A manifest mismatch keeps the underlying student, exam, and subject reference findings blocking and adds a blocking `historical_exception_manifest` finding with expected and actual evidence.

## Warning rules

Only after the entire manifest verifies exactly may the three known reference findings be emitted as nonblocking `historical_legacy_exception` warnings:

- `marks_invalid_reference_studentId` with count 95;
- `marks_invalid_reference_examId` with count 1;
- `marks_invalid_reference_subjectId` with count 1.

The preflight still reports these findings and their evidence. They are never suppressed.

## Deployment requirements

Before migration or application release, the operator must:

1. use a verified backup and record its checksum;
2. run the integrity preflight against the intended database and retain its JSON output;
3. confirm preflight status `PASS`, manifest finding count zero, and the exact approved warning counts;
4. confirm the full automated regression suite passes in the dedicated test database;
5. obtain the approvals required by the production governance and approval checklist;
6. verify rollback artifacts, responsible operators, monitoring, and halt authority;
7. execute only the approved deployment steps and retain before/after evidence.

This policy does not itself authorize a production deployment or database mutation.

## Operator responsibilities

The operator must verify the target database name, environment, backup identity, preflight output, application revision, migration plan, approval record, and rollback point before proceeding. Any discrepancy must be reported without attempting manual repair. Historical records must not be edited to make preflight pass.

After deployment, the operator must run the approved smoke tests and post-deployment checks, compare result visibility and lifecycle behavior, and retain audit evidence.

## Rollback trigger

Stop deployment and invoke the approved rollback or forward-recovery procedure when:

- a manifest count, identifier set, or fingerprint changes;
- any new integrity finding appears;
- a migration or smoke test fails;
- security, authorization, audit, lifecycle, publication, grading, promotion, or placement behavior regresses;
- historical rows are unexpectedly changed;
- deployment evidence, approval, monitoring, or rollback capability is unavailable.

References:

- [Historical exception manifest](result-engine-historical-exception-manifest.md)
- [Production governance](result-engine-production-governance.md)
- [Manual remediation policy](result-engine-manual-remediation-policy.md)
- [Risk assessment](result-engine-risk-assessment.md)
- [Production approval checklist](result-engine-production-approval-checklist.md)
- [Production rollback](result-engine-production-rollback.md)
