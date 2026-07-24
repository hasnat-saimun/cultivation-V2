# Result Engine Deployment Decision Framework

## Decision authority

Deployment requires written approval from the institution change authority, academic owner, and technical owner after independent verification. Passing tests alone is not approval.

## Decision matrix

| Case | Evidence state | Required action | Decision |
|---|---|---|---|
| A | Preflight has no findings; rehearsal, restore, rollback, and regression pass | Approve maintenance plan | GO |
| B | Findings are fully recoverable under evidence policy | Approve remediation; rehearse; rerun preflight and all certification checks | GO only after all checks pass |
| C | Identity remains unresolved but records are historical | Academic/legal operator decides preservation strategy; technical controls must not bypass preflight | HOLD unless a separately approved compatible deployment path is proven |
| D | Confirmed test/demo records | Separate retirement authorization and rehearsal; retain evidence | GO only after approved action and clean preflight |
| E | Collisions, truncation risk, ambiguous IDs, unexplained count changes, or failed migration | Investigate/remediate | NO GO |
| F | Backup provenance, repeatable restore, or rollback is unverified | Obtain/verify operational evidence | NO GO |
| G | Application/security/full regression fails | Correct and repeat rehearsal | NO GO |

## Non-negotiable no-go conditions

- unresolved student, exam, or subject identity blocks required migration;
- preflight returns BLOCKED;
- any remediation lacks dual verification and academic approval;
- row counts or raw-mark aggregates change unexpectedly;
- evidence or backup checksum differs;
- migration lock/downtime is unmeasured;
- lifecycle/audit/security/concurrency regression fails;
- rollback cannot preserve lifecycle state/history;
- application debug mode or unsafe legacy write path is active.

## Controlled GO conditions

All must be true:

1. Latest production backup is verified and restores twice.
2. Every data finding has an approved disposition.
3. Approved remediation, if any, passes on a restored copy.
4. Preflight passes without weakened rules.
5. Timed migration/postconditions pass with no data loss.
6. Read-only report parity and full lifecycle/security/concurrency suites pass.
7. Maintenance duration, deployment order, smoke scope, monitoring, and rollback are approved.
8. Named operators and decision authority sign the production approval checklist.

## Current PB5 decision

The eight missing student masters and missing exam/subject masters are not resolved. No operator disposition or authorized remediation exists. Therefore this framework does not authorize deployment.

## Escalation flow

```text
Finding
→ evidence case
→ independent verification
→ academic decision
→ technical remediation design
→ change approval
→ isolated rehearsal
→ clean preflight and certification
→ deployment approval
```

At any failed stage, return to HOLD. No stage may be skipped by Super Admin or business urgency.

## Feature activation

Lifecycle enforcement should activate atomically with the compatible schema/application deployment after smoke checks. Do not create an untested feature flag or retain a legacy bypass. During uncertainty, freeze mutations and retain administrative read access.

