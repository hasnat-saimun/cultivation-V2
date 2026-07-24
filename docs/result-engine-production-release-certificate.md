# Result Engine Controlled Production Release Certificate

Certificate date: 2026-07-24  
Release stage: RC2

## Executive summary

The Result Engine is ready for a controlled production deployment under the PB5 governance and RC2 release policy. The restored `cultivation_rhs_rehearsal` database passed the read-only integrity preflight after exact manifest verification. The accepted historical exceptions remain visible as warnings and no new integrity violation was detected.

This certificate does not authorize historical remediation and does not replace institutional change approval.

## Completed phases

- C1: production data preflight and schema compatibility
- C2: additive integrity migrations
- C3: Draft, Confirm, and Reopen lifecycle
- C4: Publish, Unpublish, and visibility lifecycle
- C5: security, authorization, audit, and operational hardening
- PB1: backup verification
- PB2: restore rehearsal
- PB3: integrity preflight
- PB4: production data reconciliation
- PB5: production governance
- RC2: fingerprint-bound release-policy alignment

## Production readiness

- Current writes remain subject to valid student, exam, subject, academic-scope, authorization, and lifecycle checks.
- Integrity findings outside the exact historical manifest remain blocking.
- Manifested records remain reported and are not altered or remediated.
- No grading, publication workflow, lifecycle, authorization, or business-rule bypass was introduced by RC2.

## Historical exception summary

The approved immutable set consists of:

- 95 orphan-student marks rows;
- missing internal student IDs 5, 6, 48, 64, 257, 332, 344, and 347;
- one marks row referencing missing exam ID 1;
- one marks row referencing missing subject ID 1.

These are controlled preservation exceptions only. They do not establish identity and may not be copied to new records.

## Manifest verification

Read-only preflight against `cultivation_rhs_rehearsal` returned `PASS`.

| Control | Verified value | Result |
|---|---|---|
| Orphan marks count | 95 | Match |
| Orphan student IDs | 5, 6, 48, 64, 257, 332, 344, 347 | Match |
| Missing exam IDs | 1 | Match |
| Missing subject IDs | 1 | Match |
| Orphan-row SHA-256 | `737830306cae440444fcea0437c4473ab90f1b86d1cfc6b50366cc9e2b1f7f82` | Match |
| Missing-master-row SHA-256 | `d63713e71e1ffea159b306f30cdf6a58b442a2ba5aedf0d2e79d93f220da478d` | Match |
| New invalid references | 0 | Pass |

The known reference findings were emitted as `historical_legacy_exception` warnings; they were not suppressed.

## Security status

Authorization, revision checks, state transitions, scoped access, rate limiting, transactional audit insertion, append-only lifecycle events, and publication visibility controls remain enforced. A failed manifest match fails closed and cannot convert new corruption into a warning.

## Regression status

RC2 added a release-policy regression test proving both sides of the contract:

- an exact synthetic manifest produces warnings and a passing preflight;
- one additional orphan record immediately produces blocking findings.

Final controlled test result:

- 309 tests;
- 1,331 assertions;
- 0 failures;
- result: PASS.

## Deployment authorization

Technical readiness does not equal business authorization. Deployment requires completed signatures in the production approval checklist, named deployment and rollback operators, a verified production backup, retained preflight JSON, an approved maintenance window, and confirmation that the deployed configuration contains the reviewed manifest.

No data remediation is authorized.

## Rollback readiness

Use the documented backup, compatibility, and forward-recovery strategy. Halt on a changed manifest, new integrity finding, migration or smoke-test failure, unexpected historical-row mutation, or security/lifecycle/result regression. Destructive schema rollback after lifecycle use remains prohibited.

## Final recommendation

Proceed with controlled production deployment after the approval checklist is signed. Preserve all manifested historical rows unchanged and enforce the RC2 preflight before migration.

References:

- [Release policy](result-engine-release-policy.md)
- [Historical exception manifest](result-engine-historical-exception-manifest.md)
- [Production governance](result-engine-production-governance.md)
- [Production approval checklist](result-engine-production-approval-checklist.md)
- [Production deployment](result-engine-production-deployment.md)
- [Production rollback](result-engine-production-rollback.md)
